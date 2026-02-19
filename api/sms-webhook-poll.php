<?php
/**
 * SMS Webhook Poller
 *
 * Polls a remote Telnyx webhook proxy queue (e.g. SiteGround), processes inbound opt-out keywords,
 * and updates local sms_consent + sms_consent_events.
 *
 * Auth:
 * - If a user session exists, allowed.
 * - Otherwise require ?key=... to match setting sms_webhook_local_key.
 */

$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/intake_schema.php';

bootstrap_intake_schema($pdo);

function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function phone_to_digits($raw) {
    $digits = preg_replace('/\D/', '', (string)$raw);
    if (strlen($digits) === 11 && strpos($digits, '1') === 0) {
        return substr($digits, 1);
    }
    return $digits;
}

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return null;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}

function safe_url_for_logs($url) {
    $url = (string)$url;
    $parts = parse_url($url);
    if (!is_array($parts)) return $url;

    $scheme = $parts['scheme'] ?? null;
    $host = $parts['host'] ?? null;
    $port = isset($parts['port']) ? (':' . $parts['port']) : '';
    $path = $parts['path'] ?? '';

    $query = '';
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $qs);
        foreach (['api_key', 'key', 'token', 'secret'] as $k) {
            if (isset($qs[$k])) $qs[$k] = '***';
        }
        $query = http_build_query($qs);
    }

    $out = '';
    if ($scheme && $host) {
        $out = $scheme . '://' . $host . $port . $path;
    } else {
        $out = $path;
    }
    if ($query !== '') $out .= '?' . $query;
    return $out;
}

function curl_request_json($method, $url, $payload = null, $headers = []) {
    $method = strtoupper((string)$method);
    $headerLines = [];

    // Avoid upstream caching of JSON endpoints.
    $headers['Cache-Control'] = $headers['Cache-Control'] ?? 'no-cache';
    $headers['Pragma'] = $headers['Pragma'] ?? 'no-cache';
    $headers['Accept'] = $headers['Accept'] ?? 'application/json';
    foreach ((array)$headers as $k => $v) {
        $headerLines[] = $k . ': ' . $v;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
    curl_setopt($ch, CURLOPT_USERAGENT, 'RoadRunnerAdmin/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    if ($method === 'POST') {
        $body = json_encode($payload);
        $headerLines[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        $msg = $err !== '' ? $err : 'Unknown transport error';
        return [null, 0, 'transport_error: ' . substr($msg, 0, 500) . ' (errno=' . (int)$errno . ')'];
    }

    $json = json_decode((string)$raw, true);
    return [$json, $status, $raw];
}

function http_get_json($url, $headers = []) {
    return curl_request_json('GET', $url, null, $headers);
}

function http_post_json($url, $payload, $headers = []) {
    return curl_request_json('POST', $url, $payload, $headers);
}

function proxy_base_from_url($url) {
    $url = trim((string)$url);
    if ($url === '') return '';

    // If the stored URL accidentally includes query/fragment, strip it for PATH_INFO routing.
    $qPos = strpos($url, '?');
    if ($qPos !== false) {
        $url = substr($url, 0, $qPos);
    }
    $hPos = strpos($url, '#');
    if ($hPos !== false) {
        $url = substr($url, 0, $hPos);
    }

    $url = rtrim($url, '/');
    // If an operator accidentally saved a specific route (/poll, /status, /mark-processed), normalize.
    foreach (['/poll', '/status', '/mark-processed'] as $suffix) {
        if (substr($url, -strlen($suffix)) === $suffix) {
            $url = substr($url, 0, -strlen($suffix));
            $url = rtrim($url, '/');
            break;
        }
    }

    return $url;
}

function extract_telnyx_inbound($payload) {
    if (!is_array($payload)) return [null, null, null];

    $eventType = $payload['data']['event_type'] ?? $payload['event_type'] ?? null;

    // Common Telnyx webhook wrapper
    $p = $payload['data']['payload'] ?? $payload['payload'] ?? null;
    $p2 = is_array($p) ? $p : [];

    $text = null;
    if (isset($p2['text'])) $text = $p2['text'];
    elseif (isset($p2['body'])) $text = $p2['body'];
    elseif (isset($p2['content'])) $text = $p2['content'];
    elseif (isset($p2['message']['text'])) $text = $p2['message']['text'];
    elseif (isset($p2['message']['body'])) $text = $p2['message']['body'];
    elseif (isset($payload['data']['text'])) $text = $payload['data']['text'];

    $from = null;
    if (isset($p2['from']['phone_number'])) $from = $p2['from']['phone_number'];
    elseif (isset($p2['from']['address'])) $from = $p2['from']['address'];
    elseif (isset($p2['from']['phone'])) $from = $p2['from']['phone'];
    elseif (isset($p2['from_number'])) $from = $p2['from_number'];
    elseif (isset($p2['from'])) $from = $p2['from'];
    elseif (isset($payload['data']['from'])) $from = $payload['data']['from'];

    return [$eventType, $text, $from];
}

function is_opt_out_text($text) {
    $t = strtoupper(trim((string)$text));
    if ($t === '') return false;
    $t = preg_replace('/\s+/', ' ', $t);
    $keywords = ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'];
    foreach ($keywords as $kw) {
        if ($t === $kw) return true;
        if (strpos($t, $kw . ' ') === 0) return true;
    }
    return false;
}

function is_help_text($text) {
    $t = strtoupper(trim((string)$text));
    return $t === 'HELP' || strpos($t, 'HELP ') === 0;
}

function is_opt_in_text($text) {
    $t = strtoupper(trim((string)$text));
    if ($t === '') return false;
    $t = preg_replace('/\s+/', ' ', $t);
    $keywords = ['START', 'UNSTOP'];
    foreach ($keywords as $kw) {
        if ($t === $kw) return true;
        if (strpos($t, $kw . ' ') === 0) return true;
    }
    return false;
}

// Auth
$hasSession = isset($_SESSION['user_id']);
$localKey = get_setting($pdo, 'sms_webhook_local_key', '');
$providedKey = (string)($_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '');
if (!$hasSession) {
    if ($localKey === '' || !hash_equals($localKey, $providedKey)) {
        json_out(['success' => false, 'error' => 'Unauthorized'], 401);
    }
}

$proxyUrl = get_setting($pdo, 'sms_webhook_proxy_url', '');
$proxyPollKey = get_setting($pdo, 'sms_webhook_proxy_poll_key', '');

if ($proxyUrl === '' || $proxyPollKey === '') {
    json_out(['success' => false, 'error' => 'Missing proxy settings (sms_webhook_proxy_url / sms_webhook_proxy_poll_key).'], 400);
}

// Prefer PATH_INFO endpoints and send key via header (avoids WAF blocking api_key querystring).
$proxyBase = proxy_base_from_url($proxyUrl);
$cacheBust = (string)time();
$pollUrl = $proxyBase . '/poll?t=' . rawurlencode($cacheBust);
$pollHeaders = ['X-API-KEY' => $proxyPollKey];

list($pollJson, $status, $raw) = http_get_json($pollUrl, $pollHeaders);

// Fallback (only if not forbidden) for proxies that require query-style routing.
if (($status === 404 || $status === 405) || (!is_array($pollJson))) {
    if ($status !== 403) {
        $fallbackPollUrl = $proxyBase . (strpos($proxyBase, '?') !== false ? '&' : '?') . http_build_query([
            'action' => 'poll',
            't' => $cacheBust,
        ]);
        list($pollJson2, $status2, $raw2) = http_get_json($fallbackPollUrl, $pollHeaders);
        if (is_array($pollJson2)) {
            $pollJson = $pollJson2;
            $status = $status2;
            $raw = $raw2;
            $pollUrl = $fallbackPollUrl;
        }
    }
}
if (!is_array($pollJson) || !isset($pollJson['webhooks']) || !is_array($pollJson['webhooks'])) {
    json_out([
        'success' => false,
        'error' => 'Invalid poll response from proxy',
        'http_status' => $status,
        'proxy_url' => safe_url_for_logs($pollUrl),
        'raw' => is_string($raw) ? substr($raw, 0, 500) : null
    ], 502);
}

$processedIds = [];
$counts = [
    'total' => 0,
    'opt_out' => 0,
    'help' => 0,
    'ignored' => 0,
    'errors' => 0,
];

$debug = !empty($_GET['debug']);
$debugSamples = [];

$pollDebug = null;
$markDebug = null;
if ($debug) {
    $pollDebug = [
        'proxy_url_setting' => $proxyUrl,
        'poll_url' => safe_url_for_logs($pollUrl),
        'poll_http_status' => $status,
    ];
}

if ($debug && is_array($pollJson) && isset($pollJson['webhooks']) && is_array($pollJson['webhooks'])) {
    $ids = [];
    foreach ($pollJson['webhooks'] as $whRow) {
        if (isset($whRow['id'])) $ids[] = (int)$whRow['id'];
        if (count($ids) >= 5) break;
    }
    $pollDebug['proxy_count'] = isset($pollJson['count']) ? (int)$pollJson['count'] : count($pollJson['webhooks']);
    $pollDebug['first_ids'] = $ids;
}

foreach ($pollJson['webhooks'] as $wh) {
    $counts['total']++;
    $id = $wh['id'] ?? null;
    $payload = $wh['payload'] ?? null;
    if (!$id || !is_array($payload)) {
        $counts['errors']++;
        continue;
    }

    try {
        list($eventType, $text, $from) = extract_telnyx_inbound($payload);
        $fromDigits = phone_to_digits($from);

        if ($fromDigits === '' || $text === null) {
            $counts['ignored']++;
            if ($debug && count($debugSamples) < 3) {
                $debugSamples[] = [
                    'id' => $id,
                    'reason' => 'missing_from_or_text',
                    'event_type' => $eventType,
                    'from' => $from,
                    'from_digits' => $fromDigits,
                    'text' => $text,
                    'payload_keys' => is_array($payload) ? array_keys($payload) : null,
                    'payload_preview' => substr(json_encode($payload), 0, 500),
                ];
            }
            $processedIds[] = $id;
            continue;
        }

        // Ensure row exists
        $pdo->prepare("INSERT IGNORE INTO sms_consent (phone_digits, last_source, last_seen_at) VALUES (?, 'telnyx_webhook', NOW())")
            ->execute([$fromDigits]);

        $pdo->prepare("UPDATE sms_consent SET last_source='telnyx_webhook', last_seen_at=NOW() WHERE phone_digits=?")
            ->execute([$fromDigits]);

        // Fetch current state for correct event logging
        $stmtState = $pdo->prepare("SELECT consent, opted_out FROM sms_consent WHERE phone_digits=? LIMIT 1");
        $stmtState->execute([$fromDigits]);
        $stateRow = $stmtState->fetch();
        $currentConsent = isset($stateRow['consent']) ? (int)$stateRow['consent'] : 0;
        $currentOptedOut = isset($stateRow['opted_out']) ? (int)$stateRow['opted_out'] : 0;

        if (is_opt_out_text($text)) {
            $pdo->prepare("UPDATE sms_consent SET opted_out=1, opt_out_at=NOW(), last_source='telnyx_webhook', last_seen_at=NOW() WHERE phone_digits=?")
                ->execute([$fromDigits]);

            $meta = json_encode([
                'webhook_id' => $id,
                'telnyx_event_type' => $eventType,
                'text' => $text
            ]);
            $pdo->prepare("INSERT INTO sms_consent_events (phone_digits, event_type, source, meta) VALUES (?, 'opt_out_received', 'telnyx_webhook', ?)")
                ->execute([$fromDigits, $meta]);

            $counts['opt_out']++;
        } elseif (is_opt_in_text($text)) {
            // Explicit opt-in via START/UNSTOP: grant consent and clear opt-out.
            $pdo->prepare("UPDATE sms_consent SET consent=1, consent_at=COALESCE(consent_at, NOW()), opted_out=0, opt_out_at=NULL, last_source='telnyx_webhook', last_seen_at=NOW() WHERE phone_digits=?")
                ->execute([$fromDigits]);

            if ($currentOptedOut === 1) {
                $meta = json_encode([
                    'webhook_id' => $id,
                    'telnyx_event_type' => $eventType,
                    'text' => $text
                ]);
                $pdo->prepare("INSERT INTO sms_consent_events (phone_digits, event_type, source, meta) VALUES (?, 'opt_out_cleared', 'telnyx_webhook', ?)")
                    ->execute([$fromDigits, $meta]);
            }

            if ($currentConsent === 0) {
                $meta = json_encode([
                    'webhook_id' => $id,
                    'telnyx_event_type' => $eventType,
                    'text' => $text
                ]);
                $pdo->prepare("INSERT INTO sms_consent_events (phone_digits, event_type, source, meta) VALUES (?, 'consent_granted', 'telnyx_webhook', ?)")
                    ->execute([$fromDigits, $meta]);
            }
        } elseif (is_help_text($text)) {
            $meta = json_encode([
                'webhook_id' => $id,
                'telnyx_event_type' => $eventType,
                'text' => $text
            ]);
            $pdo->prepare("INSERT INTO sms_consent_events (phone_digits, event_type, source, meta) VALUES (?, 'help_received', 'telnyx_webhook', ?)")
                ->execute([$fromDigits, $meta]);

            $counts['help']++;
        } else {
            $counts['ignored']++;
            if ($debug && count($debugSamples) < 3) {
                $debugSamples[] = [
                    'id' => $id,
                    'reason' => 'not_stop_or_help',
                    'event_type' => $eventType,
                    'from_digits' => $fromDigits,
                    'text' => $text,
                ];
            }
        }

        $processedIds[] = $id;
    } catch (Exception $e) {
        $counts['errors']++;
        error_log('sms-webhook-poll processing error: ' . $e->getMessage());
    }
}

// Mark processed
$marked = 0;
if (!empty($processedIds)) {
    $markUrl = $proxyBase . '/mark-processed?t=' . rawurlencode($cacheBust);
    list($markJson, $markStatus, $markRaw) = http_post_json($markUrl, ['ids' => $processedIds], ['X-API-KEY' => $proxyPollKey]);

    // Fallback only if not forbidden
    if ($markStatus !== 403 && (!is_array($markJson) || !isset($markJson['marked']))) {
        $fallbackMarkUrl = $proxyBase . (strpos($proxyBase, '?') !== false ? '&' : '?') . http_build_query([
            'action' => 'mark-processed',
            't' => $cacheBust,
        ]);
        list($markJson2, $markStatus2, $markRaw2) = http_post_json($fallbackMarkUrl, ['ids' => $processedIds], ['X-API-KEY' => $proxyPollKey]);
        if (is_array($markJson2)) {
            $markJson = $markJson2;
            $markStatus = $markStatus2;
            $markRaw = $markRaw2;
        }
    }

    if (is_array($markJson) && isset($markJson['marked'])) {
        $marked = intval($markJson['marked']);
    }

    if ($debug || $marked !== count($processedIds)) {
        $markDebug = [
            'mark_url' => safe_url_for_logs($markUrl),
            'mark_http_status' => $markStatus,
            'ids_sent' => count($processedIds),
            'marked' => $marked,
            'response_preview' => is_string($markRaw) ? substr($markRaw, 0, 300) : null,
        ];
    }
}

json_out([
    'success' => true,
    'counts' => $counts,
    'marked_processed' => $marked,
    'poll_debug' => $debug ? $pollDebug : null,
    'mark_debug' => ($debug || ($markDebug !== null)) ? $markDebug : null,
    'debug_samples' => $debug ? $debugSamples : null,
]);
