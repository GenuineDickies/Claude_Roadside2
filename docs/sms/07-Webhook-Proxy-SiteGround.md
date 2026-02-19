# SMS Webhook Proxy for SiteGround

Simple webhook proxy that receives Telnyx SMS webhooks and queues them for your local RoadRunner Admin app to poll.

## Setup on SiteGround

1. **Create a folder** on SiteGround:
   - `/public_html/sms-webhook/`

2. **Upload files**:
   - `webhook.php`
   - `.htaccess`

3. **Create a database** (or use existing) in SiteGround Site Tools.

4. **Choose a database** for the proxy queue (any MySQL database you control).

   In RoadRunner Admin go to **System → SMS Settings** and enter your **Webhook Proxy DB Host/Name/User/Password**.

   Then **copy/paste** the generated “Proxy Config Snippet” into the remote `webhook.php`.

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

5. **Generate an API key** for polling (any random 32+ character string):

```php
define('POLL_API_KEY', 'your_random_secret_key_here');
```

6. **Configure Telnyx** — Telnyx Portal → Messaging → Your Profile:
   - Webhook URL: `https://YOURDOMAIN.com/sms-webhook/webhook.php`

## Endpoints

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/webhook.php` | None | Receives Telnyx webhooks |
| GET | `/webhook.php/poll?api_key=XXX` | API Key | Fetch pending webhooks |
| POST | `/webhook.php/mark-processed?api_key=XXX` | API Key | Mark webhooks as processed |
| GET | `/webhook.php/status` | None | Health check |

Note: The provided `webhook.php` supports both path-style (`/poll`) and query-style (`?action=poll`) routing.

## Test It

```bash
# Check status
curl https://YOURDOMAIN.com/sms-webhook/webhook.php/status

# Poll for webhooks (replace YOUR_API_KEY)
curl "https://YOURDOMAIN.com/sms-webhook/webhook.php/poll?api_key=YOUR_API_KEY"
```

## Local App Configuration (RoadRunner Admin)

RoadRunner Admin uses the database-backed Settings system (not `.env`) for webhook polling.

1. Go to **System → SMS Settings**:
   - Set **Webhook Proxy URL** to your SiteGround proxy URL (example):
   - `https://YOURDOMAIN.com/sms-webhook/webhook.php`
   - Set **Webhook Proxy Poll Key** to the same `POLL_API_KEY` you configured on SiteGround.
   - (Optional) Store the proxy database credentials here as well so the next person can re-deploy the proxy without hunting through old notes.
   - (Optional) Set **Local Poller Key** so you can run the poller without a logged-in session.

2. Poll endpoint (local app):
   - If logged in: `http://localhost/claude_admin2/api/sms-webhook-poll.php`
   - If using a cron job: `http://localhost/claude_admin2/api/sms-webhook-poll.php?key=YOUR_LOCAL_POLLER_KEY`

## What gets processed

The local poller looks for inbound opt-out keywords (e.g. `STOP`, `UNSUBSCRIBE`, `CANCEL`, `END`, `QUIT`) and updates the local SMS consent tables:

- `sms_consent.opted_out = 1`
- `sms_consent.opt_out_at = NOW()`

It also appends an audit row in `sms_consent_events`.
