<?php
// Database configuration — loads credentials from .env file
// NEVER commit real credentials. Copy .env.example to .env and fill in values.

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("Missing .env file. Copy .env.example to .env and set your database credentials.");
}

$env = parse_ini_file($envFile);
if ($env === false) {
    die("Failed to parse .env file.");
}

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_USER', $env['DB_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_NAME', $env['DB_NAME'] ?? 'roadside_assistance');

// Create connection to MySQL server first (without specifying database)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed. Check server logs for details.');
}

// Create database if it doesn't exist
$pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
$pdo->exec("USE " . DB_NAME);

// Disable foreign key checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

// Create tables in order (completely clean, no constraints initially)
$tables = [
    "CREATE TABLE IF NOT EXISTS settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL,
        setting_value TEXT NOT NULL,
        category VARCHAR(50) NOT NULL DEFAULT 'general',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_setting_key (setting_key),
        KEY idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role ENUM('admin', 'dispatcher', 'manager') NOT NULL DEFAULT 'dispatcher',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS customers (
        id INT(11) NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        address TEXT,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_phone (phone),
        KEY idx_name (first_name, last_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS technicians (
        id INT(11) NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        specialization VARCHAR(100) DEFAULT NULL,
        status ENUM('available', 'busy', 'offline') NOT NULL DEFAULT 'available',
        hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS service_requests (
        id INT(11) NOT NULL AUTO_INCREMENT,
        customer_id INT(11) NOT NULL,
        technician_id INT(11) DEFAULT NULL,
        service_type ENUM('battery_jump', 'tire_change', 'lockout', 'towing', 'fuel_delivery', 'other') NOT NULL,
        description TEXT,
        location TEXT NOT NULL,
        status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
        estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        actual_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_customer_id (customer_id),
        KEY idx_technician_id (technician_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS invoices (
        id INT(11) NOT NULL AUTO_INCREMENT,
        service_request_id INT(11) NOT NULL,
        customer_id INT(11) NOT NULL,
        technician_id INT(11) NOT NULL,
        invoice_number VARCHAR(20) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('draft', 'sent', 'paid', 'overdue') NOT NULL DEFAULT 'draft',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        due_date DATE NOT NULL,
        paid_at TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY invoice_number (invoice_number),
        KEY idx_service_request_id (service_request_id),
        KEY idx_customer_id (customer_id),
        KEY idx_technician_id (technician_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

// Create all tables
foreach ($tables as $table) {
    try {
        $pdo->exec($table);
    } catch (PDOException $e) {
        continue;
    }
}

require_once __DIR__ . '/intake_schema.php';
bootstrap_intake_schema($pdo);

// Migrations: add columns that CREATE TABLE IF NOT EXISTS won't add to existing tables
try {
    $cols = $pdo->query("SHOW COLUMNS FROM service_tickets LIKE 'version'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE service_tickets ADD COLUMN version SMALLINT NOT NULL DEFAULT 1");
    }
    // Migrate legacy ticket numbers (RR-YYYYMMDD-NNNN) to canonical (RR-YYMMDD-NNN-VV)
    $pdo->exec("UPDATE service_tickets SET ticket_number = CONCAT(
        SUBSTRING(ticket_number,1,3), SUBSTRING(ticket_number,6,6), '-',
        LPAD(CAST(SUBSTRING(ticket_number,13) AS UNSIGNED), 3, '0'), '-',
        LPAD(version, 2, '0'))
        WHERE ticket_number REGEXP '^RR-[0-9]{8}-[0-9]{4}$'");
} catch (PDOException $e) { /* table may not exist yet */ }

// Re-enable foreign key checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Insert default admin user if not exists
try {
    $checkUser = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    if ($checkUser == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', password_hash('pass', PASSWORD_DEFAULT), 'admin@roadside.com', 'admin']);
    }
} catch (PDOException $e) {
    // User might already exist, continue anyway
    error_log("Note: Default admin user might already exist: " . $e->getMessage());
}

// Seed settings defaults
try {
    // Local webhook poll key (used to run the poller without a session, e.g. cron)
    // Only generate if missing or blank; never overwrite an existing non-empty key.
    $generatedLocalKey = bin2hex(random_bytes(16)); // 32 hex chars
    $stmt2 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            setting_value = CASE WHEN setting_value IS NULL OR setting_value = '' THEN VALUES(setting_value) ELSE setting_value END,
            category = VALUES(category)");
    $stmt2->execute(['sms_webhook_local_key', $generatedLocalKey, 'sms']);
} catch (PDOException $e) {
    error_log("Note: Could not seed settings defaults: " . $e->getMessage());
}
?>
