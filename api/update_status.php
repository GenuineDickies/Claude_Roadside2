<?php
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['status']) || !isset($input['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id = $input['id'];
$status = $input['status'];
$type = $input['type'];

try {
    if ($type === 'service_request') {
        // Validate status
        $validStatuses = ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }
        
        $stmt = $pdo->prepare("UPDATE service_requests SET status = ? WHERE id = ?");
        $result = $stmt->execute([$status, $id]);
        
        if ($result) {
            // If completing a request, update technician status
            if ($status === 'completed') {
                $pdo->prepare("UPDATE technicians SET status = 'available' WHERE id = (SELECT technician_id FROM service_requests WHERE id = ?)")->execute([$id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            throw new Exception('Failed to update status');
        }
    } else {
        throw new Exception('Invalid type');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
