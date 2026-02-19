<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/id_helpers.php';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;
$ticketHistory = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? $id;
    if (isset($_POST['add_request'])) {
        $ticketNum = generate_ticket_number($pdo);
        $custStmt = $pdo->prepare("SELECT first_name, last_name, phone, email FROM customers WHERE id = ?");
        $custStmt->execute([$_POST['customer_id']]);
        $cust = $custStmt->fetch();
        $stmt = $pdo->prepare("INSERT INTO service_tickets (ticket_number, customer_id, customer_name, customer_phone, customer_email, service_category, issue_description, service_address, priority, estimated_cost, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'created', ?)");
        $result = $stmt->execute([
            $ticketNum, $_POST['customer_id'],
            $cust ? $cust['first_name'] . ' ' . $cust['last_name'] : '',
            $cust ? $cust['phone'] : '', $cust ? $cust['email'] : '',
            $_POST['service_category'], sanitize_input($_POST['issue_description']),
            sanitize_input($_POST['service_address']), $_POST['priority'],
            floatval($_POST['estimated_cost']), $_SESSION['user_id'] ?? null
        ]);
        show_alert($result ? 'Service ticket created successfully!' : 'Error creating service ticket.', $result ? 'success' : 'danger');
        if ($result) $action = 'list';
    } elseif (isset($_POST['assign_technician'])) {
        $stmt = $pdo->prepare("UPDATE service_tickets SET technician_id=? WHERE id=?");
        $result = $stmt->execute([$_POST['technician_id'], $id]);
        show_alert($result ? 'Technician assigned successfully!' : 'Error assigning technician.', $result ? 'success' : 'danger');
    } elseif (isset($_POST['dispatch_ticket'])) {
        $stmt = $pdo->prepare("SELECT technician_id FROM service_tickets WHERE id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();
        if (!$ticket || !$ticket['technician_id']) {
            show_alert('Cannot dispatch: no technician assigned.', 'danger');
        } else {
            $estCheck = $pdo->prepare("SELECT COUNT(*) FROM estimates WHERE service_request_id = ? AND status = 'approved'");
            $estCheck->execute([$id]);
            if (!$estCheck->fetchColumn()) {
                show_alert('Cannot dispatch: an approved estimate is required before dispatching.', 'danger');
            } else {
                $stmt = $pdo->prepare("UPDATE service_tickets SET status='dispatched', dispatched_at=NOW() WHERE id=?");
                $result = $stmt->execute([$id]);
                if ($result) {
                    $pdo->prepare("UPDATE technicians SET status='busy' WHERE id=?")->execute([$ticket['technician_id']]);
                    show_alert('Ticket dispatched!', 'success');
                } else {
                    show_alert('Error dispatching ticket.', 'danger');
                }
            }
        }
    } elseif (isset($_POST['update_status'])) {
        $completed_at = $_POST['status'] === 'completed' ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare("UPDATE service_tickets SET status=?, price_quoted=?, completed_at=? WHERE id=?");
        $result = $stmt->execute([$_POST['status'], floatval($_POST['actual_cost']), $completed_at, $id]);
        if ($result && $_POST['status'] === 'completed') {
            $pdo->prepare("UPDATE technicians SET status='available' WHERE id = (SELECT technician_id FROM service_tickets WHERE id=?)")->execute([$id]);
        }
        show_alert($result ? 'Service ticket updated successfully!' : 'Error updating service ticket.', $result ? 'success' : 'danger');
    } elseif (isset($_POST['edit_request'])) {
        $currentStmt = $pdo->prepare("SELECT * FROM service_tickets WHERE id = ? LIMIT 1");
        $currentStmt->execute([$id]);
        $currentRequest = $currentStmt->fetch();
        if (!$currentRequest) {
            show_alert('Service ticket not found.', 'danger');
        } else {
            // Update customer snapshot
            $custStmt = $pdo->prepare("SELECT first_name, last_name, phone, email FROM customers WHERE id = ?");
            $custStmt->execute([$_POST['customer_id']]);
            $cust = $custStmt->fetch();

            $oldVersion = intval($currentRequest['version'] ?? 1);
            $newVersion = $oldVersion + 1;
            $newTicketNumber = bump_ticket_number_version($currentRequest['ticket_number'], $newVersion);

            $archiveStmt = $pdo->prepare("INSERT INTO service_ticket_versions (service_ticket_id, version, ticket_number, data, archived_by) VALUES (?,?,?,?,?)");
            $archiveStmt->execute([
                $id,
                $oldVersion,
                $currentRequest['ticket_number'],
                json_encode($currentRequest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $_SESSION['user_id'] ?? null
            ]);

            $stmt = $pdo->prepare("UPDATE service_tickets SET
                customer_id = ?, customer_name = ?, customer_phone = ?, customer_email = ?,
                service_category = ?, issue_description = ?, service_address = ?,
                location_type = ?, tow_destination = ?, tow_distance_miles = ?,
                vehicle_year = ?, vehicle_make = ?, vehicle_model = ?, vehicle_color = ?,
                vehicle_plate = ?, vehicle_vin = ?, vehicle_condition = ?, vehicle_drive_type = ?,
                priority = ?, estimated_cost = ?, payment_method = ?, requested_eta = ?,
                internal_notes = ?, customer_notes = ?,
                version = ?, ticket_number = ?
                WHERE id = ?");
            $result = $stmt->execute([
                $_POST['customer_id'],
                $cust ? $cust['first_name'] . ' ' . $cust['last_name'] : '',
                $cust ? $cust['phone'] : '', $cust ? $cust['email'] : '',
                $_POST['service_category'], sanitize_input($_POST['issue_description']),
                sanitize_input($_POST['service_address']),
                $_POST['location_type'] ?? 'roadside',
                sanitize_input($_POST['tow_destination'] ?? ''),
                !empty($_POST['tow_distance_miles']) ? floatval($_POST['tow_distance_miles']) : null,
                !empty($_POST['vehicle_year']) ? intval($_POST['vehicle_year']) : null,
                sanitize_input($_POST['vehicle_make'] ?? ''),
                sanitize_input($_POST['vehicle_model'] ?? ''),
                sanitize_input($_POST['vehicle_color'] ?? ''),
                sanitize_input($_POST['vehicle_plate'] ?? ''),
                sanitize_input($_POST['vehicle_vin'] ?? ''),
                $_POST['vehicle_condition'] ?? 'unknown',
                $_POST['vehicle_drive_type'] ?? 'Unknown',
                $_POST['priority'], floatval($_POST['estimated_cost']),
                $_POST['payment_method'] ?? 'card',
                sanitize_input($_POST['requested_eta'] ?? 'ASAP'),
                sanitize_input($_POST['internal_notes'] ?? ''),
                sanitize_input($_POST['customer_notes'] ?? ''),
                $newVersion,
                $newTicketNumber,
                $id
            ]);
            show_alert($result ? 'Service ticket saved with new version!' : 'Error updating ticket.', $result ? 'success' : 'danger');
            if ($result) {
                $action = 'view';
            }
        }
    }
}

// Get service ticket data for view
    if (($action === 'view' || $action === 'edit' || $action === 'assign') && $id) {
    $stmt = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email, c.address, t.first_name as tech_first_name, t.last_name as tech_last_name, t.phone as tech_phone FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id LEFT JOIN technicians t ON st.technician_id = t.id WHERE st.id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    if (!$request) { show_alert('Service ticket not found.', 'danger'); $action = 'list'; }
        if ($request) {
            $historyStmt = $pdo->prepare("SELECT v.*, u.username FROM service_ticket_versions v LEFT JOIN users u ON v.archived_by = u.id WHERE v.service_ticket_id = ? ORDER BY v.version DESC");
            $historyStmt->execute([$id]);
            $ticketHistory = $historyStmt->fetchAll();
        }
}

$customers = $pdo->query("SELECT id, first_name, last_name, phone FROM customers ORDER BY first_name, last_name")->fetchAll();
$availableTechnicians = $pdo->query("SELECT id, first_name, last_name, specialization FROM technicians WHERE status = 'available' ORDER BY first_name, last_name")->fetchAll();

if ($action === 'list') {
    $requests = $pdo->query("SELECT st.*, c.first_name, c.last_name, c.phone, t.first_name as tech_first_name, t.last_name as tech_last_name FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id LEFT JOIN technicians t ON st.technician_id = t.id ORDER BY st.created_at DESC")->fetchAll();
}
?>

<link rel="stylesheet" href="assets/css/pages/service-requests.css">

<div class="rr-page-header" style="margin: -28px -28px 24px -28px;">
    <div class="header-left">
        <i class="fas fa-clipboard-list header-icon"></i>
        <div>
            <h1>Service Tickets</h1>
            <div class="header-subtitle">Track and manage service operations</div>
        </div>
    </div>
    <div class="header-actions">
        <?php if ($action === 'list'): ?>
            <a href="?page=service-intake" class="btn btn-primary"><i class="fas fa-plus"></i> New Request</a>
        <?php else: ?>
            <a href="?page=service-requests" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <?php include __DIR__ . '/service-requests/list.php'; ?>
<?php elseif ($action === 'edit' && $request): ?>
    <?php include __DIR__ . '/service-requests/edit.php'; ?>
<?php elseif ($action === 'view' && $request): ?>
    <?php include __DIR__ . '/service-requests/view.php'; ?>
<?php endif; ?>
