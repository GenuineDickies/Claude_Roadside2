<?php
// Ticket CRUD + dispatch handlers — included by api/service-tickets.php
switch ($action) {

    case 'create_ticket':
        $ticketNum = generate_ticket_number($pdo);
        $rapid = intval($_POST['rapid_dispatch'] ?? 0);

        $required = ['customer_phone', 'customer_name', 'service_address', 'service_category', 'issue_description'];
        if (!$rapid) {
            $required[] = 'payment_method';
            $required[] = 'vehicle_condition';
        }
        $missing = [];
        foreach ($required as $f) {
            if (empty(trim($_POST[$f] ?? ''))) $missing[] = $f;
        }
        if (!empty($missing)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields', 'fields' => $missing]);
            break;
        }

        // Find or create customer
        $phone = preg_replace('/\D/', '', $_POST['customer_phone']);
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE REPLACE(REPLACE(REPLACE(phone,'-',''),'(',''),')','') = ?");
        $stmt->execute([$phone]);
        $customerId = $stmt->fetchColumn();

        if (!$customerId) {
            $nameParts = explode(' ', trim($_POST['customer_name']), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
            $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, phone, email) VALUES (?,?,?,?)");
            $stmt->execute([$firstName, $lastName, $_POST['customer_phone'], $_POST['customer_email'] ?? null]);
            $customerId = $pdo->lastInsertId();
        }

        // Save or find vehicle
        $vehicleId = null;
        if (!empty($_POST['vehicle_make']) && !empty($_POST['vehicle_year'])) {
            $stmt = $pdo->prepare("INSERT INTO customer_vehicles (customer_id, year, make, model, color, license_plate, license_state, vin, mileage, drive_type) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $customerId,
                intval($_POST['vehicle_year']),
                $_POST['vehicle_make'],
                $_POST['vehicle_model'] ?? null,
                $_POST['vehicle_color'] ?? null,
                $_POST['vehicle_plate'] ?? null,
                $_POST['vehicle_plate_state'] ?? null,
                $_POST['vehicle_vin'] ?? null,
                !empty($_POST['vehicle_mileage']) ? intval($_POST['vehicle_mileage']) : null,
                $_POST['vehicle_drive_type'] ?? 'Unknown',
            ]);
            $vehicleId = $pdo->lastInsertId();
        }

        // Determine priority
        $priority = $_POST['priority'] ?? 'P2';
        if (($_POST['safe_location'] ?? '1') === '0' && $priority !== 'P1') {
            $priority = 'P1';
        }

        // Insert ticket
        $stmt = $pdo->prepare("INSERT INTO service_tickets (
            ticket_number, customer_id, vehicle_id,
            customer_phone, customer_name, alt_phone, customer_email,
            customer_type, account_number, caller_relation,
            vehicle_year, vehicle_make, vehicle_model, vehicle_color, vehicle_plate, vehicle_vin, vehicle_mileage, vehicle_drive_type,
            service_address, service_lat, service_lng, location_type, location_details,
            highway_name, direction_travel, safe_location,
            tow_destination, tow_destination_lat, tow_destination_lng, tow_distance_miles,
            service_category, specific_services, issue_description,
            vehicle_condition, vehicle_accessible, keys_available, passengers, hazard_conditions,
            priority, requested_eta, scheduled_datetime, time_sensitivity,
            payment_method, estimated_cost, price_quoted, customer_approved, authorization_code, billing_notes,
            special_equipment, accessibility_needs, preferred_language, internal_notes, customer_notes,
            status, rapid_dispatch, sms_consent, sms_consent_at, created_by
        ) VALUES (
            ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
        )");
        $stmt->execute([
            $ticketNum, $customerId, $vehicleId,
            $_POST['customer_phone'], $_POST['customer_name'], $_POST['alt_phone'] ?? null, $_POST['customer_email'] ?? null,
            $_POST['customer_type'] ?? 'individual', $_POST['account_number'] ?? null, $_POST['caller_relation'] ?? 'owner',
            !empty($_POST['vehicle_year']) ? intval($_POST['vehicle_year']) : null,
            $_POST['vehicle_make'] ?? null, $_POST['vehicle_model'] ?? null, $_POST['vehicle_color'] ?? null,
            $_POST['vehicle_plate'] ?? null, $_POST['vehicle_vin'] ?? null,
            !empty($_POST['vehicle_mileage']) ? intval($_POST['vehicle_mileage']) : null,
            $_POST['vehicle_drive_type'] ?? 'Unknown',
            $_POST['service_address'],
            !empty($_POST['service_lat']) ? floatval($_POST['service_lat']) : null,
            !empty($_POST['service_lng']) ? floatval($_POST['service_lng']) : null,
            $_POST['location_type'] ?? 'roadside', $_POST['location_details'] ?? null,
            $_POST['highway_name'] ?? null, $_POST['direction_travel'] ?? null,
            intval($_POST['safe_location'] ?? 1),
            $_POST['tow_destination'] ?? null,
            !empty($_POST['tow_dest_lat']) ? floatval($_POST['tow_dest_lat']) : null,
            !empty($_POST['tow_dest_lng']) ? floatval($_POST['tow_dest_lng']) : null,
            !empty($_POST['tow_distance']) ? floatval($_POST['tow_distance']) : null,
            $_POST['service_category'],
            $_POST['specific_services'] ?? null,
            $_POST['issue_description'],
            $_POST['vehicle_condition'] ?? 'unknown',
            intval($_POST['vehicle_accessible'] ?? 1), intval($_POST['keys_available'] ?? 1),
            intval($_POST['passengers'] ?? 0), $_POST['hazard_conditions'] ?? null,
            $priority, $_POST['requested_eta'] ?? 'ASAP',
            !empty($_POST['scheduled_datetime']) ? $_POST['scheduled_datetime'] : null,
            $_POST['time_sensitivity'] ?? null,
            $_POST['payment_method'] ?? 'card',
            floatval($_POST['estimated_cost'] ?? 0), floatval($_POST['price_quoted'] ?? 0),
            intval($_POST['customer_approved'] ?? 0), $_POST['authorization_code'] ?? null,
            $_POST['billing_notes'] ?? null,
            $_POST['special_equipment'] ?? null, $_POST['accessibility_needs'] ?? null,
            $_POST['preferred_language'] ?? 'English',
            $_POST['internal_notes'] ?? null, $_POST['customer_notes'] ?? null,
            'created', intval($rapid),
            intval($_POST['sms_consent'] ?? 0),
            ($_POST['sms_consent'] ?? '0') === '1' ? date('Y-m-d H:i:s') : null,
            $_SESSION['user_id']
        ]);

        $ticketId = $pdo->lastInsertId();
        ticket_log($pdo, $ticketId, 'created', null, 'created', "Ticket {$ticketNum} created" . ($rapid ? ' (Rapid Dispatch)' : ''));

        echo json_encode(['success' => true, 'data' => ['id' => $ticketId, 'ticket_number' => $ticketNum]]);
        break;

    case 'assign_technician':
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        $techId = intval($_POST['technician_id'] ?? 0);
        if (!$ticketId || !$techId) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID and Technician ID required']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE service_tickets SET technician_id = ? WHERE id = ?");
        $stmt->execute([$techId, $ticketId]);
        ticket_log($pdo, $ticketId, 'assigned', null, null, "Technician #{$techId} assigned");
        echo json_encode(['success' => true]);
        break;

    case 'dispatch':
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        if (!$ticketId) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            break;
        }
        $stmt = $pdo->prepare("SELECT technician_id FROM service_tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        $techId = intval($_POST['technician_id'] ?? ($ticket['technician_id'] ?? 0));
        if (!$techId) {
            echo json_encode(['success' => false, 'error' => 'No technician assigned. Assign a technician before dispatching.']);
            break;
        }
        // Guard: require an approved estimate before dispatching
        $estCheck = $pdo->prepare("SELECT COUNT(*) FROM estimates WHERE service_request_id = ? AND status = 'approved'");
        $estCheck->execute([$ticketId]);
        if ($estCheck->fetchColumn() == 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot dispatch: an approved estimate is required before dispatching.']);
            break;
        }
        if (!$ticket['technician_id']) {
            $pdo->prepare("UPDATE service_tickets SET technician_id = ? WHERE id = ?")->execute([$techId, $ticketId]);
        }
        $stmt = $pdo->prepare("UPDATE service_tickets SET status = 'dispatched', dispatched_at = NOW() WHERE id = ?");
        $stmt->execute([$ticketId]);
        $pdo->prepare("UPDATE technicians SET status = 'busy' WHERE id = ?")->execute([$techId]);
        ticket_log($pdo, $ticketId, 'dispatched', null, 'dispatched', "Dispatched to tech #{$techId}");
        echo json_encode(['success' => true]);
        break;

    case 'get_ticket':
        $ticketId = intval($_GET['id'] ?? 0);
        if (!$ticketId) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->prepare("SELECT st.*, t.first_name as tech_first, t.last_name as tech_last FROM service_tickets st LEFT JOIN technicians t ON st.technician_id = t.id WHERE st.id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        if (!$ticket) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

        $hStmt = $pdo->prepare("SELECT th.*, u.username FROM ticket_history th LEFT JOIN users u ON th.user_id = u.id WHERE th.ticket_id = ? ORDER BY th.created_at DESC");
        $hStmt->execute([$ticketId]);
        $ticket['history'] = $hStmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $ticket]);
        break;

    case 'list_tickets':
        $where = ['1=1'];
        $params = [];
        if (!empty($_GET['status'])) { $where[] = 'st.status = ?'; $params[] = $_GET['status']; }
        if (!empty($_GET['priority'])) { $where[] = 'st.priority = ?'; $params[] = $_GET['priority']; }
        if (!empty($_GET['search'])) {
            $where[] = '(st.ticket_number LIKE ? OR st.customer_name LIKE ? OR st.customer_phone LIKE ?)';
            $s = '%' . $_GET['search'] . '%';
            $params = array_merge($params, [$s, $s, $s]);
        }
        $sql = "SELECT st.*, t.first_name as tech_first, t.last_name as tech_last
                FROM service_tickets st
                LEFT JOIN technicians t ON st.technician_id = t.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY FIELD(st.priority,'P1','P2','P3','P4'), st.created_at DESC
                LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

}
