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

### RoadRunner Admin: Send SMS using Settings + `TelnyxSMS`

RoadRunner Admin stores Telnyx credentials in Settings (DB), and uses the helper in `includes/TelnyxSMS.php`.

```php
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/TelnyxSMS.php';

$apiKey = (string)get_setting($pdo, 'telnyx_api_key', '');
$fromNumber = (string)get_setting($pdo, 'telnyx_from_number', '');

if ($apiKey === '' || $fromNumber === '') {
  throw new RuntimeException('Missing SMS settings (telnyx_api_key / telnyx_from_number)');
}

$sms = new TelnyxSMS([
  'api_key' => $apiKey,
  'from_number' => $fromNumber,
]);

$result = $sms->send('+15551234567', '[Brand Name]: Test message');
var_dump($result);
```

### Sending templates

Templates in `TelnyxSMS::TEMPLATES` require `brand_name` to be passed in by the caller (settings-driven):

```php
<?php
$brandName = (string)get_setting($pdo, 'sms_brand_name', '');

$result = $sms->sendTemplate('+15551234567', 'ticket_created', [
  'brand_name' => $brandName,
  'ticket_id' => 'SR-2026-0142',
  'service_type' => 'Flat tire repair',
  'location' => '1234 Main St',
]);
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
curl -X PATCH "https://api.telnyx.com/v2/messaging_profiles/YOUR_MESSAGING_PROFILE_ID" \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "webhook_url": "https://YOURDOMAIN.com/sms-webhook/webhook.php",
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
