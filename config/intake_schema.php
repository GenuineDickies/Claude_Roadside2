<?php
/**
 * Service Intake Schema — tables and seed data for service tickets
 * Auto-bootstraps on first require — safe to call repeatedly.
 */

function bootstrap_intake_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $tables = [
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
            -- Customer snapshot
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
            version SMALLINT NOT NULL DEFAULT 1,
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

        // Archived versions of service tickets
        "CREATE TABLE IF NOT EXISTS service_ticket_versions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_ticket_id INT NOT NULL,
            version SMALLINT NOT NULL,
            ticket_number VARCHAR(20) NOT NULL,
            data JSON NOT NULL,
            archived_by INT DEFAULT NULL,
            archived_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ticket (service_ticket_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // SMS Consent — phone-level consent state
        // Rules:
        // - Default: consent=0, opted_out=0
        // - We only send SMS when consent=1 and opted_out=0
        // - Consent can only switch 0 -> 1 via explicit grant
        // - Opt-out never flips consent; it records opt_out_at (and sets opted_out=1)
        "CREATE TABLE IF NOT EXISTS sms_consent (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone_digits VARCHAR(20) NOT NULL,
            consent TINYINT(1) NOT NULL DEFAULT 0,
            consent_at TIMESTAMP NULL DEFAULT NULL,
            opted_out TINYINT(1) NOT NULL DEFAULT 0,
            opt_out_at TIMESTAMP NULL DEFAULT NULL,
            last_source VARCHAR(50) DEFAULT NULL,
            last_ticket_id INT DEFAULT NULL,
            last_ticket_number VARCHAR(20) DEFAULT NULL,
            last_seen_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_phone (phone_digits),
            KEY idx_consent (consent),
            KEY idx_opted_out (opted_out),
            KEY idx_last_seen (last_seen_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // SMS Consent Events — append-only audit log
        "CREATE TABLE IF NOT EXISTS sms_consent_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone_digits VARCHAR(20) NOT NULL,
            event_type VARCHAR(30) NOT NULL,
            source VARCHAR(50) DEFAULT NULL,
            ticket_id INT DEFAULT NULL,
            ticket_number VARCHAR(20) DEFAULT NULL,
            user_id INT DEFAULT NULL,
            meta JSON DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_phone (phone_digits),
            KEY idx_event (event_type),
            KEY idx_created (created_at)
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($tables as $sql) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* exists */ }
    }

    // Migrations for early SMS consent schemas (safe, best-effort)
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN consent TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN consent_at TIMESTAMP NULL DEFAULT NULL"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN opted_out TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN opt_out_at TIMESTAMP NULL DEFAULT NULL"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN last_seen_at TIMESTAMP NULL DEFAULT NULL"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN last_source VARCHAR(50) DEFAULT NULL"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN last_ticket_id INT DEFAULT NULL"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent ADD COLUMN last_ticket_number VARCHAR(20) DEFAULT NULL"); } catch (PDOException $e) {}

    // If an older 'status' column existed, map consent when possible
    try {
        $pdo->exec("UPDATE sms_consent SET consent = CASE WHEN status = 'consented' THEN 1 ELSE consent END WHERE status IS NOT NULL");
        $pdo->exec("UPDATE sms_consent SET last_seen_at = COALESCE(last_seen_at, last_event_at)");
    } catch (PDOException $e) {}

    // Evolve sms_consent_events from ENUM(event) to flexible VARCHAR(event_type)
    try { $pdo->exec("ALTER TABLE sms_consent_events CHANGE COLUMN event event_type VARCHAR(30) NOT NULL"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE sms_consent_events ADD COLUMN meta JSON DEFAULT NULL"); } catch (PDOException $e) {}
    // Normalize early values
    try {
        $pdo->exec("UPDATE sms_consent_events SET event_type = CASE WHEN event_type = 'consented' THEN 'consent_granted' WHEN event_type = 'declined' THEN 'ticket_created' ELSE event_type END");
    } catch (PDOException $e) {}

    // Seed service categories & types
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

        $types = [
            ['towing', 'local_tow',          'Local Tow (0–15 mi)',   85.00,  125.00],
            ['towing', 'long_distance_tow',  'Long Distance Tow',   150.00,  200.00],
            ['towing', 'flatbed_tow',        'Flatbed Tow',         125.00,  175.00],
            ['towing', 'motorcycle_tow',     'Motorcycle Tow',       95.00,  140.00],
            ['lockout', 'car_lockout',       'Car Lockout',           65.00,   95.00],
            ['lockout', 'trunk_lockout',     'Trunk Lockout',         75.00,  110.00],
            ['lockout', 'broken_key',        'Broken Key Extraction', 85.00,  125.00],
            ['jump_start', 'standard_jump',  'Standard Jump Start',   55.00,   85.00],
            ['jump_start', 'heavy_duty_jump','Heavy Duty Jump',       75.00,  110.00],
            ['tire_service', 'flat_repair',  'Flat Tire Repair',      65.00,   95.00],
            ['tire_service', 'spare_mount',  'Spare Tire Mount',      55.00,   85.00],
            ['tire_service', 'tire_change',  'Tire Change (customer supplied)', 45.00, 70.00],
            ['fuel_delivery', 'gas_delivery','Gasoline Delivery',     55.00,   80.00],
            ['fuel_delivery', 'diesel_delivery','Diesel Delivery',    65.00,   90.00],
            ['mobile_repair', 'diagnostic',  'Mobile Diagnostic',     95.00,  140.00],
            ['mobile_repair', 'belt_hose',   'Belt / Hose Repair',  125.00,  175.00],
            ['mobile_repair', 'starter',     'Starter Replacement',  175.00,  250.00],
            ['mobile_repair', 'alternator',  'Alternator Replacement',185.00, 265.00],
            ['mobile_repair', 'battery_replace','Battery Replacement',120.00, 160.00],
            ['winch_recovery', 'basic_winch','Basic Winch Out',      125.00,  175.00],
            ['winch_recovery', 'off_road',   'Off-Road Recovery',    200.00,  300.00],
            ['winch_recovery', 'ditch_recovery','Ditch Recovery',    150.00,  225.00],
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
}
