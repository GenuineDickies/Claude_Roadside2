<?php
// Estimate action handlers — included by api/workflow.php
switch ($action) {

case 'create_estimate':
    $srId = intval($_POST['service_request_id'] ?? 0);
    $techId = intval($_POST['technician_id'] ?? 0);
    $diagnosis = trim($_POST['diagnosis_summary'] ?? '');

    if (!$srId || !$techId) {
        echo json_encode(['success' => false, 'error' => 'service_request_id and technician_id required']);
        break;
    }

    $sr = get_service_ticket($pdo, $srId);
    if (!$sr) { echo json_encode(['success' => false, 'error' => 'Service request not found']); break; }

    $stmt = $pdo->prepare("SELECT id, version FROM estimates WHERE service_request_id = ? ORDER BY version DESC LIMIT 1");
    $stmt->execute([$srId]);
    $prev = $stmt->fetch();
    $maxVersion = $prev ? intval($prev['version']) : 0;
    $requestedVersion = isset($_POST['version']) ? intval($_POST['version']) : 0;
    if ($requestedVersion < 1) { $requestedVersion = 0; }
    $version = $requestedVersion > 0 ? $requestedVersion : ($maxVersion + 1);

    $dupCheck = $pdo->prepare("SELECT id FROM estimates WHERE service_request_id = ? AND version = ?");
    $dupCheck->execute([$srId, $version]);
    if ($dupCheck->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Version already exists for this service request']);
        break;
    }

    $prevId = null;
    if ($prev && $version > $maxVersion) {
        $prevId = $prev['id'];
        $pdo->prepare("UPDATE estimates SET status = 'revised' WHERE id = ?")->execute([$prevId]);
    }

    $estDocId = generate_doc_id($pdo, 'EST', 'estimates', 'estimate_id', $version, $srId);
    $diagCodes = !empty($_POST['diagnostic_codes']) ? $_POST['diagnostic_codes'] : null;

    $repairCats = ['mobile_repair'];
    $validHours = in_array($sr['service_category'], $repairCats) ? 168 : 24;
    $validUntil = date('Y-m-d H:i:s', strtotime("+{$validHours} hours"));

    $stmt = $pdo->prepare("INSERT INTO estimates (estimate_id, service_request_id, version, status, technician_id, diagnosis_summary, diagnostic_codes, tax_rate, valid_until, previous_version_id, internal_notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $estDocId, $srId, $version, 'draft', $techId, $diagnosis,
        $diagCodes, floatval($_POST['tax_rate'] ?? 0.0825),
        $validUntil, $prevId, $_POST['internal_notes'] ?? null
    ]);
    $estId = $pdo->lastInsertId();

    $lineItems = json_decode($_POST['line_items'] ?? '[]', true);
    if (!empty($lineItems)) {
        $liStmt = $pdo->prepare("INSERT INTO document_line_items (document_type, document_id, line_number, item_type, catalog_service_id, catalog_part_id, description, quantity, unit_price, markup_pct, extended_price, taxable, notes) VALUES ('estimate',?,?,?,?,?,?,?,?,?,?,?,?)");
        $lineNum = 1;
        foreach ($lineItems as $li) {
            $qty = floatval($li['quantity'] ?? 1);
            $price = floatval($li['unit_price'] ?? 0);
            $markup = isset($li['markup_pct']) ? floatval($li['markup_pct']) : null;
            $ext = $qty * $price * (1 + ($markup ?? 0) / 100);
            $liStmt->execute([
                $estId, $lineNum++, $li['item_type'] ?? 'service_fee',
                $li['catalog_service_id'] ?? null, $li['catalog_part_id'] ?? null,
                $li['description'] ?? '', $qty, $price, $markup,
                round($ext, 2), $li['taxable'] ?? 1, $li['notes'] ?? null
            ]);
        }
        $items = get_line_items($pdo, 'estimate', $estId);
        $totals = calculate_totals($items);
        $subtotal = $totals['labor'] + $totals['parts'] + $totals['services'] + $totals['tow'] - $totals['discounts'];
        $taxRate = floatval($_POST['tax_rate'] ?? 0.0825);
        $taxable = array_sum(array_map(fn($i) => $i['taxable'] ? $i['extended_price'] : 0, $items));
        $taxAmt = round($taxable * $taxRate, 2);
        $total = round($subtotal + $taxAmt, 2);

        $pdo->prepare("UPDATE estimates SET subtotal_labor=?, subtotal_parts=?, subtotal_services=?, subtotal_tow=?, tax_amount=?, total=? WHERE id=?")
            ->execute([$totals['labor'], $totals['parts'], $totals['services'], $totals['tow'], $taxAmt, $total, $estId]);
    }

    audit_log($pdo, 'estimate', $estId, 'created', null, 'draft', "Estimate {$estDocId} v{$version}");
    echo json_encode(['success' => true, 'data' => ['id' => $estId, 'estimate_id' => $estDocId, 'version' => $version]]);
    break;

case 'get_estimate':
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false, 'error' => 'ID required']); break; }
    $stmt = $pdo->prepare("SELECT e.*, t.first_name as tech_first, t.last_name as tech_last FROM estimates e LEFT JOIN technicians t ON e.technician_id = t.id WHERE e.id = ?");
    $stmt->execute([$id]);
    $est = $stmt->fetch();
    if (!$est) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }
    $est['line_items'] = get_line_items($pdo, 'estimate', $id);
    $est['service_request'] = get_service_ticket($pdo, $est['service_request_id']);
    echo json_encode(['success' => true, 'data' => $est]);
    break;

case 'update_estimate_status':
    $id = intval($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowed = ['draft','presented','approved','declined','expired'];
    if (!$id || !in_array($newStatus, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid id or status']);
        break;
    }
    $stmt = $pdo->prepare("SELECT status FROM estimates WHERE id = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetchColumn();

    $params = [$newStatus];
    $sql = "UPDATE estimates SET status = ?";

    if ($newStatus === 'approved') {
        $sql .= ", approved_at = NOW(), approval_method = ?";
        $params[] = $_POST['approval_method'] ?? 'verbal';
        if (!empty($_POST['approver_name'])) {
            $sql .= ", approver_name = ?";
            $params[] = $_POST['approver_name'];
        }
    }
    if ($newStatus === 'declined' && !empty($_POST['decline_reason'])) {
        $sql .= ", decline_reason = ?";
        $params[] = $_POST['decline_reason'];
    }
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $pdo->prepare($sql)->execute($params);

    audit_log($pdo, 'estimate', $id, 'status_change', $old, $newStatus);
    echo json_encode(['success' => true]);
    break;

case 'add_estimate_line_item':
    $estId = intval($_POST['estimate_id'] ?? 0);
    if (!$estId) { echo json_encode(['success' => false, 'error' => 'estimate_id required']); break; }

    $maxLine = $pdo->prepare("SELECT COALESCE(MAX(line_number),0) FROM document_line_items WHERE document_type='estimate' AND document_id=?");
    $maxLine->execute([$estId]);
    $lineNum = $maxLine->fetchColumn() + 1;

    $qty = floatval($_POST['quantity'] ?? 1);
    $price = floatval($_POST['unit_price'] ?? 0);
    $markup = isset($_POST['markup_pct']) ? floatval($_POST['markup_pct']) : null;
    $ext = round($qty * $price * (1 + ($markup ?? 0) / 100), 2);

    $stmt = $pdo->prepare("INSERT INTO document_line_items (document_type, document_id, line_number, item_type, catalog_service_id, catalog_part_id, description, quantity, unit_price, markup_pct, extended_price, taxable, notes) VALUES ('estimate',?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $estId, $lineNum, $_POST['item_type'] ?? 'service_fee',
        $_POST['catalog_service_id'] ?? null, $_POST['catalog_part_id'] ?? null,
        $_POST['description'] ?? '', $qty, $price, $markup, $ext,
        $_POST['taxable'] ?? 1, $_POST['notes'] ?? null
    ]);
    $liId = $pdo->lastInsertId();

    $items = get_line_items($pdo, 'estimate', $estId);
    $totals = calculate_totals($items);
    $taxRate = $pdo->prepare("SELECT tax_rate FROM estimates WHERE id=?");
    $taxRate->execute([$estId]);
    $rate = floatval($taxRate->fetchColumn());
    $taxable = array_sum(array_map(fn($i) => $i['taxable'] ? $i['extended_price'] : 0, $items));
    $taxAmt = round($taxable * $rate, 2);
    $total = round($totals['labor'] + $totals['parts'] + $totals['services'] + $totals['tow'] - $totals['discounts'] + $taxAmt, 2);

    $pdo->prepare("UPDATE estimates SET subtotal_labor=?, subtotal_parts=?, subtotal_services=?, subtotal_tow=?, tax_amount=?, total=? WHERE id=?")
        ->execute([$totals['labor'], $totals['parts'], $totals['services'], $totals['tow'], $taxAmt, $total, $estId]);

    audit_log($pdo, 'estimate', $estId, 'line_item_added', null, $ext, $_POST['description'] ?? '');
    echo json_encode(['success' => true, 'data' => ['id' => $liId, 'line_number' => $lineNum, 'extended_price' => $ext, 'estimate_total' => $total]]);
    break;

case 'delete_line_item':
    $liId = intval($_POST['line_item_id'] ?? 0);
    if (!$liId) { echo json_encode(['success' => false]); break; }
    $stmt = $pdo->prepare("SELECT document_type, document_id, extended_price, description FROM document_line_items WHERE id=?");
    $stmt->execute([$liId]);
    $li = $stmt->fetch();
    if ($li) {
        $pdo->prepare("DELETE FROM document_line_items WHERE id=?")->execute([$liId]);
        audit_log($pdo, $li['document_type'], $li['document_id'], 'line_item_deleted', $li['extended_price'], null, $li['description']);
    }
    echo json_encode(['success' => true]);
    break;

case 'list_estimates':
    $srId = intval($_GET['service_request_id'] ?? 0);
    $where = '1=1';
    $params = [];
    if ($srId) { $where .= ' AND e.service_request_id = ?'; $params[] = $srId; }
    if (!empty($_GET['status'])) { $where .= ' AND e.status = ?'; $params[] = $_GET['status']; }

    $stmt = $pdo->prepare("SELECT e.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number FROM estimates e LEFT JOIN technicians t ON e.technician_id = t.id LEFT JOIN service_tickets st ON e.service_request_id = st.id WHERE {$where} ORDER BY e.created_at DESC LIMIT 100");
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

}
