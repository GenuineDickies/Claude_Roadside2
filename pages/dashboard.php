<?php
// Get dashboard statistics
$stats = [];
$stats['total_customers'] = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$stats['active_requests'] = $pdo->query("SELECT COUNT(*) FROM service_requests WHERE status IN ('pending', 'assigned', 'in_progress')")->fetchColumn();
$stats['available_technicians'] = $pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'available'")->fetchColumn();
$stats['pending_invoices'] = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status IN ('draft', 'sent')")->fetchColumn();

// Get recent service requests
$recentRequests = $pdo->query("
    SELECT sr.*, c.first_name, c.last_name, c.phone, t.first_name as tech_first_name, t.last_name as tech_last_name
    FROM service_requests sr
    LEFT JOIN customers c ON sr.customer_id = c.id
    LEFT JOIN technicians t ON sr.technician_id = t.id
    ORDER BY sr.created_at DESC
    LIMIT 10
")->fetchAll();

// Get revenue data (last 30 days)
$revenue = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0) as total
    FROM invoices 
    WHERE status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch();
?>

<div class="rr-page-header" style="margin: -28px -28px 24px -28px;">
    <div class="header-left">
        <i class="fas fa-tachometer-alt header-icon"></i>
        <div>
            <h1>Dashboard</h1>
            <div class="header-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="rr-stats">
    <div class="rr-stat navy">
        <i class="fas fa-users rr-stat-icon"></i>
        <div class="rr-stat-label">Total Customers</div>
        <div class="rr-stat-value"><?php echo $stats['total_customers']; ?></div>
    </div>
    <div class="rr-stat amber">
        <i class="fas fa-clipboard-list rr-stat-icon"></i>
        <div class="rr-stat-label">Active Requests</div>
        <div class="rr-stat-value"><?php echo $stats['active_requests']; ?></div>
    </div>
    <div class="rr-stat green">
        <i class="fas fa-user-cog rr-stat-icon"></i>
        <div class="rr-stat-label">Available Technicians</div>
        <div class="rr-stat-value"><?php echo $stats['available_technicians']; ?></div>
    </div>
    <div class="rr-stat blue">
        <i class="fas fa-dollar-sign rr-stat-icon"></i>
        <div class="rr-stat-label">Revenue (30 Days)</div>
        <div class="rr-stat-value" style="font-size: 24px;"><?php echo format_currency($revenue['total']); ?></div>
    </div>
</div>

<!-- Recent Service Requests -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-clock"></i> Recent Service Requests</h5>
                <a href="?page=service-requests" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentRequests)): ?>
                    <div class="rr-empty">
                        <i class="fas fa-clipboard-list"></i>
                        No service requests found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Service Type</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Technician</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $request): ?>
                                    <tr class="priority-<?php echo $request['priority']; ?>">
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['phone']); ?></small>
                                        </td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $request['service_type'])); ?></td>
                                        <td><?php echo get_status_badge($request['status']); ?></td>
                                        <td><?php echo get_priority_badge($request['priority']); ?></td>
                                        <td>
                                            <?php if ($request['tech_first_name']): ?>
                                                <?php echo htmlspecialchars($request['tech_first_name'] . ' ' . $request['tech_last_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo format_datetime($request['created_at']); ?></td>
                                        <td>
                                            <a href="?page=service-requests&action=view&id=<?php echo $request['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
