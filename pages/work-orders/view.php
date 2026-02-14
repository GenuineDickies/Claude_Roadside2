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
    <a href="?page=service-requests&action=view&id=<?= $wo['service_request_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars(format_ticket_number($wo['ticket_number'] ?? '')) ?></a>
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
    <div class="col-lg-8">
        <?php include __DIR__ . '/view-details.php'; ?>
    </div>
    <div class="col-lg-4">
        <?php include __DIR__ . '/view-sidebar.php'; ?>
    </div>
</div>
