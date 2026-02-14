<!-- ═══ CREATE CHANGE ORDER ══════════════════════════════════════════ -->
<div class="co-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-exchange-alt" style="font-size:26px;color:var(--amber-500)"></i>
        <div>
            <h1>New Change Order</h1>
            <p class="subtitle">For <?= htmlspecialchars($woData['work_order_id']) ?> — <?= htmlspecialchars($woData['customer_name'] ?? '') ?></p>
        </div>
    </div>
    <a href="?page=work-orders&action=view&id=<?= $woData['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to WO</a>
</div>

<form id="coForm">
    <input type="hidden" name="work_order_id" value="<?= $woData['id'] ?>">
    <input type="hidden" name="technician_id" value="<?= $woData['technician_id'] ?>">

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="intake-label"><span class="req">*</span> Change Reason</label>
            <select class="intake-select" name="change_reason" required>
                <option value="discovery">Discovery — Found during work</option>
                <option value="customer_request">Customer Request</option>
                <option value="safety">Safety Concern</option>
                <option value="parts_unavailable">Parts Unavailable</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="intake-label">Must Pause Work?</label>
            <select class="intake-select" name="must_pause_work">
                <option value="1">Yes — Pause until approved</option>
                <option value="0">No — Can continue</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="intake-label">Net Cost Impact ($)</label>
            <input type="number" class="intake-input mono" name="net_cost_impact" value="0.00" step="0.01" id="netImpactInput">
            <div style="font-size:11px;color:var(--text-tertiary);margin-top:2px">Current authorized: $<?= number_format($woData['authorized_total'], 2) ?></div>
        </div>
    </div>

    <div class="mb-3">
        <label class="intake-label"><span class="req">*</span> Detail / Justification</label>
        <textarea class="intake-textarea" name="reason_detail" rows="3" placeholder="Explain the reason for the scope change..." required></textarea>
    </div>
    <div class="mb-3">
        <label class="intake-label">Technician Justification</label>
        <textarea class="intake-textarea" name="technician_justification" rows="2" placeholder="Technical justification for the change..."></textarea>
    </div>
    <div class="mb-3">
        <label class="intake-label">Original Scope Reference</label>
        <input type="text" class="intake-input" name="original_scope_ref" placeholder="Reference to original scope that's changing">
    </div>

    <!-- Line items for CO -->
    <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px;margin-bottom:16px">
        <h3 style="font-size:14px;font-weight:600;margin:0 0 12px"><i class="fas fa-list" style="color:var(--amber-500);margin-right:6px"></i> Additional / Changed Line Items</h3>
        <table class="est-line-items" id="coLineItemsTable" style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">#</th>
                    <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">TYPE</th>
                    <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">DESCRIPTION</th>
                    <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">QTY</th>
                    <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">UNIT $</th>
                    <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:right">EXTENDED</th>
                    <th style="background:var(--bg-secondary);padding:8px;border-bottom:1px solid var(--border-medium);width:30px"></th>
                </tr>
            </thead>
            <tbody id="coItemsBody">
                <tr id="coEmptyRow"><td colspan="7" style="text-align:center;padding:20px;color:var(--text-tertiary);font-size:13px">No items. Add below.</td></tr>
            </tbody>
        </table>
        <div style="display:grid;grid-template-columns:120px 1fr 70px 90px 30px;gap:6px;margin-top:10px;align-items:end">
            <div><label style="font-size:11px;color:var(--text-tertiary)">Type</label><select id="coItemType" style="width:100%;background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:4px;padding:6px;color:var(--text-primary);font-size:12px"><option value="labor">Labor</option><option value="parts">Parts</option><option value="service_fee">Service Fee</option></select></div>
            <div><label style="font-size:11px;color:var(--text-tertiary)">Description</label><input type="text" id="coItemDesc" style="width:100%;background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:4px;padding:6px;color:var(--text-primary);font-size:12px" placeholder="Item description"></div>
            <div><label style="font-size:11px;color:var(--text-tertiary)">Qty</label><input type="number" id="coItemQty" value="1" step="0.5" min="0.5" style="width:100%;background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:4px;padding:6px;color:var(--text-primary);font-size:12px"></div>
            <div><label style="font-size:11px;color:var(--text-tertiary)">Unit $</label><input type="number" id="coItemPrice" value="0.00" step="0.01" style="width:100%;background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:4px;padding:6px;color:var(--text-primary);font-size:12px"></div>
            <div><button type="button" class="btn btn-sm btn-warning" onclick="addCoItem()" style="padding:6px 10px" title="Add"><i class="fas fa-plus"></i></button></div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px">
        <a href="?page=work-orders&action=view&id=<?= $woData['id'] ?>" class="btn btn-secondary">Cancel</a>
        <button type="button" class="btn btn-warning" onclick="submitCO()"><i class="fas fa-paper-plane"></i> Submit Change Order</button>
    </div>
</form>
