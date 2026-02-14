<div class="inv-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice-dollar" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1><?= htmlspecialchars($invoice['invoice_id']) ?></h1>
            <p class="subtitle"><?= htmlspecialchars($invoice['customer_name'] ?? '') ?> — <?= ucfirst(str_replace('_', ' ', $invoice['payment_terms'])) ?></p>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?= get_status_badge($invoice['status']) ?>
        <a href="?page=invoices-v2" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<!-- Chain -->
<div style="display:flex;align-items:center;gap:6px;margin-bottom:16px;font-size:12px">
    <a href="?page=service-requests&action=view&id=<?= $invoice['service_request_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars(format_ticket_number($invoice['ticket_number'] ?? '')) ?></a>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <a href="?page=work-orders&action=view&id=<?= $invoice['work_order_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-clipboard-check"></i> <?= htmlspecialchars($invoice['wo_doc_id'] ?? 'WO') ?></a>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <span style="color:var(--text-primary);font-weight:600;padding:4px 10px;background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:4px"><i class="fas fa-file-invoice-dollar"></i> <?= htmlspecialchars($invoice['invoice_id']) ?></span>
    <?php
    $rctLink = $pdo->prepare("SELECT id, receipt_id FROM receipts WHERE invoice_id = ?");
    $rctLink->execute([$invoice['id']]);
    $rct = $rctLink->fetch();
    if ($rct): ?>
        <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
        <a href="?page=receipts&action=view&id=<?= $rct['id'] ?>" style="color:var(--green-500);text-decoration:none;padding:4px 10px;background:rgba(34,197,94,0.08);border-radius:4px"><i class="fas fa-receipt"></i> <?= htmlspecialchars($rct['receipt_id']) ?></a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <?php include __DIR__ . '/view-details.php'; ?>
    </div>
    <div class="col-lg-4">
        <?php include __DIR__ . '/view-sidebar.php'; ?>
    </div>
</div>
