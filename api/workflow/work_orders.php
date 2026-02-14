<?php
// Work Order action handlers — included by api/workflow.php
switch ($action) {

case 'create_work_order':
    $srId = intval($_POST['service_request_id'] ?? 0);
    $estId = !empty($_POST['estimate_id']) ? intval($_POST['estimate_id']) : null;
    $techId = intval($_POST['technician_id'] ?? 0);

    if (!$srId || !$techId) {
        echo json_encode(['success' => false, 'error' => 'service_request_id and technician_id required']);
        break;
    }

    $authorizedTotal = 0;
    if ($estId) {
        $stmt = $pdo->prepare("SELECT * FROM estimates WHERE id = ? AND status = 'approved'");
        $stmt->execute([$estId]);
        $est = $stmt->fetch();
        if (!$est) {
            echo json_encode(['success' => false, 'error' => 'Estimate not found or not approved']);
            break;
        }
        $authorizedTotal = $est['total'];
    } else {
        $authorizedTotal = floatval($_POST['authorized_total'] ?? 0);
    }

    $woDocId = generate_doc_id($pdo, 'WO', 'work_orders', 'work_order_id');
    $stmt = $pdo->prepare("INSERT INTO work_orders (work_order_id, service_request_id, estimate_id, status, technician_id, authorized_total) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$woDocId, $srId, $estId, 'created', $techId, $authorizedTotal]);
    $woId = $pdo->lastInsertId();

    if ($estId) {
        copy_line_items($pdo, 'estimate', $estId, 'work_order', $woId);
    }
    $pdo->prepare("UPDATE service_tickets SET status = 'in_progress' WHERE id = ?")->execute([$srId]);

    audit_log($pdo, 'work_order', $woId, 'created', null, 'created', "WO {$woDocId} from SR #{$srId}");
    echo json_encode(['success' => true, 'data' => ['id' => $woId, 'work_order_id' => $woDocId]]);
    break;

case 'get_work_order':
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'ID required']); break; }
    $stmt = $pdo->prepare("
        SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last,
               st.ticket_number, st.customer_name, st.customer_phone, st.service_address, st.service_category,
               e.estimate_id as est_doc_id
        FROM work_orders wo
        LEFT JOIN technicians t ON wo.technician_id = t.id
        LEFT JOIN service_tickets st ON wo.service_request_id = st.id
        LEFT JOIN estimates e ON wo.estimate_id = e.id
        WHERE wo.id = ?
    ");
    $stmt->execute([$id]);
    $wo = $stmt->fetch();
    if (!$wo) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

    $wo['line_items'] = get_line_items($pdo, 'work_order', $id);

    $coStmt = $pdo->prepare("SELECT * FROM change_orders WHERE work_order_id = ? ORDER BY sequence_num");
    $coStmt->execute([$id]);
    $wo['change_orders'] = $coStmt->fetchAll();

    $logStmt = $pdo->prepare("SELECT wpl.*, t.first_name, t.last_name FROM work_order_progress_log wpl LEFT JOIN technicians t ON wpl.technician_id = t.id WHERE wpl.work_order_id = ? ORDER BY wpl.logged_at DESC LIMIT 50");
    $logStmt->execute([$id]);
    $wo['progress_log'] = $logStmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $wo]);
    break;

case 'update_work_order_status':
    $id = intval($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowed = ['created','in_progress','paused','completed','cancelled'];
    if (!$id || !in_array($newStatus, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid']); break;
    }

    $stmt = $pdo->prepare("SELECT * FROM work_orders WHERE id = ?");
    $stmt->execute([$id]);
    $wo = $stmt->fetch();
    if (!$wo) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

    $sql = "UPDATE work_orders SET status = ?";
    $params = [$newStatus];

    if ($newStatus === 'in_progress' && !$wo['work_started_at']) {
        $sql .= ", work_started_at = NOW()";
        $pdo->prepare("INSERT INTO work_order_progress_log (work_order_id, entry_type, content, technician_id) VALUES (?,'clock_start','Work started',?)")
            ->execute([$id, $wo['technician_id']]);
    }

    if ($newStatus === 'completed') {
        $sql .= ", work_completed_at = NOW()";
        $actualItems = get_line_items($pdo, 'work_order', $id);
        $totals = calculate_totals($actualItems);
        $actualTotal = $totals['labor'] + $totals['parts'] + $totals['services'] + $totals['tow'] - $totals['discounts'];
        $variance = $wo['authorized_total'] > 0 ? round(($actualTotal - $wo['authorized_total']) / $wo['authorized_total'] * 100, 2) : 0;

        $sql .= ", actual_total = ?, variance_pct = ?";
        $params[] = $actualTotal;
        $params[] = $variance;

        $pdo->prepare("INSERT INTO work_order_progress_log (work_order_id, entry_type, content, technician_id) VALUES (?,'clock_stop','Work completed',?)")
            ->execute([$id, $wo['technician_id']]);
    }

    if ($newStatus === 'paused') {
        $pdo->prepare("INSERT INTO work_order_progress_log (work_order_id, entry_type, content, technician_id) VALUES (?,'clock_stop','Work paused',?)")
            ->execute([$id, $wo['technician_id']]);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;
    $pdo->prepare($sql)->execute($params);

    audit_log($pdo, 'work_order', $id, 'status_change', $wo['status'], $newStatus);

    if ($newStatus === 'completed') {
        $_POST['work_order_id'] = $id;
        $invResult = create_invoice_from_wo($pdo, $id, $userId);
        echo json_encode(['success' => true, 'data' => ['invoice' => $invResult]]);
        break;
    }

    echo json_encode(['success' => true]);
    break;

case 'wo_progress_log':
    $woId = intval($_POST['work_order_id'] ?? 0);
    $type = $_POST['entry_type'] ?? 'note';
    $content = trim($_POST['content'] ?? '');
    $techId = intval($_POST['technician_id'] ?? 0);

    if (!$woId || !$techId) {
        echo json_encode(['success' => false, 'error' => 'work_order_id and technician_id required']); break;
    }

    $stmt = $pdo->prepare("INSERT INTO work_order_progress_log (work_order_id, entry_type, content, technician_id) VALUES (?,?,?,?)");
    $stmt->execute([$woId, $type, $content, $techId]);
    echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
    break;

case 'save_wo_diagnosis':
    $woId = intval($_POST['work_order_id'] ?? 0);
    $summary = trim($_POST['diagnosis_summary'] ?? '');
    $codes = $_POST['diagnostic_codes'] ?? null;
    if (!$woId) { echo json_encode(['success' => false, 'error' => 'work_order_id required']); break; }
    $stmt = $pdo->prepare("UPDATE work_orders SET diagnosis_summary = ?, diagnostic_codes = ? WHERE id = ?");
    $stmt->execute([$summary ?: null, ($codes && $codes !== '[]') ? $codes : null, $woId]);
    audit_log($pdo, 'work_order', $woId, 'diagnosis_updated', null, $summary ? 'updated' : 'cleared');
    echo json_encode(['success' => true]);
    break;

case 'wo_customer_signoff':
    $woId = intval($_POST['work_order_id'] ?? 0);
    if (!$woId) { echo json_encode(['success' => false]); break; }
    $pdo->prepare("UPDATE work_orders SET customer_signoff = 1, signoff_at = NOW() WHERE id = ?")->execute([$woId]);
    audit_log($pdo, 'work_order', $woId, 'customer_signoff', '0', '1');
    echo json_encode(['success' => true]);
    break;

case 'list_work_orders':
    $where = '1=1';
    $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND wo.status = ?'; $params[] = $_GET['status']; }
    if (!empty($_GET['service_request_id'])) { $where .= ' AND wo.service_request_id = ?'; $params[] = intval($_GET['service_request_id']); }
    if (!empty($_GET['technician_id'])) { $where .= ' AND wo.technician_id = ?'; $params[] = intval($_GET['technician_id']); }

    $stmt = $pdo->prepare("SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name FROM work_orders wo LEFT JOIN technicians t ON wo.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE {$where} ORDER BY wo.created_at DESC LIMIT 100");
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

}
