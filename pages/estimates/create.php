<!-- ═══ CREATE ESTIMATE ═══════════════════════════════════════════════ -->
<div class="est-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Create Estimate</h1>
            <?php $estTicketLabel = format_ticket_number($srData['ticket_number'] ?? '') ?: ('SR #' . $srData['id']); ?>
            <p class="subtitle">For <?= htmlspecialchars($estTicketLabel) ?> — <?= htmlspecialchars($srData['customer_name'] ?? '') ?></p>
        </div>
    </div>
    <a href="?page=estimates" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<!-- Document chain breadcrumb -->
<div class="doc-chain">
    <a href="?page=service-requests&action=view&id=<?= $srData['id'] ?>"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($estTicketLabel) ?></a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current"><i class="fas fa-file-invoice"></i> New Estimate</span>
</div>

<!-- Inherited customer/vehicle/location info (read-only) -->
<div class="est-inherited">
    <h3><i class="fas fa-link" style="margin-right:4px"></i> Inherited from Service Request</h3>
    <div class="est-inherited-grid">
        <div class="est-inherited-item"><div class="lbl">Customer</div><div class="val"><?= htmlspecialchars($srData['customer_name'] ?? '') ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Phone</div><div class="val" style="font-family:'JetBrains Mono',monospace"><?= htmlspecialchars($srData['customer_phone'] ?? '') ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Vehicle</div><div class="val"><?= htmlspecialchars(trim(($srData['vehicle_year'] ?? '') . ' ' . ($srData['vehicle_make'] ?? '') . ' ' . ($srData['vehicle_model'] ?? ''))) ?: '—' ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Service</div><div class="val"><?= ucfirst(str_replace('_', ' ', $srData['service_category'] ?? '')) ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Location</div><div class="val" style="font-size:12px"><?= htmlspecialchars(substr($srData['service_address'] ?? '', 0, 40)) ?></div></div>
        <div class="est-inherited-item"><div class="lbl">Issue</div><div class="val" style="font-size:12px"><?= htmlspecialchars(substr($srData['issue_description'] ?? '', 0, 60)) ?></div></div>
    </div>
</div>

<form id="estimateForm">
    <input type="hidden" name="service_request_id" value="<?= $srData['id'] ?>">

    <?php if (!empty($prefillEstimate)): ?>
    <div style="background:rgba(43,94,167,0.12);border:1px solid rgba(43,94,167,0.35);border-radius:8px;padding:12px 16px;margin-bottom:18px;font-size:12px;color:var(--navy-200);display:flex;align-items:center;gap:10px">
        <i class="fas fa-pen-to-square" style="color:var(--navy-300);"></i>
        <div>
            <div style="font-weight:600;color:var(--navy-200)">Revising Estimate <?= htmlspecialchars($prefillEstimate['estimate_id']) ?> (v<?= intval($prefillEstimate['version']) ?>)</div>
            <div style="color:var(--text-secondary)">Line items and notes are prefilled below. Adjust as needed and set the new version before saving.</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="est-label"><span class="req">*</span> Technician</label>
            <select class="est-select" name="technician_id" required>
                <option value="">Select technician...</option>
                <?php foreach ($technicians as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= ($srData['technician_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?> <?= $t['specialization'] ? '(' . htmlspecialchars($t['specialization']) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="est-label">Tax Rate (%)</label>
            <input type="number" class="est-input mono" name="tax_rate_pct" value="8.25" step="0.01" min="0" max="15" id="taxRateInput">
        </div>
        <div class="col-md-3">
            <label class="est-label"><span class="req">*</span> Version</label>
            <input type="number" class="est-input mono" name="version" id="versionInput" value="<?= htmlspecialchars($nextVersion) ?>" min="1" step="1" required>
            <small class="est-help">Used for document ID suffix (e.g., EST-<?= date('ymd') ?>-###-<?= str_pad((string)$nextVersion, 2, '0', STR_PAD_LEFT) ?>)</small>
        </div>
    </div>

    <!-- Line Items -->
    <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h3 style="font-size:14px;font-weight:600;margin:0"><i class="fas fa-list" style="color:var(--navy-300);margin-right:6px"></i> Line Items</h3>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleCatalog()" id="catalogToggleBtn"><i class="fas fa-book"></i> Browse Catalog</button>
        </div>

        <!-- Inline Catalog Panel -->
        <div class="est-catalog-panel" id="catalogPanel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <h4 style="font-size:13px;font-weight:600;color:var(--navy-300);margin:0"><i class="fas fa-book-open" style="margin-right:6px"></i> Service & Parts Catalog</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCatalog()" style="padding:2px 8px;font-size:11px"><i class="fas fa-times"></i></button>
            </div>
            <div class="est-catalog-tabs">
                <button type="button" class="est-catalog-tab active" onclick="switchCatalogTab('services', this)">Services</button>
                <button type="button" class="est-catalog-tab" onclick="switchCatalogTab('parts', this)">Parts</button>
            </div>
            <div style="position:relative;margin-bottom:12px">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-size:12px"></i>
                <input type="text" class="est-catalog-search" id="catalogSearch" placeholder="Search services or parts..." oninput="filterCatalog()">
            </div>
            <div class="est-catalog-grid" id="catalogGrid">
                <div style="text-align:center;padding:30px;color:var(--text-tertiary);grid-column:1/-1">Loading catalog...</div>
            </div>
        </div>

        <table class="est-line-items" id="lineItemsTable">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th style="width:100px">TYPE</th>
                    <th>DESCRIPTION</th>
                    <th style="width:70px">QTY</th>
                    <th style="width:100px">UNIT PRICE</th>
                    <th style="width:60px">MARKUP</th>
                    <th style="width:110px">EXTENDED</th>
                    <th style="width:30px"></th>
                </tr>
            </thead>
            <tbody id="lineItemsBody">
                <tr id="emptyRow"><td colspan="8" style="text-align:center;padding:30px;color:var(--text-tertiary)">No line items yet. Add items below or from catalog.</td></tr>
            </tbody>
        </table>

        <!-- Add item row -->
        <div class="est-add-row" id="addItemRow">
            <div><label>Type</label><select id="newItemType"><option value="labor">Labor</option><option value="parts">Parts</option><option value="service_fee">Service Fee</option><option value="tow_mileage">Tow/Mileage</option></select></div>
            <div><label>Description</label><input type="text" id="newItemDesc" placeholder="Service or part description"></div>
            <div><label>Qty</label><input type="number" id="newItemQty" value="1" step="0.5" min="0.5"></div>
            <div><label>Unit Price</label><input type="number" id="newItemPrice" value="0.00" step="0.01" min="0"></div>
            <div><label>Markup %</label><input type="number" id="newItemMarkup" value="" step="1" min="0" placeholder="—"></div>
            <div><button type="button" class="btn btn-sm btn-primary" onclick="addLineItem()" style="padding:6px 10px" title="Add item"><i class="fas fa-plus"></i></button></div>
        </div>

        <!-- Totals -->
        <div class="est-totals" id="estimateTotals">
            <div class="est-total-row"><span>Labor</span><span class="money" id="totLabor">$0.00</span></div>
            <div class="est-total-row"><span>Parts</span><span class="money" id="totParts">$0.00</span></div>
            <div class="est-total-row"><span>Services</span><span class="money" id="totServices">$0.00</span></div>
            <div class="est-total-row"><span>Tow/Mileage</span><span class="money" id="totTow">$0.00</span></div>
            <div class="est-total-row" style="border-top:1px solid var(--border-subtle);padding-top:8px"><span>Subtotal</span><span class="money" id="totSubtotal">$0.00</span></div>
            <div class="est-total-row"><span>Tax (<span id="totTaxRate">8.25</span>%)</span><span class="money" id="totTax">$0.00</span></div>
            <div class="est-total-row grand"><span>Estimate Total</span><span class="money" id="totGrand" style="color:var(--green-500)">$0.00</span></div>
        </div>
    </div>

    <div class="mb-3 mt-3">
        <label class="est-label">Internal Notes</label>
        <textarea class="est-textarea" name="internal_notes" rows="2" placeholder="Internal notes (not shown to customer)..."></textarea>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
        <a href="?page=estimates" class="btn btn-secondary">Cancel</a>
        <button type="button" class="btn btn-primary" onclick="saveEstimate('draft')"><i class="fas fa-save"></i> Save Draft</button>
        <button type="button" class="btn btn-primary" onclick="saveEstimate('presented')" style="background:linear-gradient(135deg,#234B78,#2B5EA7)"><i class="fas fa-paper-plane"></i> Save & Present</button>
    </div>
</form>

<script>
window.initialEstimate = <?= json_encode($initialEstimateData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.initialEstimateLineItems = <?= json_encode($prefillLineItemsForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
