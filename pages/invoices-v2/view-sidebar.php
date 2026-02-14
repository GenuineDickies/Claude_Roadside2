<!-- Amount summary cards -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="inv-amount-card">
            <div class="lbl">Grand Total</div>
            <div class="val" style="color:var(--navy-300)">$<?= number_format($invoice['grand_total'], 2) ?></div>
        </div>
    </div>
    <div class="col-6">
        <div class="inv-amount-card">
            <div class="lbl">Balance Due</div>
            <div class="val" style="color:<?= $invoice['balance_due'] > 0 ? 'var(--amber-500)' : 'var(--green-500)' ?>">$<?= number_format($invoice['balance_due'], 2) ?></div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="card mb-3">
    <div class="card-body">
        <h5 style="font-size:13px;font-weight:600;margin-bottom:12px">Actions</h5>
        <div style="display:flex;flex-direction:column;gap:8px">
            <?php if ($invoice['status'] === 'generated'): ?>
                <button class="btn btn-primary btn-sm" onclick="updateInvStatus(<?= $invoice['id'] ?>,'sent')"><i class="fas fa-paper-plane"></i> Mark as Sent</button>
            <?php endif; ?>
            <?php if ($invoice['balance_due'] > 0): ?>
                <button class="btn btn-success btn-sm" onclick="recordPayment(<?= $invoice['id'] ?>)"><i class="fas fa-dollar-sign"></i> Record Payment</button>
                <button class="btn btn-outline-warning btn-sm" onclick="applyDiscount(<?= $invoice['id'] ?>)"><i class="fas fa-tag"></i> Apply Discount</button>
            <?php endif; ?>
            <?php if (in_array($invoice['status'], ['sent', 'viewed']) && strtotime($invoice['due_date']) < time()): ?>
                <button class="btn btn-danger btn-sm" onclick="updateInvStatus(<?= $invoice['id'] ?>,'overdue')"><i class="fas fa-exclamation-triangle"></i> Mark Overdue</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="card mb-3">
    <div class="card-body">
        <h5 style="font-size:13px;font-weight:600;margin-bottom:12px"><i class="fas fa-history" style="color:var(--navy-300)"></i> Payment History</h5>
        <?php if (empty($payments)): ?>
            <p style="text-align:center;padding:20px;color:var(--text-tertiary);font-size:12px">No payments recorded.</p>
        <?php else: foreach ($payments as $pmt): ?>
            <div class="inv-payment-row">
                <div>
                    <div style="font-weight:500;color:var(--text-primary)"><?= ucfirst($pmt['payment_method']) ?></div>
                    <div style="font-size:11px;color:var(--text-tertiary)"><?= format_datetime($pmt['processed_at']) ?></div>
                </div>
                <div style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--green-500)">+$<?= number_format($pmt['amount'], 2) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Dates -->
<div class="card">
    <div class="card-body">
        <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Invoice Date</div>
        <div style="font-size:13px;color:var(--text-primary);margin-bottom:10px"><?= format_date($invoice['invoice_date']) ?></div>
        <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Due Date</div>
        <div style="font-size:13px;color:<?= strtotime($invoice['due_date']) < time() && $invoice['balance_due'] > 0 ? 'var(--red-500)' : 'var(--text-primary)' ?>"><?= format_date($invoice['due_date']) ?></div>
        <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin:10px 0 4px">Payment Terms</div>
        <div style="font-size:13px;color:var(--text-primary)"><?= ucfirst(str_replace('_', ' ', $invoice['payment_terms'])) ?></div>
        <?php if ($invoice['paid_at']): ?>
            <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin:10px 0 4px">Paid At</div>
            <div style="font-size:13px;color:var(--green-500)"><?= format_datetime($invoice['paid_at']) ?></div>
        <?php endif; ?>
    </div>
</div>
