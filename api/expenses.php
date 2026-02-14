<?php
/**
 * Expenses API — Router
 * Track all business expenses with categories, budgets, and reporting.
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';
require_once __DIR__ . '/../config/expenses_schema.php';

bootstrap_expenses_schema($pdo);

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

$crudActions = ['categories', 'list', 'create', 'update', 'delete', 'check_recurring'];
$reportActions = ['summary', 'dashboard'];

try {
    if (in_array($action, $crudActions, true)) {
        require __DIR__ . '/expenses/crud.php';
    } elseif (in_array($action, $reportActions, true)) {
        require __DIR__ . '/expenses/reports.php';
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
