<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Simple routing
$page = $_GET['page'] ?? 'dashboard';

// Check if user is logged in (simplified for MVP)
if (!isset($_SESSION['user_id']) && $page !== 'login') {
    header('Location: ?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadside Assistance Admin Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="?page=dashboard">
                    <i class="fas fa-tools"></i> Roadside Assistance Admin
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="?page=dashboard">Dashboard</a>
                    <a class="nav-link" href="?page=customers">Customers</a>
                    <a class="nav-link" href="?page=service-requests">Service Requests</a>
                    <a class="nav-link" href="?page=technicians">Technicians</a>
                    <a class="nav-link" href="?page=invoices">Invoices</a>
                    <a class="nav-link" href="?page=services">
                        <i class="fas fa-wrench"></i> Services
                    </a>
                    <a class="nav-link" href="?page=logout">Logout</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <div class="container-fluid mt-3">
        <?php
        // Simple routing system
        switch ($page) {
            case 'login':
                include 'pages/login.php';
                break;
            case 'logout':
                include 'pages/logout.php';
                break;
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'customers':
                include 'pages/customers.php';
                break;
            case 'service-requests':
                include 'pages/service-requests.php';
                break;
            case 'technicians':
                include 'pages/technicians.php';
                break;
            case 'invoices':
                include 'pages/invoices.php';
                break;
            case 'services':
                include 'services.php';
                break;
            default:
                include 'pages/dashboard.php';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
