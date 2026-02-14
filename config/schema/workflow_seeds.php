<?php
/**
 * Workflow seed data — rate schedules and sample parts inventory
 */

function seed_workflow_data(PDO $pdo): void {
    // Seed rate schedules if empty
    $rateCount = $pdo->query("SELECT COUNT(*) FROM rate_schedules")->fetchColumn();
    if ($rateCount == 0) {
        $rates = [
            ['Standard Hourly',    'standard',    85.00,  1.00, '2025-01-01'],
            ['After Hours',        'after_hours', 127.50, 1.50, '2025-01-01'],
            ['Weekend',            'weekend',     110.50, 1.30, '2025-01-01'],
            ['Holiday',            'holiday',     148.75, 1.75, '2025-01-01'],
            ['Emergency / P1',     'emergency',   170.00, 2.00, '2025-01-01'],
        ];
        $stmt = $pdo->prepare("INSERT INTO rate_schedules (name, type, hourly_rate, multiplier, effective_from) VALUES (?,?,?,?,?)");
        foreach ($rates as $r) {
            try { $stmt->execute($r); } catch (PDOException $e) {}
        }
    }

    // Seed parts inventory samples if empty
    $partsCount = $pdo->query("SELECT COUNT(*) FROM parts_inventory")->fetchColumn();
    if ($partsCount == 0) {
        $parts = [
            ['BAT-001', 'Standard Car Battery (Group 24)',   'Batteries',  89.99, 65.00],
            ['BAT-002', 'Premium Car Battery (Group 35)',    'Batteries', 129.99, 50.00],
            ['BAT-003', 'Heavy Duty Battery (Group 65)',     'Batteries', 169.99, 50.00],
            ['BLT-001', 'Serpentine Belt (Universal)',       'Belts',      24.99, 80.00],
            ['BLT-002', 'Timing Belt Kit',                   'Belts',      79.99, 60.00],
            ['HSE-001', 'Radiator Hose (Upper)',             'Hoses',      18.99, 80.00],
            ['HSE-002', 'Radiator Hose (Lower)',             'Hoses',      22.99, 80.00],
            ['STR-001', 'Starter Motor (Reman)',             'Electrical', 119.99, 50.00],
            ['ALT-001', 'Alternator (Reman)',                'Electrical', 149.99, 50.00],
            ['FLT-001', 'Oil Filter (Standard)',             'Filters',      6.99, 100.00],
            ['FLT-002', 'Air Filter (Standard)',             'Filters',     12.99, 100.00],
            ['SPK-001', 'Spark Plug (Iridium, each)',        'Ignition',     8.99, 80.00],
            ['FUS-001', 'Fuse Assortment Kit',               'Electrical',   9.99, 100.00],
            ['CLN-001', 'Coolant 50/50 (1 gal)',             'Fluids',      12.99, 60.00],
            ['OIL-001', 'Motor Oil 5W-30 (5 qt)',            'Fluids',      24.99, 60.00],
        ];
        $stmt = $pdo->prepare("INSERT INTO parts_inventory (part_number, name, category, unit_cost, markup_pct) VALUES (?,?,?,?,?)");
        foreach ($parts as $p) {
            try { $stmt->execute($p); } catch (PDOException $e) {}
        }
    }
}
