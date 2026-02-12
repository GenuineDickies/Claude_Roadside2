<?php
/**
 * Estimate Builder — Create/View/Manage Estimates
 * Parent: Service Request. Line items from catalog. Tax calc. Approval workflow.
 */
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$srId = isset($_GET['sr_id']) ? intval($_GET['sr_id']) : null;

// Get service request data if creating from SR
$srData = null;
if ($srId) {
    $stmt = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
    $stmt->execute([$srId]);
    $srData = $stmt->fetch();
}

// Get estimate data if viewing/editing
$estimate = null;
$lineItems = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT e.*, t.first_name as tech_first, t.last_name as tech_last FROM estimates e LEFT JOIN technicians t ON e.technician_id = t.id WHERE e.id = ?");
    $stmt->execute([$id]);
    $estimate = $stmt->fetch();
    if ($estimate) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type = 'estimate' AND document_id = ? ORDER BY line_number");
        $liStmt->execute([$id]);
        $lineItems = $liStmt->fetchAll();
        if (!$srData) {
            $stmt2 = $pdo->prepare("SELECT st.*, c.first_name, c.last_name, c.phone, c.email FROM service_tickets st LEFT JOIN customers c ON st.customer_id = c.id WHERE st.id = ?");
            $stmt2->execute([$estimate['service_request_id']]);
            $srData = $stmt2->fetch();
        }
    }
}

// List all estimates
$estimates = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND e.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT e.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.service_category FROM estimates e LEFT JOIN technicians t ON e.technician_id = t.id LEFT JOIN service_tickets st ON e.service_request_id = st.id WHERE {$where} ORDER BY e.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $estimates = $stmt->fetchAll();
}

$technicians = $pdo->query("SELECT id, first_name, last_name, specialization FROM technicians ORDER BY first_name")->fetchAll();
?>

<style>
/* ── Estimate Page — Scoped Styles ───────────────────────────────── */
.est-header {
    background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 20px 28px; margin: -28px -28px 24px -28px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.est-header h1 { font-size: 24px; font-weight: 700; color: var(--navy-300); letter-spacing: -0.5px; margin: 0; }
.est-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

/* Status lifecycle bar */
.est-lifecycle { display: flex; gap: 4px; margin-bottom: 20px; }
.est-lifecycle-step { flex: 1; padding: 8px 12px; text-align: center; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 6px; color: var(--text-tertiary); }
.est-lifecycle-step.active { background: rgba(43,94,167,0.12); border-color: var(--navy-500); color: var(--navy-300); }
.est-lifecycle-step.done { background: rgba(34,197,94,0.08); border-color: rgba(34,197,94,0.3); color: var(--green-500); }

/* Breadcrumb chain */
.doc-chain { display: flex; align-items: center; gap: 6px; margin-bottom: 16px; font-size: 12px; }
.doc-chain a { color: var(--navy-300); text-decoration: none; padding: 4px 10px; background: rgba(43,94,167,0.08); border-radius: 4px; }
.doc-chain a:hover { background: rgba(43,94,167,0.15); }
.doc-chain .sep { color: var(--text-tertiary); }
.doc-chain .current { color: var(--text-primary); font-weight: 600; padding: 4px 10px; background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 4px; }

/* Inherited info card */
.est-inherited { background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; }
.est-inherited h3 { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-tertiary); margin: 0 0 10px; }
.est-inherited-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
.est-inherited-item .lbl { font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.3px; }
.est-inherited-item .val { font-size: 13px; color: var(--text-primary); font-weight: 500; margin-top: 2px; }

/* Line items table */
.est-line-items { width: 100%; border-collapse: collapse; margin-top: 12px; }
.est-line-items th { background: var(--bg-secondary); padding: 10px 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-tertiary); border-bottom: 1px solid var(--border-medium); text-align: left; }
.est-line-items th:last-child { text-align: right; }
.est-line-items td { padding: 10px 12px; border-bottom: 1px solid var(--border-subtle); font-size: 13px; vertical-align: middle; }
.est-line-items td:last-child { text-align: right; }
.est-line-items .money { font-family: 'JetBrains Mono', monospace; font-size: 12px; }
.est-line-items .type-badge { font-size: 10px; padding: 2px 8px; border-radius: 3px; text-transform: uppercase; font-weight: 600; }
.est-line-items .type-labor { background: rgba(59,130,246,0.1); color: var(--blue-500); }
.est-line-items .type-parts { background: rgba(168,85,247,0.1); color: var(--purple-500); }
.est-line-items .type-service_fee { background: rgba(34,197,94,0.1); color: var(--green-500); }
.est-line-items .type-tow_mileage { background: rgba(245,158,11,0.1); color: var(--amber-500); }
.est-line-items tr:hover { background: var(--bg-surface-hover); }
.est-line-items .remove-btn { color: var(--text-tertiary); cursor: pointer; font-size: 12px; opacity: 0.5; }
.est-line-items .remove-btn:hover { color: var(--red-500); opacity: 1; }

/* Totals */
.est-totals { margin-top: 16px; margin-left: auto; width: 320px; }
.est-total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
.est-total-row.grand { padding: 12px 0; border-top: 2px solid var(--navy-500); font-size: 16px; font-weight: 700; margin-top: 4px; }
.est-total-row .money { font-family: 'JetBrains Mono', monospace; }

/* Form elements — scoped to estimates page */
.est-label { font-size: 12px; font-weight: 600; color: #BCC3D4; margin-bottom: 4px; display: flex; align-items: center; gap: 4px; }
.est-label .req { color: var(--red-500); }
.est-input, .est-select, .est-textarea {
    width: 100%; background: var(--bg-primary); border: 1px solid var(--border-medium);
    border-radius: 6px; padding: 8px 12px; color: var(--text-primary); font-size: 13px;
    font-family: 'DM Sans', sans-serif; transition: border-color 0.2s, box-shadow 0.2s;
}
.est-input:focus, .est-select:focus, .est-textarea:focus {
    border-color: var(--navy-400); outline: none; box-shadow: 0 0 0 3px rgba(43,94,167,0.15);
}
.est-input.mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; }
.est-textarea { resize: vertical; min-height: 60px; }
.est-select { cursor: pointer; }

/* Add item form */
.est-add-row { display: grid; grid-template-columns: 140px 1fr 80px 100px 100px 36px; gap: 8px; align-items: end; margin-top: 12px; padding: 12px; background: var(--bg-primary); border: 1px dashed var(--border-medium); border-radius: 6px; }
@media (max-width: 768px) { .est-add-row { grid-template-columns: 1fr 1fr; } }
.est-add-row label { font-size: 11px; color: var(--text-tertiary); margin-bottom: 2px; display: block; }
.est-add-row input, .est-add-row select { background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 4px; padding: 6px 8px; color: var(--text-primary); font-size: 12px; width: 100%; }
.est-add-row input:focus, .est-add-row select:focus { border-color: var(--navy-400); outline: none; }

/* Catalog panel */
.est-catalog-panel { background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px; padding: 20px; margin-bottom: 20px; display: none; }
.est-catalog-panel.open { display: block; }
.est-catalog-tabs { display: flex; gap: 4px; margin-bottom: 14px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 8px; }
.est-catalog-tab { padding: 6px 14px; border-radius: 6px 6px 0 0; font-size: 12px; font-weight: 600; color: var(--text-secondary); cursor: pointer; border: 1px solid transparent; border-bottom: none; background: transparent; }
.est-catalog-tab:hover { color: var(--text-primary); }
.est-catalog-tab.active { color: var(--navy-300); background: rgba(43,94,167,0.08); border-color: var(--border-medium); }
.est-catalog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; max-height: 320px; overflow-y: auto; padding-right: 4px; }
.est-catalog-item { background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 8px; padding: 12px 14px; cursor: pointer; transition: all 0.15s; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.est-catalog-item:hover { border-color: var(--navy-400); background: var(--bg-surface-hover); transform: translateY(-1px); }
.est-catalog-item .item-info { flex: 1; min-width: 0; }
.est-catalog-item .item-name { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.est-catalog-item .item-meta { font-size: 11px; color: var(--text-tertiary); margin-top: 2px; }
.est-catalog-item .item-price { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 600; color: var(--navy-300); white-space: nowrap; }
.est-catalog-search { width: 100%; background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 6px; padding: 8px 12px 8px 34px; color: var(--text-primary); font-size: 13px; margin-bottom: 12px; }
.est-catalog-search:focus { border-color: var(--navy-400); outline: none; }

/* Approval card */
.est-approval { background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px; padding: 20px; margin-top: 20px; }
.est-approval h3 { font-size: 14px; font-weight: 600; margin: 0 0 12px; color: var(--text-primary); }

/* List table */
.est-list-status { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
</style>

<?php if ($action === 'list'): ?>

<!-- ═══ ESTIMATES LIST ════════════════════════════════════════════════ -->
<div class="est-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Estimates</h1>
            <p class="subtitle">Diagnosis & cost proposals for customer approval</p>
        </div>
    </div>
    <div style="display:flex;gap:8px">
        <select class="est-select" onchange="location.href='?page=estimates&status='+this.value" style="width:auto;padding:6px 12px;font-size:12px">
            <option value="">All Statuses</option>
            <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="presented" <?= ($_GET['status'] ?? '') === 'presented' ? 'selected' : '' ?>>Presented</option>
            <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="declined" <?= ($_GET['status'] ?? '') === 'declined' ? 'selected' : '' ?>>Declined</option>
        </select>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:140px">ESTIMATE ID</th>
                        <th>TICKET</th>
                        <th>CUSTOMER</th>
                        <th>TECHNICIAN</th>
                        <th>TOTAL</th>
                        <th>STATUS</th>
                        <th>CREATED</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($estimates)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-tertiary)">No estimates found. Estimates are created from Service Requests.</td></tr>
                    <?php else: foreach ($estimates as $est): ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--navy-300)"><?= htmlspecialchars($est['estimate_id']) ?></td>
                            <td><a href="?page=service-requests&action=view&id=<?= $est['service_request_id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($est['ticket_number'] ?? '#' . $est['service_request_id']) ?></a></td>
                            <td><?= htmlspecialchars($est['customer_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars(($est['tech_first'] ?? '') . ' ' . ($est['tech_last'] ?? '')) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600">$<?= number_format($est['total'], 2) ?></td>
                            <td><?= get_status_badge($est['status']) ?></td>
                            <td style="font-size:12px;color:var(--text-secondary)"><?= format_datetime($est['created_at']) ?></td>
                            <td><a href="?page=estimates&action=view&id=<?= $est['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'create' && $srData): ?>

<!-- ═══ CREATE ESTIMATE ═══════════════════════════════════════════════ -->
<div class="est-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Create Estimate</h1>
            <p class="subtitle">For <?= htmlspecialchars($srData['ticket_number'] ?? 'SR #' . $srData['id']) ?> — <?= htmlspecialchars($srData['customer_name'] ?? '') ?></p>
        </div>
    </div>
    <a href="?page=estimates" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<!-- Document chain breadcrumb -->
<div class="doc-chain">
    <a href="?page=service-requests&action=view&id=<?= $srData['id'] ?>"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($srData['ticket_number'] ?? 'SR #' . $srData['id']) ?></a>
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
    </div>

    <div class="mb-3">
        <label class="est-label"><span class="req">*</span> Diagnosis Summary</label>
        <textarea class="est-textarea" name="diagnosis_summary" rows="3" placeholder="Technician's on-scene assessment..." required></textarea>
    </div>
    <div class="mb-4">
        <label class="est-label">Diagnostic Codes (OBD-II)</label>
        <input type="text" class="est-input mono" name="diagnostic_codes" placeholder="P0300, P0171, etc. (comma separated)">
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
            <div>
                <label>Type</label>
                <select id="newItemType">
                    <option value="labor">Labor</option>
                    <option value="parts">Parts</option>
                    <option value="service_fee">Service Fee</option>
                    <option value="tow_mileage">Tow/Mileage</option>
                </select>
            </div>
            <div>
                <label>Description</label>
                <input type="text" id="newItemDesc" placeholder="Service or part description">
            </div>
            <div>
                <label>Qty</label>
                <input type="number" id="newItemQty" value="1" step="0.5" min="0.5">
            </div>
            <div>
                <label>Unit Price</label>
                <input type="number" id="newItemPrice" value="0.00" step="0.01" min="0">
            </div>
            <div>
                <label>Markup %</label>
                <input type="number" id="newItemMarkup" value="" step="1" min="0" placeholder="—">
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addLineItem()" style="padding:6px 10px" title="Add item"><i class="fas fa-plus"></i></button>
            </div>
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

<?php elseif ($action === 'view' && $estimate): ?>

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
    <a href="?page=service-requests&action=view&id=<?= $estimate['service_request_id'] ?>"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($srData['ticket_number'] ?? 'SR') ?></a>
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

<!-- Diagnosis -->
<div class="card mb-3">
    <div class="card-body">
        <h5 style="font-size:13px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px"><i class="fas fa-stethoscope" style="color:var(--navy-300)"></i> Diagnosis</h5>
        <p style="font-size:13px;color:var(--text-primary)"><?= nl2br(htmlspecialchars($estimate['diagnosis_summary'])) ?></p>
        <?php if ($estimate['diagnostic_codes']): ?>
            <div style="margin-top:8px">
                <?php foreach (json_decode($estimate['diagnostic_codes'], true) ?: [] as $code): ?>
                    <span style="display:inline-block;padding:2px 8px;background:rgba(168,85,247,0.1);color:var(--purple-500);border-radius:3px;font-family:'JetBrains Mono',monospace;font-size:12px;margin:2px"><?= htmlspecialchars($code) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

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
        <div class="card">
            <div class="card-body">
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:0.3px">Valid Until</div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:14px;color:<?= strtotime($estimate['valid_until']) < time() ? 'var(--red-500)' : 'var(--text-primary)' ?>"><?= format_datetime($estimate['valid_until']) ?></div>
            </div>
        </div>
    </div>
    <?php if ($estimate['approved_at']): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:0.3px">Approved</div>
                <div style="font-size:14px;color:var(--green-500)"><?= format_datetime($estimate['approved_at']) ?> (<?= $estimate['approval_method'] ?? 'verbal' ?>)</div>
            </div>
        </div>
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
        <a href="?page=estimates&action=create&sr_id=<?= $estimate['service_request_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-redo"></i> Create Revision</a>
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

<?php endif; // end view ?>

<script>
const API_W = 'api/workflow.php';
const API_T = 'api/service-tickets.php';
let lineItems = [];
let lineNum = 0;

// ─── Add Line Item ──────────────────────────────────────────────────
function addLineItem() {
    const type = document.getElementById('newItemType').value;
    const desc = document.getElementById('newItemDesc').value.trim();
    const qty = parseFloat(document.getElementById('newItemQty').value) || 1;
    const price = parseFloat(document.getElementById('newItemPrice').value) || 0;
    const markup = document.getElementById('newItemMarkup').value ? parseFloat(document.getElementById('newItemMarkup').value) : null;

    if (!desc) { alert('Description required'); return; }

    lineNum++;
    const ext = Math.round(qty * price * (1 + (markup || 0) / 100) * 100) / 100;
    lineItems.push({ line_number: lineNum, item_type: type, description: desc, quantity: qty, unit_price: price, markup_pct: markup, extended_price: ext, taxable: 1 });

    renderLineItems();

    // Clear inputs
    document.getElementById('newItemDesc').value = '';
    document.getElementById('newItemPrice').value = '0.00';
    document.getElementById('newItemMarkup').value = '';
    document.getElementById('newItemDesc').focus();
}

function removeLineItem(idx) {
    lineItems.splice(idx, 1);
    lineItems.forEach((li, i) => li.line_number = i + 1);
    lineNum = lineItems.length;
    renderLineItems();
}

function renderLineItems() {
    const tbody = document.getElementById('lineItemsBody');
    if (!lineItems.length) {
        tbody.innerHTML = '<tr id="emptyRow"><td colspan="8" style="text-align:center;padding:30px;color:var(--text-tertiary)">No line items yet.</td></tr>';
        updateTotals();
        return;
    }
    tbody.innerHTML = lineItems.map((li, idx) => `
        <tr>
            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)">${li.line_number}</td>
            <td><span class="type-badge type-${li.item_type}">${li.item_type.replace(/_/g,' ')}</span></td>
            <td>${li.description}</td>
            <td class="money">${li.quantity.toFixed(2)}</td>
            <td class="money">$${li.unit_price.toFixed(2)}</td>
            <td class="money">${li.markup_pct !== null ? li.markup_pct + '%' : '—'}</td>
            <td class="money" style="font-weight:600">$${li.extended_price.toFixed(2)}</td>
            <td><span class="remove-btn" onclick="removeLineItem(${idx})"><i class="fas fa-times"></i></span></td>
        </tr>
    `).join('');
    updateTotals();
}

function updateTotals() {
    let labor = 0, parts = 0, services = 0, tow = 0;
    lineItems.forEach(li => {
        switch(li.item_type) {
            case 'labor': labor += li.extended_price; break;
            case 'parts': parts += li.extended_price; break;
            case 'service_fee': services += li.extended_price; break;
            case 'tow_mileage': tow += li.extended_price; break;
        }
    });
    const subtotal = labor + parts + services + tow;
    const taxRate = parseFloat(document.getElementById('taxRateInput')?.value || 8.25) / 100;
    const taxable = lineItems.reduce((sum, li) => sum + (li.taxable ? li.extended_price : 0), 0);
    const tax = Math.round(taxable * taxRate * 100) / 100;
    const grand = subtotal + tax;

    document.getElementById('totLabor').textContent = '$' + labor.toFixed(2);
    document.getElementById('totParts').textContent = '$' + parts.toFixed(2);
    document.getElementById('totServices').textContent = '$' + services.toFixed(2);
    document.getElementById('totTow').textContent = '$' + tow.toFixed(2);
    document.getElementById('totSubtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('totTaxRate').textContent = (taxRate * 100).toFixed(2);
    document.getElementById('totTax').textContent = '$' + tax.toFixed(2);
    document.getElementById('totGrand').textContent = '$' + grand.toFixed(2);
}

document.getElementById('taxRateInput')?.addEventListener('input', updateTotals);

// ─── Catalog Panel ──────────────────────────────────────────────────
let catalogData = { services: [], parts: [] };
let catalogTab = 'services';

function toggleCatalog() {
    const panel = document.getElementById('catalogPanel');
    const isOpen = panel.classList.toggle('open');
    if (isOpen && !catalogData.services.length && !catalogData.parts.length) loadCatalog();
}

function switchCatalogTab(tab, btn) {
    catalogTab = tab;
    document.querySelectorAll('.est-catalog-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('catalogSearch').value = '';
    renderCatalog();
}

async function loadCatalog() {
    try {
        const [svcRes, partsRes] = await Promise.all([
            fetch(API_T + '?action=get_services'),
            fetch(API_W + '?action=search_parts&q=')
        ]);
        const svcJson = await svcRes.json();
        const partsJson = await partsRes.json();
        if (svcJson.success) catalogData.services = svcJson.data;
        if (partsJson.success) catalogData.parts = partsJson.data;
        renderCatalog();
    } catch(e) { console.error(e); }
}

function filterCatalog() {
    renderCatalog();
}

function renderCatalog() {
    const grid = document.getElementById('catalogGrid');
    const search = document.getElementById('catalogSearch').value.toLowerCase().trim();
    let items = catalogTab === 'services' ? catalogData.services : catalogData.parts;

    if (search) {
        items = items.filter(i => {
            const name = (i.name || '').toLowerCase();
            const cat = (i.category_name || i.category || '').toLowerCase();
            const num = (i.part_number || '').toLowerCase();
            return name.includes(search) || cat.includes(search) || num.includes(search);
        });
    }

    if (!items.length) {
        grid.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-tertiary);grid-column:1/-1">' +
            (search ? 'No matches for "' + search + '"' : 'No items in catalog') + '</div>';
        return;
    }

    if (catalogTab === 'services') {
        grid.innerHTML = items.map(s =>
            '<div class="est-catalog-item" onclick="addCatalogService(' + JSON.stringify(s).replace(/"/g, '&quot;') + ')">' +
            '<div class="item-info"><div class="item-name">' + escHtml(s.name) + '</div>' +
            '<div class="item-meta">' + escHtml(s.category_name || '') + '</div></div>' +
            '<div class="item-price">$' + parseFloat(s.base_rate).toFixed(2) + '</div></div>'
        ).join('');
    } else {
        grid.innerHTML = items.map(p =>
            '<div class="est-catalog-item" onclick="addCatalogPart(' + JSON.stringify(p).replace(/"/g, '&quot;') + ')">' +
            '<div class="item-info"><div class="item-name">' + escHtml(p.name) + '</div>' +
            '<div class="item-meta"><span style="font-family:JetBrains Mono,monospace;font-size:10px">' + escHtml(p.part_number) + '</span> · ' + escHtml(p.category || '') + ' · ' + p.markup_pct + '% markup</div></div>' +
            '<div class="item-price">$' + parseFloat(p.unit_cost).toFixed(2) + '</div></div>'
        ).join('');
    }
}

function addCatalogService(s) {
    document.getElementById('newItemType').value = 'service_fee';
    document.getElementById('newItemDesc').value = s.name;
    document.getElementById('newItemPrice').value = s.base_rate;
    document.getElementById('newItemMarkup').value = '';
    addLineItem();
}

function addCatalogPart(p) {
    document.getElementById('newItemType').value = 'parts';
    document.getElementById('newItemDesc').value = '[' + p.part_number + '] ' + p.name;
    document.getElementById('newItemPrice').value = p.unit_cost;
    document.getElementById('newItemMarkup').value = p.markup_pct;
    addLineItem();
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ─── Save Estimate ──────────────────────────────────────────────────
async function saveEstimate(initialStatus = 'draft') {
    const form = document.getElementById('estimateForm');
    const fd = new FormData(form);
    fd.append('action', 'create_estimate');
    fd.append('tax_rate', (parseFloat(fd.get('tax_rate_pct')) / 100).toFixed(4));
    fd.append('line_items', JSON.stringify(lineItems));

    // Parse diagnostic codes
    const codes = fd.get('diagnostic_codes')?.split(',').map(c => c.trim()).filter(Boolean);
    if (codes?.length) fd.set('diagnostic_codes', JSON.stringify(codes));

    try {
        const res = await fetch(API_W, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            if (initialStatus === 'presented') {
                const fd2 = new FormData();
                fd2.append('action', 'update_estimate_status');
                fd2.append('id', json.data.id);
                fd2.append('status', 'presented');
                await fetch(API_W, { method: 'POST', body: fd2 });
            }
            window.location.href = `?page=estimates&action=view&id=${json.data.id}`;
        } else {
            alert('Error: ' + (json.error || 'Failed'));
        }
    } catch(e) { alert('Network error'); }
}

// ─── Status Actions ─────────────────────────────────────────────────
async function updateEstStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_estimate_status');
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) location.reload();
    else alert('Error updating status');
}

async function approveEstimate(id) {
    const method = prompt('Approval method (verbal, signature, digital):', 'verbal');
    if (!method) return;
    const name = prompt('Approver name:');
    const fd = new FormData();
    fd.append('action', 'update_estimate_status');
    fd.append('id', id);
    fd.append('status', 'approved');
    fd.append('approval_method', method);
    if (name) fd.append('approver_name', name);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function declineEstimate(id) {
    const reason = prompt('Decline reason:');
    if (reason === null) return;
    const fd = new FormData();
    fd.append('action', 'update_estimate_status');
    fd.append('id', id);
    fd.append('status', 'declined');
    fd.append('decline_reason', reason);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function createWoFromEstimate(estId, srId, techId) {
    const fd = new FormData();
    fd.append('action', 'create_work_order');
    fd.append('service_request_id', srId);
    fd.append('estimate_id', estId);
    fd.append('technician_id', techId);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        window.location.href = `?page=work-orders&action=view&id=${json.data.id}`;
    } else {
        alert('Error: ' + (json.error || 'Failed'));
    }
}
</script>
