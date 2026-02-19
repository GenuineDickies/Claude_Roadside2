<?php
/**
 * Telnyx SMS Webhook Proxy (SiteGround)
 *
 * Upload to: /public_html/sms-webhook/
 * Webhook URL (Telnyx): https://YOURDOMAIN.com/sms-webhook/webhook.php
 *
 * Purpose:
 * - Receives Telnyx webhooks (unauthenticated POST)
 * - Stores payloads into a queue table
 * - Provides authenticated polling endpoints for the local app
 */

// ─────────────────────────────────────────────────────────────
// CONFIG: Fill these in on SiteGround
// ─────────────────────────────────────────────────────────────
// SECURITY NOTE:
// - Do NOT hardcode credentials into this file in git.
// - Put secrets into a sibling file named config.php (not committed) OR set env vars.
//
// Supported config sources (in order):
// 1) config.php (same folder) defining DB_HOST/DB_NAME/DB_USER/DB_PASS/POLL_API_KEY
// 2) Environment variables: SMS_PROXY_DB_HOST/NAME/USER/PASS and SMS_PROXY_POLL_API_KEY
// 3) Fallback placeholders below (will fail until configured)

$localConfigPath = __DIR__ . '/config.php';
if (is_file($localConfigPath)) {
    require_once $localConfigPath;
}

function env_or_null($key) {
    $v = getenv($key);
    if ($v === false) return null;
    $v = trim((string)$v);
    return $v === '' ? null : $v;
}

if (!defined('DB_HOST')) define('DB_HOST', env_or_null('SMS_PROXY_DB_HOST') ?? 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', env_or_null('SMS_PROXY_DB_NAME') ?? 'your_database_name');
if (!defined('DB_USER')) define('DB_USER', env_or_null('SMS_PROXY_DB_USER') ?? 'your_database_user');
if (!defined('DB_PASS')) define('DB_PASS', env_or_null('SMS_PROXY_DB_PASS') ?? 'your_database_password');

// Random 32+ character string. Used by /poll and /mark-processed.
if (!defined('POLL_API_KEY')) define('POLL_API_KEY', env_or_null('SMS_PROXY_POLL_API_KEY') ?? 'CHANGE_THIS_TO_A_RANDOM_STRING_32_CHARS');

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
// Support both PATH_INFO routing (/webhook.php/poll) and query param (?action=poll)
$path = $_SERVER['PATH_INFO'] ?? ($_GET['action'] ?? '');

function get_api_key_from_request() {
    $apiKey = (string)($_GET['api_key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? ''));
    if ($apiKey === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        // Support: Authorization: Bearer <key>
        if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', (string)$_SERVER['HTTP_AUTHORIZATION'], $m)) {
            $apiKey = trim($m[1]);
        }
    }
    return $apiKey;
}

function require_api_key() {
    $apiKey = get_api_key_from_request();
    if (!hash_equals(POLL_API_KEY, $apiKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
}

function is_debug_allowed() {
    if (empty($_GET['debug'])) return false;
    $apiKey = get_api_key_from_request();
    return $apiKey !== '' && hash_equals(POLL_API_KEY, $apiKey);
}

// We only want to expose diagnostic details when debug=1 AND the api key is correct.
$debugAllowed = is_debug_allowed();

// Some hosts strip PATH_INFO; fall back to action query param.
$path = (string)$path;

// SiteGround/cPanel-style UIs sometimes display the MySQL user as "username@localhost".
// PDO expects just the username when connecting to a local host.
$dbUser = DB_USER;
if (in_array(DB_HOST, ['localhost', '127.0.0.1'], true) && strpos($dbUser, '@') !== false) {
    $dbUser = preg_replace('/@.+$/', '', $dbUser);
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        $dbUser,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    $resp = ['error' => 'Database connection failed'];
    if ($debugAllowed) {
        $resp['debug'] = [
            'php_version' => PHP_VERSION,
            'db_host' => DB_HOST,
            'db_name' => DB_NAME,
            'db_user' => $dbUser,
            'path_info' => $_SERVER['PATH_INFO'] ?? null,
            'action' => $_GET['action'] ?? null,
            'error_code' => $e->getCode(),
            'error_message' => substr($e->getMessage(), 0, 300),
        ];
    }
    echo json_encode($resp);
    exit;
}

// Create queue table if needed
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sms_webhook_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payload JSON NOT NULL,
            received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed TINYINT(1) NOT NULL DEFAULT 0,
            processed_at DATETIME NULL,
            INDEX idx_processed (processed),
            INDEX idx_received (received_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database init failed']);
    exit;
}

// require_api_key() now defined above

// ─────────────────────────────────────────────────────────────
// Routes
// ─────────────────────────────────────────────────────────────

// Receive webhook from Telnyx
if ($method === 'POST' && ($path === '' || $path === '/')) {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO sms_webhook_queue (payload) VALUES (?)');
        $stmt->execute([json_encode($payload)]);
        echo json_encode(['status' => 'queued', 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Queue insert failed']);
    }
    exit;
}

// Poll: fetch unprocessed webhooks
if ($method === 'GET' && ($path === '/poll' || $path === 'poll')) {
    require_api_key();

    try {
        $stmt = $pdo->query('
            SELECT id, payload, received_at
            FROM sms_webhook_queue
            WHERE processed = 0
            ORDER BY received_at ASC
            LIMIT 50
        ');
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['payload'] = json_decode($row['payload'], true);
        }

        $out = ['webhooks' => $rows, 'count' => count($rows)];
        if ($debugAllowed) {
            $ids = [];
            foreach ($rows as $r) {
                if (isset($r['id'])) $ids[] = (int)$r['id'];
                if (count($ids) >= 10) break;
            }
            $out['debug'] = [
                'db_host' => DB_HOST,
                'db_name' => DB_NAME,
                'db_user' => $dbUser,
                'returned_ids' => $ids,
            ];
        }

        echo json_encode($out);
    } catch (PDOException $e) {
        http_response_code(500);
        $resp = ['error' => 'Poll query failed'];
        if ($debugAllowed) {
            $resp['debug'] = [
                'error_code' => $e->getCode(),
                'error_message' => substr($e->getMessage(), 0, 300),
            ];
        }
        echo json_encode($resp);
    }
    exit;
}

// Mark processed
if ($method === 'POST' && ($path === '/mark-processed' || $path === 'mark-processed')) {
    require_api_key();

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $ids = $data['ids'] ?? [];

    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['status' => 'ok', 'marked' => 0]);
        exit;
    }

    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if (empty($ids)) {
        echo json_encode(['status' => 'ok', 'marked' => 0]);
        exit;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE sms_webhook_queue SET processed=1, processed_at=NOW() WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        echo json_encode(['status' => 'ok', 'marked' => (int)$stmt->rowCount()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Mark processed failed']);
    }
    exit;
}

// Status/health check
if ($method === 'GET' && ($path === '/status' || $path === 'status' || $path === '')) {
    try {
        $pending = (int)$pdo->query('SELECT COUNT(*) FROM sms_webhook_queue WHERE processed = 0')->fetchColumn();
        $out = [
            'status' => 'ok',
            'pending_webhooks' => $pending,
            'timestamp' => date('c')
        ];
        if ($debugAllowed) {
            $total = (int)$pdo->query('SELECT COUNT(*) FROM sms_webhook_queue')->fetchColumn();
            $processed = (int)$pdo->query('SELECT COUNT(*) FROM sms_webhook_queue WHERE processed = 1')->fetchColumn();
            $newestId = $pdo->query('SELECT MAX(id) FROM sms_webhook_queue')->fetchColumn();
            $oldestPendingId = $pdo->query('SELECT MIN(id) FROM sms_webhook_queue WHERE processed = 0')->fetchColumn();
            $newestPendingId = $pdo->query('SELECT MAX(id) FROM sms_webhook_queue WHERE processed = 0')->fetchColumn();

            $out['debug'] = [
                'path_info' => $_SERVER['PATH_INFO'] ?? null,
                'action' => $_GET['action'] ?? null,
                'counts' => [
                    'total' => $total,
                    'processed' => $processed,
                    'pending' => $pending,
                ],
                'ids' => [
                    'newest_id' => $newestId !== false ? (int)$newestId : null,
                    'oldest_pending_id' => $oldestPendingId !== false ? (int)$oldestPendingId : null,
                    'newest_pending_id' => $newestPendingId !== false ? (int)$newestPendingId : null,
                ],
            ];
        }
        echo json_encode($out);
    } catch (PDOException $e) {
        http_response_code(500);
        $resp = ['error' => 'Status query failed'];
        if ($debugAllowed) {
            $resp['debug'] = [
                'error_code' => $e->getCode(),
                'error_message' => substr($e->getMessage(), 0, 300),
            ];
        }
        echo json_encode($resp);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
