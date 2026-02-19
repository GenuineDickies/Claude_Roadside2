<?php
// Customer/catalog lookup handlers — included by api/service-tickets.php
switch ($action) {

    case 'customer_get':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Missing id']);
            break;
        }
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, phone, email, address FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $cust = $stmt->fetch();
        if (!$cust) {
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
            break;
        }
        $vStmt = $pdo->prepare("SELECT * FROM customer_vehicles WHERE customer_id = ? ORDER BY id DESC");
        $vStmt->execute([$cust['id']]);
        $cust['vehicles'] = $vStmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $cust]);
        break;

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

        foreach ($customers as &$cust) {
            $vStmt = $pdo->prepare("SELECT * FROM customer_vehicles WHERE customer_id = ? ORDER BY id DESC");
            $vStmt->execute([$cust['id']]);
            $cust['vehicles'] = $vStmt->fetchAll();
        }
        unset($cust);

        echo json_encode(['success' => true, 'data' => $customers]);
        break;

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
        if ($towMiles > 15) {
            $total += ($towMiles - 15) * 4.50;
        }
        echo json_encode(['success' => true, 'data' => ['estimated_cost' => round($total, 2)]]);
        break;

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

        $towDist = null;
        if (preg_match('/tow\s+(\d+)\s*mi/i', $input, $m)) {
            $towDist = intval($m[1]);
        }

        $tirePos = null;
        $posMap = ['lr' => 'Left Rear', 'lf' => 'Left Front', 'rr' => 'Right Rear', 'rf' => 'Right Front'];
        if (preg_match('/\b(lr|lf|rr|rf)\b/i', $input, $m)) {
            $tirePos = $posMap[strtolower($m[1])] ?? null;
        }

        echo json_encode(['success' => true, 'data' => ['suggestions' => $suggestions, 'tow_distance' => $towDist, 'tire_position' => $tirePos]]);
        break;

}
