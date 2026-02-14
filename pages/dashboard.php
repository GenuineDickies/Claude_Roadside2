<?php
// Get dashboard statistics
$stats = [];
$stats['total_customers'] = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$stats['active_requests'] = $pdo->query("SELECT COUNT(*) FROM service_tickets WHERE status IN ('created', 'dispatched', 'acknowledged', 'en_route', 'on_scene', 'in_progress')")->fetchColumn();
$stats['available_technicians'] = $pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'available'")->fetchColumn();
$stats['pending_invoices'] = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status IN ('draft', 'sent')")->fetchColumn();

// Get recent service requests
$recentRequests = $pdo->query("
    SELECT st.*, c.first_name, c.last_name, c.phone, t.first_name as tech_first_name, t.last_name as tech_last_name
    FROM service_tickets st
    LEFT JOIN customers c ON st.customer_id = c.id
    LEFT JOIN technicians t ON st.technician_id = t.id
    ORDER BY st.created_at DESC
    LIMIT 10
")->fetchAll();

// ── Revenue data ──────────────────────────────────────────────────
$revenue30 = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total
    FROM invoices
    WHERE status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch()['total'];

// Try workflow invoices too
$revenueV2 = 0;
try {
    $revenueV2 = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0)
        FROM invoices_v2
        WHERE status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn();
} catch (Exception $e) {}
$totalRevenue = $revenue30 + $revenueV2;

// ── Expense data ──────────────────────────────────────────────────
$monthExpenses = 0;
$ytdExpenses = 0;
$topExpenseCats = [];
try {
    $monthExpenses = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) FROM expenses
        WHERE YEAR(expense_date) = YEAR(CURDATE()) AND MONTH(expense_date) = MONTH(CURDATE())
        AND status != 'voided'
    ")->fetchColumn();
    $ytdExpenses = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) FROM expenses
        WHERE YEAR(expense_date) = YEAR(CURDATE()) AND status != 'voided'
    ")->fetchColumn();
    $topExpenseCats = $pdo->query("
        SELECT ec.name, ec.icon, ec.color, COALESCE(SUM(e.total_amount), 0) as spent
        FROM expense_categories ec
        LEFT JOIN expenses e ON e.category_id = ec.id
            AND YEAR(e.expense_date) = YEAR(CURDATE()) AND MONTH(e.expense_date) = MONTH(CURDATE())
            AND e.status != 'voided'
        WHERE ec.is_active = 1
        GROUP BY ec.id ORDER BY spent DESC LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {}

$profit30 = $totalRevenue - $monthExpenses;

// ── Compliance data (simple document tracker) ────────────────────
$complianceMissing = 0;
$complianceExpired = 0;
$complianceExpiring = 0;
$complianceAlerts = [];
try {
    $complianceMissing = $pdo->query("SELECT COUNT(*) FROM compliance_items WHERE have_it = 0")->fetchColumn();
    $complianceExpired = $pdo->query("
        SELECT COUNT(*) FROM compliance_items
        WHERE have_it = 1 AND expiry_date IS NOT NULL AND expiry_date < CURDATE()
    ")->fetchColumn();
    $complianceExpiring = $pdo->query("
        SELECT COUNT(*) FROM compliance_items
        WHERE have_it = 1 AND expiry_date IS NOT NULL AND expiry_date >= CURDATE()
        AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL remind_days_before DAY)
    ")->fetchColumn();

    // Items needing attention
    $complianceAlerts = $pdo->query("
        SELECT name, category, expiry_date, have_it,
               DATEDIFF(expiry_date, CURDATE()) as days_left,
               CASE WHEN have_it = 0 THEN 'missing'
                    WHEN expiry_date < CURDATE() THEN 'expired'
                    ELSE 'expiring' END as alert_type
        FROM compliance_items
        WHERE have_it = 0
           OR (have_it = 1 AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL remind_days_before DAY))
        ORDER BY CASE WHEN have_it = 0 THEN 1 WHEN expiry_date < CURDATE() THEN 2 ELSE 3 END,
                 expiry_date ASC
        LIMIT 5
    ")->fetchAll();
} catch (Exception $e) {}
$complianceNeedsAttention = $complianceMissing + $complianceExpired + $complianceExpiring;
?>

<link rel="stylesheet" href="assets/css/pages/dashboard.css">

<?php include __DIR__ . '/dashboard/header.php'; ?>
<?php include __DIR__ . '/dashboard/stats.php'; ?>

<!-- Two Column: Recent Requests + Sidebar -->
<div class="dash-grid">
    <?php include __DIR__ . '/dashboard/main-content.php'; ?>
    <?php include __DIR__ . '/dashboard/sidebar.php'; ?>
</div>
