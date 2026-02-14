<?php
/**
 * Change Orders — Mid-workflow scope changes, cost impact, approval gate
 * Parent: Work Order
 */
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$woId = isset($_GET['wo_id']) ? intval($_GET['wo_id']) : null;

// Load CO for view
$co = null; $coLineItems = []; $woData = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT co.*, wo.work_order_id as wo_doc_id, wo.service_request_id, wo.authorized_total, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.customer_phone FROM change_orders co LEFT JOIN work_orders wo ON co.work_order_id = wo.id LEFT JOIN technicians t ON co.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE co.id = ?");
    $stmt->execute([$id]);
    $co = $stmt->fetch();
    if ($co) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type='change_order' AND document_id=? ORDER BY line_number");
        $liStmt->execute([$id]);
        $coLineItems = $liStmt->fetchAll();
    }
}

// Load WO data for creating CO
if ($woId && !$co) {
    $stmt = $pdo->prepare("SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name FROM work_orders wo LEFT JOIN technicians t ON wo.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE wo.id = ?");
    $stmt->execute([$woId]);
    $woData = $stmt->fetch();
}

// List
$changeOrders = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND co.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT co.*, wo.work_order_id as wo_doc_id, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name FROM change_orders co LEFT JOIN work_orders wo ON co.work_order_id = wo.id LEFT JOIN technicians t ON co.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE {$where} ORDER BY co.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $changeOrders = $stmt->fetchAll();
}
$technicians = $pdo->query("SELECT id, first_name, last_name FROM technicians ORDER BY first_name")->fetchAll();
?>

<link rel="stylesheet" href="assets/css/pages/change-orders.css">

<?php if ($action === 'list'): ?>
    <?php include __DIR__ . '/change-orders/list.php'; ?>
<?php elseif ($action === 'create' && $woData): ?>
    <?php include __DIR__ . '/change-orders/create.php'; ?>
<?php elseif ($action === 'view' && $co): ?>
    <?php include __DIR__ . '/change-orders/view.php'; ?>
<?php endif; ?>

<script src="assets/js/pages/change-orders.js"></script>
