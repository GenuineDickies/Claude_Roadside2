<?php
/**
 * Send Test SMS (Telnyx)
 *
 * POST JSON:
 * - to: string
 * - message: string
 * - grant_consent: bool (optional) - if true, sets consent=1 and clears opted_out for this number
 *
 * Auth: requires logged-in session.
 */

$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/intake_schema.php';
require_once __DIR__ . '/../includes/TelnyxSMS.php';

bootstrap_intake_schema($pdo);

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return null;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function phone_to_digits($raw) {
    $digits = preg_replace('/\D/', '', (string)$raw);
    if (strlen($digits) === 11 && strpos($digits, '1') === 0) {
        return substr($digits, 1);
    }
    return $digits;
}

if (!isset($_SESSION['user_id'])) {
    json_out(['success' => false, 'error' => 'Unauthorized'], 401);
}

$body = get_json_body();
if (!is_array($body)) {
    // allow form-encoded fallback
    $body = $_POST;
}

$to = trim((string)($body['to'] ?? ''));
$message = trim((string)($body['message'] ?? ''));
$grantConsent = !empty($body['grant_consent']);

if ($to === '' || $message === '') {
    json_out(['success' => false, 'error' => 'Missing to/message'], 400);
}

$telnyxApiKey = get_setting($pdo, 'telnyx_api_key', '');
$telnyxFrom = get_setting($pdo, 'telnyx_from_number', '');

$keyLooksBad = false;
if ($telnyxApiKey !== '') {
    if (preg_match('/\s/', $telnyxApiKey)) $keyLooksBad = true;
    if (stripos($telnyxApiKey, 'bearer ') === 0) $keyLooksBad = true;
    if (!preg_match('/^KEY[0-9A-Za-z_\-]{10,}$/', $telnyxApiKey)) $keyLooksBad = true;
}

$keyFingerprint = null;
if ($telnyxApiKey !== '') {
    $len = strlen($telnyxApiKey);
    $keyFingerprint = [
        'prefix' => substr($telnyxApiKey, 0, 3),
        'starts_with_KEY' => (strpos($telnyxApiKey, 'KEY') === 0),
        'length' => $len,
        'non_alnum_dash_underscore' => preg_match('/[^0-9A-Za-z_\-]/', $telnyxApiKey) ? true : false,
        'matches_KEY_plus_32hex' => ($len === 35 && preg_match('/^KEY[0-9A-Fa-f]{32}$/', $telnyxApiKey)) ? true : false,
    ];
}

if ($telnyxApiKey === '' || $telnyxFrom === '') {
    json_out([
        'success' => false,
        'error' => 'Missing Telnyx settings (telnyx_api_key / telnyx_from_number).'
    ], 400);
}

if ($keyLooksBad) {
    json_out([
        'success' => false,
        'error' => 'Telnyx API key in settings looks malformed. Re-save a valid Secret API Key (usually starts with KEY...) with no spaces/newlines.',
        'key_fingerprint' => $keyFingerprint,
    ], 400);
}

$digits = phone_to_digits($to);
if ($digits === '' || strlen($digits) < 10) {
    json_out(['success' => false, 'error' => 'Invalid phone number'], 400);
}

// Ensure consent row exists
$pdo->prepare("INSERT IGNORE INTO sms_consent (phone_digits, last_source, last_seen_at) VALUES (?, 'manual_test_sms', NOW())")
    ->execute([$digits]);

$stmt = $pdo->prepare("SELECT consent, opted_out FROM sms_consent WHERE phone_digits=? LIMIT 1");
$stmt->execute([$digits]);
$row = $stmt->fetch();
$consent = isset($row['consent']) ? (int)$row['consent'] : 0;
$optedOut = isset($row['opted_out']) ? (int)$row['opted_out'] : 0;

$consentChanged = false;
$optOutCleared = false;

if ($grantConsent) {
    // Consent can only go 0->1; also clear opted_out to allow sending after a fresh re-consent.
    if ($consent === 0 || $optedOut === 1) {
        $pdo->prepare("UPDATE sms_consent SET consent = 1, consent_at = COALESCE(consent_at, NOW()), opted_out = 0, opt_out_at = NULL, last_source='manual_test_sms', last_seen_at=NOW() WHERE phone_digits=?")
            ->execute([$digits]);
        $consentChanged = ($consent === 0);
        $optOutCleared = ($optedOut === 1);
        $consent = 1;
        $optedOut = 0;
    }

    if ($consentChanged) {
        $meta = json_encode(['reason' => 'manual_test_sms']);
        $pdo->prepare("INSERT INTO sms_consent_events (phone_digits, event_type, source, user_id, meta) VALUES (?, 'consent_granted', 'manual_test_sms', ?, ?)")
            ->execute([$digits, (int)$_SESSION['user_id'], $meta]);
    }

    if ($optOutCleared) {
        $meta = json_encode(['reason' => 'manual_test_sms']);
        $pdo->prepare("INSERT INTO sms_consent_events (phone_digits, event_type, source, user_id, meta) VALUES (?, 'opt_out_cleared', 'manual_test_sms', ?, ?)")
            ->execute([$digits, (int)$_SESSION['user_id'], $meta]);
    }
}

if ($consent !== 1 || $optedOut === 1) {
    json_out([
        'success' => false,
        'error' => 'SMS not permitted for this phone (requires consent=1 and opted_out=0).',
        'consent' => $consent,
        'opted_out' => $optedOut,
        'hint' => 'Check “Grant consent (0→1) + send” for a one-time test if you have permission.'
    ], 403);
}

try {
    $sms = new TelnyxSMS([
        'api_key' => $telnyxApiKey,
        'from_number' => $telnyxFrom,
    ]);

    $result = $sms->send($to, $message);

    $meta = json_encode([
        'to' => $to,
        'from' => $telnyxFrom,
        'telnyx' => $result,
    ]);
    $pdo->prepare("INSERT INTO sms_consent_events (phone_digits, event_type, source, user_id, meta) VALUES (?, 'test_sms_sent', 'manual_test_sms', ?, ?)")
        ->execute([$digits, (int)$_SESSION['user_id'], $meta]);

    if (!empty($result['success'])) {
        json_out([
            'success' => true,
            'to_digits' => $digits,
            'telnyx' => $result,
        ]);
    }

    json_out([
        'success' => false,
        'to_digits' => $digits,
        'error' => $result['error'] ?? 'Failed to send',
        'detail' => $result['detail'] ?? null,
        'code' => $result['code'] ?? null,
        'http_code' => $result['http_code'] ?? null,
        'key_fingerprint' => $keyFingerprint,
        'next_steps' => [
            'Confirm telnyx_api_key is a Telnyx Secret API Key (often starts with KEY...)',
            'Re-paste the key in System → SMS Settings (no extra spaces) and Save',
            'Confirm telnyx_from_number is E.164 (e.g. +15035551234) and belongs to your Telnyx account'
        ],
        'telnyx' => $result,
    ], 502);
} catch (Exception $e) {
    error_log('sms-send-test error: ' . $e->getMessage());
    json_out(['success' => false, 'error' => 'Server error sending SMS'], 500);
}
