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

if (!$input || !isset($input['request_id']) || !isset($input['technician_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$requestId = $input['request_id'];
$technicianId = $input['technician_id'];

try {
    // Check if technician is available
    $stmt = $pdo->prepare("SELECT status FROM technicians WHERE id = ?");
    $stmt->execute([$technicianId]);
    $technician = $stmt->fetch();
    
    if (!$technician) {
        throw new Exception('Technician not found');
    }
    
    if ($technician['status'] !== 'available') {
        throw new Exception('Technician is not available');
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Assign technician to service request (does NOT dispatch or change tech status)
    $stmt = $pdo->prepare("UPDATE service_requests SET technician_id = ?, status = 'assigned' WHERE id = ?");
    $result = $stmt->execute([$technicianId, $requestId]);
    
    if ($result) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Technician assigned successfully']);
    } else {
        $pdo->rollBack();
        throw new Exception('Failed to assign technician');
    }
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
