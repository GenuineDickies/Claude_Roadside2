<?php
/**
 * Shared API bootstrap — session, auth check, JSON headers, DB connection
 * Provides: $pdo, $userId
 */
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
