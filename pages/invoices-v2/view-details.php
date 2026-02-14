<!-- Customer / Service info -->
<div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:16px 20px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Customer</div><div style="font-size:13px;color:var(--text-primary);font-weight:500;margin-top:2px"><?= htmlspecialchars($invoice['customer_name'] ?? '') ?></div></div>
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Phone</div><div style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars($invoice['customer_phone'] ?? '') ?></div></div>
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Vehicle</div><div style="font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars(trim(($invoice['vehicle_year'] ?? '') . ' ' . ($invoice['vehicle_make'] ?? '') . ' ' . ($invoice['vehicle_model'] ?? ''))) ?: '—' ?></div></div>
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Technician</div><div style="font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars(($invoice['tech_first'] ?? '') . ' ' . ($invoice['tech_last'] ?? '')) ?></div></div>
    </div>
</div>

<!-- Line Items -->
<div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px;margin-bottom:16px">
    <h3 style="font-size:14px;font-weight:600;margin:0 0 12px"><i class="fas fa-list" style="color:var(--navy-300);margin-right:6px"></i> Line Items</h3>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">#</th>
                <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">TYPE</th>
                <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">DESCRIPTION</th>
                <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:left">QTY</th>
                <th style="background:var(--bg-secondary);padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:right">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lineItems as $li): ?>
            <tr style="border-bottom:1px solid var(--border-subtle)">
                <td style="padding:10px 12px;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)"><?= $li['line_number'] ?></td>
                <td style="padding:10px 12px"><span style="font-size:10px;padding:2px 8px;border-radius:3px;text-transform:uppercase;font-weight:600;background:rgba(59,130,246,0.1);color:var(--blue-500)"><?= str_replace('_', ' ', $li['item_type']) ?></span></td>
                <td style="padding:10px 12px;font-size:13px"><?= htmlspecialchars($li['description']) ?></td>
                <td style="padding:10px 12px;font-family:'JetBrains Mono',monospace;font-size:12px"><?= number_format($li['quantity'], 2) ?></td>
                <td style="padding:10px 12px;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;text-align:right">$<?= number_format($li['extended_price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div style="margin-top:16px;margin-left:auto;width:320px">
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px"><span>Subtotal</span><span style="font-family:'JetBrains Mono',monospace">$<?= number_format($invoice['subtotal'], 2) ?></span></div>
        <?php if ($invoice['discount_amount'] > 0): ?>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;color:var(--green-500)"><span>Discount</span><span style="font-family:'JetBrains Mono',monospace">-$<?= number_format($invoice['discount_amount'], 2) ?></span></div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px"><span>Tax</span><span style="font-family:'JetBrains Mono',monospace">$<?= number_format($invoice['tax_amount'], 2) ?></span></div>
        <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:2px solid var(--navy-500);font-size:16px;font-weight:700;margin-top:4px"><span>Grand Total</span><span style="font-family:'JetBrains Mono',monospace;color:var(--navy-300)">$<?= number_format($invoice['grand_total'], 2) ?></span></div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px"><span>Paid</span><span style="font-family:'JetBrains Mono',monospace;color:var(--green-500)">$<?= number_format($invoice['amount_paid'], 2) ?></span></div>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;font-weight:600"><span>Balance Due</span><span style="font-family:'JetBrains Mono',monospace;color:<?= $invoice['balance_due'] > 0 ? 'var(--amber-500)' : 'var(--green-500)' ?>">$<?= number_format($invoice['balance_due'], 2) ?></span></div>
    </div>
</div>
