<?php
/**
 * Workflow shared helpers — audit log, line items, document chain.
 * Included by api/workflow.php before dispatching to handlers.
 */

function audit_log(PDO $pdo, string $docType, int $docId, string $action, $oldVal = null, $newVal = null, ?string $notes = null): void {
    $stmt = $pdo->prepare("INSERT INTO document_audit_log (document_type, document_id, action, old_value, new_value, performed_by, ip_address, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$docType, $docId, $action, $oldVal, $newVal, $_SESSION['user_id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? null, $notes]);
}

function get_line_items(PDO $pdo, string $docType, int $docId): array {
    $stmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type = ? AND document_id = ? ORDER BY line_number");
    $stmt->execute([$docType, $docId]);
    return $stmt->fetchAll();
}

function calculate_totals(array $lineItems): array {
    $labor = $parts = $services = $tow = $discounts = 0;
    foreach ($lineItems as $li) {
        switch ($li['item_type']) {
            case 'labor':       $labor += $li['extended_price']; break;
            case 'parts':       $parts += $li['extended_price']; break;
            case 'service_fee': $services += $li['extended_price']; break;
            case 'tow_mileage': $tow += $li['extended_price']; break;
            case 'discount':    $discounts += abs($li['extended_price']); break;
        }
    }
    return compact('labor', 'parts', 'services', 'tow', 'discounts');
}

function copy_line_items(PDO $pdo, string $srcType, int $srcId, string $destType, int $destId, bool $asActual = false): void {
    $items = get_line_items($pdo, $srcType, $srcId);
    $stmt = $pdo->prepare("INSERT INTO document_line_items (document_type, document_id, line_number, item_type, catalog_service_id, catalog_part_id, description, quantity, unit_price, markup_pct, extended_price, taxable, is_actual, source_line_item_id, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($items as $li) {
        $stmt->execute([
            $destType, $destId, $li['line_number'], $li['item_type'],
            $li['catalog_service_id'], $li['catalog_part_id'],
            $li['description'], $li['quantity'], $li['unit_price'],
            $li['markup_pct'], $li['extended_price'], $li['taxable'],
            $asActual ? 1 : 0, $li['id'], $li['notes']
        ]);
    }
}

function get_service_ticket(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email, c.address FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function create_invoice_from_wo(PDO $pdo, int $woId, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM work_orders WHERE id = ?");
    $stmt->execute([$woId]);
    $wo = $stmt->fetch();
    if (!$wo) return null;

    $sr = get_service_ticket($pdo, $wo['service_request_id']);
    $custType = $sr['customer_type'] ?? 'individual';
    $terms = 'due_on_receipt';
    if (in_array($custType, ['fleet','insurance','commercial'])) $terms = 'net_30';

    $dueDate = match($terms) {
        'net_15' => date('Y-m-d', strtotime('+15 days')),
        'net_30' => date('Y-m-d', strtotime('+30 days')),
        'net_60' => date('Y-m-d', strtotime('+60 days')),
        default  => date('Y-m-d'),
    };

    $invDocId = generate_doc_id($pdo, 'INV', 'invoices_v2', 'invoice_id');

    $woItems = get_line_items($pdo, 'work_order', $woId);
    $coStmt = $pdo->prepare("SELECT id FROM change_orders WHERE work_order_id = ? AND status = 'approved'");
    $coStmt->execute([$woId]);
    $coItems = [];
    while ($coRow = $coStmt->fetch()) {
        $coItems = array_merge($coItems, get_line_items($pdo, 'change_order', $coRow['id']));
    }

    $allItems = array_merge($woItems, $coItems);
    $totals = calculate_totals($allItems);
    $subtotal = $totals['labor'] + $totals['parts'] + $totals['services'] + $totals['tow'] - $totals['discounts'];

    $taxable = array_sum(array_map(fn($i) => $i['taxable'] ? $i['extended_price'] : 0, $allItems));
    $taxRate = 0.0825;
    $taxAmt = round($taxable * $taxRate, 2);
    $grandTotal = round($subtotal + $taxAmt, 2);

    $stmt = $pdo->prepare("INSERT INTO invoices_v2 (invoice_id, work_order_id, service_request_id, status, invoice_date, due_date, payment_terms, subtotal, tax_amount, grand_total, amount_paid, balance_due) VALUES (?,?,?,?,CURDATE(),?,?,?,?,?,0.00,?)");
    $stmt->execute([$invDocId, $woId, $wo['service_request_id'], 'generated', $dueDate, $terms, round($subtotal, 2), $taxAmt, $grandTotal, $grandTotal]);
    $invId = $pdo->lastInsertId();

    $liStmt = $pdo->prepare("INSERT INTO document_line_items (document_type, document_id, line_number, item_type, catalog_service_id, catalog_part_id, description, quantity, unit_price, markup_pct, extended_price, taxable, is_actual, source_line_item_id, notes) VALUES ('invoice',?,?,?,?,?,?,?,?,?,?,?,1,?,?)");
    $lineNum = 1;
    foreach ($allItems as $li) {
        $liStmt->execute([
            $invId, $lineNum++, $li['item_type'],
            $li['catalog_service_id'], $li['catalog_part_id'],
            $li['description'], $li['quantity'], $li['unit_price'],
            $li['markup_pct'], $li['extended_price'], $li['taxable'],
            $li['id'], $li['notes']
        ]);
    }

    audit_log($pdo, 'invoice', $invId, 'created', null, 'generated', "Invoice {$invDocId} from WO {$wo['work_order_id']}");
    $pdo->prepare("UPDATE service_tickets SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$wo['service_request_id']]);

    return ['id' => $invId, 'invoice_id' => $invDocId, 'grand_total' => $grandTotal];
}

function create_receipt(PDO $pdo, int $invId, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM invoices_v2 WHERE id = ?");
    $stmt->execute([$invId]);
    $inv = $stmt->fetch();
    if (!$inv) return null;

    $txn = $pdo->prepare("SELECT * FROM payment_transactions WHERE invoice_id = ? ORDER BY created_at DESC LIMIT 1");
    $txn->execute([$invId]);
    $lastTxn = $txn->fetch();

    $rctDocId = generate_doc_id($pdo, 'RCT', 'receipts', 'receipt_id');
    $sr = get_service_ticket($pdo, $inv['service_request_id']);
    $summary = "Service completed for " . ($sr['customer_name'] ?? 'customer') . ". ";
    $summary .= ucfirst(str_replace('_', ' ', $sr['service_category'] ?? 'service')) . " performed. ";
    $summary .= "Total charged: $" . number_format($inv['grand_total'], 2) . ".";

    $payMethod = $lastTxn['payment_method'] ?? 'card';
    $payRef = $lastTxn['processor_txn_id'] ?? ($lastTxn['id'] ?? 'N/A');

    $stmt = $pdo->prepare("INSERT INTO receipts (receipt_id, invoice_id, service_request_id, payment_method_used, payment_reference, processor_txn_id, amount_paid, payment_date, service_summary) VALUES (?,?,?,?,?,?,?,NOW(),?)");
    $stmt->execute([$rctDocId, $invId, $inv['service_request_id'], $payMethod, $payRef, $lastTxn['processor_txn_id'] ?? null, $inv['grand_total'], $summary]);
    $rctId = $pdo->lastInsertId();

    if ($lastTxn) {
        $pdo->prepare("UPDATE payment_transactions SET receipt_id = ? WHERE id = ?")->execute([$rctId, $lastTxn['id']]);
    }
    $pdo->prepare("UPDATE service_tickets SET status = 'closed' WHERE id = ?")->execute([$inv['service_request_id']]);
    audit_log($pdo, 'receipt', $rctId, 'created', null, 'generated', "Receipt {$rctDocId}");

    return ['id' => $rctId, 'receipt_id' => $rctDocId];
}

function get_document_chain(PDO $pdo, int $srId): array {
    $chain = ['service_request' => null, 'estimates' => [], 'work_orders' => [], 'change_orders' => [], 'invoices' => [], 'receipts' => []];

    $stmt = $pdo->prepare("SELECT id, ticket_number, status, created_at FROM service_tickets WHERE id = ?");
    $stmt->execute([$srId]);
    $chain['service_request'] = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT id, estimate_id, version, status, total, created_at FROM estimates WHERE service_request_id = ? ORDER BY version");
    $stmt->execute([$srId]);
    $chain['estimates'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, work_order_id, status, authorized_total, actual_total, created_at FROM work_orders WHERE service_request_id = ? ORDER BY created_at");
    $stmt->execute([$srId]);
    $chain['work_orders'] = $stmt->fetchAll();

    foreach ($chain['work_orders'] as $wo) {
        $coStmt = $pdo->prepare("SELECT id, change_order_id, sequence_num, status, net_cost_impact, created_at FROM change_orders WHERE work_order_id = ? ORDER BY sequence_num");
        $coStmt->execute([$wo['id']]);
        $chain['change_orders'] = array_merge($chain['change_orders'], $coStmt->fetchAll());
    }

    $stmt = $pdo->prepare("SELECT id, invoice_id, status, grand_total, balance_due, created_at FROM invoices_v2 WHERE service_request_id = ? ORDER BY created_at");
    $stmt->execute([$srId]);
    $chain['invoices'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, receipt_id, amount_paid, payment_date, created_at FROM receipts WHERE service_request_id = ? ORDER BY created_at");
    $stmt->execute([$srId]);
    $chain['receipts'] = $stmt->fetchAll();

    return $chain;
}
