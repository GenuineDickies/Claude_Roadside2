<?php
/**
 * SMS Settings
 * System configuration for Customer Care SMS.
 */

$currentBrandName = trim((string)get_setting($pdo, 'sms_brand_name', ''));
$publicBaseUrl = trim((string)get_setting($pdo, 'app_public_base_url', ''));
$storedTelnyxApiKey = get_setting($pdo, 'telnyx_api_key', '');
$telnyxApiKeySet = $storedTelnyxApiKey !== '';
$currentTelnyxFrom = get_setting($pdo, 'telnyx_from_number', '');
$proxyUrl = get_setting($pdo, 'sms_webhook_proxy_url', '');
$proxyPollKeySet = get_setting($pdo, 'sms_webhook_proxy_poll_key', '') !== '';
$localWebhookKeySet = get_setting($pdo, 'sms_webhook_local_key', '') !== '';

// Webhook proxy database credentials (stored for reference / copy-paste into the remote proxy)
$proxyDbHost = get_setting($pdo, 'sms_webhook_proxy_db_host', 'localhost');
$proxyDbName = get_setting($pdo, 'sms_webhook_proxy_db_name', '');
$proxyDbUser = get_setting($pdo, 'sms_webhook_proxy_db_user', '');
$proxyDbPassSet = get_setting($pdo, 'sms_webhook_proxy_db_pass', '') !== '';

function normalize_telnyx_from_number($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';

    // Allow +E.164
    if (strpos($raw, '+') === 0) {
        $digits = preg_replace('/\D/', '', $raw);
        return '+' . $digits;
    }

    // US-centric normalization from digits
    $digits = preg_replace('/\D/', '', $raw);
    if (strlen($digits) === 10) return '+1' . $digits;
    if (strlen($digits) === 11 && strpos($digits, '1') === 0) return '+' . $digits;

    // Fallback: return original trimmed value (will be validated by caller)
    return $raw;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sms_settings'])) {
    $brandName = trim((string)($_POST['sms_brand_name'] ?? ''));
    $publicBaseUrlRaw = trim((string)($_POST['app_public_base_url'] ?? ''));
    $telnyxApiKey = trim((string)($_POST['telnyx_api_key'] ?? ''));
    // Normalize common paste mistakes
    if ($telnyxApiKey !== '') {
        // Remove wrapping quotes
        $telnyxApiKey = trim($telnyxApiKey, " \t\n\r\0\x0B\"");
        $telnyxApiKey = trim($telnyxApiKey, " \t\n\r\0\x0B'");
        // Remove accidental Bearer prefix
        if (stripos($telnyxApiKey, 'bearer ') === 0) {
            $telnyxApiKey = trim(substr($telnyxApiKey, 7));
        }
        // Remove ALL whitespace/newlines inside the key
        $telnyxApiKey = preg_replace('/\s+/', '', $telnyxApiKey);
    }
    $telnyxFromRaw = trim((string)($_POST['telnyx_from_number'] ?? ''));
    $telnyxFrom = normalize_telnyx_from_number($telnyxFromRaw);
    $proxyUrlRaw = trim((string)($_POST['sms_webhook_proxy_url'] ?? ''));
    $proxyPollKey = trim((string)($_POST['sms_webhook_proxy_poll_key'] ?? ''));
    $localWebhookKey = trim((string)($_POST['sms_webhook_local_key'] ?? ''));
    $proxyDbHostRaw = trim((string)($_POST['sms_webhook_proxy_db_host'] ?? ''));
    $proxyDbNameRaw = trim((string)($_POST['sms_webhook_proxy_db_name'] ?? ''));
    $proxyDbUserRaw = trim((string)($_POST['sms_webhook_proxy_db_user'] ?? ''));
    $proxyDbPassRaw = trim((string)($_POST['sms_webhook_proxy_db_pass'] ?? ''));
    $errors = [];

    if ($brandName === '') $errors[] = 'Brand Name is required.';
    if (mb_strlen($brandName) > 100) $errors[] = 'Brand Name must be 100 characters or less.';

    if ($publicBaseUrlRaw !== '') {
        if (!preg_match('#^https?://#i', $publicBaseUrlRaw)) {
            $errors[] = 'Public Base URL must start with http:// or https://.';
        } else {
            $publicBaseUrlRaw = rtrim($publicBaseUrlRaw, '/');
            $parsed = @parse_url($publicBaseUrlRaw);
            $host = is_array($parsed) ? ($parsed['host'] ?? '') : '';
            if ($host === '') {
                $errors[] = 'Public Base URL must be a valid URL (e.g. https://yourdomain.com).';
            }
        }
    }

    // Only validate if the field was provided
    if ($telnyxFromRaw !== '') {
        // Basic E.164 validation if it looks like +digits
        if (strpos($telnyxFrom, '+') === 0) {
            $digits = preg_replace('/\D/', '', $telnyxFrom);
            if (strlen($digits) < 10 || strlen($digits) > 15) {
                $errors[] = 'Telnyx From Number must be a valid E.164 number (e.g. +15551234567).';
            }
        } else {
            // If user entered something non-numeric/non-E.164
            $errors[] = 'Telnyx From Number must be digits or E.164 (e.g. +15551234567).';
        }
    }

    if ($telnyxApiKey !== '') {
        // Telnyx secret keys are commonly KEY + 32 hex chars (len 35). If we see that length, validate strictly.
        if (strlen($telnyxApiKey) === 35) {
            if (!preg_match('/^KEY[0-9A-Fa-f]{32}$/', $telnyxApiKey)) {
                $errors[] = 'Telnyx API Key looks malformed (expected KEY + 32 hex characters). You may have copied the API key ID instead of the secret token.';
            }
        } else {
            // Fallback: require KEY prefix and a reasonable length, no spaces
            if (!preg_match('/^KEY[0-9A-Za-z_\-]{10,}$/', $telnyxApiKey)) {
                $errors[] = 'Telnyx API Key looks invalid. Paste the Telnyx Secret API Key (usually starts with KEY...) with no spaces.';
            }
        }
    }

    if ($proxyUrlRaw !== '') {
        if (!preg_match('#^https?://#i', $proxyUrlRaw)) {
            $errors[] = 'Webhook Proxy URL must start with http:// or https://.';
        }
    }

    if ($proxyDbHostRaw !== '' && mb_strlen($proxyDbHostRaw) > 100) $errors[] = 'Webhook Proxy DB Host must be 100 characters or less.';
    if ($proxyDbNameRaw !== '' && mb_strlen($proxyDbNameRaw) > 100) $errors[] = 'Webhook Proxy DB Name must be 100 characters or less.';
    if ($proxyDbUserRaw !== '' && mb_strlen($proxyDbUserRaw) > 100) $errors[] = 'Webhook Proxy DB User must be 100 characters or less.';

    if (!empty($errors)) {
        show_alert(implode(' ', $errors), 'danger');
        $currentBrandName = $brandName;
        $publicBaseUrl = $publicBaseUrlRaw !== '' ? $publicBaseUrlRaw : $publicBaseUrl;
        $currentTelnyxFrom = $telnyxFromRaw;
        $proxyUrl = $proxyUrlRaw;
        $proxyDbHost = $proxyDbHostRaw;
        $proxyDbName = $proxyDbNameRaw;
        $proxyDbUser = $proxyDbUserRaw;
    } else {
        $ok1 = set_setting($pdo, 'sms_brand_name', $brandName, 'sms');
        $okPublic = true;
        $ok2 = true;
        $ok3 = true;
        $ok4 = true;
        $ok5 = true;
        $ok6 = true;
        $ok7 = true;
        $ok8 = true;
        $ok9 = true;
        $ok10 = true;

        // Only update Public Base URL if provided (blank = keep existing)
        if ($publicBaseUrlRaw !== '') {
            $okPublic = set_setting($pdo, 'app_public_base_url', rtrim($publicBaseUrlRaw, '/'), 'system');
        }

        // Only update API key if provided (avoid re-saving blank)
        if ($telnyxApiKey !== '') {
            $ok2 = set_setting($pdo, 'telnyx_api_key', $telnyxApiKey, 'sms');
        }

        // Only update From number if provided (blank = keep existing)
        if ($telnyxFromRaw !== '') {
            $ok3 = set_setting($pdo, 'telnyx_from_number', $telnyxFrom, 'sms');
        }

        // Only update Proxy URL if provided (blank = keep existing)
        if ($proxyUrlRaw !== '') {
            $ok4 = set_setting($pdo, 'sms_webhook_proxy_url', $proxyUrlRaw, 'sms');
        }

        // Only update proxy poll key if provided (avoid re-saving blank)
        if ($proxyPollKey !== '') {
            $ok5 = set_setting($pdo, 'sms_webhook_proxy_poll_key', $proxyPollKey, 'sms');
        }

        // Only update local webhook key if provided (avoid re-saving blank)
        if ($localWebhookKey !== '') {
            $ok6 = set_setting($pdo, 'sms_webhook_local_key', $localWebhookKey, 'sms');
        }

        // Webhook proxy DB credentials are stored for reference / copy-paste into the remote proxy.
        // Only update when provided (blank = keep existing). Password is write-only: only updated if provided.
        if ($proxyDbHostRaw !== '') {
            $ok7 = set_setting($pdo, 'sms_webhook_proxy_db_host', $proxyDbHostRaw, 'sms');
        }
        if ($proxyDbNameRaw !== '') {
            $ok8 = set_setting($pdo, 'sms_webhook_proxy_db_name', $proxyDbNameRaw, 'sms');
        }
        if ($proxyDbUserRaw !== '') {
            $ok9 = set_setting($pdo, 'sms_webhook_proxy_db_user', $proxyDbUserRaw, 'sms');
        }
        if ($proxyDbPassRaw !== '') {
            $ok10 = set_setting($pdo, 'sms_webhook_proxy_db_pass', $proxyDbPassRaw, 'sms');
        }

        if ($ok1 && $okPublic && $ok2 && $ok3 && $ok4 && $ok5 && $ok6 && $ok7 && $ok8 && $ok9 && $ok10) {
            show_alert('SMS settings saved.', 'success');
        } else {
            show_alert('Some SMS settings could not be saved. Check server logs for details.', 'danger');
        }

        $currentBrandName = $brandName;
        if ($publicBaseUrlRaw !== '') {
            $publicBaseUrl = rtrim($publicBaseUrlRaw, '/');
        }
        if ($telnyxFromRaw !== '') {
            $currentTelnyxFrom = $telnyxFrom;
        }

        $storedTelnyxApiKey = get_setting($pdo, 'telnyx_api_key', '');
        $telnyxApiKeySet = $storedTelnyxApiKey !== '';

        if ($proxyUrlRaw !== '') {
            $proxyUrl = $proxyUrlRaw;
        }
        $proxyPollKeySet = get_setting($pdo, 'sms_webhook_proxy_poll_key', '') !== '';
        $localWebhookKeySet = get_setting($pdo, 'sms_webhook_local_key', '') !== '';

        if ($proxyDbHostRaw !== '') $proxyDbHost = $proxyDbHostRaw;
        if ($proxyDbNameRaw !== '') $proxyDbName = $proxyDbNameRaw;
        if ($proxyDbUserRaw !== '') $proxyDbUser = $proxyDbUserRaw;
        $proxyDbPassSet = get_setting($pdo, 'sms_webhook_proxy_db_pass', '') !== '';
    }
}

$brandForScript = ($currentBrandName !== '') ? $currentBrandName : '[Brand Name]';
$consentScript = "By providing your phone number, you agree to receive Customer Care SMS from {$brandForScript}. Message frequency may vary. Standard Message and Data Rates may apply. Reply STOP to opt out. Reply HELP for help. We will not share mobile information with third parties for promotional or marketing purposes.";

$telnyxKeyFingerprint = null;
if ($telnyxApiKeySet) {
    $len = strlen($storedTelnyxApiKey);
    $isHex32 = ($len === 35 && preg_match('/^KEY[0-9A-Fa-f]{32}$/', $storedTelnyxApiKey)) ? 'yes' : 'no';
    $telnyxKeyFingerprint = substr($storedTelnyxApiKey, 0, 3) . '… (len ' . $len . ', KEY+32hex ' . $isHex32 . ')';
}

$proxyDbSnippet = "define('DB_HOST', '" . addslashes($proxyDbHost) . "');\n"
    . "define('DB_NAME', '" . addslashes($proxyDbName) . "');\n"
    . "define('DB_USER', '" . addslashes($proxyDbUser) . "');\n"
    . "define('DB_PASS', '" . ($proxyDbPassSet ? '********' : '') . "');\n\n"
    . "define('POLL_API_KEY', '" . ($proxyPollKeySet ? '********' : '') . "');";
?>

<style>
.smsset-header {
    background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 24px 28px;
    margin: -28px -28px 24px -28px;
}

.smsset-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--navy-300);
    letter-spacing: -0.5px;
    margin: 0;
}

.smsset-subtitle {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 2px 0 0;
}

.smsset-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-medium);
    border-radius: 10px;
}

.smsset-card-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.smsset-card-title {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--text-tertiary);
}

.smsset-card-body {
    padding: 18px;
}

.smsset-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 8px;
}

.smsset-help {
    font-size: 12px;
    color: var(--text-tertiary);
    margin-top: 8px;
}

.smsset-mono {
    font-family: 'JetBrains Mono', monospace;
}

.smsset-preview {
    background: var(--bg-secondary);
    border: 1px solid var(--border-medium);
    border-left: 3px solid var(--purple-500);
    border-radius: 12px;
    padding: 14px;
    color: var(--text-primary);
    font-size: 12.5px;
    line-height: 1.7;
}
</style>

<div class="smsset-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-comment-sms" style="font-size:26px;color:var(--purple-500)"></i>
        <div>
            <h1 class="smsset-title">SMS Settings</h1>
            <p class="smsset-subtitle">Configure Customer Care SMS branding and consent copy</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="smsset-card">
            <div class="smsset-card-header">
                <h3 class="smsset-card-title">Branding</h3>
                <span class="badge" style="background: var(--accent-glow-purple); color: var(--purple-500); border: 1px solid var(--border-medium);">SMS</span>
            </div>
            <div class="smsset-card-body">
                <form method="POST" action="?page=sms-settings">
                    <div class="mb-3">
                        <div class="smsset-label">Brand Name</div>
                        <input
                            type="text"
                            name="sms_brand_name"
                            class="form-control"
                            value="<?= htmlspecialchars($currentBrandName, ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="100"
                            placeholder="e.g. Your Company Name"
                            required
                        >
                        <div class="smsset-help">
                            Customer-facing business/brand identifier used in the intake consent script and SMS templates. This may differ from the application name.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Telnyx API Key</div>
                        <input
                            type="password"
                            name="telnyx_api_key"
                            class="form-control smsset-mono"
                            value=""
                            placeholder="<?= $telnyxApiKeySet ? 'Configured (leave blank to keep)' : 'Paste Telnyx API Key' ?>"
                            autocomplete="off"
                        >
                        <div class="smsset-help">
                            Stored in settings as <span class="smsset-mono">telnyx_api_key</span>. Leave blank to keep the current key.
                            Paste the raw Secret API Key only (no “Bearer ” prefix, no spaces/newlines).
                            <?php if ($telnyxKeyFingerprint): ?>
                                <div style="margin-top:6px">Configured key: <span class="smsset-mono"><?= htmlspecialchars($telnyxKeyFingerprint, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Telnyx From Number</div>
                        <input
                            type="text"
                            name="telnyx_from_number"
                            class="form-control smsset-mono"
                            value="<?= htmlspecialchars($currentTelnyxFrom, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="e.g. +15551234567"
                        >
                        <div class="smsset-help">
                            E.164 recommended. Stored as <span class="smsset-mono">telnyx_from_number</span>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Public Base URL (Your Domain)</div>
                        <input
                            type="text"
                            name="app_public_base_url"
                            class="form-control smsset-mono"
                            value="<?= htmlspecialchars($publicBaseUrl, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="e.g. https://yourdomain.com"
                        >
                        <div class="smsset-help">
                            Optional. Used as a base for any customer-facing links generated by the app (receipts, tracking, surveys). Stored as <span class="smsset-mono">app_public_base_url</span>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Webhook Proxy URL</div>
                        <input
                            type="text"
                            name="sms_webhook_proxy_url"
                            class="form-control smsset-mono"
                            value="<?= htmlspecialchars($proxyUrl, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="e.g. https://YOURDOMAIN.com/sms-webhook/webhook.php"
                        >
                        <div class="smsset-help">
                            Optional. Used by the local poller endpoint <span class="smsset-mono">api/sms-webhook-poll.php</span>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Webhook Proxy Poll Key</div>
                        <input
                            type="password"
                            name="sms_webhook_proxy_poll_key"
                            class="form-control smsset-mono"
                            value=""
                            placeholder="<?= $proxyPollKeySet ? 'Configured (leave blank to keep)' : 'Paste proxy poll key' ?>"
                            autocomplete="off"
                        >
                        <div class="smsset-help">
                            Stored as <span class="smsset-mono">sms_webhook_proxy_poll_key</span>. Leave blank to keep.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Local Poller Key</div>
                        <input
                            type="password"
                            name="sms_webhook_local_key"
                            class="form-control smsset-mono"
                            value=""
                            placeholder="<?= $localWebhookKeySet ? 'Configured (leave blank to keep)' : 'Set a local key for cron/polling' ?>"
                            autocomplete="off"
                        >
                        <div class="smsset-help">
                            Optional. If set, you can call <span class="smsset-mono">api/sms-webhook-poll.php?key=...</span> without a logged-in session.
                        </div>
                    </div>

                    <hr style="border-color: var(--border-subtle); margin: 18px 0;">

                    <div class="mb-3">
                        <div class="smsset-label">Webhook Proxy DB Host</div>
                        <input
                            type="text"
                            name="sms_webhook_proxy_db_host"
                            class="form-control smsset-mono"
                            value="<?= htmlspecialchars($proxyDbHost, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="localhost"
                        >
                        <div class="smsset-help">
                            Stored as <span class="smsset-mono">sms_webhook_proxy_db_host</span>. Used for the remote webhook proxy database connection.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Webhook Proxy DB Name</div>
                        <input
                            type="text"
                            name="sms_webhook_proxy_db_name"
                            class="form-control smsset-mono"
                            value="<?= htmlspecialchars($proxyDbName, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="your_database_name"
                        >
                        <div class="smsset-help">
                            Stored as <span class="smsset-mono">sms_webhook_proxy_db_name</span>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Webhook Proxy DB User</div>
                        <input
                            type="text"
                            name="sms_webhook_proxy_db_user"
                            class="form-control smsset-mono"
                            value="<?= htmlspecialchars($proxyDbUser, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="your_database_user"
                        >
                        <div class="smsset-help">
                            Stored as <span class="smsset-mono">sms_webhook_proxy_db_user</span>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="smsset-label">Webhook Proxy DB Password</div>
                        <input
                            type="password"
                            name="sms_webhook_proxy_db_pass"
                            class="form-control smsset-mono"
                            value=""
                            placeholder="<?= $proxyDbPassSet ? 'Configured (leave blank to keep)' : 'your_database_password' ?>"
                            autocomplete="off"
                        >
                        <div class="smsset-help">
                            Stored as <span class="smsset-mono">sms_webhook_proxy_db_pass</span>. Leave blank to keep.
                        </div>
                    </div>

                    <div class="smsset-help" style="margin-top:10px">
                        <div style="font-weight:600; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.6px; font-size: 12px; margin-bottom: 8px;">Proxy Config Snippet</div>
                        <pre class="smsset-preview smsset-mono" style="white-space:pre-wrap; margin:0"><?= htmlspecialchars($proxyDbSnippet, ENT_QUOTES, 'UTF-8') ?></pre>
                        <div class="smsset-help" style="margin-top:8px">
                            <span class="smsset-mono">POLL_API_KEY</span> is the same value stored as <span class="smsset-mono">sms_webhook_proxy_poll_key</span>.
                        </div>
                    </div>

                    <button type="submit" name="save_sms_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save SMS Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="smsset-card">
            <div class="smsset-card-header">
                <h3 class="smsset-card-title">Consent Script Preview</h3>
                <span class="smsset-mono" style="font-size:12px;color:var(--text-tertiary)">sms_brand_name</span>
            </div>
            <div class="smsset-card-body">
                <div class="smsset-preview smsset-mono">
                    “<?= htmlspecialchars($consentScript, ENT_QUOTES, 'UTF-8') ?>”
                </div>
                <div class="smsset-help">
                    This preview matches the required compliance language used on the New Intake consent modal.
                </div>
            </div>
        </div>

        <div class="smsset-card" style="margin-top: 14px;">
            <div class="smsset-card-header">
                <h3 class="smsset-card-title">Test SMS</h3>
                <span class="badge" style="background: var(--bg-secondary); color: var(--text-tertiary); border: 1px solid var(--border-medium);">Telnyx</span>
            </div>
            <div class="smsset-card-body">
                <div class="mb-3">
                    <div class="smsset-label">To Number</div>
                    <input id="smsTestTo" type="text" class="form-control smsset-mono" value="5037643154" placeholder="e.g. 5037643154 or +15037643154">
                    <div class="smsset-help">Requires <span class="smsset-mono">consent=1</span> and <span class="smsset-mono">opted_out=0</span>.</div>
                </div>

                <div class="mb-3">
                    <div class="smsset-label">Message</div>
                    <textarea id="smsTestMessage" class="form-control smsset-mono" rows="3" placeholder="Type a short test message">Test SMS: if you received this, outbound SMS is working.</textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="smsTestGrantConsent">
                    <label class="form-check-label" for="smsTestGrantConsent">
                        Grant consent (0→1) and clear opt-out for this test
                    </label>
                    <div class="smsset-help">Only use this if you have permission from the customer.</div>
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <button id="smsTestSendBtn" type="button" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Test SMS
                    </button>
                    <div id="smsTestResult" class="smsset-help" style="margin:0"></div>
                </div>
            </div>
        </div>

        <div class="smsset-card" style="margin-top: 14px;">
            <div class="smsset-card-header">
                <h3 class="smsset-card-title">Webhook Poller</h3>
                <span class="badge" style="background: var(--bg-secondary); color: var(--text-tertiary); border: 1px solid var(--border-medium);">Proxy</span>
            </div>
            <div class="smsset-card-body">
                <div class="smsset-help" style="margin-top:0">
                    Runs <span class="smsset-mono">api/sms-webhook-poll.php</span> now and processes inbound keywords (STOP/HELP) from the remote proxy queue.
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top: 12px;">
                    <button id="smsPollNowBtn" type="button" class="btn btn-outline-primary">
                        <i class="fas fa-sync-alt"></i> Run Poll Now
                    </button>
                    <div id="smsPollNowResult" class="smsset-help" style="margin:0"></div>
                </div>

                <div id="smsPollNowDetails" class="smsset-help" style="margin-top:10px; display:none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const btn = document.getElementById('smsTestSendBtn');
    const elTo = document.getElementById('smsTestTo');
    const elMsg = document.getElementById('smsTestMessage');
    const elGrant = document.getElementById('smsTestGrantConsent');
    const elResult = document.getElementById('smsTestResult');

    if (!btn) return;

    function setResult(text, kind) {
        elResult.textContent = text;
        elResult.style.color = kind === 'ok' ? 'var(--success)' : (kind === 'warn' ? 'var(--warning)' : 'var(--danger)');
    }

    btn.addEventListener('click', async function () {
        const to = (elTo.value || '').trim();
        const message = (elMsg.value || '').trim();
        const grant = !!elGrant.checked;

        if (!to || !message) {
            setResult('Enter a To number and message.', 'warn');
            return;
        }

        btn.disabled = true;
        setResult('Sending…', 'warn');

        try {
            const resp = await fetch('api/sms-send-test.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ to: to, message: message, grant_consent: grant })
            });

            const data = await resp.json().catch(() => ({}));
            if (resp.ok && data && data.success) {
                const status = (data.telnyx && data.telnyx.status) ? data.telnyx.status : 'queued';
                setResult('Sent (status: ' + status + ')', 'ok');
            } else {
                let msg = (data && (data.error || data.hint)) ? (data.error || data.hint) : ('Send failed (HTTP ' + resp.status + ')');
                const parts = [];
                if (data && data.http_code) parts.push('Telnyx HTTP ' + data.http_code);
                if (data && data.code) parts.push('code ' + data.code);
                if (data && data.detail) parts.push(String(data.detail));
                if (data && data.key_fingerprint) {
                    const kf = data.key_fingerprint;
                    if (typeof kf.length === 'number') parts.push('key len ' + kf.length);
                    if (typeof kf.starts_with_KEY === 'boolean') parts.push('starts KEY ' + (kf.starts_with_KEY ? 'yes' : 'no'));
                    if (typeof kf.matches_KEY_plus_32hex === 'boolean') parts.push('KEY+32hex ' + (kf.matches_KEY_plus_32hex ? 'yes' : 'no'));
                }
                if (parts.length) msg += ' — ' + parts.join(' — ');
                setResult(msg, 'bad');
            }
        } catch (e) {
            setResult('Network error sending SMS.', 'bad');
        } finally {
            btn.disabled = false;
        }
    });
})();

(function () {
    const btn = document.getElementById('smsPollNowBtn');
    const elResult = document.getElementById('smsPollNowResult');
    const elDetails = document.getElementById('smsPollNowDetails');
    if (!btn) return;

    function setResult(text, kind) {
        elResult.textContent = text;
        elResult.style.color = kind === 'ok' ? 'var(--success)' : (kind === 'warn' ? 'var(--warning)' : 'var(--danger)');
    }

    function setDetails(html) {
        elDetails.innerHTML = html;
        elDetails.style.display = html ? 'block' : 'none';
    }

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        setResult('Polling…', 'warn');
        setDetails('');

        try {
            const resp = await fetch('api/sms-webhook-poll.php', { method: 'GET' });
            const data = await resp.json().catch(() => ({}));

            if (resp.ok && data && data.success) {
                const c = data.counts || {};
                const marked = typeof data.marked_processed !== 'undefined' ? data.marked_processed : '?';
                setResult('Done — processed ' + (c.total ?? 0) + ', marked ' + marked, 'ok');
                setDetails(
                    '<div class="smsset-mono">'
                    + 'total=' + (c.total ?? 0)
                    + ' opt_out=' + (c.opt_out ?? 0)
                    + ' help=' + (c.help ?? 0)
                    + ' ignored=' + (c.ignored ?? 0)
                    + ' errors=' + (c.errors ?? 0)
                    + '</div>'
                );
            } else {
                const msg = (data && data.error) ? data.error : ('Poll failed (HTTP ' + resp.status + ')');
                setResult(msg, 'bad');
                if (data && (data.http_status || data.raw)) {
                    const extra = [];
                    if (data.http_status) extra.push('proxy_http=' + data.http_status);
                    if (data.raw) extra.push('proxy_raw=' + String(data.raw));
                    if (extra.length) setDetails('<div class="smsset-mono">' + extra.join(' ') + '</div>');
                }
            }
        } catch (e) {
            setResult('Network error polling webhooks.', 'bad');
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>
