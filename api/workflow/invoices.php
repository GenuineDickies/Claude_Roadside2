<?php
// Invoice & Receipt action handlers — included by api/workflow.php
switch ($action) {

case 'create_invoice':
    $woId = intval($_POST['work_order_id'] ?? 0);
    if (!$woId) { echo json_encode(['success' => false, 'error' => 'work_order_id required']); break; }
    $result = create_invoice_from_wo($pdo, $woId, $userId);
    echo json_encode($result ? ['success' => true, 'data' => $result] : ['success' => false, 'error' => 'Failed to create invoice']);
    break;

case 'get_invoice':
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false]); break; }
    $stmt = $pdo->prepare("
        SELECT iv.*, wo.work_order_id as wo_doc_id, wo.technician_id,
               st.ticket_number, st.customer_name, st.customer_phone, st.customer_email, st.service_address,
               t.first_name as tech_first, t.last_name as tech_last
        FROM invoices_v2 iv
        LEFT JOIN work_orders wo ON iv.work_order_id = wo.id
        LEFT JOIN service_tickets st ON iv.service_request_id = st.id
        LEFT JOIN technicians t ON wo.technician_id = t.id
        WHERE iv.id = ?
    ");
    $stmt->execute([$id]);
    $inv = $stmt->fetch();
    if (!$inv) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

    $inv['line_items'] = get_line_items($pdo, 'invoice', $id);
    $ptStmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE invoice_id = ? ORDER BY created_at");
    $ptStmt->execute([$id]);
    $inv['payments'] = $ptStmt->fetchAll();
    $inv['chain'] = get_document_chain($pdo, $inv['service_request_id']);
    echo json_encode(['success' => true, 'data' => $inv]);
    break;

case 'update_invoice_status':
    $id = intval($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if (!$id || !in_array($newStatus, ['generated','sent','viewed','paid','partial','overdue','disputed','written_off'])) {
        echo json_encode(['success' => false]); break;
    }
    $stmt = $pdo->prepare("SELECT status FROM invoices_v2 WHERE id = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetchColumn();

    $sql = "UPDATE invoices_v2 SET status = ?";
    $params = [$newStatus];
    if ($newStatus === 'sent') { $sql .= ", sent_email_at = NOW()"; }
    if ($newStatus === 'viewed') { $sql .= ", viewed_at = NOW()"; }
    if ($newStatus === 'paid') { $sql .= ", paid_at = NOW()"; }
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $pdo->prepare($sql)->execute($params);

    audit_log($pdo, 'invoice', $id, 'status_change', $old, $newStatus);
    echo json_encode(['success' => true]);
    break;

case 'record_payment':
    $invId = intval($_POST['invoice_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = $_POST['payment_method'] ?? 'card';

    if (!$invId || $amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'invoice_id and amount required']); break;
    }

    $stmt = $pdo->prepare("INSERT INTO payment_transactions (invoice_id, payment_method, amount, processor, processor_txn_id, status, processed_at, recorded_by) VALUES (?,?,?,?,?,?,NOW(),?)");
    $stmt->execute([$invId, $method, $amount, $_POST['processor'] ?? 'manual', $_POST['processor_txn_id'] ?? null, 'completed', $userId]);
    $txnId = $pdo->lastInsertId();

    $pdo->prepare("UPDATE invoices_v2 SET amount_paid = amount_paid + ?, balance_due = balance_due - ? WHERE id = ?")
        ->execute([$amount, $amount, $invId]);

    $stmt = $pdo->prepare("SELECT balance_due, service_request_id FROM invoices_v2 WHERE id = ?");
    $stmt->execute([$invId]);
    $inv = $stmt->fetch();

    $receiptData = null;
    if ($inv['balance_due'] <= 0) {
        $pdo->prepare("UPDATE invoices_v2 SET status = 'paid', paid_at = NOW() WHERE id = ?")->execute([$invId]);
        $receiptData = create_receipt($pdo, $invId, $userId);
    } else {
        $pdo->prepare("UPDATE invoices_v2 SET status = 'partial' WHERE id = ?")->execute([$invId]);
    }

    audit_log($pdo, 'invoice', $invId, 'payment_received', null, $amount, "Method: {$method}");
    echo json_encode(['success' => true, 'data' => ['txn_id' => $txnId, 'receipt' => $receiptData]]);
    break;

case 'apply_discount':
    $invId = intval($_POST['invoice_id'] ?? 0);
    $amount = floatval($_POST['discount_amount'] ?? 0);
    $reason = trim($_POST['discount_reason'] ?? '');

    if (!$invId || $amount <= 0) { echo json_encode(['success' => false]); break; }

    $pdo->prepare("UPDATE invoices_v2 SET discount_amount = discount_amount + ?, discount_reason = CONCAT(COALESCE(discount_reason,''), ?), grand_total = grand_total - ?, balance_due = balance_due - ? WHERE id = ?")
        ->execute([$amount, ($reason ? "\n" . $reason : ''), $amount, $amount, $invId]);

    audit_log($pdo, 'invoice', $invId, 'discount_applied', null, $amount, $reason);
    echo json_encode(['success' => true]);
    break;

case 'list_invoices':
    $where = '1=1';
    $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND iv.status = ?'; $params[] = $_GET['status']; }
    if (!empty($_GET['service_request_id'])) { $where .= ' AND iv.service_request_id = ?'; $params[] = intval($_GET['service_request_id']); }

    $stmt = $pdo->prepare("SELECT iv.*, st.ticket_number, st.customer_name, st.customer_phone FROM invoices_v2 iv LEFT JOIN service_tickets st ON iv.service_request_id = st.id WHERE {$where} ORDER BY iv.created_at DESC LIMIT 100");
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

case 'get_receipt':
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false]); break; }
    $stmt = $pdo->prepare("
        SELECT r.*, iv.invoice_id as inv_doc_id, iv.grand_total, iv.payment_terms,
               st.ticket_number, st.customer_name, st.customer_phone, st.customer_email, st.service_address, st.service_category
        FROM receipts r
        LEFT JOIN invoices_v2 iv ON r.invoice_id = iv.id
        LEFT JOIN service_tickets st ON r.service_request_id = st.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $rct = $stmt->fetch();
    if (!$rct) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }
    $rct['chain'] = get_document_chain($pdo, $rct['service_request_id']);
    echo json_encode(['success' => true, 'data' => $rct]);
    break;

case 'deliver_receipt':
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success' => false]); break; }
    $pdo->prepare("UPDATE receipts SET delivered_at = NOW() WHERE id = ?")->execute([$id]);
    audit_log($pdo, 'receipt', $id, 'delivered');
    echo json_encode(['success' => true]);
    break;

case 'list_receipts':
    $where = '1=1';
    $params = [];
    if (!empty($_GET['service_request_id'])) { $where .= ' AND r.service_request_id = ?'; $params[] = intval($_GET['service_request_id']); }

    $stmt = $pdo->prepare("SELECT r.*, iv.invoice_id as inv_doc_id, st.ticket_number, st.customer_name FROM receipts r LEFT JOIN invoices_v2 iv ON r.invoice_id = iv.id LEFT JOIN service_tickets st ON r.service_request_id = st.id WHERE {$where} ORDER BY r.created_at DESC LIMIT 100");
    $stmt->execute($params);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

}
