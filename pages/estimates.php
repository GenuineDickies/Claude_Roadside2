<?php
/**
 * Estimate Builder — Create/View/Manage Estimates
 * Parent: Service Request. Line items from catalog. Tax calc. Approval workflow.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$srId = isset($_GET['sr_id']) ? intval($_GET['sr_id']) : null;

// Get service request data if creating from SR
$srData = null;
if ($srId) {
    $stmt = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
    $stmt->execute([$srId]);
    $srData = $stmt->fetch();
}

$copyFromId = isset($_GET['copy_from']) ? intval($_GET['copy_from']) : null;
$prefillEstimate = null;
$prefillLineItems = [];
$prefillLineItemsForJs = [];
$initialEstimateData = null;

if ($copyFromId) {
    $copyStmt = $pdo->prepare("SELECT * FROM estimates WHERE id = ?");
    $copyStmt->execute([$copyFromId]);
    $prefillEstimate = $copyStmt->fetch();

    if ($prefillEstimate) {
        if (!$srId) {
            $srId = intval($prefillEstimate['service_request_id']);
        }
        if (!$srData || intval($srData['id'] ?? 0) !== intval($prefillEstimate['service_request_id'])) {
            $stmt = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
            $stmt->execute([$prefillEstimate['service_request_id']]);
            $srData = $stmt->fetch();
        }

        $liStmt = $pdo->prepare("SELECT line_number, item_type, description, quantity, unit_price, markup_pct, extended_price, taxable, notes FROM document_line_items WHERE document_type = 'estimate' AND document_id = ? ORDER BY line_number");
        $liStmt->execute([$copyFromId]);
        $prefillLineItems = $liStmt->fetchAll();

        foreach ($prefillLineItems as $li) {
            $prefillLineItemsForJs[] = [
                'line_number' => (int)$li['line_number'],
                'item_type' => $li['item_type'],
                'description' => $li['description'],
                'quantity' => isset($li['quantity']) ? (float)$li['quantity'] : 0,
                'unit_price' => isset($li['unit_price']) ? (float)$li['unit_price'] : 0,
                'markup_pct' => $li['markup_pct'] !== null ? (float)$li['markup_pct'] : null,
                'extended_price' => isset($li['extended_price']) ? (float)$li['extended_price'] : 0,
                'taxable' => (int)$li['taxable'],
                'notes' => $li['notes'],
            ];
        }

        $initialEstimateData = [
            'id' => (int)$prefillEstimate['id'],
            'estimate_id' => $prefillEstimate['estimate_id'],
            'version' => (int)$prefillEstimate['version'],
            'technician_id' => $prefillEstimate['technician_id'] !== null ? (int)$prefillEstimate['technician_id'] : null,
            'tax_rate' => isset($prefillEstimate['tax_rate']) ? (float)$prefillEstimate['tax_rate'] : null,
            'internal_notes' => $prefillEstimate['internal_notes'] ?? null,
        ];
    }
}

if (!$srData && $srId) {
    $stmt = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
    $stmt->execute([$srId]);
    $srData = $stmt->fetch();
}

$nextVersion = 1;
if ($srId) {
    $verStmt = $pdo->prepare("SELECT COALESCE(MAX(version), 0) FROM estimates WHERE service_request_id = ?");
    $verStmt->execute([$srId]);
    $nextVersion = intval($verStmt->fetchColumn()) + 1;
    if ($nextVersion < 1) { $nextVersion = 1; }
}

// Get estimate data if viewing/editing
$estimate = null;
$lineItems = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT e.*, t.first_name as tech_first, t.last_name as tech_last FROM estimates e LEFT JOIN technicians t ON e.technician_id = t.id WHERE e.id = ?");
    $stmt->execute([$id]);
    $estimate = $stmt->fetch();
    if ($estimate) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type = 'estimate' AND document_id = ? ORDER BY line_number");
        $liStmt->execute([$id]);
        $lineItems = $liStmt->fetchAll();
        if (!$srData) {
            $stmt2 = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
            $stmt2->execute([$estimate['service_request_id']]);
            $srData = $stmt2->fetch();
        }
    }
}

// List all estimates
$estimates = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND e.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT e.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.service_category FROM estimates e LEFT JOIN technicians t ON e.technician_id = t.id LEFT JOIN service_tickets st ON e.service_request_id = st.id WHERE {$where} ORDER BY e.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $estimates = $stmt->fetchAll();
}

$technicians = $pdo->query("SELECT id, first_name, last_name, specialization FROM technicians ORDER BY first_name")->fetchAll();
?>

<link rel="stylesheet" href="assets/css/pages/estimates.css">

<?php if ($action === 'list'): ?>
    <?php include __DIR__ . '/estimates/list.php'; ?>
<?php elseif ($action === 'create' && $srData): ?>
    <?php include __DIR__ . '/estimates/create.php'; ?>
<?php elseif ($action === 'view' && $estimate): ?>
    <?php include __DIR__ . '/estimates/view.php'; ?>
<?php endif; ?>

<script src="assets/js/pages/estimates.js"></script>
<script src="assets/js/pages/estimates-catalog.js"></script>
