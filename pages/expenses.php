<?php
/**
 * Expenses Page — Track all business expenses
 * Category breakdown, budget vs actual, recurring, tax deductions
 */

// Fetch categories for filters/form
$expenseCats = [];
try {
    $pdo->exec("SELECT 1 FROM expense_categories LIMIT 1");
    $expenseCats = $pdo->query("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
} catch (Exception $e) {
    // Tables don't exist yet — will be created on first API call
}

// Get technicians for linking
$techsList = $pdo->query("SELECT id, first_name, last_name FROM technicians ORDER BY first_name")->fetchAll();
?>

<link rel="stylesheet" href="assets/css/pages/expenses.css">

<?php include __DIR__ . '/expenses/content.php'; ?>
<?php include __DIR__ . '/expenses/modal.php'; ?>

<script src="assets/js/pages/expenses.js"></script>
