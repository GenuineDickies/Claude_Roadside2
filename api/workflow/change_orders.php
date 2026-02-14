<?php
// Change Order action handlers — included by api/workflow.php
switch ($action) {

case 'create_change_order':
    $woId = intval($_POST['work_order_id'] ?? 0);
    $techId = intval($_POST['technician_id'] ?? 0);
    $reason = $_POST['change_reason'] ?? 'discovery';
    $detail = trim($_POST['reason_detail'] ?? '');

    if (!$woId || !$techId || !$detail) {
        echo json_encode(['success' => false, 'error' => 'work_order_id, technician_id, reason_detail required']); break;
    }

    $stmt = $pdo->prepare("SELECT * FROM work_orders WHERE id = ?");
    $stmt->execute([$woId]);
    $wo = $stmt->fetch();
    if (!$wo) { echo json_encode(['success' => false, 'error' => 'Work order not found']); break; }

    $seqStmt = $pdo->prepare("SELECT COALESCE(MAX(sequence_num),0)+1 FROM change_orders WHERE work_order_id = ?");
    $seqStmt->execute([$woId]);
    $seq = $seqStmt->fetchColumn();

    $coDocId = generate_doc_id($pdo, 'CO', 'change_orders', 'change_order_id');
    $netImpact = floatval($_POST['net_cost_impact'] ?? 0);

    $approvedCOs = $pdo->prepare("SELECT COALESCE(SUM(net_cost_impact),0) FROM change_orders WHERE work_order_id = ? AND status = 'approved'");
    $approvedCOs->execute([$woId]);
    $existingImpact = floatval($approvedCOs->fetchColumn());
    $revisedTotal = $wo['authorized_total'] + $existingImpact + $netImpact;

    $stmt = $pdo->prepare("INSERT INTO change_orders (change_order_id, work_order_id, sequence_num, status, change_reason, reason_detail, original_scope_ref, net_cost_impact, revised_wo_total, must_pause_work, technician_id, technician_justification) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $coDocId, $woId, $seq, 'proposed', $reason, $detail,
        $_POST['original_scope_ref'] ?? null, $netImpact, round($revisedTotal, 2),
        intval($_POST['must_pause_work'] ?? 1), $techId,
        $_POST['technician_justification'] ?? null
    ]);
    $coId = $pdo->lastInsertId();

    $lineItems = json_decode($_POST['line_items'] ?? '[]', true);
    if (!empty($lineItems)) {
        $liStmt = $pdo->prepare("INSERT INTO document_line_items (document_type, document_id, line_number, item_type, catalog_service_id, catalog_part_id, description, quantity, unit_price, markup_pct, extended_price, taxable, notes) VALUES ('change_order',?,?,?,?,?,?,?,?,?,?,?,?)");
        $lineNum = 1;
        foreach ($lineItems as $li) {
            $qty = floatval($li['quantity'] ?? 1);
            $price = floatval($li['unit_price'] ?? 0);
            $markup = isset($li['markup_pct']) ? floatval($li['markup_pct']) : null;
            $ext = round($qty * $price * (1 + ($markup ?? 0) / 100), 2);
            $liStmt->execute([
                $coId, $lineNum++, $li['item_type'] ?? 'service_fee',
                $li['catalog_service_id'] ?? null, $li['catalog_part_id'] ?? null,
                $li['description'] ?? '', $qty, $price, $markup, $ext,
                $li['taxable'] ?? 1, $li['notes'] ?? null
            ]);
        }
    }

    if (intval($_POST['must_pause_work'] ?? 1)) {
        $pdo->prepare("UPDATE work_orders SET status = 'paused' WHERE id = ? AND status = 'in_progress'")->execute([$woId]);
    }

    audit_log($pdo, 'change_order', $coId, 'created', null, 'proposed', "CO {$coDocId} on WO #{$woId}");
    echo json_encode(['success' => true, 'data' => ['id' => $coId, 'change_order_id' => $coDocId, 'sequence_num' => $seq]]);
    break;

case 'update_change_order_status':
    $id = intval($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if (!$id || !in_array($newStatus, ['presented','approved','declined','voided'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid']); break;
    }

    $stmt = $pdo->prepare("SELECT * FROM change_orders WHERE id = ?");
    $stmt->execute([$id]);
    $co = $stmt->fetch();
    if (!$co) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

    $sql = "UPDATE change_orders SET status = ?";
    $params = [$newStatus];

    if ($newStatus === 'approved') {
        $sql .= ", approved_at = NOW(), approval_method = ?";
        $params[] = $_POST['approval_method'] ?? 'verbal';
        $pdo->prepare("UPDATE work_orders SET authorized_total = ? WHERE id = ?")
            ->execute([$co['revised_wo_total'], $co['work_order_id']]);
        copy_line_items($pdo, 'change_order', $id, 'work_order', $co['work_order_id']);
        $pdo->prepare("UPDATE work_orders SET status = 'in_progress' WHERE id = ? AND status = 'paused'")->execute([$co['work_order_id']]);
    }

    if ($newStatus === 'declined') {
        $sql .= ", declined_at = NOW()";
        if (!empty($_POST['decline_reason'])) {
            $sql .= ", decline_reason = ?";
            $params[] = $_POST['decline_reason'];
        }
        $pdo->prepare("UPDATE work_orders SET status = 'in_progress' WHERE id = ? AND status = 'paused'")->execute([$co['work_order_id']]);
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;
    $pdo->prepare($sql)->execute($params);

    audit_log($pdo, 'change_order', $id, 'status_change', $co['status'], $newStatus);
    echo json_encode(['success' => true]);
    break;

case 'get_change_order':
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false]); break; }
    $stmt = $pdo->prepare("SELECT co.*, wo.work_order_id as wo_doc_id, t.first_name as tech_first, t.last_name as tech_last FROM change_orders co LEFT JOIN work_orders wo ON co.work_order_id = wo.id LEFT JOIN technicians t ON co.technician_id = t.id WHERE co.id = ?");
    $stmt->execute([$id]);
    $co = $stmt->fetch();
    if (!$co) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }
    $co['line_items'] = get_line_items($pdo, 'change_order', $id);
    echo json_encode(['success' => true, 'data' => $co]);
    break;

}
