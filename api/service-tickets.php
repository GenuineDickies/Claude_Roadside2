<?php
/**
 * Service Ticket Intake API — Router
 * Handles ticket CRUD, customer lookup, catalog queries, and dispatch.
 */
require_once __DIR__ . '/../includes/api_bootstrap.php';
require_once __DIR__ . '/../includes/id_helpers.php';
require_once __DIR__ . '/../config/intake_schema.php';

bootstrap_intake_schema($pdo);

function ticket_log($pdo, $ticketId, $action, $oldVal = null, $newVal = null, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, action, old_value, new_value, details, user_id) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$ticketId, $action, $oldVal, $newVal, $details, $_SESSION['user_id'] ?? null]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$lookupActions = ['customer_lookup', 'customer_get', 'customer_search', 'recent_customers', 'get_categories', 'get_services', 'estimate_cost', 'available_technicians', 'parse_shorthand'];
$ticketActions = ['create_ticket', 'assign_technician', 'dispatch', 'get_ticket', 'list_tickets'];

if (in_array($action, $lookupActions, true)) {
    require __DIR__ . '/service-tickets/lookups.php';
} elseif (in_array($action, $ticketActions, true)) {
    require __DIR__ . '/service-tickets/tickets.php';
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}
