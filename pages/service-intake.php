<?php
/**
 * Service Request Intake Form — Orchestrator
 * 47 fields across 7 collapsible sections, Rapid Dispatch mode,
 * customer phone lookup, shorthand parser, auto-cost estimation.
 *
 * CSS → assets/css/pages/intake-*.css
 * JS  → assets/js/pages/intake-*.js
 * HTML partials → pages/service-intake/
 */

// Fetch data for dropdowns
$customersForDropdown = $pdo->query("SELECT id, first_name, last_name, phone FROM customers ORDER BY first_name")->fetchAll();
$techsForDropdown = $pdo->query("SELECT id, first_name, last_name, specialization, status FROM technicians ORDER BY first_name")->fetchAll();
?>

<link rel="stylesheet" href="assets/css/pages/intake-layout.css">
<link rel="stylesheet" href="assets/css/pages/intake-sections.css">
<link rel="stylesheet" href="assets/css/pages/intake-controls.css">

<?php include __DIR__ . '/service-intake/header.php'; ?>
<?php include __DIR__ . '/service-intake/sections-top.php'; ?>
<?php include __DIR__ . '/service-intake/sections-bottom.php'; ?>
<?php include __DIR__ . '/service-intake/sidebar.php'; ?>

<script src="assets/js/pages/intake-core.js"></script>
<script src="assets/js/pages/intake-customer.js"></script>
<script src="assets/js/pages/intake-services.js"></script>
<script src="assets/js/pages/intake-submit.js"></script>
<script src="assets/js/pages/intake-quick-entry.js"></script>
