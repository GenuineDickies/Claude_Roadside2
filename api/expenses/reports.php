<?php
// Expense reporting handlers — included by api/expenses.php
switch ($action) {

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

    case 'dashboard':
        $month = date('m');
        $year = date('Y');

        $mExp = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM expenses WHERE YEAR(expense_date)=? AND MONTH(expense_date)=? AND status!='voided'");
        $mExp->execute([$year, $month]);
        $monthExpenses = $mExp->fetchColumn();

        $lastM = date('m', strtotime('-1 month'));
        $lastY = date('Y', strtotime('-1 month'));
        $lmExp = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM expenses WHERE YEAR(expense_date)=? AND MONTH(expense_date)=? AND status!='voided'");
        $lmExp->execute([$lastY, $lastM]);
        $lastMonthExpenses = $lmExp->fetchColumn();

        $ytdExp = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM expenses WHERE YEAR(expense_date)=? AND status!='voided'");
        $ytdExp->execute([$year]);
        $ytdExpenses = $ytdExp->fetchColumn();

        $budget = $pdo->query("SELECT COALESCE(SUM(budget_monthly),0) FROM expense_categories WHERE is_active=1")->fetchColumn();

        $topVendors = $pdo->prepare("
            SELECT vendor, SUM(total_amount) as total, COUNT(*) as count
            FROM expenses
            WHERE YEAR(expense_date)=? AND MONTH(expense_date)=? AND status!='voided'
            GROUP BY vendor ORDER BY total DESC LIMIT 5
        ");
        $topVendors->execute([$year, $month]);

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

}
