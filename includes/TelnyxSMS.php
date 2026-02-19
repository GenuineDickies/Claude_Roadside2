<?php
/**
 * Telnyx SMS Service
 * 
 * Handles all SMS operations for RoadRunner Admin
 * 
 * @requires PHP 7.4+
 * @requires curl extension
 * @requires sodium extension (for webhook verification)
 */

class TelnyxSMS {
    
    private $api_key;
    private $from_number;
    private $profile_id;
    private $public_key;
    private $base_url = 'https://api.telnyx.com/v2';
    
    // Message templates
    const TEMPLATES = [
        'ticket_created' => "{brand_name}: Service confirmed! Ticket #{ticket_id}.\n{service_type} at {location}.\nWe're dispatching help now. Reply STOP to opt out.",
        
        'tech_dispatched' => "{brand_name}: {tech_name} is on the way!\nVehicle: {vehicle_desc}\nETA: {eta_minutes} minutes\nReply STOP to opt out.",
        
        'eta_update' => "{brand_name} Update: New ETA {eta_time}.\n{reason}\nYour technician {tech_name} will arrive soon.\nReply STOP to opt out.",
        
        'tech_arrived' => "{brand_name}: {tech_name} has arrived at your location.\nPlease look for: {vehicle_desc}\nQuestions? Call {support_phone}\nReply STOP to opt out.",
        
        'service_complete' => "{brand_name}: Service complete!\n{service_summary}\nTotal: \${amount}\nThank you for choosing {brand_name}!\nReply STOP to opt out.",
        
        'payment_received' => "{brand_name} Payment: \${amount} received.\nInvoice #{invoice_id}\nThank you!\nReply STOP to opt out.",
        
        'help_response' => "{brand_name}\nSupport: {support_phone}\nEmail: {support_email}\nHours: {support_hours}\nReply STOP to unsubscribe.",
        
        'stop_response' => "{brand_name}: You've been unsubscribed and will no longer receive messages. Reply START to resubscribe.",
        
        'start_response' => "{brand_name}: Welcome back! You've been resubscribed to service notifications. Reply STOP to opt out."
    ];
    
    /**
     * Initialize with credentials from config or environment
     *
     * @param array $config Optional keys: api_key, from_number, profile_id, public_key
     */
    public function __construct($config = []) {
        if (!is_array($config)) $config = [];

        $this->api_key = $config['api_key'] ?? $this->getEnv('TELNYX_API_KEY');
        $this->from_number = $config['from_number'] ?? $this->getEnv('TELNYX_FROM_NUMBER', '');
        $this->profile_id = $config['profile_id'] ?? $this->getEnv('TELNYX_MESSAGING_PROFILE_ID');
        $this->public_key = $config['public_key'] ?? $this->getEnv('TELNYX_PUBLIC_KEY');
        
        if (!$this->api_key) {
            throw new Exception('TELNYX_API_KEY not configured');
        }

        if (!$this->from_number) {
            throw new Exception('TELNYX_FROM_NUMBER not configured');
        }
    }
    
    /**
     * Get environment variable with fallback
     */
    private function getEnv($key, $default = null) {
        return getenv($key) ?: ($_ENV[$key] ?? $default);
    }
    
    /**
     * Send SMS message
     * 
     * @param string $to Recipient phone (any format)
     * @param string $message Message text
     * @param array $options Additional options (webhook_url, etc.)
     * @return array Response with success, message_id, status, cost, error
     */
    public function send($to, $message, $options = []) {
        $to = $this->formatE164($to);
        
        if (!$to) {
            return [
                'success' => false,
                'error' => 'Invalid phone number format'
            ];
        }
        
        // Check message length
        $length_check = $this->checkMessageLength($message);
        if (!$length_check['valid']) {
            return [
                'success' => false,
                'error' => 'Message too long: ' . $length_check['segments'] . ' segments (max 10)'
            ];
        }
        
        $payload = [
            'from' => $this->from_number,
            'to' => $to,
            'text' => $message
        ];
        
        // Merge options
        if (!empty($options['webhook_url'])) {
            $payload['webhook_url'] = $options['webhook_url'];
        }
        if (!empty($options['webhook_failover_url'])) {
            $payload['webhook_failover_url'] = $options['webhook_failover_url'];
        }
        
        $response = $this->request('POST', '/messages', $payload);
        
        if ($response['http_code'] === 200) {
            $data = $response['data']['data'];
            return [
                'success' => true,
                'message_id' => $data['id'],
                'status' => $data['to'][0]['status'] ?? 'queued',
                'segments' => $data['parts'] ?? 1,
                'cost' => $data['cost']['amount'] ?? null,
                'carrier' => $data['to'][0]['carrier'] ?? null,
                'encoding' => $data['encoding'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'http_code' => $response['http_code'],
            'error' => $response['data']['errors'][0]['title'] ?? 'Unknown error',
            'detail' => $response['data']['errors'][0]['detail'] ?? null,
            'code' => $response['data']['errors'][0]['code'] ?? null
        ];
    }
    
    /**
     * Send MMS message
     * 
     * @param string $to Recipient phone
     * @param string $message Message text
     * @param array $media_urls Array of media URLs (max 1MB total)
     * @return array Response
     */
    public function sendMMS($to, $message, $media_urls) {
        $to = $this->formatE164($to);
        
        if (!$to) {
            return ['success' => false, 'error' => 'Invalid phone number'];
        }
        
        $payload = [
            'from' => $this->from_number,
            'to' => $to,
            'text' => $message,
            'media_urls' => $media_urls,
            'type' => 'MMS'
        ];
        
        $response = $this->request('POST', '/messages', $payload);
        
        return [
            'success' => $response['http_code'] === 200,
            'message_id' => $response['data']['data']['id'] ?? null,
            'error' => $response['data']['errors'][0]['title'] ?? null
        ];
    }
    
    /**
     * Send template message
     * 
     * @param string $to Recipient phone
     * @param string $template Template name (key from TEMPLATES)
     * @param array $variables Key-value pairs to replace in template
     * @return array Response
     */
    public function sendTemplate($to, $template, $variables = []) {
        if (!isset(self::TEMPLATES[$template])) {
            return ['success' => false, 'error' => "Unknown template: $template"];
        }

        // Brand must be provided by the caller (settings-driven). Avoid hardcoding.
        if (!is_array($variables) || !isset($variables['brand_name']) || trim((string)$variables['brand_name']) === '') {
            return ['success' => false, 'error' => 'Missing required template variable: brand_name'];
        }
        
        $message = self::TEMPLATES[$template];
        
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $this->send($to, $message);
    }
    
    /**
     * Get message by ID
     * 
     * @param string $message_id Telnyx message ID
     * @return array Message data or error
     */
    public function getMessage($message_id) {
        $response = $this->request('GET', "/messages/$message_id");
        
        if ($response['http_code'] === 200) {
            return [
                'success' => true,
                'data' => $response['data']['data']
            ];
        }
        
        return [
            'success' => false,
            'error' => $response['data']['errors'][0]['title'] ?? 'Message not found'
        ];
    }
    
    /**
     * List messaging profiles
     * 
     * @return array Profiles or error
     */
    public function getProfiles() {
        $response = $this->request('GET', '/messaging_profiles');
        
        if ($response['http_code'] === 200) {
            return [
                'success' => true,
                'profiles' => $response['data']['data']
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to fetch profiles'];
    }
    
    /**
     * Update messaging profile webhook
     * 
     * @param string $profile_id Profile ID
     * @param string $webhook_url Primary webhook URL
     * @param string|null $failover_url Failover webhook URL
     * @return array Result
     */
    public function updateWebhook($profile_id, $webhook_url, $failover_url = null) {
        $payload = ['webhook_url' => $webhook_url];
        
        if ($failover_url) {
            $payload['webhook_failover_url'] = $failover_url;
        }
        
        $response = $this->request('PATCH', "/messaging_profiles/$profile_id", $payload);
        
        return ['success' => $response['http_code'] === 200];
    }
    
    /**
     * Verify webhook signature
     * 
     * @param string $payload Raw request body
     * @param string $signature telnyx-signature-ed25519 header
     * @param string $timestamp telnyx-timestamp header
     * @return bool True if valid
     */
    public function verifyWebhook($payload, $signature, $timestamp) {
        if (!$this->public_key) {
            return false; // Can't verify without public key
        }
        
        // Check timestamp (prevent replay attacks)
        $webhook_time = (int)$timestamp;
        if (abs(time() - $webhook_time) > 300) {
            return false;
        }
        
        // Decode signature
        $sig_bytes = base64_decode($signature);
        if ($sig_bytes === false) {
            return false;
        }
        
        // Build signed payload
        $signed_payload = $timestamp . '|' . $payload;
        
        // Decode public key
        $pub_key_bytes = base64_decode($this->public_key);
        if ($pub_key_bytes === false) {
            return false;
        }
        
        // Verify with sodium
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            error_log('sodium extension not available for webhook verification');
            return true; // Skip verification if sodium not available
        }
        
        return sodium_crypto_sign_verify_detached(
            $sig_bytes,
            $signed_payload,
            $pub_key_bytes
        );
    }
    
    /**
     * Format phone number to E.164
     * 
     * @param string $phone Any phone format
     * @return string|null E.164 format or null if invalid
     */
    public function formatE164($phone) {
        // Remove all non-digits except +
        $clean = preg_replace('/[^\d+]/', '', $phone);
        
        // Already E.164
        if (preg_match('/^\+1\d{10}$/', $clean)) {
            return $clean;
        }
        
        // Just digits
        $digits = preg_replace('/\D/', '', $phone);
        
        // 10 digit US
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        
        // 11 digit starting with 1
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }
        
        return null;
    }
    
    /**
     * Check message length and segment count
     * 
     * @param string $message Message text
     * @return array Info about length, segments, validity
     */
    public function checkMessageLength($message) {
        // GSM-7 basic character set plus extension table
        // Simplified check: if message has emojis or non-Latin scripts, use UCS-2
        $gsm7_pattern = '/^[@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ\x1b !"#¤%&\'()*+,\-.\/0-9:;<=>?¡A-ZÄÖÑܧ¿a-zäöñüà€\[\\\\\]^{|}~]*$/u';
        
        // For simplicity, check if all chars are in ASCII printable + common GSM chars
        // This catches most cases correctly
        $is_gsm7 = preg_match('/^[\x20-\x7E\n\r]*$/', $message) && !preg_match('/[\x{1F300}-\x{1F9FF}]/u', $message);
        
        if (!$is_gsm7) {
            $encoding = 'UCS-2';
            $max_single = 70;
            $max_multi = 67;
        } else {
            $encoding = 'GSM-7';
            $max_single = 160;
            $max_multi = 153;
        }
        
        $length = mb_strlen($message);
        
        if ($length <= $max_single) {
            $segments = 1;
        } else {
            $segments = ceil($length / $max_multi);
        }
        
        return [
            'length' => $length,
            'segments' => $segments,
            'encoding' => $encoding,
            'valid' => $segments <= 10,
            'chars_remaining' => $segments === 1 
                ? $max_single - $length 
                : ($segments * $max_multi) - $length
        ];
    }
    
    /**
     * Check if keyword is opt-out
     * 
     * @param string $text Message text
     * @return bool True if opt-out keyword
     */
    public function isOptOut($text) {
        $keywords = ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'];
        return in_array(strtoupper(trim($text)), $keywords);
    }
    
    /**
     * Check if keyword is help request
     * 
     * @param string $text Message text
     * @return bool True if help keyword
     */
    public function isHelpRequest($text) {
        $keywords = ['HELP', 'INFO'];
        return in_array(strtoupper(trim($text)), $keywords);
    }
    
    /**
     * Check if keyword is re-subscribe
     * 
     * @param string $text Message text
     * @return bool True if re-subscribe keyword
     */
    public function isResubscribe($text) {
        return strtoupper(trim($text)) === 'START';
    }
    
    /**
     * Get account balance
     * 
     * @return array Balance info or error
     */
    public function getBalance() {
        $response = $this->request('GET', '/balance');
        
        if ($response['http_code'] === 200) {
            return [
                'success' => true,
                'balance' => $response['data']['data']['balance'],
                'currency' => $response['data']['data']['currency']
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to fetch balance'];
    }
    
    /**
     * Make API request
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request body
     * @return array Response with http_code and data
     */
    private function request($method, $endpoint, $data = null) {
        $url = $this->base_url . $endpoint;
        
        $ch = curl_init($url);
        
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
                
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'http_code' => 0,
                'data' => ['errors' => [['title' => 'cURL error: ' . $error]]]
            ];
        }
        
        return [
            'http_code' => $http_code,
            'data' => json_decode($response, true) ?? []
        ];
    }
}

// ============================================================
// STANDALONE HELPER FUNCTIONS (for backward compatibility)
// ============================================================

/**
 * Initialize Telnyx service singleton
 */
function get_telnyx() {
    static $instance = null;
    if ($instance === null) {
        $instance = new TelnyxSMS();
    }
    return $instance;
}

/**
 * Send SMS (simple wrapper)
 */
function send_sms($to, $message) {
    return get_telnyx()->send($to, $message);
}

/**
 * Send template SMS
 */
function send_sms_template($to, $template, $variables = []) {
    return get_telnyx()->sendTemplate($to, $template, $variables);
}

/**
 * Check SMS consent before sending
 */
function send_sms_with_consent($to, $message) {
    if (!function_exists('has_sms_consent') || !has_sms_consent($to)) {
        return [
            'success' => false,
            'error' => 'No SMS consent on file',
            'blocked' => true
        ];
    }
    return send_sms($to, $message);
}
