# Telnyx Webhook Handling

Complete guide to receiving and processing SMS webhooks from Telnyx.

## Overview

Telnyx sends HTTP POST webhooks for:
1. **Inbound messages** — When someone texts your number
2. **Delivery status** — When outbound messages are sent/delivered/failed

---

## Webhook Configuration

### Configure via Portal

1. Go to [Messaging Profiles](https://portal.telnyx.com/#/app/messaging)
2. Click your messaging profile
3. Enter webhook URL
4. Save

### Configure via API

```bash
curl -X PATCH "https://api.telnyx.com/v2/messaging_profiles/40019be9-93d9-478c-96ee-a90883641625" \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "webhook_url": "https://your-domain.com/api/sms/webhook.php",
    "webhook_failover_url": "https://backup.your-domain.com/webhook.php"
  }'
```

### Example Webhook URL
```
Webhook URL: https://YOURDOMAIN.com/sms-webhook/webhook.php
```

---

## Webhook URL Hierarchy

Telnyx determines where to send webhooks:

1. **Per-message URL** — `webhook_url` in send request (highest priority)
2. **Messaging profile URL** — Configured on profile
3. **No webhook** — Events still available in Message Detail Records

---

## Event Types

| Event | Trigger | Direction |
|-------|---------|-----------|
| `message.received` | Inbound SMS/MMS arrives | Inbound |
| `message.sent` | Message accepted by carrier | Outbound |
| `message.finalized` | Message reaches terminal state | Outbound |

---

## Payload Structure

All webhooks share this structure:

```json
{
  "data": {
    "event_type": "message.received",
    "id": "unique-event-id",
    "occurred_at": "2026-02-15T20:16:07.588+00:00",
    "payload": { /* message details */ },
    "record_type": "event"
  },
  "meta": {
    "attempt": 1,
    "delivered_to": "https://example.com/webhooks"
  }
}
```

---

## Event Examples

### Inbound Message (`message.received`)

```json
{
  "data": {
    "event_type": "message.received",
    "id": "b301ed3f-1490-491f-995f-6e64e69674d4",
    "occurred_at": "2026-02-15T20:16:07.588+00:00",
    "payload": {
      "direction": "inbound",
      "encoding": "GSM-7",
      "from": {
        "carrier": "T-Mobile USA",
        "line_type": "long_code",
        "phone_number": "+13125550001"
      },
      "id": "84cca175-9755-4859-b67f-4730d7f58aa3",
      "media": [],
      "messaging_profile_id": "740572b6-099c-44a1-89b9-6c92163bc68d",
      "parts": 1,
      "received_at": "2026-02-15T20:16:07.503+00:00",
      "record_type": "message",
      "text": "Hello from customer!",
      "to": [
        {
          "carrier": "Telnyx",
          "phone_number": "+15551234567",
          "status": "webhook_delivered"
        }
      ],
      "type": "SMS"
    },
    "record_type": "event"
  },
  "meta": {
    "attempt": 1,
    "delivered_to": "https://example.com/webhooks"
  }
}
```

### Delivery Receipt (`message.finalized`)

```json
{
  "data": {
    "event_type": "message.finalized",
    "id": "4ee8c3a6-4995-4309-a3c6-38e3db9ea4be",
    "occurred_at": "2026-02-15T21:32:14.148+00:00",
    "payload": {
      "completed_at": "2026-02-15T21:32:14.148+00:00",
      "cost": {
        "amount": "0.0051",
        "currency": "USD"
      },
      "direction": "outbound",
      "id": "ac012cbf-5e09-46af-a69a-7c0e2d90993c",
      "parts": 1,
      "sent_at": "2026-02-15T21:32:13.596+00:00",
      "text": "Your technician is on the way!",
      "to": [
        {
          "carrier": "T-MOBILE USA, INC.",
          "phone_number": "+13125550002",
          "status": "delivered"
        }
      ],
      "type": "SMS",
      "tcr_campaign_id": "CNZO3VL",
      "tcr_campaign_registered": "REGISTERED"
    },
    "record_type": "event"
  }
}
```

---

## Delivery Statuses

| Status | Description |
|--------|-------------|
| `queued` | Message queued on Telnyx |
| `sending` | Being sent to carrier |
| `sent` | Accepted by carrier |
| `delivered` | Confirmed delivered to handset |
| `sending_failed` | Telnyx failed to send |
| `delivery_failed` | Carrier failed to deliver |
| `delivery_unconfirmed` | No confirmation received |

---

## PHP Webhook Handler

### Basic Handler

```php
<?php
// api/sms/webhook.php

// Respond immediately (required within 2 seconds)
http_response_code(200);
echo 'OK';

// Flush output and continue processing
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Get webhook payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data || !isset($data['data']['event_type'])) {
    exit;
}

$event_type = $data['data']['event_type'];
$message_data = $data['data']['payload'];

// Log webhook
error_log("Telnyx webhook: $event_type");

switch ($event_type) {
    case 'message.received':
        handle_inbound_message($message_data);
        break;
        
    case 'message.sent':
        handle_message_sent($message_data);
        break;
        
    case 'message.finalized':
        handle_delivery_receipt($message_data);
        break;
}

function handle_inbound_message($data) {
    $from = $data['from']['phone_number'];
    $to = $data['to'][0]['phone_number'];
    $text = $data['text'] ?? '';
    $message_id = $data['id'];
    
    // Check for opt-out keywords
    $text_upper = strtoupper(trim($text));
    if (in_array($text_upper, ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'])) {
        handle_opt_out($from);
        return;
    }
    
    if (in_array($text_upper, ['HELP', 'INFO'])) {
        handle_help_request($from);
        return;
    }
    
    // Log to database
    log_inbound_message($from, $to, $text, $message_id);
    
    // Check for MMS media
    if (!empty($data['media'])) {
        foreach ($data['media'] as $media) {
            // Download and store media (URLs expire in 30 days)
            store_media($message_id, $media['url'], $media['content_type']);
        }
    }
}

function handle_delivery_receipt($data) {
    $message_id = $data['id'];
    $status = $data['to'][0]['status'] ?? 'unknown';
    $completed_at = $data['completed_at'] ?? null;
    
    // Update message status in database
    update_message_status($message_id, $status, $completed_at);
    
    if ($status === 'delivery_failed') {
        $errors = $data['errors'] ?? [];
        log_delivery_failure($message_id, $errors);
    }
}

function handle_opt_out($phone) {
    // Update customer SMS consent in database
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE customers 
        SET sms_consent = 0, 
            sms_opt_out_at = NOW() 
        WHERE phone = ?
    ");
    $stmt->execute([$phone]);
    
    error_log("SMS opt-out: $phone");
}

function handle_help_request($phone) {
    // Auto-reply with help message
  send_sms($phone, "[Brand Name]. For help call [Support Phone]. Reply STOP to opt out.");
}
```

### With Signature Verification (Recommended for Production)

```php
<?php
// api/sms/webhook.php

// Get headers
$signature = $_SERVER['HTTP_TELNYX_SIGNATURE_ED25519'] ?? '';
$timestamp = $_SERVER['HTTP_TELNYX_TIMESTAMP'] ?? '';
$payload = file_get_contents('php://input');

// Verify signature
$public_key = getenv('TELNYX_PUBLIC_KEY');

if (!verify_telnyx_signature($payload, $signature, $timestamp, $public_key)) {
    http_response_code(403);
    exit('Invalid signature');
}

// Check timestamp (prevent replay attacks)
$webhook_time = (int)$timestamp;
$current_time = time();
if (abs($current_time - $webhook_time) > 300) { // 5 minute tolerance
    http_response_code(403);
    exit('Timestamp too old');
}

// Continue processing...
http_response_code(200);

function verify_telnyx_signature($payload, $signature, $timestamp, $public_key) {
    // Decode the signature
    $sig_bytes = base64_decode($signature);
    if ($sig_bytes === false) {
        return false;
    }
    
    // Build the signed payload
    $signed_payload = $timestamp . '|' . $payload;
    
    // Verify using Ed25519
    // Note: Requires sodium extension (PHP 7.2+)
    $pub_key_bytes = base64_decode($public_key);
    
    return sodium_crypto_sign_verify_detached(
        $sig_bytes,
        $signed_payload,
        $pub_key_bytes
    );
}
```

---

## Retry Behavior

### Policy

| Setting | Value |
|---------|-------|
| Timeout | 2 seconds (must respond) |
| Retries | Up to 3 attempts per URL |
| Failover | Tries failover URL if primary fails |
| Total attempts | Up to 6 (3 primary + 3 failover) |
| Success response | Any 2xx status |

### Best Practices

1. **Respond immediately** — Return 200 before processing
2. **Handle duplicates** — Use `data.id` as idempotency key
3. **Handle out-of-order** — Use `occurred_at` timestamps
4. **Use HTTPS** — Always TLS in production
5. **Verify signatures** — Validate webhook authenticity

---

## IP Allowlist

If using a strict firewall, you may need to allowlist Telnyx IPs. However, Telnyx recommends **signature verification** over IP allowlisting as it's more secure and doesn't require firewall changes.

If you must use IP allowlisting, check Telnyx documentation for current IPs as they may change:
- [Telnyx IP Documentation](https://support.telnyx.com/en/articles/)

---

## Database Schema for SMS Logging

```sql
CREATE TABLE sms_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    telnyx_message_id VARCHAR(50) UNIQUE,
    direction ENUM('inbound', 'outbound') NOT NULL,
    from_number VARCHAR(20) NOT NULL,
    to_number VARCHAR(20) NOT NULL,
    message_text TEXT,
    status VARCHAR(30) DEFAULT 'queued',
    parts INT DEFAULT 1,
    cost DECIMAL(8,5),
    carrier VARCHAR(50),
    related_ticket_id INT,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_telnyx_id (telnyx_message_id),
    INDEX idx_from (from_number),
    INDEX idx_to (to_number),
    INDEX idx_ticket (related_ticket_id),
    INDEX idx_status (status)
);

CREATE TABLE sms_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    url VARCHAR(500),
    content_type VARCHAR(100),
    local_path VARCHAR(255),
    size INT,
    downloaded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES sms_messages(id)
);

CREATE TABLE sms_consent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    consented TINYINT(1) DEFAULT 1,
    consent_method ENUM('verbal-dispatcher', 'web-form', 'sms-keyword') NOT NULL,
    consented_at TIMESTAMP NULL,
    opted_out_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_phone (phone_number)
);
```

---

## Testing Webhooks Locally

### Using ngrok

```bash
# Install ngrok
brew install ngrok

# Start tunnel
ngrok http 80

# Use the https URL as your webhook
# Example: https://abc123.ngrok.io/api/sms/webhook.php
```

### Test with curl

```bash
curl -X POST http://localhost/claude_admin2/api/sms/webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "data": {
      "event_type": "message.received",
      "id": "test-123",
      "payload": {
        "from": {"phone_number": "+15551234567"},
        "to": [{"phone_number": "+15551234567"}],
        "text": "Test message"
      }
    }
  }'
```

---

## Troubleshooting

### Webhooks Not Arriving

1. Check webhook URL is correct in messaging profile
2. Verify URL is publicly accessible (HTTPS)
3. Check firewall allows Telnyx IPs
4. Verify your endpoint returns 200 within 2 seconds

### Duplicate Webhooks

- Normal behavior during retries
- Use `data.id` to deduplicate
- Store processed IDs and skip if already processed

### Signature Verification Failing

1. Ensure using raw request body (not parsed)
2. Check public key is correct
3. Verify timestamp is within 5 minutes
4. Check sodium extension is installed

---

## Quick Links

- [Webhook Documentation](https://developers.telnyx.com/docs/messaging/messages/receiving-webhooks)
- [Get Public Key](https://portal.telnyx.com/#/app/api-keys) (scroll to "Public Key" section)
- [ngrok Setup](https://developers.telnyx.com/development/development-tools/ngrok-setup)

**Note**: The public key for webhook signature verification is found in your Telnyx Portal under Account Settings > API Keys > Public Key. This is different from your API Key.
