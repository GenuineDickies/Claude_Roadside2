# SiteGround SMS Webhook Proxy

Upload this folder to SiteGround:

- Destination: `/public_html/sms-webhook/`
- Files:
  - `webhook.php`
  - `.htaccess`

## Configure

The proxy needs MySQL database credentials and a poll key.

Recommended workflow:

1) In RoadRunner Admin go to **System → SMS Settings** and fill in:
  - Webhook Proxy DB Host/Name/User/Password
  - Webhook Proxy Poll Key

2) On your hosting, create a `config.php` file in the same folder as `webhook.php`.
  - Start from `config.sample.php` and paste your real values.
  - Keep `config.php` out of git (this repo ignores it).

Notes:
- If your host UI shows the MySQL user as `something@localhost`, you can paste that as-is.
  The proxy script will automatically strip the `@localhost` part when `DB_HOST` is `localhost`.
- For remote managed MySQL (Azure/RDS/etc), the `@server` portion may be required; in that case `DB_HOST` will not be `localhost`.

Alternative (advanced): set environment variables instead of `config.php`:
- `SMS_PROXY_DB_HOST`, `SMS_PROXY_DB_NAME`, `SMS_PROXY_DB_USER`, `SMS_PROXY_DB_PASS`
- `SMS_PROXY_POLL_API_KEY`

## Telnyx Webhook URL

Set in Telnyx to:

- `https://YOURDOMAIN.com/sms-webhook/webhook.php`

## Test

- Status:
  - `https://YOURDOMAIN.com/sms-webhook/webhook.php/status`

- Status (debug, requires API key):
  - `https://YOURDOMAIN.com/sms-webhook/webhook.php/status?debug=1&api_key=YOUR_KEY`

- Poll (replace API key):
  - `https://YOURDOMAIN.com/sms-webhook/webhook.php/poll?api_key=YOUR_KEY`

## RoadRunner Admin (local) integration

In **System → SMS Settings** set:

- Webhook Proxy URL → `https://YOURDOMAIN.com/sms-webhook/webhook.php`
- Webhook Proxy Poll Key → same as `POLL_API_KEY`

Then run the local poller:

- `http://localhost/claude_admin2/api/sms-webhook-poll.php`
