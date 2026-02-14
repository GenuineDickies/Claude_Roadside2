<?php
/**
 * RoadRunner Workflow API — Router
 * Handles the full document chain: SR → EST → WO → CO → INV → RCT
 *
 * Dispatches to handler files in api/workflow/ based on action.
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';
require_once __DIR__ . '/../includes/id_helpers.php';
require_once __DIR__ . '/../config/workflow_schema.php';

bootstrap_workflow_schema($pdo);

require_once __DIR__ . '/workflow/helpers.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$estimateActions = ['create_estimate', 'get_estimate', 'update_estimate_status', 'add_estimate_line_item', 'delete_line_item', 'list_estimates'];
$woActions = ['create_work_order', 'get_work_order', 'update_work_order_status', 'wo_progress_log', 'save_wo_diagnosis', 'wo_customer_signoff', 'list_work_orders'];
$coActions = ['create_change_order', 'update_change_order_status', 'get_change_order'];
$invActions = ['create_invoice', 'get_invoice', 'update_invoice_status', 'record_payment', 'apply_discount', 'list_invoices', 'get_receipt', 'deliver_receipt', 'list_receipts'];
$chainActions = ['get_chain', 'get_audit_log', 'search_parts', 'get_rate_schedules', 'workflow_stats'];

if (in_array($action, $estimateActions, true)) {
    require __DIR__ . '/workflow/estimates.php';
} elseif (in_array($action, $woActions, true)) {
    require __DIR__ . '/workflow/work_orders.php';
} elseif (in_array($action, $coActions, true)) {
    require __DIR__ . '/workflow/change_orders.php';
} elseif (in_array($action, $invActions, true)) {
    require __DIR__ . '/workflow/invoices.php';
} elseif (in_array($action, $chainActions, true)) {
    require __DIR__ . '/workflow/chain.php';
} else {
    echo json_encode(['success' => false, 'error' => "Unknown action: {$action}"]);
}
