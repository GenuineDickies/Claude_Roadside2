<!-- ═══ VIEW ESTIMATE ═════════════════════════════════════════════════ -->
<div class="est-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1><?= htmlspecialchars($estimate['estimate_id']) ?></h1>
            <p class="subtitle">Version <?= $estimate['version'] ?> — <?= htmlspecialchars(($estimate['tech_first'] ?? '') . ' ' . ($estimate['tech_last'] ?? '')) ?></p>
        </div>
    </div>
    <div style="display:flex;gap:8px">
        <?= get_status_badge($estimate['status']) ?>
        <a href="?page=estimates" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<!-- Document chain -->
<div class="doc-chain">
    <?php $viewTicketLabel = format_ticket_number($srData['ticket_number'] ?? '') ?: 'SR'; ?>
    <a href="?page=service-requests&action=view&id=<?= $estimate['service_request_id'] ?>"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($viewTicketLabel) ?></a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current"><i class="fas fa-file-invoice"></i> <?= htmlspecialchars($estimate['estimate_id']) ?></span>
    <?php
    $wo = $pdo->prepare("SELECT id, work_order_id FROM work_orders WHERE estimate_id = ?");
    $wo->execute([$estimate['id']]);
    $woLink = $wo->fetch();
    if ($woLink): ?>
        <span class="sep"><i class="fas fa-chevron-right"></i></span>
        <a href="?page=work-orders&action=view&id=<?= $woLink['id'] ?>"><i class="fas fa-clipboard-check"></i> <?= htmlspecialchars($woLink['work_order_id']) ?></a>
    <?php endif; ?>
</div>

<!-- Status lifecycle -->
<div class="est-lifecycle">
    <?php
    $statuses = ['draft','presented','approved'];
    foreach ($statuses as $s):
        $cls = $estimate['status'] === $s ? 'active' : (array_search($s, $statuses) < array_search($estimate['status'], $statuses) ? 'done' : '');
    ?>
        <div class="est-lifecycle-step <?= $cls ?>"><?= ucfirst($s) ?></div>
    <?php endforeach; ?>
</div>

<!-- Inherited info -->
<?php if ($srData): ?>
<div class="est-inherited">
    <h3><i class="fas fa-link" style="margin-right:4px"></i> Inherited from Service Request</h3>
    <div class="est-inherited-grid">
        <div class="est-inherited-item"><div class="lbl">Customer</div><div class="val"><?= htmlspecialchars($srData['customer_name'] ?? '') ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Phone</div><div class="val" style="font-family:'JetBrains Mono',monospace"><?= htmlspecialchars($srData['customer_phone'] ?? '') ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Vehicle</div><div class="val"><?= htmlspecialchars(trim(($srData['vehicle_year'] ?? '') . ' ' . ($srData['vehicle_make'] ?? '') . ' ' . ($srData['vehicle_model'] ?? ''))) ?: '—' ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Service</div><div class="val"><?= ucfirst(str_replace('_', ' ', $srData['service_category'] ?? '')) ?></div></div>
    </div>
</div>
<?php endif; ?>

<!-- Line Items -->
<div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px;margin-bottom:20px">
    <h3 style="font-size:14px;font-weight:600;margin:0 0 12px"><i class="fas fa-list" style="color:var(--navy-300);margin-right:6px"></i> Line Items</h3>
    <table class="est-line-items">
        <thead>
            <tr><th>#</th><th>TYPE</th><th>DESCRIPTION</th><th>QTY</th><th>UNIT PRICE</th><th>MARKUP</th><th>EXTENDED</th></tr>
        </thead>
        <tbody>
            <?php foreach ($lineItems as $li): ?>
            <tr>
                <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)"><?= $li['line_number'] ?></td>
                <td><span class="type-badge type-<?= $li['item_type'] ?>"><?= str_replace('_', ' ', $li['item_type']) ?></span></td>
                <td><?= htmlspecialchars($li['description']) ?></td>
                <td class="money"><?= number_format($li['quantity'], 2) ?></td>
                <td class="money">$<?= number_format($li['unit_price'], 2) ?></td>
                <td class="money"><?= $li['markup_pct'] !== null ? $li['markup_pct'] . '%' : '—' ?></td>
                <td class="money" style="font-weight:600">$<?= number_format($li['extended_price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="est-totals">
        <div class="est-total-row"><span>Labor</span><span class="money">$<?= number_format($estimate['subtotal_labor'], 2) ?></span></div>
        <div class="est-total-row"><span>Parts</span><span class="money">$<?= number_format($estimate['subtotal_parts'], 2) ?></span></div>
        <div class="est-total-row"><span>Services</span><span class="money">$<?= number_format($estimate['subtotal_services'], 2) ?></span></div>
        <div class="est-total-row"><span>Tow/Mileage</span><span class="money">$<?= number_format($estimate['subtotal_tow'], 2) ?></span></div>
        <div class="est-total-row" style="border-top:1px solid var(--border-subtle);padding-top:8px"><span>Subtotal</span><span class="money">$<?= number_format($estimate['subtotal_labor'] + $estimate['subtotal_parts'] + $estimate['subtotal_services'] + $estimate['subtotal_tow'], 2) ?></span></div>
        <div class="est-total-row"><span>Tax (<?= round($estimate['tax_rate'] * 100, 2) ?>%)</span><span class="money">$<?= number_format($estimate['tax_amount'], 2) ?></span></div>
        <div class="est-total-row grand"><span>Estimate Total</span><span class="money" style="color:var(--green-500)">$<?= number_format($estimate['total'], 2) ?></span></div>
    </div>
</div>

<!-- Validity -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:0.3px">Valid Until</div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:14px;color:<?= strtotime($estimate['valid_until']) < time() ? 'var(--red-500)' : 'var(--text-primary)' ?>"><?= format_datetime($estimate['valid_until']) ?></div>
        </div></div>
    </div>
    <?php if ($estimate['approved_at']): ?>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:0.3px">Approved</div>
            <div style="font-size:14px;color:var(--green-500)"><?= format_datetime($estimate['approved_at']) ?> (<?= $estimate['approval_method'] ?? 'verbal' ?>)</div>
        </div></div>
    </div>
    <?php endif; ?>
</div>

<!-- Actions -->
<?php if (in_array($estimate['status'], ['draft', 'presented'])): ?>
<div class="est-approval">
    <h3>Actions</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php if ($estimate['status'] === 'draft'): ?>
            <button class="btn btn-primary btn-sm" onclick="updateEstStatus(<?= $estimate['id'] ?>,'presented')"><i class="fas fa-paper-plane"></i> Present to Customer</button>
        <?php endif; ?>
        <?php if ($estimate['status'] === 'presented'): ?>
            <button class="btn btn-success btn-sm" onclick="approveEstimate(<?= $estimate['id'] ?>)"><i class="fas fa-check"></i> Approve</button>
            <button class="btn btn-danger btn-sm" onclick="declineEstimate(<?= $estimate['id'] ?>)"><i class="fas fa-times"></i> Decline</button>
        <?php endif; ?>
        <a href="?page=estimates&action=create&sr_id=<?= $estimate['service_request_id'] ?>&copy_from=<?= $estimate['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-pen"></i> Revise Estimate</a>
    </div>
</div>
<?php endif; ?>

<?php if ($estimate['status'] === 'approved' && !$woLink): ?>
<div class="est-approval" style="border-color:rgba(34,197,94,0.3)">
    <h3 style="color:var(--green-500)"><i class="fas fa-check-circle"></i> Estimate Approved — Create Work Order</h3>
    <p style="font-size:13px;color:var(--text-secondary)">This estimate is approved. Create a Work Order to authorize work.</p>
    <button class="btn btn-success btn-sm" onclick="createWoFromEstimate(<?= $estimate['id'] ?>, <?= $estimate['service_request_id'] ?>, <?= $estimate['technician_id'] ?>)"><i class="fas fa-clipboard-check"></i> Create Work Order</button>
</div>
<?php endif; ?>
