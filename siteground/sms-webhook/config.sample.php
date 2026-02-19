<?php
/**
 * SiteGround SMS Webhook Proxy — LOCAL CONFIG (sample)
 *
 * Copy this file to `config.php` (same folder) on your hosting.
 * DO NOT commit `config.php` to git.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Must match the value stored in RoadRunner Admin setting `sms_webhook_proxy_poll_key`
// and used by /poll and /mark-processed.
define('POLL_API_KEY', 'PASTE_THE_POLL_KEY_HERE');
