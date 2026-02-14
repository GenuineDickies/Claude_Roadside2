<?php
/**
 * Expenses Schema — tables and seed data
 * Auto-bootstraps on first require — safe to call repeatedly.
 */

function bootstrap_expenses_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS expense_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        icon VARCHAR(50) DEFAULT 'fas fa-tag',
        color VARCHAR(20) DEFAULT '#8A92A6',
        budget_monthly DECIMAL(10,2) DEFAULT 0.00,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        vendor VARCHAR(100) NOT NULL,
        description TEXT,
        amount DECIMAL(10,2) NOT NULL,
        tax_amount DECIMAL(10,2) DEFAULT 0.00,
        total_amount DECIMAL(10,2) NOT NULL,
        expense_date DATE NOT NULL,
        payment_method ENUM('cash','card','check','ach','other') DEFAULT 'card',
        reference_number VARCHAR(50) DEFAULT NULL,
        receipt_path VARCHAR(255) DEFAULT NULL,
        is_recurring TINYINT(1) DEFAULT 0,
        recurring_frequency ENUM('weekly','biweekly','monthly','quarterly','annual') DEFAULT NULL,
        recurring_next_date DATE DEFAULT NULL,
        service_request_id INT DEFAULT NULL,
        work_order_id INT DEFAULT NULL,
        technician_id INT DEFAULT NULL,
        vehicle_tag VARCHAR(50) DEFAULT NULL,
        notes TEXT,
        is_tax_deductible TINYINT(1) DEFAULT 1,
        status ENUM('pending','approved','paid','voided') DEFAULT 'paid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_category (category_id),
        KEY idx_date (expense_date),
        KEY idx_vendor (vendor),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS expense_recurring_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_id INT NOT NULL,
        generated_expense_id INT NOT NULL,
        generated_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default categories
    $catCount = $pdo->query("SELECT COUNT(*) FROM expense_categories")->fetchColumn();
    if ($catCount == 0) {
        $cats = [
            ['Fuel & Gas', 'fas fa-gas-pump', '#F59E0B', 800.00, 1],
            ['Parts & Materials', 'fas fa-cogs', '#3B82F6', 1500.00, 2],
            ['Tools & Equipment', 'fas fa-tools', '#8B5CF6', 500.00, 3],
            ['Insurance', 'fas fa-shield-alt', '#EF4444', 600.00, 4],
            ['Vehicle Maintenance', 'fas fa-truck', '#22C55E', 400.00, 5],
            ['Subscriptions & Software', 'fas fa-laptop', '#6366F1', 200.00, 6],
            ['Office & Admin', 'fas fa-building', '#8A92A6', 150.00, 7],
            ['Marketing & Advertising', 'fas fa-bullhorn', '#EC4899', 300.00, 8],
            ['Licensing & Permits', 'fas fa-id-card', '#14B8A6', 100.00, 9],
            ['Payroll & Contractors', 'fas fa-users', '#F97316', 3000.00, 10],
            ['Other', 'fas fa-ellipsis-h', '#64748B', 200.00, 11],
        ];
        $stmt = $pdo->prepare("INSERT INTO expense_categories (name, icon, color, budget_monthly, sort_order) VALUES (?,?,?,?,?)");
        foreach ($cats as $c) { $stmt->execute($c); }
    }
}
