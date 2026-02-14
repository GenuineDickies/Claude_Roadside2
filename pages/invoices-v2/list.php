<div class="inv-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice-dollar" style="font-size:26px;color:var(--navy-300)"></i>
        <div><h1>Invoices</h1><p class="subtitle">Billing from completed work orders</p></div>
    </div>
    <select onchange="location.href='?page=invoices-v2&status='+this.value" style="padding:6px 12px;font-size:12px;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:6px;color:var(--text-primary)">
        <option value="">All Statuses</option>
        <option value="generated" <?= ($_GET['status'] ?? '') === 'generated' ? 'selected' : '' ?>>Generated</option>
        <option value="sent" <?= ($_GET['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="partial" <?= ($_GET['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Partial</option>
        <option value="paid" <?= ($_GET['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
        <option value="overdue" <?= ($_GET['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
    </select>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0">
                <thead>
                    <tr><th>INVOICE</th><th>TICKET</th><th>CUSTOMER</th><th>TOTAL</th><th>PAID</th><th>BALANCE</th><th>STATUS</th><th>DUE DATE</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-tertiary)">No invoices. Invoices auto-generate when work orders complete.</td></tr>
                    <?php else: foreach ($invoices as $inv): ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--navy-300)"><?= htmlspecialchars($inv['invoice_id']) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars(format_ticket_number($inv['ticket_number'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($inv['customer_name'] ?? '') ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600">$<?= number_format($inv['grand_total'], 2) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--green-500)">$<?= number_format($inv['amount_paid'], 2) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:<?= $inv['balance_due'] > 0 ? 'var(--amber-500)' : 'var(--green-500)' ?>">$<?= number_format($inv['balance_due'], 2) ?></td>
                            <td><?= get_status_badge($inv['status']) ?></td>
                            <td style="font-size:12px;color:<?= strtotime($inv['due_date']) < time() && $inv['balance_due'] > 0 ? 'var(--red-500)' : 'var(--text-secondary)' ?>"><?= format_date($inv['due_date']) ?></td>
                            <td><a href="?page=invoices-v2&action=view&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
