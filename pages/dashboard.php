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

<style>
/* ── Dashboard — Scoped Styles ──────────────────────────────────────── */
.dash-header {
    background: linear-gradient(135deg, rgba(43,94,167,0.08) 0%, rgba(18,21,27,0.95) 50%, rgba(43,94,167,0.04) 100%),
                linear-gradient(180deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 20px 28px;
    margin: -28px -28px 24px -28px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.dash-header h1 { font-size: 24px; font-weight: 700; color: var(--navy-300); letter-spacing: -0.5px; margin: 0; }
.dash-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }
.dash-header .timestamp { font-size: 12px; font-family: 'JetBrains Mono', monospace; color: var(--text-tertiary); }

/* Stat grid */
.dash-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
.dash-stat {
    background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px;
    padding: 16px 18px; transition: transform 0.2s, box-shadow 0.2s;
}
.dash-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
.dash-stat .label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-tertiary); display: flex; align-items: center; gap: 6px; }
.dash-stat .value { font-size: 26px; font-weight: 700; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); margin: 4px 0 2px; }
.dash-stat .sub { font-size: 11px; color: var(--text-secondary); }
.dash-stat.navy { border-left: 4px solid var(--navy-500); }
.dash-stat.amber { border-left: 4px solid #F59E0B; }
.dash-stat.green { border-left: 4px solid #22C55E; }
.dash-stat.red { border-left: 4px solid #EF4444; }
.dash-stat.blue { border-left: 4px solid #3B82F6; }

/* P&L card */
.dash-pl-card {
    background: linear-gradient(135deg, var(--bg-surface) 0%, rgba(18,21,27,0.95) 100%);
    border: 2px solid var(--border-medium); border-radius: 14px;
    padding: 20px 24px; margin-bottom: 20px;
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; align-items: center;
}
@media (max-width: 768px) { .dash-pl-card { grid-template-columns: 1fr; } }
.dash-pl-item { text-align: center; }
.dash-pl-item .label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-tertiary); margin-bottom: 4px; }
.dash-pl-item .value { font-size: 28px; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
.dash-pl-item .sub { font-size: 11px; color: var(--text-secondary); margin-top: 2px; }
.dash-pl-divider { width: 1px; height: 50px; background: var(--border-medium); justify-self: center; }

/* Compliance alert strip */
.dash-compliance-strip {
    border-radius: 10px; padding: 14px 20px; margin-bottom: 20px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.dash-compliance-strip.ok { background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.2); }
.dash-compliance-strip.warn { background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.2); }
.dash-compliance-strip.danger { background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2); }
.dash-compliance-strip .strip-left { display: flex; align-items: center; gap: 12px; }
.dash-compliance-strip .strip-score { font-size: 20px; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
.dash-compliance-strip .strip-text { font-size: 13px; color: var(--text-primary); }
.dash-compliance-strip .strip-items { display: flex; gap: 6px; flex-wrap: wrap; }
.dash-compliance-strip .strip-item {
    font-size: 11px; padding: 3px 10px; border-radius: 4px; display: flex; align-items: center; gap: 5px;
}

/* Two-column layout */
.dash-grid { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }
@media (max-width: 1100px) { .dash-grid { grid-template-columns: 1fr; } }

/* Expense breakdown mini */
.dash-expense-item { display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border-subtle); }
.dash-expense-item:last-child { border-bottom: none; }
.dash-expense-item .cat { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-primary); }
.dash-expense-item .cat i { font-size: 12px; width: 20px; text-align: center; }
.dash-expense-item .amt { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--text-secondary); }

/* Quick actions */
.dash-quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
.dash-quick-btn {
    display: flex; align-items: center; gap: 10px; padding: 12px 16px;
    background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 8px;
    color: var(--text-primary); text-decoration: none; font-size: 13px; font-weight: 600;
    transition: all 0.2s;
}
.dash-quick-btn:hover { border-color: var(--navy-500); background: rgba(43,94,167,0.06); color: var(--navy-300); transform: translateY(-1px); }
.dash-quick-btn i { font-size: 16px; color: var(--navy-300); }
</style>

<!-- Header -->
<div class="dash-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-tachometer-alt" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Command Center</h1>
            <p class="subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?> — here's your operation at a glance</p>
        </div>
    </div>
    <div class="timestamp"><?php echo date('l, M j, Y — g:i A'); ?></div>
</div>

<!-- Compliance Reminder Strip -->
<?php
$stripClass = 'ok';
$stripIcon = 'fa-check-circle';
$stripColor = '#22C55E';
$stripText = 'All documents & licenses look good';
if ($complianceNeedsAttention > 0) {
    $stripClass = 'warn'; $stripIcon = 'fa-bell'; $stripColor = '#FBBF24';
    $parts = [];
    if ($complianceMissing > 0) $parts[] = $complianceMissing . ' missing';
    if ($complianceExpired > 0) $parts[] = $complianceExpired . ' expired';
    if ($complianceExpiring > 0) $parts[] = $complianceExpiring . ' expiring soon';
    $stripText = 'Heads up: ' . implode(', ', $parts) . ' — take a look when you get a chance';
}
?>
<div class="dash-compliance-strip <?= $stripClass ?>">
    <div class="strip-left">
        <i class="fas <?= $stripIcon ?>" style="font-size:18px;color:<?= $stripColor ?>"></i>
        <div>
            <div class="strip-text"><strong>Documents:</strong> <?= $stripText ?></div>
        </div>
    </div>
    <div class="strip-items">
        <?php foreach (array_slice($complianceAlerts, 0, 3) as $alert): ?>
            <?php
            $catIcons = ['license' => 'fa-id-badge', 'permit' => 'fa-file-alt', 'certification' => 'fa-certificate', 'insurance' => 'fa-shield-alt', 'registration' => 'fa-clipboard-list', 'inspection' => 'fa-clipboard-check', 'other' => 'fa-file'];
            $aIcon = $catIcons[$alert['category']] ?? 'fa-file';
            $aColor = $alert['alert_type'] === 'missing' ? 'rgba(148,163,184,0.1);color:#94A3B8' :
                      ($alert['alert_type'] === 'expired' ? 'rgba(239,68,68,0.1);color:#EF4444' : 'rgba(245,158,11,0.1);color:#FBBF24');
            ?>
            <span class="strip-item" style="background:<?= $aColor ?>">
                <i class="fas <?= $aIcon ?>" style="font-size:10px"></i>
                <?= htmlspecialchars($alert['name']) ?>
                <?php if ($alert['alert_type'] === 'missing'): ?>
                    (need)
                <?php elseif ($alert['days_left'] !== null): ?>
                    (<?= $alert['days_left'] < 0 ? abs($alert['days_left']) . 'd ago' : $alert['days_left'] . 'd left' ?>)
                <?php endif; ?>
            </span>
        <?php endforeach; ?>
        <a href="?page=compliance" class="btn btn-sm btn-outline-primary" style="font-size:11px">View All</a>
    </div>
</div>

<!-- P&L Summary -->
<div class="dash-pl-card">
    <div class="dash-pl-item">
        <div class="label"><i class="fas fa-arrow-up" style="color:#22C55E"></i> Revenue (30 Days)</div>
        <div class="value" style="color:#22C55E"><?= format_currency($totalRevenue) ?></div>
        <div class="sub">From paid invoices</div>
    </div>
    <div class="dash-pl-item">
        <div class="label"><i class="fas fa-arrow-down" style="color:#EF4444"></i> Expenses (This Month)</div>
        <div class="value" style="color:#EF4444"><?= format_currency($monthExpenses) ?></div>
        <div class="sub">All operating costs</div>
    </div>
    <div class="dash-pl-item">
        <div class="label"><i class="fas fa-chart-line" style="color:<?= $profit30 >= 0 ? '#22C55E' : '#EF4444' ?>"></i> Net Profit</div>
        <div class="value" style="color:<?= $profit30 >= 0 ? '#22C55E' : '#EF4444' ?>"><?= format_currency($profit30) ?></div>
        <div class="sub">Revenue minus expenses</div>
    </div>
</div>

<!-- Operations Stats -->
<div class="dash-stats">
    <div class="dash-stat navy">
        <div class="label"><i class="fas fa-users"></i> Customers</div>
        <div class="value"><?= $stats['total_customers'] ?></div>
        <div class="sub">Total in database</div>
    </div>
    <div class="dash-stat amber">
        <div class="label"><i class="fas fa-clipboard-list"></i> Active Jobs</div>
        <div class="value"><?= $stats['active_requests'] ?></div>
        <div class="sub">Pending, assigned, in-progress</div>
    </div>
    <div class="dash-stat green">
        <div class="label"><i class="fas fa-user-cog"></i> Techs Available</div>
        <div class="value"><?= $stats['available_technicians'] ?></div>
        <div class="sub">Ready to dispatch</div>
    </div>
    <div class="dash-stat blue">
        <div class="label"><i class="fas fa-file-invoice-dollar"></i> Unpaid Invoices</div>
        <div class="value"><?= $stats['pending_invoices'] ?></div>
        <div class="sub">Draft or sent</div>
    </div>
</div>

<!-- Two Column: Recent Requests + Sidebar -->
<div class="dash-grid">
    <!-- LEFT: Recent Requests -->
    <div>
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
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $request): ?>
                                    <tr>
                                        <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)"><?= htmlspecialchars($request['ticket_number']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?></strong><br>
                                            <small style="font-family:'JetBrains Mono',monospace;color:var(--text-tertiary)"><?= htmlspecialchars(format_phone($request['phone'])) ?></small>
                                        </td>
                                        <td><?= ucfirst(str_replace('_', ' ', $request['service_category'])) ?></td>
                                        <td><?= get_status_badge($request['status']) ?></td>
                                        <td><?= get_priority_badge($request['priority']) ?></td>
                                        <td style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-tertiary)"><?= format_datetime($request['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Sidebar -->
    <div>
        <!-- Quick Actions -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body" style="padding:12px">
                <div class="dash-quick-actions">
                    <a href="?page=service-intake" class="dash-quick-btn"><i class="fas fa-plus-circle"></i> New Request</a>
                    <a href="?page=customers&action=add" class="dash-quick-btn"><i class="fas fa-user-plus"></i> Add Customer</a>
                    <a href="?page=expenses" class="dash-quick-btn"><i class="fas fa-wallet"></i> Log Expense</a>
                    <a href="?page=compliance" class="dash-quick-btn"><i class="fas fa-file-alt"></i> Documents</a>
                </div>
            </div>
        </div>

        <!-- Expense Breakdown -->
        <div class="card" style="margin-bottom:16px">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-wallet"></i> Expenses This Month</h5>
                <a href="?page=expenses" class="btn btn-sm btn-outline-primary" style="font-size:11px">Details</a>
            </div>
            <div class="card-body">
                <?php if (empty($topExpenseCats) || $monthExpenses == 0): ?>
                    <p style="font-size:12px;color:var(--text-secondary);text-align:center;padding:12px">No expenses recorded this month.<br><a href="?page=expenses">Start tracking</a></p>
                <?php else: ?>
                    <?php foreach ($topExpenseCats as $cat): ?>
                        <?php if ($cat['spent'] > 0): ?>
                        <div class="dash-expense-item">
                            <div class="cat"><i class="<?= $cat['icon'] ?>" style="color:<?= $cat['color'] ?>"></i> <?= htmlspecialchars($cat['name']) ?></div>
                            <div class="amt"><?= format_currency($cat['spent']) ?></div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div style="text-align:right;margin-top:8px;padding-top:8px;border-top:1px solid var(--border-medium)">
                        <span style="font-size:12px;color:var(--text-tertiary)">Total:</span>
                        <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700;color:var(--text-primary)"><?= format_currency($monthExpenses) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- YTD Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar-alt"></i> <?= date('Y') ?> Year-to-Date</h5>
            </div>
            <div class="card-body">
                <div class="dash-expense-item">
                    <div class="cat"><i class="fas fa-arrow-up" style="color:#22C55E"></i> Total Revenue</div>
                    <div class="amt" style="color:#22C55E;font-weight:600"><?= format_currency($totalRevenue) ?></div>
                </div>
                <div class="dash-expense-item">
                    <div class="cat"><i class="fas fa-arrow-down" style="color:#EF4444"></i> Total Expenses</div>
                    <div class="amt" style="color:#EF4444;font-weight:600"><?= format_currency($ytdExpenses) ?></div>
                </div>
                <div style="text-align:right;margin-top:8px;padding-top:8px;border-top:1px solid var(--border-medium)">
                    <span style="font-size:12px;color:var(--text-tertiary)">Net:</span>
                    <?php $ytdNet = $totalRevenue - $ytdExpenses; ?>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700;color:<?= $ytdNet >= 0 ? '#22C55E' : '#EF4444' ?>"><?= format_currency($ytdNet) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
