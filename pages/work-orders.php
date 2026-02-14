<?php
/**
 * Work Order Tracker — Labor clock, progress log, line items, change orders, completion
 * Parent: Estimate (optional) → Service Request
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load WO for view/edit
$wo = null; $lineItems = []; $progressLog = []; $changeOrders = []; $srData = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.customer_phone, st.service_address, st.service_category, st.vehicle_year, st.vehicle_make, st.vehicle_model, st.issue_description, e.estimate_id as est_doc_id FROM work_orders wo LEFT JOIN technicians t ON wo.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id LEFT JOIN estimates e ON wo.estimate_id = e.id WHERE wo.id = ?");
    $stmt->execute([$id]);
    $wo = $stmt->fetch();
    if ($wo) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type='work_order' AND document_id=? ORDER BY line_number");
        $liStmt->execute([$id]);
        $lineItems = $liStmt->fetchAll();
        $logStmt = $pdo->prepare("SELECT wpl.*, t.first_name, t.last_name FROM work_order_progress_log wpl LEFT JOIN technicians t ON wpl.technician_id = t.id WHERE wpl.work_order_id = ? ORDER BY wpl.logged_at DESC LIMIT 50");
        $logStmt->execute([$id]);
        $progressLog = $logStmt->fetchAll();
        $coStmt = $pdo->prepare("SELECT * FROM change_orders WHERE work_order_id = ? ORDER BY sequence_num");
        $coStmt->execute([$id]);
        $changeOrders = $coStmt->fetchAll();
    }
}

// List
$workOrders = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND wo.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.service_category FROM work_orders wo LEFT JOIN technicians t ON wo.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE {$where} ORDER BY wo.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $workOrders = $stmt->fetchAll();
}
$technicians = $pdo->query("SELECT id, first_name, last_name, specialization FROM technicians ORDER BY first_name")->fetchAll();
?>

<link rel="stylesheet" href="assets/css/pages/work-orders.css">

<?php if ($action === 'list'): ?>
    <?php include __DIR__ . '/work-orders/list.php'; ?>
<?php elseif ($action === 'view' && $wo): ?>
    <?php include __DIR__ . '/work-orders/view.php'; ?>
<?php endif; ?>

<script src="assets/js/pages/work-orders.js"></script>
