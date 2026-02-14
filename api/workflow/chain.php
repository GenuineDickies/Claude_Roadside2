<?php
// Chain, audit, catalog, and stats handlers — included by api/workflow.php
switch ($action) {

case 'get_chain':
    $srId = intval($_GET['service_request_id'] ?? 0);
    if (!$srId) { echo json_encode(['success' => false]); break; }
    echo json_encode(['success' => true, 'data' => get_document_chain($pdo, $srId)]);
    break;

case 'get_audit_log':
    $docType = $_GET['document_type'] ?? '';
    $docId = intval($_GET['document_id'] ?? 0);
    if (!$docType || !$docId) { echo json_encode(['success' => false]); break; }
    $stmt = $pdo->prepare("SELECT dal.*, u.username FROM document_audit_log dal LEFT JOIN users u ON dal.performed_by = u.id WHERE dal.document_type = ? AND dal.document_id = ? ORDER BY dal.performed_at DESC LIMIT 100");
    $stmt->execute([$docType, $docId]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

case 'search_parts':
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        $stmt = $pdo->query("SELECT * FROM parts_inventory WHERE active = 1 ORDER BY category, name LIMIT 50");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;
    }
    $stmt = $pdo->prepare("SELECT * FROM parts_inventory WHERE active = 1 AND (name LIKE ? OR part_number LIKE ? OR category LIKE ?) ORDER BY name LIMIT 20");
    $s = "%{$q}%";
    $stmt->execute([$s, $s, $s]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

case 'get_rate_schedules':
    $stmt = $pdo->query("SELECT * FROM rate_schedules WHERE active = 1 ORDER BY type");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

case 'workflow_stats':
    $stats = [];
    $stats['estimates'] = [
        'draft' => $pdo->query("SELECT COUNT(*) FROM estimates WHERE status='draft'")->fetchColumn(),
        'presented' => $pdo->query("SELECT COUNT(*) FROM estimates WHERE status='presented'")->fetchColumn(),
        'approved' => $pdo->query("SELECT COUNT(*) FROM estimates WHERE status='approved'")->fetchColumn(),
    ];
    $stats['work_orders'] = [
        'active' => $pdo->query("SELECT COUNT(*) FROM work_orders WHERE status IN ('created','in_progress','paused')")->fetchColumn(),
        'completed' => $pdo->query("SELECT COUNT(*) FROM work_orders WHERE status='completed'")->fetchColumn(),
    ];
    $stats['invoices'] = [
        'outstanding' => $pdo->query("SELECT COUNT(*) FROM invoices_v2 WHERE status IN ('generated','sent','viewed','partial')")->fetchColumn(),
        'overdue' => $pdo->query("SELECT COUNT(*) FROM invoices_v2 WHERE status='overdue'")->fetchColumn(),
        'total_receivable' => $pdo->query("SELECT COALESCE(SUM(balance_due),0) FROM invoices_v2 WHERE status IN ('generated','sent','viewed','partial','overdue')")->fetchColumn(),
    ];
    $stats['change_orders'] = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM change_orders WHERE status IN ('proposed','presented')")->fetchColumn(),
    ];
    echo json_encode(['success' => true, 'data' => $stats]);
    break;

}
