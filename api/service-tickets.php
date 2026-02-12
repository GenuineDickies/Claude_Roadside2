<?php
/**
 * Service Ticket Intake API
 * Handles ticket CRUD, customer lookup, catalog queries, and dispatch
 */
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ─── Bootstrap intake tables ─────────────────────────────────────────
$intakeTables = [
    // Customer vehicles — linked to customers
    "CREATE TABLE IF NOT EXISTS customer_vehicles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        year INT DEFAULT NULL,
        make VARCHAR(50) DEFAULT NULL,
        model VARCHAR(80) DEFAULT NULL,
        color VARCHAR(30) DEFAULT NULL,
        license_plate VARCHAR(20) DEFAULT NULL,
        license_state VARCHAR(5) DEFAULT NULL,
        vin VARCHAR(17) DEFAULT NULL,
        mileage INT DEFAULT NULL,
        drive_type ENUM('FWD','RWD','AWD','4WD','Unknown') DEFAULT 'Unknown',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_customer (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Service tickets — the main intake record
    "CREATE TABLE IF NOT EXISTS service_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_number VARCHAR(20) NOT NULL,
        customer_id INT NOT NULL,
        vehicle_id INT DEFAULT NULL,
        -- Customer snapshot (in case customer record changes)
        customer_phone VARCHAR(20) NOT NULL,
        customer_name VARCHAR(120) NOT NULL,
        alt_phone VARCHAR(20) DEFAULT NULL,
        customer_email VARCHAR(100) DEFAULT NULL,
        customer_type ENUM('individual','fleet','insurance','motor_club','commercial') NOT NULL DEFAULT 'individual',
        account_number VARCHAR(50) DEFAULT NULL,
        caller_relation ENUM('owner','driver','passenger','third_party') DEFAULT 'owner',
        -- Vehicle snapshot
        vehicle_year INT DEFAULT NULL,
        vehicle_make VARCHAR(50) DEFAULT NULL,
        vehicle_model VARCHAR(80) DEFAULT NULL,
        vehicle_color VARCHAR(30) DEFAULT NULL,
        vehicle_plate VARCHAR(20) DEFAULT NULL,
        vehicle_vin VARCHAR(17) DEFAULT NULL,
        vehicle_mileage INT DEFAULT NULL,
        vehicle_drive_type ENUM('FWD','RWD','AWD','4WD','Unknown') DEFAULT 'Unknown',
        -- Location
        service_address TEXT NOT NULL,
        service_lat DECIMAL(10,7) DEFAULT NULL,
        service_lng DECIMAL(10,7) DEFAULT NULL,
        location_type ENUM('roadside','parking_lot','residence','business','highway','other') DEFAULT 'roadside',
        location_details TEXT DEFAULT NULL,
        highway_name VARCHAR(100) DEFAULT NULL,
        direction_travel ENUM('N','S','E','W','NB','SB','EB','WB') DEFAULT NULL,
        safe_location TINYINT(1) NOT NULL DEFAULT 1,
        tow_destination TEXT DEFAULT NULL,
        tow_destination_lat DECIMAL(10,7) DEFAULT NULL,
        tow_destination_lng DECIMAL(10,7) DEFAULT NULL,
        tow_distance_miles DECIMAL(6,1) DEFAULT NULL,
        -- Service info
        service_category ENUM('towing','lockout','jump_start','tire_service','fuel_delivery','mobile_repair','winch_recovery','other') NOT NULL,
        specific_services TEXT DEFAULT NULL,
        issue_description TEXT NOT NULL,
        vehicle_condition ENUM('runs_drives','runs_no_drive','no_start','accident','immobile','unknown') NOT NULL DEFAULT 'unknown',
        vehicle_accessible TINYINT(1) NOT NULL DEFAULT 1,
        keys_available TINYINT(1) NOT NULL DEFAULT 1,
        passengers INT NOT NULL DEFAULT 0,
        hazard_conditions TEXT DEFAULT NULL,
        -- Urgency
        priority ENUM('P1','P2','P3','P4') NOT NULL DEFAULT 'P2',
        requested_eta VARCHAR(50) DEFAULT 'ASAP',
        scheduled_datetime DATETIME DEFAULT NULL,
        eta_minutes INT DEFAULT NULL,
        time_sensitivity TEXT DEFAULT NULL,
        -- Payment
        payment_method ENUM('card','cash','invoice','insurance','motor_club') NOT NULL DEFAULT 'card',
        estimated_cost DECIMAL(10,2) DEFAULT 0.00,
        price_quoted DECIMAL(10,2) DEFAULT 0.00,
        customer_approved TINYINT(1) NOT NULL DEFAULT 0,
        authorization_code VARCHAR(50) DEFAULT NULL,
        billing_notes TEXT DEFAULT NULL,
        -- Special
        special_equipment TEXT DEFAULT NULL,
        accessibility_needs TEXT DEFAULT NULL,
        preferred_language VARCHAR(20) DEFAULT 'English',
        internal_notes TEXT DEFAULT NULL,
        customer_notes TEXT DEFAULT NULL,
        -- Assignment
        technician_id INT DEFAULT NULL,
        -- Status lifecycle
        status ENUM('draft','created','dispatched','acknowledged','en_route','on_scene','in_progress','completed','closed','cancelled','escalated','on_hold') NOT NULL DEFAULT 'created',
        rapid_dispatch TINYINT(1) NOT NULL DEFAULT 0,
        sms_consent TINYINT(1) NOT NULL DEFAULT 0,
        sms_consent_at TIMESTAMP NULL DEFAULT NULL,
        -- Timestamps
        dispatched_at TIMESTAMP NULL DEFAULT NULL,
        acknowledged_at TIMESTAMP NULL DEFAULT NULL,
        arrived_at TIMESTAMP NULL DEFAULT NULL,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_ticket (ticket_number),
        KEY idx_customer (customer_id),
        KEY idx_technician (technician_id),
        KEY idx_status (status),
        KEY idx_priority (priority),
        KEY idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Ticket status history / audit trail
    "CREATE TABLE IF NOT EXISTS ticket_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        old_value VARCHAR(100) DEFAULT NULL,
        new_value VARCHAR(100) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_ticket (ticket_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Service categories for dropdown population
    "CREATE TABLE IF NOT EXISTS service_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        UNIQUE KEY uniq_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Service types linked to categories
    "CREATE TABLE IF NOT EXISTS service_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        slug VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        base_rate DECIMAL(10,2) DEFAULT 0.00,
        after_hours_rate DECIMAL(10,2) DEFAULT 0.00,
        description TEXT DEFAULT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        KEY idx_category (category_id),
        UNIQUE KEY uniq_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($intakeTables as $sql) {
    try { $pdo->exec($sql); } catch (PDOException $e) { /* exists */ }
}

// ─── Seed service categories & types ─────────────────────────────────
$catCount = $pdo->query("SELECT COUNT(*) FROM service_categories")->fetchColumn();
if ($catCount == 0) {
    $cats = [
        ['towing',          'Towing',           'fa-truck-pickup',    1],
        ['lockout',         'Lockout',          'fa-key',             2],
        ['jump_start',      'Jump Start',       'fa-car-battery',     3],
        ['tire_service',    'Tire Service',     'fa-circle-notch',    4],
        ['fuel_delivery',   'Fuel Delivery',    'fa-gas-pump',        5],
        ['mobile_repair',   'Mobile Repair',    'fa-wrench',          6],
        ['winch_recovery',  'Winch / Recovery', 'fa-truck-monster',   7],
        ['other',           'Other',            'fa-ellipsis-h',      8],
    ];
    $stmt = $pdo->prepare("INSERT INTO service_categories (slug, name, icon, sort_order) VALUES (?,?,?,?)");
    foreach ($cats as $c) { try { $stmt->execute($c); } catch (PDOException $e) {} }

    // Seed service types per category
    $types = [
        // Towing
        ['towing', 'local_tow',          'Local Tow (0–15 mi)',   85.00,  125.00],
        ['towing', 'long_distance_tow',  'Long Distance Tow',   150.00,  200.00],
        ['towing', 'flatbed_tow',        'Flatbed Tow',         125.00,  175.00],
        ['towing', 'motorcycle_tow',     'Motorcycle Tow',       95.00,  140.00],
        // Lockout
        ['lockout', 'car_lockout',       'Car Lockout',           65.00,   95.00],
        ['lockout', 'trunk_lockout',     'Trunk Lockout',         75.00,  110.00],
        ['lockout', 'broken_key',        'Broken Key Extraction', 85.00,  125.00],
        // Jump Start
        ['jump_start', 'standard_jump',  'Standard Jump Start',   55.00,   85.00],
        ['jump_start', 'heavy_duty_jump','Heavy Duty Jump',       75.00,  110.00],
        // Tire Service
        ['tire_service', 'flat_repair',  'Flat Tire Repair',      65.00,   95.00],
        ['tire_service', 'spare_mount',  'Spare Tire Mount',      55.00,   85.00],
        ['tire_service', 'tire_change',  'Tire Change (customer supplied)', 45.00, 70.00],
        // Fuel Delivery
        ['fuel_delivery', 'gas_delivery','Gasoline Delivery',     55.00,   80.00],
        ['fuel_delivery', 'diesel_delivery','Diesel Delivery',    65.00,   90.00],
        // Mobile Repair
        ['mobile_repair', 'diagnostic',  'Mobile Diagnostic',     95.00,  140.00],
        ['mobile_repair', 'belt_hose',   'Belt / Hose Repair',  125.00,  175.00],
        ['mobile_repair', 'starter',     'Starter Replacement',  175.00,  250.00],
        ['mobile_repair', 'alternator',  'Alternator Replacement',185.00, 265.00],
        ['mobile_repair', 'battery_replace','Battery Replacement',120.00, 160.00],
        // Winch / Recovery
        ['winch_recovery', 'basic_winch','Basic Winch Out',      125.00,  175.00],
        ['winch_recovery', 'off_road',   'Off-Road Recovery',    200.00,  300.00],
        ['winch_recovery', 'ditch_recovery','Ditch Recovery',    150.00,  225.00],
        // Other
        ['other', 'accident_standby',    'Accident Stand-By',     85.00,  125.00],
        ['other', 'custom_service',      'Custom / Other',         0.00,    0.00],
    ];
    $catIds = [];
    foreach ($pdo->query("SELECT id, slug FROM service_categories")->fetchAll() as $r) {
        $catIds[$r['slug']] = $r['id'];
    }
    $stmt = $pdo->prepare("INSERT INTO service_types (category_id, slug, name, base_rate, after_hours_rate) VALUES (?,?,?,?,?)");
    foreach ($types as $t) {
        $catId = $catIds[$t[0]] ?? null;
        if ($catId) { try { $stmt->execute([$catId, $t[1], $t[2], $t[3], $t[4]]); } catch (PDOException $e) {} }
    }
}

// ─── Helper: generate ticket number ──────────────────────────────────
function generate_ticket_number($pdo) {
    $dateStr = date('Ymd');
    $prefix = "RR-{$dateStr}-";
    $stmt = $pdo->prepare("SELECT ticket_number FROM service_tickets WHERE ticket_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    if ($last) {
        $seq = intval(substr($last, -4)) + 1;
    } else {
        $seq = 1;
    }
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// ─── Helper: ticket audit log ────────────────────────────────────────
function ticket_log($pdo, $ticketId, $action, $oldVal = null, $newVal = null, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO ticket_history (ticket_id, action, old_value, new_value, details, user_id) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$ticketId, $action, $oldVal, $newVal, $details, $_SESSION['user_id'] ?? null]);
}

// ─── Route API requests ──────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ── Customer lookup by phone ─────────────────────────────────────
    case 'customer_lookup':
        $phone = preg_replace('/\D/', '', $_GET['phone'] ?? '');
        if (strlen($phone) < 6) {
            echo json_encode(['success' => true, 'data' => []]);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT c.id, c.first_name, c.last_name, c.phone, c.email, c.address,
                   (SELECT MAX(sr.created_at) FROM service_requests sr WHERE sr.customer_id = c.id) as last_service
            FROM customers c
            WHERE REPLACE(REPLACE(REPLACE(c.phone, '-',''), '(',''), ')','') LIKE ?
            ORDER BY c.first_name LIMIT 5
        ");
        $stmt->execute(['%' . $phone . '%']);
        $customers = $stmt->fetchAll();

        // Get vehicles for each matched customer
        foreach ($customers as &$cust) {
            $vStmt = $pdo->prepare("SELECT * FROM customer_vehicles WHERE customer_id = ? ORDER BY id DESC");
            $vStmt->execute([$cust['id']]);
            $cust['vehicles'] = $vStmt->fetchAll();
        }
        unset($cust);

        echo json_encode(['success' => true, 'data' => $customers]);
        break;

    // ── Customer search by name ──────────────────────────────────────
    case 'customer_search':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT c.id, c.first_name, c.last_name, c.phone, c.email
            FROM customers c
            WHERE CONCAT(c.first_name, ' ', c.last_name) LIKE ?
            ORDER BY c.first_name LIMIT 10
        ");
        $stmt->execute(['%' . $q . '%']);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Recent customers ─────────────────────────────────────────────
    case 'recent_customers':
        $stmt = $pdo->query("
            SELECT c.id, c.first_name, c.last_name, c.phone,
                   (SELECT sr.service_type FROM service_requests sr WHERE sr.customer_id = c.id ORDER BY sr.created_at DESC LIMIT 1) as last_service_type,
                   (SELECT MAX(sr.created_at) FROM service_requests sr WHERE sr.customer_id = c.id) as last_service_date
            FROM customers c
            ORDER BY (SELECT MAX(sr.created_at) FROM service_requests sr WHERE sr.customer_id = c.id) DESC
            LIMIT 20
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Service catalog ──────────────────────────────────────────────
    case 'get_categories':
        $rows = $pdo->query("SELECT * FROM service_categories WHERE active = 1 ORDER BY sort_order")->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'get_services':
        $catSlug = $_GET['category'] ?? '';
        if ($catSlug) {
            $stmt = $pdo->prepare("
                SELECT st.* FROM service_types st
                JOIN service_categories sc ON st.category_id = sc.id
                WHERE sc.slug = ? AND st.active = 1
                ORDER BY st.name
            ");
            $stmt->execute([$catSlug]);
        } else {
            $stmt = $pdo->query("SELECT st.*, sc.slug as category_slug, sc.name as category_name FROM service_types st JOIN service_categories sc ON st.category_id = sc.id WHERE st.active = 1 ORDER BY sc.sort_order, st.name");
        }
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    // ── Estimate cost ────────────────────────────────────────────────
    case 'estimate_cost':
        $serviceIds = json_decode($_POST['service_ids'] ?? '[]', true);
        $towMiles = floatval($_POST['tow_miles'] ?? 0);
        $isAfterHours = ($_POST['after_hours'] ?? '0') === '1';

        $total = 0;
        if (!empty($serviceIds)) {
            $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
            $rateCol = $isAfterHours ? 'after_hours_rate' : 'base_rate';
            $stmt = $pdo->prepare("SELECT SUM({$rateCol}) as total FROM service_types WHERE id IN ({$placeholders})");
            $stmt->execute($serviceIds);
            $total = floatval($stmt->fetchColumn());
        }
        // Add mileage for tow beyond base 15 miles at $4.50/mi
        if ($towMiles > 15) {
            $total += ($towMiles - 15) * 4.50;
        }
        echo json_encode(['success' => true, 'data' => ['estimated_cost' => round($total, 2)]]);
        break;

    // ── Available technicians ────────────────────────────────────────
    case 'available_technicians':
        $rows = $pdo->query("
            SELECT t.id, t.first_name, t.last_name, t.phone, t.specialization, t.status,
                   (SELECT COUNT(*) FROM service_tickets st WHERE st.technician_id = t.id AND st.status IN ('dispatched','acknowledged','en_route','on_scene','in_progress')) as active_jobs
            FROM technicians t
            WHERE t.status = 'available'
            ORDER BY active_jobs ASC, t.first_name
        ")->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ── Create ticket ────────────────────────────────────────────────
    case 'create_ticket':
        $ticketNum = generate_ticket_number($pdo);
        $rapid = intval($_POST['rapid_dispatch'] ?? 0);

        // Required fields check
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
            // Create new customer
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
        // Auto-escalate if unsafe location
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

    // ── Assign technician (does NOT dispatch) ────────────────────────
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

    // ── Dispatch ticket (separate from assignment) ────────────────────
    case 'dispatch':
        $ticketId = intval($_POST['ticket_id'] ?? 0);
        if (!$ticketId) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            break;
        }
        // Get assigned technician
        $stmt = $pdo->prepare("SELECT technician_id FROM service_tickets WHERE id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        $techId = intval($_POST['technician_id'] ?? ($ticket['technician_id'] ?? 0));
        if (!$techId) {
            echo json_encode(['success' => false, 'error' => 'No technician assigned. Assign a technician before dispatching.']);
            break;
        }
        // If technician was passed but not yet assigned, assign first
        if (!$ticket['technician_id']) {
            $pdo->prepare("UPDATE service_tickets SET technician_id = ? WHERE id = ?")->execute([$techId, $ticketId]);
        }
        $stmt = $pdo->prepare("UPDATE service_tickets SET status = 'dispatched', dispatched_at = NOW() WHERE id = ?");
        $stmt->execute([$ticketId]);
        $pdo->prepare("UPDATE technicians SET status = 'busy' WHERE id = ?")->execute([$techId]);
        ticket_log($pdo, $ticketId, 'dispatched', null, 'dispatched', "Dispatched to tech #{$techId}");
        echo json_encode(['success' => true]);
        break;

    // ── Get single ticket ────────────────────────────────────────────
    case 'get_ticket':
        $ticketId = intval($_GET['id'] ?? 0);
        if (!$ticketId) { echo json_encode(['success' => false]); break; }
        $stmt = $pdo->prepare("SELECT st.*, t.first_name as tech_first, t.last_name as tech_last FROM service_tickets st LEFT JOIN technicians t ON st.technician_id = t.id WHERE st.id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        if (!$ticket) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

        // Get history
        $hStmt = $pdo->prepare("SELECT th.*, u.username FROM ticket_history th LEFT JOIN users u ON th.user_id = u.id WHERE th.ticket_id = ? ORDER BY th.created_at DESC");
        $hStmt->execute([$ticketId]);
        $ticket['history'] = $hStmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $ticket]);
        break;

    // ── List tickets (with filters) ──────────────────────────────────
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

    // ── Parse shorthand ──────────────────────────────────────────────
    case 'parse_shorthand':
        $input = strtolower(trim($_POST['text'] ?? ''));
        $mappings = [
            'js'       => ['category' => 'jump_start', 'service' => 'standard_jump', 'label' => 'Jump Start'],
            'jmp'      => ['category' => 'jump_start', 'service' => 'standard_jump', 'label' => 'Jump Start'],
            'ft'       => ['category' => 'tire_service', 'service' => 'flat_repair', 'label' => 'Flat Tire'],
            'flat'     => ['category' => 'tire_service', 'service' => 'flat_repair', 'label' => 'Flat Tire'],
            'lo'       => ['category' => 'lockout', 'service' => 'car_lockout', 'label' => 'Lockout'],
            'lock'     => ['category' => 'lockout', 'service' => 'car_lockout', 'label' => 'Lockout'],
            'tow'      => ['category' => 'towing', 'service' => 'local_tow', 'label' => 'Local Tow'],
            'fuel'     => ['category' => 'fuel_delivery', 'service' => 'gas_delivery', 'label' => 'Fuel Delivery'],
            'gas'      => ['category' => 'fuel_delivery', 'service' => 'gas_delivery', 'label' => 'Gasoline Delivery'],
            'diesel'   => ['category' => 'fuel_delivery', 'service' => 'diesel_delivery', 'label' => 'Diesel Delivery'],
            'winch'    => ['category' => 'winch_recovery', 'service' => 'basic_winch', 'label' => 'Winch Out'],
            'diag'     => ['category' => 'mobile_repair', 'service' => 'diagnostic', 'label' => 'Mobile Diagnostic'],
        ];

        $suggestions = [];
        foreach ($mappings as $key => $val) {
            if (strpos($input, $key) !== false) {
                $suggestions[] = $val;
            }
        }

        // Parse tow distance from "tow 15mi" or "tow 20"
        $towDist = null;
        if (preg_match('/tow\s+(\d+)\s*mi/i', $input, $m)) {
            $towDist = intval($m[1]);
        }

        // Parse tire position from "ft lr", "ft rf", etc.
        $tirePos = null;
        $posMap = ['lr' => 'Left Rear', 'lf' => 'Left Front', 'rr' => 'Right Rear', 'rf' => 'Right Front'];
        if (preg_match('/\b(lr|lf|rr|rf)\b/i', $input, $m)) {
            $tirePos = $posMap[strtolower($m[1])] ?? null;
        }

        echo json_encode(['success' => true, 'data' => ['suggestions' => $suggestions, 'tow_distance' => $towDist, 'tire_position' => $tirePos]]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}
