<?php
/**
 * Invoice Generator (v2) — Workflow chain invoices
 * Auto-generated from completed WOs. Payment recording, discounts, delivery.
 */
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load invoice for view
$invoice = null; $lineItems = []; $payments = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT iv.*, wo.work_order_id as wo_doc_id, wo.technician_id, st.ticket_number, st.customer_name, st.customer_phone, st.customer_email, st.service_address, st.service_category, st.vehicle_year, st.vehicle_make, st.vehicle_model, t.first_name as tech_first, t.last_name as tech_last FROM invoices_v2 iv LEFT JOIN work_orders wo ON iv.work_order_id = wo.id LEFT JOIN service_tickets st ON iv.service_request_id = st.id LEFT JOIN technicians t ON wo.technician_id = t.id WHERE iv.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
    if ($invoice) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type='invoice' AND document_id=? ORDER BY line_number");
        $liStmt->execute([$id]);
        $lineItems = $liStmt->fetchAll();
        $ptStmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE invoice_id=? ORDER BY created_at");
        $ptStmt->execute([$id]);
        $payments = $ptStmt->fetchAll();
    }
}

// List
$invoices = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND iv.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT iv.*, st.ticket_number, st.customer_name, st.customer_phone FROM invoices_v2 iv LEFT JOIN service_tickets st ON iv.service_request_id = st.id WHERE {$where} ORDER BY iv.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
}
?>

<link rel="stylesheet" href="assets/css/pages/invoices-v2.css">

<?php if ($action === 'list'): ?>
    <?php include __DIR__ . '/invoices-v2/list.php'; ?>
<?php elseif ($action === 'view' && $invoice): ?>
    <?php include __DIR__ . '/invoices-v2/view.php'; ?>
<?php endif; ?>

<script src="assets/js/pages/invoices-v2.js"></script>
