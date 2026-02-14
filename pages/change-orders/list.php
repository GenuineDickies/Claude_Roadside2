<div class="co-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-exchange-alt" style="font-size:26px;color:var(--amber-500)"></i>
        <div><h1>Change Orders</h1><p class="subtitle">Mid-workflow scope changes and cost adjustments</p></div>
    </div>
    <select onchange="location.href='?page=change-orders&status='+this.value" style="padding:6px 12px;font-size:12px;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:6px;color:var(--text-primary)">
        <option value="">All Statuses</option>
        <option value="proposed" <?= ($_GET['status'] ?? '') === 'proposed' ? 'selected' : '' ?>>Proposed</option>
        <option value="presented" <?= ($_GET['status'] ?? '') === 'presented' ? 'selected' : '' ?>>Presented</option>
        <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="declined" <?= ($_GET['status'] ?? '') === 'declined' ? 'selected' : '' ?>>Declined</option>
    </select>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0">
                <thead>
                    <tr><th>CO ID</th><th>WORK ORDER</th><th>CUSTOMER</th><th>REASON</th><th>IMPACT</th><th>STATUS</th><th>CREATED</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($changeOrders)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-tertiary)">No change orders found.</td></tr>
                    <?php else: foreach ($changeOrders as $c): ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--amber-500)"><?= htmlspecialchars($c['change_order_id']) ?></td>
                            <td><a href="?page=work-orders&action=view&id=<?= $c['work_order_id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($c['wo_doc_id'] ?? '') ?></a></td>
                            <td><?= htmlspecialchars($c['customer_name'] ?? '') ?></td>
                            <td><span class="co-reason-badge co-reason-<?= $c['change_reason'] ?>"><?= str_replace('_', ' ', $c['change_reason']) ?></span></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:<?= $c['net_cost_impact'] >= 0 ? 'var(--red-500)' : 'var(--green-500)' ?>"><?= $c['net_cost_impact'] >= 0 ? '+' : '' ?>$<?= number_format($c['net_cost_impact'], 2) ?></td>
                            <td><?= get_status_badge($c['status']) ?></td>
                            <td style="font-size:12px;color:var(--text-secondary)"><?= format_datetime($c['created_at']) ?></td>
                            <td><a href="?page=change-orders&action=view&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-warning">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
