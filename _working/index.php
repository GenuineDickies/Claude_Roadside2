<?php
// Use local session directory to avoid permission issues
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) { mkdir($sessionDir, 0755, true); }
session_save_path($sessionDir);
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

// Handle logout before rendering
if ($page === 'logout') {
    include 'pages/logout.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoadRunner — <?php echo ucfirst(str_replace('-', ' ', $page)); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <button class="rr-sidebar-toggle" onclick="document.querySelector('.rr-sidebar').classList.toggle('open')">
        <i class="fas fa-bars"></i>
    </button>
    <div class="rr-app">
        <aside class="rr-sidebar">
            <div class="rr-sidebar-brand">
                <div class="brand-icon"><i class="fas fa-road"></i></div>
                <div>
                    <div class="brand-text">RoadRunner</div>
                    <div class="brand-sub">Command Center</div>
                </div>
            </div>
            <nav class="rr-nav">
                <div class="rr-nav-section">Operations</div>
                <a class="rr-nav-link <?php echo $page==='dashboard'?'active':''; ?>" href="?page=dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="rr-nav-link <?php echo $page==='service-requests'?'active':''; ?>" href="?page=service-requests">
                    <i class="fas fa-clipboard-list"></i> Service Requests
                </a>
                <a class="rr-nav-link <?php echo $page==='customers'?'active':''; ?>" href="?page=customers">
                    <i class="fas fa-users"></i> Customers
                </a>
                <a class="rr-nav-link <?php echo $page==='technicians'?'active':''; ?>" href="?page=technicians">
                    <i class="fas fa-user-cog"></i> Technicians
                </a>

                <div class="rr-nav-section">Finance</div>
                <a class="rr-nav-link <?php echo $page==='invoices'?'active':''; ?>" href="?page=invoices">
                    <i class="fas fa-file-invoice-dollar"></i> Invoices
                </a>

                <div class="rr-nav-section">Catalog</div>
                <a class="rr-nav-link <?php echo $page==='services'?'active':''; ?>" href="?page=services">
                    <i class="fas fa-wrench"></i> Services
                </a>

                <div class="rr-nav-section">System</div>
                <a class="rr-nav-link <?php echo $page==='director'?'active':''; ?>" href="?page=director">
                    <i class="fas fa-satellite-dish"></i> Director
                </a>
            </nav>
            <div class="rr-nav-footer">
                <a class="rr-nav-link" href="?page=logout">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </aside>

        <main class="rr-main">
            <div class="rr-content">
                <?php
                switch ($page) {
                    case 'dashboard':   include 'pages/dashboard.php'; break;
                    case 'customers':   include 'pages/customers.php'; break;
                    case 'service-requests': include 'pages/service-requests.php'; break;
                    case 'technicians': include 'pages/technicians.php'; break;
                    case 'invoices':    include 'pages/invoices.php'; break;
                    case 'services':    include 'services.php'; break;
                    case 'director':    include 'pages/director.php'; break;
                    default:            include 'pages/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>
    <?php else: ?>
        <?php include 'pages/login.php'; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
