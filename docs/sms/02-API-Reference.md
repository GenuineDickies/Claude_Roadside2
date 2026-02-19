# Telnyx Messaging API Reference

Complete API reference for sending and receiving SMS/MMS via Telnyx.

## Authentication

All API requests require Bearer token authentication:

```bash
Authorization: Bearer YOUR_TELNYX_API_KEY
```

**Get your API key**: [portal.telnyx.com/api-keys](https://portal.telnyx.com/#/app/api-keys)

---

## Base URL

```
https://api.telnyx.com/v2
```

---

## Send a Message

### Endpoint
```
POST /messages
```

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `to` | string | ✅ | Recipient phone number (E.164 format: `+1XXXXXXXXXX`) |
| `from` | string | ✅ | Your Telnyx number (E.164 format) |
| `text` | string | ✅ for SMS | Message content (max 10 segments = 1530 chars) |
| `media_urls` | array | ✅ for MMS | Array of media URLs (max 1MB total) |
| `messaging_profile_id` | string | ⚪ | Required if using number pool |
| `type` | string | ⚪ | `SMS` or `MMS` (auto-detected) |
| `webhook_url` | string | ⚪ | Per-message webhook URL |
| `webhook_failover_url` | string | ⚪ | Failover webhook URL |

### SMS Example

```bash
curl -X POST https://api.telnyx.com/v2/messages \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "+15551234567",
    "to": "+15551234567",
    "text": "[Brand Name]: Your technician is on the way! ETA 15 min. Reply STOP to opt out."
  }'
```

### MMS Example

```bash
curl -X POST https://api.telnyx.com/v2/messages \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "+15551234567",
    "to": "+15551234567",
    "text": "[Brand Name]: Your technician John.",
    "media_urls": ["https://example.com/tech-photo.jpg"],
    "type": "MMS"
  }'
```

### Response (Success)

```json
{
  "data": {
    "record_type": "message",
    "direction": "outbound",
    "id": "40385f64-5717-4562-b3fc-2c963f66afa6",
    "type": "SMS",
    "messaging_profile_id": "4000eba1-a0c0-4563-9925-b25e842a7cb6",
    "from": {
      "phone_number": "+15551234567",
      "carrier": "Telnyx",
      "line_type": "VoIP"
    },
    "to": [
      {
        "phone_number": "+15551234567",
        "status": "queued",
        "carrier": "T-MOBILE USA, INC.",
        "line_type": "Wireless"
      }
    ],
    "text": "[Brand Name]: Your technician is on the way!",
    "encoding": "GSM-7",
    "parts": 1,
    "cost": {
      "amount": "0.0051",
      "currency": "USD"
    },
    "cost_breakdown": {
      "carrier_fee": { "amount": "0.00305", "currency": "USD" },
      "rate": { "amount": "0.00205", "currency": "USD" }
    },
    "tcr_campaign_id": "CXXXXXX",
    "tcr_campaign_registered": "REGISTERED"
  }
}
```

---

## PHP Implementation

### Send SMS Function

```php
<?php
/**
 * Send SMS via Telnyx API
 * 
 * @param string $to Recipient phone number (E.164 format)
 * @param string $message Message content
 * @param string|null $from Sender number (defaults to env)
 * @return array Response data
 */
function send_sms($to, $message, $from = null) {
    $api_key = getenv('TELNYX_API_KEY') ?: $_ENV['TELNYX_API_KEY'];
  $from_number = $from ?? (getenv('TELNYX_FROM_NUMBER') ?: '+15551234567');
    
    // Format phone number to E.164
    $to = format_e164($to);
    
    $payload = [
        'from' => $from_number,
        'to' => $to,
        'text' => $message
    ];
    
    $ch = curl_init('https://api.telnyx.com/v2/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    return [
        'success' => $http_code === 200,
        'http_code' => $http_code,
        'message_id' => $data['data']['id'] ?? null,
        'status' => $data['data']['to'][0]['status'] ?? null,
        'cost' => $data['data']['cost']['amount'] ?? null,
        'error' => $data['errors'][0]['title'] ?? null,
        'raw' => $data
    ];
}

/**
 * Format phone number to E.164
 */
function format_e164($phone) {
    // Remove all non-digits
    $digits = preg_replace('/\D/', '', $phone);
    
    // Handle US numbers
    if (strlen($digits) === 10) {
        return '+1' . $digits;
    } elseif (strlen($digits) === 11 && $digits[0] === '1') {
        return '+' . $digits;
    }
    
    // Already formatted or international
    if (strpos($phone, '+') === 0) {
        return $phone;
    }
    
    return '+' . $digits;
}
```

### Send MMS Function

```php
<?php
/**
 * Send MMS via Telnyx API
 * 
 * @param string $to Recipient phone number
 * @param string $message Message content
 * @param array $media_urls Array of media URLs
 * @return array Response data
 */
function send_mms($to, $message, $media_urls) {
    $api_key = getenv('TELNYX_API_KEY');
  $from_number = getenv('TELNYX_FROM_NUMBER') ?: '+15551234567';
    
    $payload = [
        'from' => $from_number,
        'to' => format_e164($to),
        'text' => $message,
        'media_urls' => $media_urls,
        'type' => 'MMS'
    ];
    
    $ch = curl_init('https://api.telnyx.com/v2/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $http_code === 200,
        'data' => json_decode($response, true)
    ];
}
```

---

## Retrieve a Message

### Endpoint
```
GET /messages/{message_id}
```

### Example

```bash
curl -X GET "https://api.telnyx.com/v2/messages/40385f64-5717-4562-b3fc-2c963f66afa6" \
  -H "Authorization: Bearer $TELNYX_API_KEY"
```

---

## List Messaging Profiles

### Endpoint
```
GET /messaging_profiles
```

### Example

```bash
curl -X GET "https://api.telnyx.com/v2/messaging_profiles" \
  -H "Authorization: Bearer $TELNYX_API_KEY"
```

---

## Update Messaging Profile Webhook

### Endpoint
```
PATCH /messaging_profiles/{profile_id}
```

### Example

```bash
curl -X PATCH "https://api.telnyx.com/v2/messaging_profiles/40019be9-93d9-478c-96ee-a90883641625" \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "webhook_url": "https://your-domain.com/api/sms/webhook.php",
    "webhook_failover_url": "https://backup.your-domain.com/webhook.php"
  }'
```

---

## Message Encoding

### GSM-7 Character Set (160 chars/segment)

Standard characters that fit in GSM-7:
```
A-Z a-z 0-9
@ £ $ ¥ è é ù ì ò Ç Ø ø Å å
Δ _ Φ Γ Λ Ω Π Ψ Σ Θ Ξ
^ { } \ [ ~ ] |
€
! " # ¤ % & ' ( ) * + , - . / : ; < = > ? ¡ § ¿
Space, newline, carriage return
```

### UCS-2 Encoding (70 chars/segment)

Used when message contains:
- Emojis 😀
- Non-Latin scripts (Chinese, Arabic, etc.)
- Special Unicode characters

### Segment Calculation

| Encoding | Single Segment | Multi-Segment |
|----------|----------------|---------------|
| GSM-7 | 160 chars | 153 chars/segment |
| UCS-2 | 70 chars | 67 chars/segment |

**Maximum**: 10 segments (1530 GSM-7 chars or 670 UCS-2 chars)

---

## Error Codes

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad request (validation error) |
| 401 | Invalid API key |
| 403 | Forbidden (number not enabled) |
| 422 | Unprocessable (registration required) |
| 402 | Insufficient balance |
| 429 | Rate limited |
| 500 | Server error |

### Common Error Responses

```json
{
  "errors": [
    {
      "code": "10009",
      "title": "Authentication failed",
      "detail": "Invalid API key"
    }
  ]
}
```

| Code | Title | Solution |
|------|-------|----------|
| 10009 | Authentication failed | Check API key |
| 40001 | Invalid phone number format | Use E.164 format |
| 40002 | Number not assigned to profile | Assign to messaging profile |
| 40003 | Registration required | Complete 10DLC registration |
| 40300 | Rate limit exceeded | Reduce request rate |

---

## Rate Limits

| Resource | Limit |
|----------|-------|
| API requests | 600/minute |
| Messages per second | Based on 10DLC campaign |
| Concurrent connections | 100 |

---

## Quick Links

- [Full API Reference](https://developers.telnyx.com/api-reference/messages/send-a-message)
- [PHP SDK](https://github.com/team-telnyx/telnyx-php)
- [Node SDK](https://github.com/team-telnyx/telnyx-node)
- [Python SDK](https://github.com/team-telnyx/telnyx-python)
