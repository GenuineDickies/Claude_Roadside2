<?php
/**
 * RoadRunner Workflow API
 * Handles the full document chain: SR → EST → WO → CO → INV → RCT
 * 
 * All document transitions are audited. Data flows forward via inheritance.
 * Uses document_line_items polymorphic table for all priced documents.
 */
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) mkdir($sessionDir, 0755, true);
session_save_path($sessionDir);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/workflow_schema.php';
require_once __DIR__ . '/../includes/functions.php';

bootstrap_workflow_schema($pdo);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

// ─── Helpers ─────────────────────────────────────────────────────────

function audit_log(PDO $pdo, string $docType, int $docId, string $action, $oldVal = null, $newVal = null, ?string $notes = null): void {
    $stmt = $pdo->prepare("INSERT INTO document_audit_log (document_type, document_id, action, old_value, new_value, performed_by, ip_address, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$docType, $docId, $action, $oldVal, $newVal, $_SESSION['user_id'] ?? 0, $_SERVER['REMOTE_ADDR'] ?? null, $notes]);
}

function generate_doc_id(PDO $pdo, string $prefix, string $table, string $column): string {
    $dateStr = date('Ymd');
    $full = "{$prefix}-{$dateStr}-";
    $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE {$column} LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$full . '%']);
    $last = $stmt->fetchColumn();
    $seq = $last ? intval(substr($last, -4)) + 1 : 1;
    return $full . str_pad($seq, 4, '0', STR_PAD_LEFT);
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

// Get the service ticket (service request) for inheritance
function get_service_ticket(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email, c.address FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ─── Route ───────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

// ══════════════════════════════════════════════════════════════════════
// ESTIMATES
// ══════════════════════════════════════════════════════════════════════

case 'create_estimate':
    $srId = intval($_POST['service_request_id'] ?? 0);
    $techId = intval($_POST['technician_id'] ?? 0);
    $diagnosis = trim($_POST['diagnosis_summary'] ?? '');
    
    if (!$srId || !$techId || !$diagnosis) {
        echo json_encode(['success' => false, 'error' => 'service_request_id, technician_id, and diagnosis_summary required']);
        break;
    }
    
    // Check parent SR exists
    $sr = get_service_ticket($pdo, $srId);
    if (!$sr) { echo json_encode(['success' => false, 'error' => 'Service request not found']); break; }
    
    // Check for existing revision
    $version = 1;
    $prevId = null;
    $stmt = $pdo->prepare("SELECT id, version FROM estimates WHERE service_request_id = ? ORDER BY version DESC LIMIT 1");
    $stmt->execute([$srId]);
    $prev = $stmt->fetch();
    if ($prev) {
        $version = $prev['version'] + 1;
        $prevId = $prev['id'];
        // Mark previous as revised
        $pdo->prepare("UPDATE estimates SET status = 'revised' WHERE id = ?")->execute([$prevId]);
    }
    
    $estDocId = generate_doc_id($pdo, 'EST', 'estimates', 'estimate_id');
    $diagCodes = !empty($_POST['diagnostic_codes']) ? $_POST['diagnostic_codes'] : null;
    
    // Validity: 7 days for repair, 24h for roadside
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
    
    // Insert line items if provided
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
        // Update subtotals
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
    
    $updates = ['status' => $newStatus];
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
    
    // Get next line number
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
    
    // Recalculate estimate totals
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

// ══════════════════════════════════════════════════════════════════════
// WORK ORDERS
// ══════════════════════════════════════════════════════════════════════

case 'create_work_order':
    $srId = intval($_POST['service_request_id'] ?? 0);
    $estId = !empty($_POST['estimate_id']) ? intval($_POST['estimate_id']) : null;
    $techId = intval($_POST['technician_id'] ?? 0);
    
    if (!$srId || !$techId) {
        echo json_encode(['success' => false, 'error' => 'service_request_id and technician_id required']);
        break;
    }
    
    $authorizedTotal = 0;
    
    // If from an approved estimate, inherit data
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
        // Catalog-priced — get total from POST or calculate
        $authorizedTotal = floatval($_POST['authorized_total'] ?? 0);
    }
    
    $woDocId = generate_doc_id($pdo, 'WO', 'work_orders', 'work_order_id');
    $stmt = $pdo->prepare("INSERT INTO work_orders (work_order_id, service_request_id, estimate_id, status, technician_id, authorized_total) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$woDocId, $srId, $estId, 'created', $techId, $authorizedTotal]);
    $woId = $pdo->lastInsertId();
    
    // Copy line items from estimate if exists
    if ($estId) {
        copy_line_items($pdo, 'estimate', $estId, 'work_order', $woId);
    }
    
    // Update service ticket status
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
    
    // Get change orders
    $coStmt = $pdo->prepare("SELECT * FROM change_orders WHERE work_order_id = ? ORDER BY sequence_num");
    $coStmt->execute([$id]);
    $wo['change_orders'] = $coStmt->fetchAll();
    
    // Get progress log
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
        // Log clock start
        $pdo->prepare("INSERT INTO work_order_progress_log (work_order_id, entry_type, content, technician_id) VALUES (?,'clock_start','Work started',?)")
            ->execute([$id, $wo['technician_id']]);
    }
    
    if ($newStatus === 'completed') {
        $sql .= ", work_completed_at = NOW()";
        // Calculate actual totals
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
    
    // If completed, auto-generate invoice
    if ($newStatus === 'completed') {
        // Trigger invoice generation
        $_POST['work_order_id'] = $id;
        $_POST['action'] = 'create_invoice';
        // We'll call create_invoice inline
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

// ══════════════════════════════════════════════════════════════════════
// CHANGE ORDERS
// ══════════════════════════════════════════════════════════════════════

case 'create_change_order':
    $woId = intval($_POST['work_order_id'] ?? 0);
    $techId = intval($_POST['technician_id'] ?? 0);
    $reason = $_POST['change_reason'] ?? 'discovery';
    $detail = trim($_POST['reason_detail'] ?? '');
    
    if (!$woId || !$techId || !$detail) {
        echo json_encode(['success' => false, 'error' => 'work_order_id, technician_id, reason_detail required']); break;
    }
    
    // Get parent WO
    $stmt = $pdo->prepare("SELECT * FROM work_orders WHERE id = ?");
    $stmt->execute([$woId]);
    $wo = $stmt->fetch();
    if (!$wo) { echo json_encode(['success' => false, 'error' => 'Work order not found']); break; }
    
    // Get sequence number
    $seqStmt = $pdo->prepare("SELECT COALESCE(MAX(sequence_num),0)+1 FROM change_orders WHERE work_order_id = ?");
    $seqStmt->execute([$woId]);
    $seq = $seqStmt->fetchColumn();
    
    $coDocId = generate_doc_id($pdo, 'CO', 'change_orders', 'change_order_id');
    $netImpact = floatval($_POST['net_cost_impact'] ?? 0);
    
    // Calculate revised total: current WO authorized + all approved COs + this CO
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
    
    // Insert line items if provided
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
    
    // Pause WO if required
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
        
        // Update parent WO authorized total
        $pdo->prepare("UPDATE work_orders SET authorized_total = ? WHERE id = ?")
            ->execute([$co['revised_wo_total'], $co['work_order_id']]);
        
        // Copy CO line items to WO
        copy_line_items($pdo, 'change_order', $id, 'work_order', $co['work_order_id']);
        
        // Resume WO if paused
        $pdo->prepare("UPDATE work_orders SET status = 'in_progress' WHERE id = ? AND status = 'paused'")->execute([$co['work_order_id']]);
    }
    
    if ($newStatus === 'declined') {
        $sql .= ", declined_at = NOW()";
        if (!empty($_POST['decline_reason'])) {
            $sql .= ", decline_reason = ?";
            $params[] = $_POST['decline_reason'];
        }
        // Resume WO if paused
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

// ══════════════════════════════════════════════════════════════════════
// INVOICES
// ══════════════════════════════════════════════════════════════════════

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
    
    // Get payment transactions
    $ptStmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE invoice_id = ? ORDER BY created_at");
    $ptStmt->execute([$id]);
    $inv['payments'] = $ptStmt->fetchAll();
    
    // Get chain references
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
    
    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (invoice_id, payment_method, amount, processor, processor_txn_id, status, processed_at, recorded_by) VALUES (?,?,?,?,?,?,NOW(),?)");
    $stmt->execute([$invId, $method, $amount, $_POST['processor'] ?? 'manual', $_POST['processor_txn_id'] ?? null, 'completed', $userId]);
    $txnId = $pdo->lastInsertId();
    
    // Update invoice paid amount
    $pdo->prepare("UPDATE invoices_v2 SET amount_paid = amount_paid + ?, balance_due = balance_due - ? WHERE id = ?")
        ->execute([$amount, $amount, $invId]);
    
    // Check if fully paid
    $stmt = $pdo->prepare("SELECT balance_due, service_request_id FROM invoices_v2 WHERE id = ?");
    $stmt->execute([$invId]);
    $inv = $stmt->fetch();
    
    $receiptData = null;
    if ($inv['balance_due'] <= 0) {
        $pdo->prepare("UPDATE invoices_v2 SET status = 'paid', paid_at = NOW() WHERE id = ?")->execute([$invId]);
        // Auto-generate receipt
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

// ══════════════════════════════════════════════════════════════════════
// RECEIPTS
// ══════════════════════════════════════════════════════════════════════

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

// ══════════════════════════════════════════════════════════════════════
// DOCUMENT CHAIN
// ══════════════════════════════════════════════════════════════════════

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

// ── Catalog lookups for line item builders ──────────────────────────

case 'search_parts':
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode(['success' => true, 'data' => []]); break; }
    $stmt = $pdo->prepare("SELECT * FROM parts_inventory WHERE active = 1 AND (name LIKE ? OR part_number LIKE ? OR category LIKE ?) ORDER BY name LIMIT 20");
    $s = "%{$q}%";
    $stmt->execute([$s, $s, $s]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

case 'get_rate_schedules':
    $stmt = $pdo->query("SELECT * FROM rate_schedules WHERE active = 1 ORDER BY type");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    break;

// ── Dashboard / summary stats ────────────────────────────────────────

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

default:
    echo json_encode(['success' => false, 'error' => "Unknown action: {$action}"]);
}

// ══════════════════════════════════════════════════════════════════════
// Helper Functions (not cases)
// ══════════════════════════════════════════════════════════════════════

function create_invoice_from_wo(PDO $pdo, int $woId, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM work_orders WHERE id = ?");
    $stmt->execute([$woId]);
    $wo = $stmt->fetch();
    if (!$wo) return null;
    
    // Get SR for customer type → payment terms
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
    
    // Get all WO line items + approved CO line items
    $woItems = get_line_items($pdo, 'work_order', $woId);
    
    // Get approved change order line items
    $coStmt = $pdo->prepare("SELECT id FROM change_orders WHERE work_order_id = ? AND status = 'approved'");
    $coStmt->execute([$woId]);
    $coItems = [];
    while ($coRow = $coStmt->fetch()) {
        $coItems = array_merge($coItems, get_line_items($pdo, 'change_order', $coRow['id']));
    }
    
    $allItems = array_merge($woItems, $coItems);
    $totals = calculate_totals($allItems);
    $subtotal = $totals['labor'] + $totals['parts'] + $totals['services'] + $totals['tow'] - $totals['discounts'];
    
    // Tax calculation
    $taxable = array_sum(array_map(fn($i) => $i['taxable'] ? $i['extended_price'] : 0, $allItems));
    $taxRate = 0.0825; // Default; could be jurisdiction-based
    $taxAmt = round($taxable * $taxRate, 2);
    $grandTotal = round($subtotal + $taxAmt, 2);
    
    $stmt = $pdo->prepare("INSERT INTO invoices_v2 (invoice_id, work_order_id, service_request_id, status, invoice_date, due_date, payment_terms, subtotal, tax_amount, grand_total, amount_paid, balance_due) VALUES (?,?,?,?,CURDATE(),?,?,?,?,?,0.00,?)");
    $stmt->execute([$invDocId, $woId, $wo['service_request_id'], 'generated', $dueDate, $terms, round($subtotal, 2), $taxAmt, $grandTotal, $grandTotal]);
    $invId = $pdo->lastInsertId();
    
    // Copy all line items to invoice
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
    
    // Update SR status
    $pdo->prepare("UPDATE service_tickets SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$wo['service_request_id']]);
    
    return ['id' => $invId, 'invoice_id' => $invDocId, 'grand_total' => $grandTotal];
}

function create_receipt(PDO $pdo, int $invId, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM invoices_v2 WHERE id = ?");
    $stmt->execute([$invId]);
    $inv = $stmt->fetch();
    if (!$inv) return null;
    
    // Get the last payment transaction
    $txn = $pdo->prepare("SELECT * FROM payment_transactions WHERE invoice_id = ? ORDER BY created_at DESC LIMIT 1");
    $txn->execute([$invId]);
    $lastTxn = $txn->fetch();
    
    $rctDocId = generate_doc_id($pdo, 'RCT', 'receipts', 'receipt_id');
    
    // Build service summary
    $sr = get_service_ticket($pdo, $inv['service_request_id']);
    $summary = "Service completed for " . ($sr['customer_name'] ?? 'customer') . ". ";
    $summary .= ucfirst(str_replace('_', ' ', $sr['service_category'] ?? 'service')) . " performed. ";
    $summary .= "Total charged: $" . number_format($inv['grand_total'], 2) . ".";
    
    $payMethod = $lastTxn['payment_method'] ?? 'card';
    $payRef = $lastTxn['processor_txn_id'] ?? ($lastTxn['id'] ?? 'N/A');
    
    $stmt = $pdo->prepare("INSERT INTO receipts (receipt_id, invoice_id, service_request_id, payment_method_used, payment_reference, processor_txn_id, amount_paid, payment_date, service_summary) VALUES (?,?,?,?,?,?,?,NOW(),?)");
    $stmt->execute([$rctDocId, $invId, $inv['service_request_id'], $payMethod, $payRef, $lastTxn['processor_txn_id'] ?? null, $inv['grand_total'], $summary]);
    $rctId = $pdo->lastInsertId();
    
    // Link receipt to transaction
    if ($lastTxn) {
        $pdo->prepare("UPDATE payment_transactions SET receipt_id = ? WHERE id = ?")->execute([$rctId, $lastTxn['id']]);
    }
    
    // Close the ticket
    $pdo->prepare("UPDATE service_tickets SET status = 'closed' WHERE id = ?")->execute([$inv['service_request_id']]);
    
    audit_log($pdo, 'receipt', $rctId, 'created', null, 'generated', "Receipt {$rctDocId}");
    
    return ['id' => $rctId, 'receipt_id' => $rctDocId];
}

function get_document_chain(PDO $pdo, int $srId): array {
    $chain = ['service_request' => null, 'estimates' => [], 'work_orders' => [], 'change_orders' => [], 'invoices' => [], 'receipts' => []];
    
    // SR
    $stmt = $pdo->prepare("SELECT id, ticket_number, status, created_at FROM service_tickets WHERE id = ?");
    $stmt->execute([$srId]);
    $chain['service_request'] = $stmt->fetch();
    
    // Estimates
    $stmt = $pdo->prepare("SELECT id, estimate_id, version, status, total, created_at FROM estimates WHERE service_request_id = ? ORDER BY version");
    $stmt->execute([$srId]);
    $chain['estimates'] = $stmt->fetchAll();
    
    // Work Orders
    $stmt = $pdo->prepare("SELECT id, work_order_id, status, authorized_total, actual_total, created_at FROM work_orders WHERE service_request_id = ? ORDER BY created_at");
    $stmt->execute([$srId]);
    $chain['work_orders'] = $stmt->fetchAll();
    
    // Change Orders (via work orders)
    foreach ($chain['work_orders'] as $wo) {
        $coStmt = $pdo->prepare("SELECT id, change_order_id, sequence_num, status, net_cost_impact, created_at FROM change_orders WHERE work_order_id = ? ORDER BY sequence_num");
        $coStmt->execute([$wo['id']]);
        $chain['change_orders'] = array_merge($chain['change_orders'], $coStmt->fetchAll());
    }
    
    // Invoices
    $stmt = $pdo->prepare("SELECT id, invoice_id, status, grand_total, balance_due, created_at FROM invoices_v2 WHERE service_request_id = ? ORDER BY created_at");
    $stmt->execute([$srId]);
    $chain['invoices'] = $stmt->fetchAll();
    
    // Receipts
    $stmt = $pdo->prepare("SELECT id, receipt_id, amount_paid, payment_date, created_at FROM receipts WHERE service_request_id = ? ORDER BY created_at");
    $stmt->execute([$srId]);
    $chain['receipts'] = $stmt->fetchAll();
    
    return $chain;
}
