# SMS Troubleshooting Guide

Common issues, error codes, and solutions for Telnyx SMS integration.

---

## Quick Diagnostics

RoadRunner Admin stores Telnyx configuration in Settings (DB). For the curl examples below, use the same API key and From number found in Settings (`telnyx_api_key`, `telnyx_from_number`).

### Test API Connection

```bash
curl -X GET "https://api.telnyx.com/v2/messaging_profiles" \
  -H "Authorization: Bearer $TELNYX_API_KEY"
```

**Expected**: JSON with your messaging profiles  
**If error**: Check API key validity

### Test Send

```bash
curl -X POST "https://api.telnyx.com/v2/messages" \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "YOUR_TELNYX_NUMBER",
    "to": "+15551234567",
    "text": "Test message"
  }'
```

---

## Common Errors

### Authentication Errors

#### Error 10009: Authentication failed

```json
{
  "errors": [{
    "code": "10009",
    "title": "Authentication failed"
  }]
}
```

**Causes**:
- Invalid API key
- API key revoked
- Missing `Bearer` prefix

**Solutions**:
1. Verify API key in [portal](https://portal.telnyx.com/#/app/api-keys)
2. Check for typos/truncation
3. Ensure header format: `Authorization: Bearer KEY...`

---

### Number/Profile Errors

#### Error 40002: Number not assigned to messaging profile

**Causes**:
- Phone number not linked to any messaging profile
- Wrong `from` number

**Solutions**:
1. Go to [My Numbers](https://portal.telnyx.com/#/app/numbers/my-numbers)
2. Find your number
3. Assign to messaging profile

#### Error 40300: Number not enabled for messaging

**Causes**:
- Number is voice-only
- Messaging not activated

**Solutions**:
1. Check number capabilities in portal
2. Enable SMS/MMS features
3. May need to purchase a messaging-enabled number

---

### 10DLC Registration Errors

#### Error: Registration required

```json
{
  "errors": [{
    "code": "40003",
    "title": "Registration required",
    "detail": "10DLC registration required for this destination"
  }]
}
```

**Causes**:
- Number not assigned to 10DLC campaign
- Campaign not approved
- Sending to US carrier without registration

**Solutions**:
1. Complete 10DLC registration (see [10DLC Compliance](?page=sms-knowledge&doc=10dlc))
2. Assign number to approved campaign
3. Wait for campaign approval (2-3 business days)

#### Campaign Status Issues

Check campaign status:
```bash
curl -X GET "https://api.telnyx.com/v2/10dlc/campaigns/{campaign_id}" \
  -H "Authorization: Bearer $TELNYX_API_KEY"
```

| Status | Meaning | Action |
|--------|---------|--------|
| `PENDING` | Under review | Wait |
| `ACTIVE` | Approved | Ready to send |
| `REJECTED` | Denied | Review rejection reason, resubmit |
| `SUSPENDED` | Violation | Contact Telnyx support |

---

### Delivery Failures

#### Status: delivery_failed

**Common Error Codes**:

| Code | Meaning | Solution |
|------|---------|----------|
| 30003 | Unreachable | Invalid/disconnected number |
| 30004 | Message blocked | Carrier filter, check content |
| 30005 | Unknown destination | Invalid phone number format |
| 30006 | Landline | Cannot SMS landlines |
| 30007 | Carrier violation | Content flagged by carrier |
| 30008 | Unknown error | Retry or contact support |

#### Debugging Delivery Issues

```php
<?php
// Check delivery status
function check_message_status($message_id) {
  require_once __DIR__ . '/../../config/database.php';
  require_once __DIR__ . '/../../includes/functions.php';

  $api_key = (string)get_setting($pdo, 'telnyx_api_key', '');
  if ($api_key === '') {
    return ['status' => 'unknown', 'errors' => ['Missing telnyx_api_key setting']];
  }
    
    $ch = curl_init("https://api.telnyx.com/v2/messages/$message_id");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $api_key"
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    return [
        'status' => $data['data']['to'][0]['status'] ?? 'unknown',
        'errors' => $data['data']['errors'] ?? [],
        'carrier' => $data['data']['to'][0]['carrier'] ?? 'unknown'
    ];
}
```

---

### Webhook Issues

#### Webhooks Not Arriving

**Diagnostic Steps**:

1. **Check webhook URL**:
   ```bash
  curl -X GET "https://api.telnyx.com/v2/messaging_profiles/YOUR_MESSAGING_PROFILE_ID" \
     -H "Authorization: Bearer $TELNYX_API_KEY" | jq '.data.webhook_url'
   ```

  RoadRunner Admin uses a **public SiteGround webhook proxy**. Telnyx should send webhooks to the proxy URL stored in Settings as `sms_webhook_proxy_url`.

2. **Test endpoint accessibility**:
   ```bash
  curl -I https://YOURDOMAIN.com/sms-webhook/webhook.php/status
   ```

  RoadRunner Admin does **not** expose a public local webhook endpoint.

3. **Confirm the local poller is running**: the local app polls the proxy queue and processes STOP/HELP/START keywords via `api/sms-webhook-poll.php`.

3. **Check firewall**: Telnyx recommends signature verification over IP allowlisting (see Webhooks documentation)

4. **Check SSL certificate**: Must be valid HTTPS

5. **Test with ngrok**:
   ```bash
   ngrok http 80
   # Update webhook URL to ngrok URL
   ```

#### Webhook Timeout (Retries)

**Cause**: Endpoint takes >2 seconds to respond

**Solution**: Respond immediately, process async

```php
<?php
// Respond first
http_response_code(200);
echo 'OK';

// Flush and continue
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Now process (after response sent)
$data = json_decode(file_get_contents('php://input'), true);
process_webhook($data);
```

#### Duplicate Webhooks

**Cause**: Normal retry behavior

**Solution**: Deduplicate by event ID

```php
<?php
function process_webhook_once($data) {
    global $pdo;
    
    $event_id = $data['data']['id'];
    
    // Check if already processed
    $stmt = $pdo->prepare("SELECT id FROM webhook_log WHERE event_id = ?");
    $stmt->execute([$event_id]);
    
    if ($stmt->fetch()) {
        return; // Already processed
    }
    
    // Mark as processed
    $stmt = $pdo->prepare("INSERT INTO webhook_log (event_id) VALUES (?)");
    $stmt->execute([$event_id]);
    
    // Process
    handle_webhook($data);
}
```

---

### Rate Limiting

#### Error 429: Too Many Requests

**Causes**:
- Exceeding API rate limit (600 req/min)
- Exceeding campaign throughput

**Solutions**:

1. **Implement backoff**:
```php
<?php
function send_with_retry($to, $message, $max_retries = 3) {
    $delay = 1;
    
    for ($i = 0; $i < $max_retries; $i++) {
        $result = send_sms($to, $message);
        
        if ($result['http_code'] !== 429) {
            return $result;
        }
        
        sleep($delay);
        $delay *= 2; // Exponential backoff
    }
    
    return ['success' => false, 'error' => 'Rate limited'];
}
```

2. **Queue messages**: Use database queue + cron job

3. **Check throughput**: Verify 10DLC tier limits

---

### Content Issues

#### Message Blocked by Carrier

**Common Causes**:
- SHAFT-C content (Sex, Hate, Alcohol, Firearms, Tobacco, Cannabis)
- URL shorteners (bit.ly, etc.)
- Too many links
- Spam-like patterns
- Missing opt-out language

**Solutions**:
1. Remove prohibited content
2. Use full URLs, not shorteners
3. Include "Reply STOP to opt out"
4. Avoid ALL CAPS
5. Avoid excessive punctuation!!!

#### Long Messages Failing

**Cause**: Exceeding segment limit (10 segments max)

**Solution**: Keep under 1530 GSM-7 chars or 670 UCS-2 chars

```php
<?php
function check_message_length($message) {
    // Check for emojis and non-Latin scripts (require UCS-2)
    // Simple heuristic: ASCII printable + newlines = GSM-7
    $is_gsm7 = preg_match('/^[\x20-\x7E\n\r]*$/', $message);
    
    if (!$is_gsm7) {
        // UCS-2 encoding (emojis, unicode)
        $max_single = 70;
        $max_multi = 67;
    } else {
        // GSM-7 encoding (standard ASCII text)
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
        'valid' => $segments <= 10,
        'encoding' => $is_gsm7 ? 'GSM-7' : 'UCS-2'
    ];
}
```

**Note**: GSM-7 includes some non-ASCII characters (€, £, etc.), but for simplicity this function treats standard ASCII as GSM-7. The actual encoding is determined by Telnyx based on message content.

---

### Phone Number Issues

#### Invalid Number Format

**Correct Format**: E.164 (`+1XXXXXXXXXX`)

```php
<?php
function validate_phone($phone) {
    // Remove all non-digits except +
    $clean = preg_replace('/[^\d+]/', '', $phone);
    
    // Check E.164 format
    if (preg_match('/^\+1\d{10}$/', $clean)) {
        return ['valid' => true, 'formatted' => $clean];
    }
    
    // Try to fix US numbers
    $digits = preg_replace('/\D/', '', $phone);
    
    if (strlen($digits) === 10) {
        return ['valid' => true, 'formatted' => '+1' . $digits];
    }
    
    if (strlen($digits) === 11 && $digits[0] === '1') {
        return ['valid' => true, 'formatted' => '+' . $digits];
    }
    
    return ['valid' => false, 'error' => 'Invalid phone number'];
}
```

#### Landline Detection

Telnyx returns `line_type` in responses:

```php
<?php
$response = send_sms($phone, $message);

$line_type = $response['raw']['data']['to'][0]['line_type'] ?? '';

if ($line_type === 'Landline') {
    // Cannot receive SMS
    log_error("Cannot SMS landline: $phone");
}
```

---

## Diagnostic Tools

### Check Account Status

```bash
# Balance
curl -X GET "https://api.telnyx.com/v2/balance" \
  -H "Authorization: Bearer $TELNYX_API_KEY"

# Numbers
curl -X GET "https://api.telnyx.com/v2/phone_numbers" \
  -H "Authorization: Bearer $TELNYX_API_KEY"

# 10DLC Brands
curl -X GET "https://api.telnyx.com/v2/10dlc/brands" \
  -H "Authorization: Bearer $TELNYX_API_KEY"

# 10DLC Campaigns
curl -X GET "https://api.telnyx.com/v2/10dlc/campaigns" \
  -H "Authorization: Bearer $TELNYX_API_KEY"
```

### PHP Debug Function

```php
<?php
function debug_sms_config() {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/functions.php';

    $api_key = (string)get_setting($pdo, 'telnyx_api_key', '');
    $from = (string)get_setting($pdo, 'telnyx_from_number', '');
    $brand = (string)get_setting($pdo, 'sms_brand_name', '');
    $proxy_url = (string)get_setting($pdo, 'sms_webhook_proxy_url', '');
    $proxy_poll_key = (string)get_setting($pdo, 'sms_webhook_proxy_poll_key', '');
    $local_key = (string)get_setting($pdo, 'sms_webhook_local_key', '');
    $public_base_url = (string)get_setting($pdo, 'app_public_base_url', '');
    
    echo "=== SMS Configuration ===\n";
    echo "API Key: " . (strlen($api_key) > 10 ? substr($api_key, 0, 10) . '...' : 'MISSING') . "\n";
    echo "From Number: " . ($from ?: 'MISSING') . "\n";
    echo "Brand Name: " . ($brand ?: 'MISSING') . "\n";
    echo "Proxy URL: " . ($proxy_url ?: 'MISSING') . "\n";
    echo "Proxy Poll Key: " . ($proxy_poll_key ? '(set)' : 'MISSING') . "\n";
    echo "Local Key: " . ($local_key ? '(set)' : 'MISSING') . "\n";
    echo "Public Base URL: " . ($public_base_url ?: 'MISSING') . "\n";
    
    // Test API
    $ch = curl_init('https://api.telnyx.com/v2/messaging_profiles');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $api_key"]
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "API Test: " . ($code === 200 ? "✓ Connected" : "✗ Error $code") . "\n";
    
    if ($code === 200) {
        $data = json_decode($response, true);
        echo "Profiles Found: " . count($data['data']) . "\n";
    }
}

// Tip: paste this into a temporary script (e.g. docs/sms/debug_sms_config.php) and run:
// php docs/sms/debug_sms_config.php
```

---

## Support Contacts

| Issue | Contact |
|-------|---------|
| General support | support@telnyx.com |
| 10DLC questions | 10dlcquestions@telnyx.com |
| Portal | [portal.telnyx.com](https://portal.telnyx.com) |
| Phone | +1.888.980.9750 (24/7) |
| Slack community | [joinslack.telnyx.com](https://joinslack.telnyx.com) |
| API status | [status.telnyx.com](https://status.telnyx.com) |
