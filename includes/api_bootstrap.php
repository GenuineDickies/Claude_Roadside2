<?php
/**
 * Shared API bootstrap — session, auth check, JSON headers, DB connection
 * Provides: $pdo, $userId, json_response()
 */
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * Send JSON response and exit
 */
function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'error' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];
