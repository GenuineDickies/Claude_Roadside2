# Telnyx Webhook Handling

Complete guide to receiving and processing SMS webhooks from Telnyx.

## Overview

Telnyx sends HTTP POST webhooks for:
1. **Inbound messages** — When someone texts your number
2. **Delivery status** — When outbound messages are sent/delivered/failed

---

## RoadRunner Admin Webhook Flow (Actual Implementation)

RoadRunner Admin does **not** expose a public webhook endpoint from the local app.
Instead it uses a small **SiteGround webhook proxy** that queues Telnyx webhooks in a MySQL table, then the local app polls and processes them.

Flow:

1. Telnyx → `https://YOURDOMAIN.com/sms-webhook/webhook.php` (public proxy)
2. Proxy queues payloads into `sms_webhook_queue`
3. Local app calls `api/sms-webhook-poll.php` to:
  - fetch queued events from the proxy (`/poll`)
  - process STOP/HELP/START/UNSTOP keywords
  - update local consent tables
  - mark proxy rows processed (`/mark-processed`)

---

## Webhook Configuration

### Configure via Portal

1. Go to [Messaging Profiles](https://portal.telnyx.com/#/app/messaging)
2. Click your messaging profile
3. Enter webhook URL
4. Save

### Configure via API

```bash
curl -X PATCH "https://api.telnyx.com/v2/messaging_profiles/YOUR_MESSAGING_PROFILE_ID" \
  -H "Authorization: Bearer $TELNYX_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "webhook_url": "https://YOURDOMAIN.com/sms-webhook/webhook.php",
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

## RoadRunner Implementation (Proxy + Poller)

RoadRunner Admin handles Telnyx webhooks in **two** places:

1. **Public proxy receiver (SiteGround)**: `siteground/sms-webhook/webhook.php`
   - Receives Telnyx `POST` webhooks (unauthenticated)
   - Queues payloads in the proxy database table `sms_webhook_queue`

2. **Local poller/processor**: `api/sms-webhook-poll.php`
   - Authenticates to the proxy using `sms_webhook_proxy_poll_key`
   - Polls queued events (`/poll`), processes opt-out keywords (STOP/HELP/START/UNSTOP), updates local consent state
   - Acknowledges processed proxy rows (`/mark-processed`)

### Signature Verification

The shipped proxy focuses on fast queuing and does **not** currently verify Telnyx webhook signatures.
If you need signature verification, add it at the proxy layer before queue insert using Telnyx webhook signing headers.

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

## Database Schema (Actual)

Local RoadRunner Admin database tables (created/updated by `config/intake_schema.php`):

```sql
CREATE TABLE IF NOT EXISTS sms_consent (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone_digits VARCHAR(20) NOT NULL,
  consent TINYINT(1) NOT NULL DEFAULT 0,
  consent_at TIMESTAMP NULL DEFAULT NULL,
  opted_out TINYINT(1) NOT NULL DEFAULT 0,
  opt_out_at TIMESTAMP NULL DEFAULT NULL,
  last_source VARCHAR(50) DEFAULT NULL,
  last_ticket_id INT DEFAULT NULL,
  last_ticket_number VARCHAR(20) DEFAULT NULL,
  last_seen_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_phone (phone_digits),
  KEY idx_consent (consent),
  KEY idx_opted_out (opted_out),
  KEY idx_last_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sms_consent_events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone_digits VARCHAR(20) NOT NULL,
  event_type VARCHAR(30) NOT NULL,
  source VARCHAR(50) DEFAULT NULL,
  ticket_id INT DEFAULT NULL,
  ticket_number VARCHAR(20) DEFAULT NULL,
  user_id INT DEFAULT NULL,
  meta JSON DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_phone (phone_digits),
  KEY idx_event (event_type),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

SiteGround proxy database queue table (created by `siteground/sms-webhook/webhook.php`):

```sql
CREATE TABLE IF NOT EXISTS sms_webhook_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payload JSON NOT NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  processed TINYINT(1) NOT NULL DEFAULT 0,
  processed_at DATETIME NULL,
  INDEX idx_processed (processed),
  INDEX idx_received (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Testing (Proxy + Poller)

For RoadRunner Admin, test the **public proxy** and then run the **local poller**.

### 1) Check proxy health

```bash
curl https://YOURDOMAIN.com/sms-webhook/webhook.php/status
```

### 2) Run the local poller

- If logged in: `http://localhost/claude_admin2/api/sms-webhook-poll.php`
- For cron: `http://localhost/claude_admin2/api/sms-webhook-poll.php?key=YOUR_LOCAL_POLLER_KEY`

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
