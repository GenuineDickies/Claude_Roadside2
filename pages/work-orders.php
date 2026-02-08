<?php
/**
 * Work Order Tracker — Labor clock, progress log, line items, change orders, completion
 * Parent: Estimate (optional) → Service Request
 */
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load WO for view/edit
$wo = null; $lineItems = []; $progressLog = []; $changeOrders = []; $srData = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.customer_phone, st.service_address, st.service_category, st.vehicle_year, st.vehicle_make, st.vehicle_model, st.issue_description, e.estimate_id as est_doc_id, e.diagnosis_summary FROM work_orders wo LEFT JOIN technicians t ON wo.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id LEFT JOIN estimates e ON wo.estimate_id = e.id WHERE wo.id = ?");
    $stmt->execute([$id]);
    $wo = $stmt->fetch();
    if ($wo) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type='work_order' AND document_id=? ORDER BY line_number");
        $liStmt->execute([$id]);
        $lineItems = $liStmt->fetchAll();
        $logStmt = $pdo->prepare("SELECT wpl.*, t.first_name, t.last_name FROM work_order_progress_log wpl LEFT JOIN technicians t ON wpl.technician_id = t.id WHERE wpl.work_order_id = ? ORDER BY wpl.logged_at DESC LIMIT 50");
        $logStmt->execute([$id]);
        $progressLog = $logStmt->fetchAll();
        $coStmt = $pdo->prepare("SELECT * FROM change_orders WHERE work_order_id = ? ORDER BY sequence_num");
        $coStmt->execute([$id]);
        $changeOrders = $coStmt->fetchAll();
    }
}

// List
$workOrders = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND wo.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.service_category FROM work_orders wo LEFT JOIN technicians t ON wo.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE {$where} ORDER BY wo.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $workOrders = $stmt->fetchAll();
}
$technicians = $pdo->query("SELECT id, first_name, last_name, specialization FROM technicians ORDER BY first_name")->fetchAll();
?>

<style>
/* ── Work Order — Scoped Styles ──────────────────────────────────── */
.wo-header { background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%); border-bottom: 2px solid var(--navy-500); padding: 20px 28px; margin: -28px -28px 24px -28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.wo-header h1 { font-size: 24px; font-weight: 700; color: var(--navy-300); letter-spacing: -0.5px; margin: 0; }
.wo-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

/* Lifecycle bar */
.wo-lifecycle { display: flex; gap: 4px; margin-bottom: 20px; }
.wo-lifecycle-step { flex: 1; padding: 8px 12px; text-align: center; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 6px; color: var(--text-tertiary); position: relative; }
.wo-lifecycle-step.active { background: rgba(43,94,167,0.12); border-color: var(--navy-500); color: var(--navy-300); }
.wo-lifecycle-step.done { background: rgba(34,197,94,0.08); border-color: rgba(34,197,94,0.3); color: var(--green-500); }

/* Timer */
.wo-timer { display: inline-flex; align-items: center; gap: 8px; background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 8px; padding: 10px 16px; }
.wo-timer .clock { font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 600; color: var(--green-500); }
.wo-timer.paused .clock { color: var(--amber-500); }
.wo-timer-label { font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; }

/* Progress log */
.wo-log { max-height: 400px; overflow-y: auto; }
.wo-log-entry { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border-subtle); font-size: 13px; }
.wo-log-entry:last-child { border-bottom: none; }
.wo-log-icon { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 11px; }
.wo-log-icon.clock_start { background: rgba(34,197,94,0.1); color: var(--green-500); }
.wo-log-icon.clock_stop { background: rgba(239,68,68,0.1); color: var(--red-500); }
.wo-log-icon.note { background: rgba(59,130,246,0.1); color: var(--blue-500); }
.wo-log-icon.photo { background: rgba(168,85,247,0.1); color: var(--purple-500); }
.wo-log-icon.status_change { background: rgba(245,158,11,0.1); color: var(--amber-500); }
.wo-log-icon.checklist_item { background: rgba(34,197,94,0.1); color: var(--green-500); }
.wo-log-meta { font-size: 11px; color: var(--text-tertiary); margin-top: 2px; }

/* Variance badge */
.wo-variance { display: inline-block; padding: 2px 8px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 12px; font-weight: 600; }
.wo-variance.positive { background: rgba(239,68,68,0.1); color: var(--red-500); }
.wo-variance.negative { background: rgba(34,197,94,0.1); color: var(--green-500); }
.wo-variance.zero { background: rgba(59,130,246,0.1); color: var(--blue-500); }

/* Change order mini-card */
.wo-co-card { background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 8px; padding: 12px 16px; margin-bottom: 8px; }
.wo-co-card.approved { border-left: 3px solid var(--green-500); }
.wo-co-card.declined { border-left: 3px solid var(--red-500); }
.wo-co-card.proposed, .wo-co-card.presented { border-left: 3px solid var(--amber-500); }
</style>

<?php if ($action === 'list'): ?>

<!-- ═══ WORK ORDERS LIST ═════════════════════════════════════════════ -->
<div class="wo-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-clipboard-check" style="font-size:26px;color:var(--navy-300)"></i>
        <div><h1>Work Orders</h1><p class="subtitle">Track active jobs, labor, and completion</p></div>
    </div>
    <div style="display:flex;gap:8px">
        <select onchange="location.href='?page=work-orders&status='+this.value" style="padding:6px 12px;font-size:12px;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:6px;color:var(--text-primary)">
            <option value="">All Statuses</option>
            <option value="created" <?= ($_GET['status'] ?? '') === 'created' ? 'selected' : '' ?>>Created</option>
            <option value="in_progress" <?= ($_GET['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="paused" <?= ($_GET['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Paused</option>
            <option value="completed" <?= ($_GET['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0">
                <thead>
                    <tr><th>WO ID</th><th>TICKET</th><th>CUSTOMER</th><th>SERVICE</th><th>TECHNICIAN</th><th>AUTHORIZED</th><th>STATUS</th><th>CREATED</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($workOrders)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-tertiary)">No work orders found.</td></tr>
                    <?php else: foreach ($workOrders as $w): ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--navy-300)"><?= htmlspecialchars($w['work_order_id']) ?></td>
                            <td><a href="?page=service-requests&action=view&id=<?= $w['service_request_id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($w['ticket_number'] ?? '#' . $w['service_request_id']) ?></a></td>
                            <td><?= htmlspecialchars($w['customer_name'] ?? '') ?></td>
                            <td style="font-size:12px"><?= ucfirst(str_replace('_', ' ', $w['service_category'] ?? '')) ?></td>
                            <td><?= htmlspecialchars(($w['tech_first'] ?? '') . ' ' . ($w['tech_last'] ?? '')) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600">$<?= number_format($w['authorized_total'], 2) ?></td>
                            <td><?= get_status_badge($w['status']) ?></td>
                            <td style="font-size:12px;color:var(--text-secondary)"><?= format_datetime($w['created_at']) ?></td>
                            <td><a href="?page=work-orders&action=view&id=<?= $w['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'view' && $wo): ?>

<!-- ═══ VIEW WORK ORDER ══════════════════════════════════════════════ -->
<div class="wo-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-clipboard-check" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1><?= htmlspecialchars($wo['work_order_id']) ?></h1>
            <p class="subtitle"><?= htmlspecialchars(($wo['tech_first'] ?? '') . ' ' . ($wo['tech_last'] ?? '')) ?> — <?= htmlspecialchars($wo['customer_name'] ?? '') ?></p>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?= get_status_badge($wo['status']) ?>
        <a href="?page=work-orders" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<!-- Document chain -->
<div class="doc-chain" style="display:flex;align-items:center;gap:6px;margin-bottom:16px;font-size:12px">
    <a href="?page=service-requests&action=view&id=<?= $wo['service_request_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($wo['ticket_number'] ?? 'SR') ?></a>
    <?php if ($wo['estimate_id']): ?>
        <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
        <a href="?page=estimates&action=view&id=<?= $wo['estimate_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-file-invoice"></i> <?= htmlspecialchars($wo['est_doc_id'] ?? 'EST') ?></a>
    <?php endif; ?>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <span style="color:var(--text-primary);font-weight:600;padding:4px 10px;background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:4px"><i class="fas fa-clipboard-check"></i> <?= htmlspecialchars($wo['work_order_id']) ?></span>
    <?php
    $invLink = $pdo->prepare("SELECT id, invoice_id FROM invoices_v2 WHERE work_order_id = ?");
    $invLink->execute([$wo['id']]);
    $inv = $invLink->fetch();
    if ($inv): ?>
        <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
        <a href="?page=invoices-v2&action=view&id=<?= $inv['id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-file-invoice-dollar"></i> <?= htmlspecialchars($inv['invoice_id']) ?></a>
    <?php endif; ?>
</div>

<!-- Lifecycle -->
<div class="wo-lifecycle">
    <?php
    $statuses = ['created','in_progress','paused','completed'];
    foreach ($statuses as $s):
        $cls = $wo['status'] === $s ? 'active' : (array_search($s, $statuses) < array_search($wo['status'], $statuses) ? 'done' : '');
    ?>
        <div class="wo-lifecycle-step <?= $cls ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <!-- Left Column: Info + Line Items + Change Orders -->
    <div class="col-lg-8">
        <!-- Inherited info -->
        <div class="est-inherited" style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:16px 20px;margin-bottom:16px">
            <h3 style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);margin:0 0 10px"><i class="fas fa-link" style="margin-right:4px"></i> Job Details</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Customer</div><div style="font-size:13px;color:var(--text-primary);font-weight:500;margin-top:2px"><?= htmlspecialchars($wo['customer_name'] ?? '') ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Phone</div><div style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars($wo['customer_phone'] ?? '') ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Vehicle</div><div style="font-size:13px;color:var(--text-primary);font-weight:500;margin-top:2px"><?= htmlspecialchars(trim(($wo['vehicle_year'] ?? '') . ' ' . ($wo['vehicle_make'] ?? '') . ' ' . ($wo['vehicle_model'] ?? ''))) ?: '—' ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Service</div><div style="font-size:13px;color:var(--text-primary);margin-top:2px"><?= ucfirst(str_replace('_', ' ', $wo['service_category'] ?? '')) ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Location</div><div style="font-size:12px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars(substr($wo['service_address'] ?? '', 0, 50)) ?></div></div>
                <?php if ($wo['diagnosis_summary']): ?>
                <div style="grid-column:1/-1"><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Diagnosis</div><div style="font-size:12px;color:var(--text-secondary);margin-top:2px"><?= htmlspecialchars(substr($wo['diagnosis_summary'], 0, 150)) ?></div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Financials overview -->
        <div class="row g-2 mb-3">
            <div class="col-4">
                <div class="card"><div class="card-body" style="padding:12px;text-align:center">
                    <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Authorized</div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700;color:var(--navy-300)">$<?= number_format($wo['authorized_total'], 2) ?></div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card"><div class="card-body" style="padding:12px;text-align:center">
                    <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Actual</div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700;color:var(--text-primary)">$<?= number_format($wo['actual_total'] ?? 0, 2) ?></div>
                </div></div>
            </div>
            <div class="col-4">
                <div class="card"><div class="card-body" style="padding:12px;text-align:center">
                    <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Variance</div>
                    <?php
                    $vPct = $wo['variance_pct'] ?? 0;
                    $vCls = $vPct > 0 ? 'positive' : ($vPct < 0 ? 'negative' : 'zero');
                    ?>
                    <span class="wo-variance <?= $vCls ?>" style="font-size:16px"><?= $vPct > 0 ? '+' : '' ?><?= number_format($vPct, 1) ?>%</span>
                </div></div>
            </div>
        </div>

        <!-- Line Items -->
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px;margin-bottom:16px">
            <h3 style="font-size:14px;font-weight:600;margin:0 0 12px"><i class="fas fa-list" style="color:var(--navy-300);margin-right:6px"></i> Line Items</h3>
            <table class="est-line-items" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">#</th>
                        <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">TYPE</th>
                        <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">DESCRIPTION</th>
                        <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">QTY</th>
                        <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:right">EXTENDED</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lineItems)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-tertiary)">No line items.</td></tr>
                    <?php else: foreach ($lineItems as $li): ?>
                        <tr style="border-bottom:1px solid var(--border-subtle)">
                            <td style="padding:10px 12px;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)"><?= $li['line_number'] ?></td>
                            <td style="padding:10px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:3px;text-transform:uppercase;font-weight:600;background:rgba(59,130,246,0.1);color:var(--blue-500)"><?= str_replace('_', ' ', $li['item_type']) ?></span></td>
                            <td style="padding:10px 12px;font-size:13px"><?= htmlspecialchars($li['description']) ?></td>
                            <td style="padding:10px 12px;font-family:'JetBrains Mono',monospace;font-size:12px"><?= number_format($li['quantity'], 2) ?></td>
                            <td style="padding:10px 12px;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;text-align:right">$<?= number_format($li['extended_price'], 2) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Change Orders -->
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px;margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <h3 style="font-size:14px;font-weight:600;margin:0"><i class="fas fa-exchange-alt" style="color:var(--amber-500);margin-right:6px"></i> Change Orders</h3>
                <?php if (in_array($wo['status'], ['in_progress', 'paused'])): ?>
                    <a href="?page=change-orders&action=create&wo_id=<?= $wo['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-plus"></i> New CO</a>
                <?php endif; ?>
            </div>
            <?php if (empty($changeOrders)): ?>
                <p style="text-align:center;padding:20px;color:var(--text-tertiary);font-size:13px">No change orders for this work order.</p>
            <?php else: foreach ($changeOrders as $co): ?>
                <div class="wo-co-card <?= $co['status'] ?>">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <a href="?page=change-orders&action=view&id=<?= $co['id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--navy-300)"><?= htmlspecialchars($co['change_order_id']) ?></a>
                            <span style="font-size:11px;color:var(--text-tertiary);margin-left:8px">#<?= $co['sequence_num'] ?></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:<?= $co['net_cost_impact'] >= 0 ? 'var(--red-500)' : 'var(--green-500)' ?>"><?= $co['net_cost_impact'] >= 0 ? '+' : '' ?>$<?= number_format($co['net_cost_impact'], 2) ?></span>
                            <?= get_status_badge($co['status']) ?>
                        </div>
                    </div>
                    <div style="font-size:12px;color:var(--text-secondary);margin-top:6px"><?= htmlspecialchars(substr($co['reason_detail'], 0, 100)) ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Right Column: Timer + Actions + Progress Log -->
    <div class="col-lg-4">
        <!-- Timer / Status controls -->
        <div class="card mb-3">
            <div class="card-body" style="text-align:center">
                <div class="wo-timer <?= $wo['status'] === 'paused' ? 'paused' : '' ?>" style="margin-bottom:12px;display:inline-flex">
                    <div>
                        <div class="wo-timer-label">Elapsed</div>
                        <div class="clock" id="elapsedClock">--:--:--</div>
                    </div>
                </div>
                <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:8px">
                    <?php if ($wo['status'] === 'created'): ?>
                        <button class="btn btn-success btn-sm" onclick="updateWoStatus(<?= $wo['id'] ?>,'in_progress')"><i class="fas fa-play"></i> Start Work</button>
                    <?php elseif ($wo['status'] === 'in_progress'): ?>
                        <button class="btn btn-warning btn-sm" onclick="updateWoStatus(<?= $wo['id'] ?>,'paused')"><i class="fas fa-pause"></i> Pause</button>
                        <button class="btn btn-success btn-sm" onclick="completeWork(<?= $wo['id'] ?>)"><i class="fas fa-check"></i> Complete</button>
                    <?php elseif ($wo['status'] === 'paused'): ?>
                        <button class="btn btn-success btn-sm" onclick="updateWoStatus(<?= $wo['id'] ?>,'in_progress')"><i class="fas fa-play"></i> Resume</button>
                    <?php elseif ($wo['status'] === 'completed'): ?>
                        <span style="color:var(--green-500);font-weight:600"><i class="fas fa-check-circle"></i> Completed</span>
                    <?php endif; ?>
                </div>
                <?php if ($wo['work_started_at']): ?>
                    <div style="font-size:11px;color:var(--text-tertiary);margin-top:8px">Started: <?= format_datetime($wo['work_started_at']) ?></div>
                <?php endif; ?>
                <?php if ($wo['work_completed_at']): ?>
                    <div style="font-size:11px;color:var(--text-tertiary)">Completed: <?= format_datetime($wo['work_completed_at']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer signoff -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 style="font-size:13px;font-weight:600;margin-bottom:8px"><i class="fas fa-user-check" style="color:var(--navy-300)"></i> Customer Signoff</h5>
                <?php if ($wo['customer_signoff']): ?>
                    <div style="color:var(--green-500);font-size:13px"><i class="fas fa-check-circle"></i> Signed <?= $wo['signoff_at'] ? format_datetime($wo['signoff_at']) : '' ?></div>
                <?php else: ?>
                    <button class="btn btn-sm btn-outline-primary" onclick="customerSignoff(<?= $wo['id'] ?>)"><i class="fas fa-signature"></i> Record Signoff</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progress Log -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 style="font-size:13px;font-weight:600;margin-bottom:8px"><i class="fas fa-stream" style="color:var(--navy-300)"></i> Progress Log</h5>
                <?php if (in_array($wo['status'], ['in_progress', 'paused'])): ?>
                    <div style="display:flex;gap:6px;margin-bottom:12px">
                        <input type="text" id="logNoteInput" placeholder="Add note..." style="flex:1;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:4px;padding:6px 8px;color:var(--text-primary);font-size:12px">
                        <button class="btn btn-sm btn-primary" onclick="addProgressNote(<?= $wo['id'] ?>, <?= $wo['technician_id'] ?>)"><i class="fas fa-plus"></i></button>
                    </div>
                <?php endif; ?>
                <div class="wo-log">
                    <?php if (empty($progressLog)): ?>
                        <p style="text-align:center;padding:20px;color:var(--text-tertiary);font-size:12px">No log entries yet.</p>
                    <?php else: foreach ($progressLog as $log):
                        $icons = ['clock_start' => 'fa-play', 'clock_stop' => 'fa-stop', 'note' => 'fa-sticky-note', 'photo' => 'fa-camera', 'status_change' => 'fa-exchange-alt', 'checklist_item' => 'fa-check-square'];
                    ?>
                        <div class="wo-log-entry">
                            <div class="wo-log-icon <?= $log['entry_type'] ?>"><i class="fas <?= $icons[$log['entry_type']] ?? 'fa-circle' ?>"></i></div>
                            <div>
                                <div style="color:var(--text-primary)"><?= htmlspecialchars($log['content'] ?? ucfirst(str_replace('_', ' ', $log['entry_type']))) ?></div>
                                <div class="wo-log-meta"><?= htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?> — <?= format_datetime($log['logged_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
const API_W = 'api/workflow.php';

// Timer for in-progress WOs
<?php if ($wo && $wo['work_started_at'] && !$wo['work_completed_at']): ?>
(function() {
    const startMs = new Date('<?= $wo['work_started_at'] ?>').getTime();
    const el = document.getElementById('elapsedClock');
    function tick() {
        const diff = Math.floor((Date.now() - startMs) / 1000);
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        el.textContent = [h,m,s].map(v => String(v).padStart(2,'0')).join(':');
    }
    tick(); setInterval(tick, 1000);
})();
<?php endif; ?>

async function updateWoStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_work_order_status');
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) location.reload();
    else alert('Error: ' + (json.error || 'Failed'));
}

async function completeWork(id) {
    if (!confirm('Mark work as complete? This will auto-generate an invoice.')) return;
    await updateWoStatus(id, 'completed');
}

async function customerSignoff(id) {
    if (!confirm('Record customer signoff for this work order?')) return;
    const fd = new FormData();
    fd.append('action', 'wo_customer_signoff');
    fd.append('work_order_id', id);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function addProgressNote(woId, techId) {
    const inp = document.getElementById('logNoteInput');
    const content = inp.value.trim();
    if (!content) return;
    const fd = new FormData();
    fd.append('action', 'wo_progress_log');
    fd.append('work_order_id', woId);
    fd.append('entry_type', 'note');
    fd.append('content', content);
    fd.append('technician_id', techId);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}
</script>
