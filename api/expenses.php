<?php
/**
 * Expenses API — Track all business expenses
 * Categories: fuel, parts, tools, insurance, vehicle_maintenance,
 *             subscriptions, office, marketing, licensing, other
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// ── Bootstrap Tables ────────────────────────────────────────────────
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

// ── Seed Default Categories ────────────────────────────────────────
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

// ── Route ───────────────────────────────────────────────────────────
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {

        // ── List Categories ─────────────────────────────────────
        case 'categories':
            $rows = $pdo->query("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        // ── List Expenses (filtered) ────────────────────────────
        case 'list':
            $where = ['1=1'];
            $params = [];

            if (!empty($_GET['category_id'])) {
                $where[] = 'e.category_id = ?';
                $params[] = $_GET['category_id'];
            }
            if (!empty($_GET['vendor'])) {
                $where[] = 'e.vendor LIKE ?';
                $params[] = '%' . $_GET['vendor'] . '%';
            }
            if (!empty($_GET['date_from'])) {
                $where[] = 'e.expense_date >= ?';
                $params[] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $where[] = 'e.expense_date <= ?';
                $params[] = $_GET['date_to'];
            }
            if (!empty($_GET['status'])) {
                $where[] = 'e.status = ?';
                $params[] = $_GET['status'];
            }
            if (!empty($_GET['payment_method'])) {
                $where[] = 'e.payment_method = ?';
                $params[] = $_GET['payment_method'];
            }

            $whereClause = implode(' AND ', $where);
            $limit = min((int)($_GET['limit'] ?? 50), 200);
            $offset = max((int)($_GET['offset'] ?? 0), 0);

            $stmt = $pdo->prepare("
                SELECT e.*, ec.name as category_name, ec.icon as category_icon, ec.color as category_color
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                WHERE $whereClause
                ORDER BY e.expense_date DESC, e.id DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();

            // Also get totals for the filtered set
            $stmtTotal = $pdo->prepare("
                SELECT COUNT(*) as count, COALESCE(SUM(e.total_amount),0) as total
                FROM expenses e WHERE $whereClause
            ");
            $stmtTotal->execute($params);
            $totals = $stmtTotal->fetch();

            echo json_encode(['success' => true, 'data' => $rows, 'totals' => $totals]);
            break;

        // ── Create Expense ──────────────────────────────────────
        case 'create':
            if ($method !== 'POST') { throw new Exception('POST required'); }

            $required = ['category_id', 'vendor', 'amount', 'expense_date'];
            foreach ($required as $f) {
                if (empty($_POST[$f])) throw new Exception("Missing: $f");
            }

            $amount = floatval($_POST['amount']);
            $tax = floatval($_POST['tax_amount'] ?? 0);
            $total = $amount + $tax;

            $stmt = $pdo->prepare("INSERT INTO expenses
                (category_id, vendor, description, amount, tax_amount, total_amount, expense_date,
                 payment_method, reference_number, is_recurring, recurring_frequency, recurring_next_date,
                 service_request_id, work_order_id, technician_id, vehicle_tag, notes, is_tax_deductible, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $_POST['category_id'],
                trim($_POST['vendor']),
                trim($_POST['description'] ?? ''),
                $amount,
                $tax,
                $total,
                $_POST['expense_date'],
                $_POST['payment_method'] ?? 'card',
                $_POST['reference_number'] ?? null,
                !empty($_POST['is_recurring']) ? 1 : 0,
                $_POST['recurring_frequency'] ?? null,
                $_POST['recurring_next_date'] ?? null,
                $_POST['service_request_id'] ?? null,
                $_POST['work_order_id'] ?? null,
                $_POST['technician_id'] ?? null,
                $_POST['vehicle_tag'] ?? null,
                trim($_POST['notes'] ?? ''),
                isset($_POST['is_tax_deductible']) ? (int)$_POST['is_tax_deductible'] : 1,
                $_POST['status'] ?? 'paid',
            ]);

            echo json_encode(['success' => true, 'data' => ['id' => $pdo->lastInsertId()]]);
            break;

        // ── Update Expense ──────────────────────────────────────
        case 'update':
            if ($method !== 'POST') { throw new Exception('POST required'); }
            if (empty($_POST['id'])) { throw new Exception('Missing: id'); }

            $amount = floatval($_POST['amount']);
            $tax = floatval($_POST['tax_amount'] ?? 0);
            $total = $amount + $tax;

            $stmt = $pdo->prepare("UPDATE expenses SET
                category_id=?, vendor=?, description=?, amount=?, tax_amount=?, total_amount=?,
                expense_date=?, payment_method=?, reference_number=?, is_recurring=?,
                recurring_frequency=?, recurring_next_date=?, service_request_id=?,
                work_order_id=?, technician_id=?, vehicle_tag=?, notes=?, is_tax_deductible=?, status=?
                WHERE id=?
            ");
            $stmt->execute([
                $_POST['category_id'],
                trim($_POST['vendor']),
                trim($_POST['description'] ?? ''),
                $amount,
                $tax,
                $total,
                $_POST['expense_date'],
                $_POST['payment_method'] ?? 'card',
                $_POST['reference_number'] ?? null,
                !empty($_POST['is_recurring']) ? 1 : 0,
                $_POST['recurring_frequency'] ?? null,
                $_POST['recurring_next_date'] ?? null,
                $_POST['service_request_id'] ?? null,
                $_POST['work_order_id'] ?? null,
                $_POST['technician_id'] ?? null,
                $_POST['vehicle_tag'] ?? null,
                trim($_POST['notes'] ?? ''),
                isset($_POST['is_tax_deductible']) ? (int)$_POST['is_tax_deductible'] : 1,
                $_POST['status'] ?? 'paid',
                $_POST['id'],
            ]);

            echo json_encode(['success' => true]);
            break;

        // ── Delete Expense ──────────────────────────────────────
        case 'delete':
            if ($method !== 'POST') { throw new Exception('POST required'); }
            if (empty($_POST['id'])) { throw new Exception('Missing: id'); }

            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => true]);
            break;

        // ── Monthly Summary ─────────────────────────────────────
        case 'summary':
            $year = $_GET['year'] ?? date('Y');
            $month = $_GET['month'] ?? date('m');

            // By category
            $stmt = $pdo->prepare("
                SELECT ec.id, ec.name, ec.icon, ec.color, ec.budget_monthly,
                       COALESCE(SUM(e.total_amount), 0) as spent,
                       COUNT(e.id) as transaction_count
                FROM expense_categories ec
                LEFT JOIN expenses e ON e.category_id = ec.id
                    AND YEAR(e.expense_date) = ? AND MONTH(e.expense_date) = ?
                    AND e.status != 'voided'
                WHERE ec.is_active = 1
                GROUP BY ec.id
                ORDER BY ec.sort_order
            ");
            $stmt->execute([$year, $month]);
            $byCategory = $stmt->fetchAll();

            // Monthly totals (last 12 months)
            $monthlyTotals = $pdo->query("
                SELECT DATE_FORMAT(expense_date, '%Y-%m') as month,
                       COALESCE(SUM(total_amount), 0) as total
                FROM expenses
                WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                  AND status != 'voided'
                GROUP BY month
                ORDER BY month
            ")->fetchAll();

            // YTD total
            $ytd = $pdo->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM expenses
                WHERE YEAR(expense_date) = ? AND status != 'voided'
            ");
            $ytd->execute([$year]);
            $ytdTotal = $ytd->fetch()['total'];

            // This month total
            $thisMonth = $pdo->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM expenses
                WHERE YEAR(expense_date) = ? AND MONTH(expense_date) = ? AND status != 'voided'
            ");
            $thisMonth->execute([$year, $month]);
            $monthTotal = $thisMonth->fetch()['total'];

            // Total budget
            $budgetTotal = $pdo->query("SELECT COALESCE(SUM(budget_monthly), 0) FROM expense_categories WHERE is_active=1")->fetchColumn();

            // Tax deductible total this year
            $taxDeductible = $pdo->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM expenses
                WHERE YEAR(expense_date) = ? AND is_tax_deductible = 1 AND status != 'voided'
            ");
            $taxDeductible->execute([$year]);
            $taxTotal = $taxDeductible->fetch()['total'];

            echo json_encode(['success' => true, 'data' => [
                'by_category' => $byCategory,
                'monthly_totals' => $monthlyTotals,
                'ytd_total' => floatval($ytdTotal),
                'month_total' => floatval($monthTotal),
                'budget_total' => floatval($budgetTotal),
                'tax_deductible_ytd' => floatval($taxTotal),
            ]]);
            break;

        // ── Recurring expenses check ────────────────────────────
        case 'check_recurring':
            $due = $pdo->query("
                SELECT e.*, ec.name as category_name
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                WHERE e.is_recurring = 1 AND e.recurring_next_date <= CURDATE()
                  AND e.status != 'voided'
            ")->fetchAll();
            echo json_encode(['success' => true, 'data' => $due]);
            break;

        // ── Dashboard widget data ───────────────────────────────
        case 'dashboard':
            $month = date('m');
            $year = date('Y');

            // This month expenses
            $mExp = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM expenses WHERE YEAR(expense_date)=? AND MONTH(expense_date)=? AND status!='voided'");
            $mExp->execute([$year, $month]);
            $monthExpenses = $mExp->fetchColumn();

            // Last month expenses
            $lastM = date('m', strtotime('-1 month'));
            $lastY = date('Y', strtotime('-1 month'));
            $lmExp = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM expenses WHERE YEAR(expense_date)=? AND MONTH(expense_date)=? AND status!='voided'");
            $lmExp->execute([$lastY, $lastM]);
            $lastMonthExpenses = $lmExp->fetchColumn();

            // YTD
            $ytdExp = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM expenses WHERE YEAR(expense_date)=? AND status!='voided'");
            $ytdExp->execute([$year]);
            $ytdExpenses = $ytdExp->fetchColumn();

            // Budget vs actual this month
            $budget = $pdo->query("SELECT COALESCE(SUM(budget_monthly),0) FROM expense_categories WHERE is_active=1")->fetchColumn();

            // Top 5 vendors this month
            $topVendors = $pdo->prepare("
                SELECT vendor, SUM(total_amount) as total, COUNT(*) as count
                FROM expenses
                WHERE YEAR(expense_date)=? AND MONTH(expense_date)=? AND status!='voided'
                GROUP BY vendor ORDER BY total DESC LIMIT 5
            ");
            $topVendors->execute([$year, $month]);

            // Upcoming recurring
            $recurring = $pdo->query("
                SELECT e.vendor, e.total_amount, e.recurring_next_date, ec.name as category_name
                FROM expenses e
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                WHERE e.is_recurring = 1 AND e.status != 'voided'
                ORDER BY e.recurring_next_date ASC LIMIT 5
            ")->fetchAll();

            echo json_encode(['success' => true, 'data' => [
                'month_expenses' => floatval($monthExpenses),
                'last_month_expenses' => floatval($lastMonthExpenses),
                'ytd_expenses' => floatval($ytdExpenses),
                'month_budget' => floatval($budget),
                'top_vendors' => $topVendors->fetchAll(),
                'upcoming_recurring' => $recurring,
            ]]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
