# SMS Knowledge Base

Comprehensive documentation for Telnyx SMS integration, 10DLC compliance, and API usage.

## Quick Navigation

| Document | Description |
|----------|-------------|
| [10DLC Compliance](?page=sms-knowledge&doc=10dlc) | 10DLC registration, brands, campaigns, and compliance |
| [API Reference](?page=sms-knowledge&doc=api) | Telnyx API endpoints, authentication, sending/receiving |
| [Webhooks](?page=sms-knowledge&doc=webhooks) | Webhook setup, event types, signature verification |
| [Message Templates](?page=sms-knowledge&doc=templates) | Message templates for dispatch workflow |
| [Opt-In & Consent](?page=sms-knowledge&doc=consent) | TCPA compliance, consent collection, opt-out handling |
| [Troubleshooting](?page=sms-knowledge&doc=troubleshooting) | Common errors, delivery failures, debugging |

## How RoadRunner Admin is configured

RoadRunner Admin stores SMS configuration in the database-backed Settings system (not `.env`).

Configure in **System → SMS Settings**:

- `sms_brand_name` — customer-facing business identifier for SMS
- `telnyx_api_key` — Telnyx Secret API key (write-only in UI)
- `telnyx_from_number` — Telnyx number in E.164 (e.g. `+15551234567`)
- `sms_webhook_proxy_url` — public proxy URL Telnyx posts to (SiteGround)
- `sms_webhook_proxy_poll_key` — API key used by the local app to poll the proxy
- `sms_webhook_local_key` — optional key for running the local poller without a logged-in session
- `app_public_base_url` — optional base URL used for customer-facing links (e.g. `https://YOURDOMAIN.com`)

## Key Compliance Dates

| Date | Event |
|------|-------|
| Feb 3, 2025 | Unregistered 10DLC traffic BLOCKED entirely |
| Now (2026) | All A2P traffic to US carriers MUST be registered |

## Emergency Contacts

- **Telnyx Support**: support@telnyx.com | +1.888.980.9750 (24/7)
- **10DLC Questions**: 10dlcquestions@telnyx.com
- **Mission Control Portal**: https://portal.telnyx.com
