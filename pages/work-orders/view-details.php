<!-- Inherited info -->
<div class="est-inherited" style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:16px 20px;margin-bottom:16px">
    <h3 style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);margin:0 0 10px"><i class="fas fa-link" style="margin-right:4px"></i> Job Details</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Customer</div><div style="font-size:13px;color:var(--text-primary);font-weight:500;margin-top:2px"><?= htmlspecialchars($wo['customer_name'] ?? '') ?></div></div>
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Phone</div><div style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars($wo['customer_phone'] ?? '') ?></div></div>
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Vehicle</div><div style="font-size:13px;color:var(--text-primary);font-weight:500;margin-top:2px"><?= htmlspecialchars(trim(($wo['vehicle_year'] ?? '') . ' ' . ($wo['vehicle_make'] ?? '') . ' ' . ($wo['vehicle_model'] ?? ''))) ?: '—' ?></div></div>
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Service</div><div style="font-size:13px;color:var(--text-primary);margin-top:2px"><?= ucfirst(str_replace('_', ' ', $wo['service_category'] ?? '')) ?></div></div>
        <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Location</div><div style="font-size:12px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars(substr($wo['service_address'] ?? '', 0, 50)) ?></div></div>
    </div>
</div>

<!-- Diagnosis Section (Work Order stage) -->
<div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:16px 20px;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <h3 style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-tertiary);margin:0"><i class="fas fa-stethoscope" style="color:var(--navy-300);margin-right:6px"></i> Diagnosis</h3>
        <?php if (in_array($wo['status'], ['created','in_progress','paused'])): ?>
        <button class="btn btn-sm btn-outline-primary" onclick="toggleDiagnosisEdit()" id="diagEditBtn" style="padding:2px 10px;font-size:11px"><i class="fas fa-edit"></i> Edit</button>
        <?php endif; ?>
    </div>
    <!-- View mode -->
    <div id="diagView">
        <?php if ($wo['diagnosis_summary']): ?>
            <p style="font-size:13px;color:var(--text-primary);margin:0 0 8px"><?= nl2br(htmlspecialchars($wo['diagnosis_summary'])) ?></p>
            <?php if ($wo['diagnostic_codes']): ?>
                <div>
                    <?php foreach (json_decode($wo['diagnostic_codes'], true) ?: [] as $code): ?>
                        <span style="display:inline-block;padding:2px 8px;background:rgba(168,85,247,0.1);color:var(--purple-500);border-radius:3px;font-family:'JetBrains Mono',monospace;font-size:12px;margin:2px"><?= htmlspecialchars($code) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p style="font-size:13px;color:var(--text-tertiary);margin:0;font-style:italic">No diagnosis recorded yet. Click Edit to add technician's on-scene assessment.</p>
        <?php endif; ?>
    </div>
    <!-- Edit mode -->
    <div id="diagEdit" style="display:none">
        <div class="mb-2">
            <label style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;display:block;margin-bottom:4px">Diagnosis Summary</label>
            <textarea id="diagSummaryInput" rows="3" style="width:100%;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:6px;padding:8px 12px;color:var(--text-primary);font-size:13px;font-family:'DM Sans',sans-serif;resize:vertical" placeholder="Technician's on-scene assessment..."><?= htmlspecialchars($wo['diagnosis_summary'] ?? '') ?></textarea>
        </div>
        <div class="mb-2">
            <label style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;display:block;margin-bottom:4px">Diagnostic Codes (OBD-II)</label>
            <input type="text" id="diagCodesInput" style="width:100%;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:6px;padding:8px 12px;color:var(--text-primary);font-family:'JetBrains Mono',monospace;font-size:13px" placeholder="P0300, P0171, etc. (comma separated)" value="<?= htmlspecialchars(implode(', ', json_decode($wo['diagnostic_codes'] ?? '[]', true) ?: [])) ?>">
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button class="btn btn-sm btn-secondary" onclick="toggleDiagnosisEdit()" style="padding:4px 12px;font-size:12px">Cancel</button>
            <button class="btn btn-sm btn-primary" onclick="saveDiagnosis(<?= $wo['id'] ?>)" style="padding:4px 12px;font-size:12px"><i class="fas fa-save"></i> Save</button>
        </div>
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

<?php include __DIR__ . '/view-changes.php'; ?>
