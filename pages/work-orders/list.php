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
                            <?php $woTicket = format_ticket_number($w['ticket_number'] ?? ''); ?>
                            <td><a href="?page=service-requests&action=view&id=<?= $w['service_request_id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($woTicket ?: '#' . $w['service_request_id']) ?></a></td>
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
