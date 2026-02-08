<?php
/**
 * Change Orders — Mid-workflow scope changes, cost impact, approval gate
 * Parent: Work Order
 */
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$woId = isset($_GET['wo_id']) ? intval($_GET['wo_id']) : null;

// Load CO for view
$co = null; $coLineItems = []; $woData = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT co.*, wo.work_order_id as wo_doc_id, wo.service_request_id, wo.authorized_total, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name, st.customer_phone FROM change_orders co LEFT JOIN work_orders wo ON co.work_order_id = wo.id LEFT JOIN technicians t ON co.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE co.id = ?");
    $stmt->execute([$id]);
    $co = $stmt->fetch();
    if ($co) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type='change_order' AND document_id=? ORDER BY line_number");
        $liStmt->execute([$id]);
        $coLineItems = $liStmt->fetchAll();
    }
}

// Load WO data for creating CO
if ($woId && !$co) {
    $stmt = $pdo->prepare("SELECT wo.*, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name FROM work_orders wo LEFT JOIN technicians t ON wo.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE wo.id = ?");
    $stmt->execute([$woId]);
    $woData = $stmt->fetch();
}

// List
$changeOrders = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND co.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT co.*, wo.work_order_id as wo_doc_id, t.first_name as tech_first, t.last_name as tech_last, st.ticket_number, st.customer_name FROM change_orders co LEFT JOIN work_orders wo ON co.work_order_id = wo.id LEFT JOIN technicians t ON co.technician_id = t.id LEFT JOIN service_tickets st ON wo.service_request_id = st.id WHERE {$where} ORDER BY co.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $changeOrders = $stmt->fetchAll();
}
$technicians = $pdo->query("SELECT id, first_name, last_name FROM technicians ORDER BY first_name")->fetchAll();
?>

<style>
.co-header { background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%); border-bottom: 2px solid var(--amber-500); padding: 20px 28px; margin: -28px -28px 24px -28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.co-header h1 { font-size: 24px; font-weight: 700; color: var(--amber-500); letter-spacing: -0.5px; margin: 0; }
.co-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

.co-impact { background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px; padding: 20px; margin-bottom: 16px; text-align: center; }
.co-impact-value { font-family: 'JetBrains Mono', monospace; font-size: 28px; font-weight: 700; }
.co-impact-value.positive { color: var(--red-500); }
.co-impact-value.negative { color: var(--green-500); }

.co-reason-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
.co-reason-discovery { background: rgba(168,85,247,0.1); color: var(--purple-500); }
.co-reason-customer_request { background: rgba(59,130,246,0.1); color: var(--blue-500); }
.co-reason-safety { background: rgba(239,68,68,0.1); color: var(--red-500); }
.co-reason-parts_unavailable { background: rgba(245,158,11,0.1); color: var(--amber-500); }
.co-reason-other { background: rgba(107,114,128,0.1); color: var(--text-tertiary); }
</style>

<?php if ($action === 'list'): ?>

<div class="co-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-exchange-alt" style="font-size:26px;color:var(--amber-500)"></i>
        <div><h1>Change Orders</h1><p class="subtitle">Mid-workflow scope changes and cost adjustments</p></div>
    </div>
    <select onchange="location.href='?page=change-orders&status='+this.value" style="padding:6px 12px;font-size:12px;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:6px;color:var(--text-primary)">
        <option value="">All Statuses</option>
        <option value="proposed" <?= ($_GET['status'] ?? '') === 'proposed' ? 'selected' : '' ?>>Proposed</option>
        <option value="presented" <?= ($_GET['status'] ?? '') === 'presented' ? 'selected' : '' ?>>Presented</option>
        <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="declined" <?= ($_GET['status'] ?? '') === 'declined' ? 'selected' : '' ?>>Declined</option>
    </select>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0">
                <thead>
                    <tr><th>CO ID</th><th>WORK ORDER</th><th>CUSTOMER</th><th>REASON</th><th>IMPACT</th><th>STATUS</th><th>CREATED</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($changeOrders)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-tertiary)">No change orders found.</td></tr>
                    <?php else: foreach ($changeOrders as $c): ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--amber-500)"><?= htmlspecialchars($c['change_order_id']) ?></td>
                            <td><a href="?page=work-orders&action=view&id=<?= $c['work_order_id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($c['wo_doc_id'] ?? '') ?></a></td>
                            <td><?= htmlspecialchars($c['customer_name'] ?? '') ?></td>
                            <td><span class="co-reason-badge co-reason-<?= $c['change_reason'] ?>"><?= str_replace('_', ' ', $c['change_reason']) ?></span></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:<?= $c['net_cost_impact'] >= 0 ? 'var(--red-500)' : 'var(--green-500)' ?>"><?= $c['net_cost_impact'] >= 0 ? '+' : '' ?>$<?= number_format($c['net_cost_impact'], 2) ?></td>
                            <td><?= get_status_badge($c['status']) ?></td>
                            <td style="font-size:12px;color:var(--text-secondary)"><?= format_datetime($c['created_at']) ?></td>
                            <td><a href="?page=change-orders&action=view&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-warning">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'create' && $woData): ?>

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

<?php elseif ($action === 'view' && $co): ?>

<!-- ═══ VIEW CHANGE ORDER ════════════════════════════════════════════ -->
<div class="co-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-exchange-alt" style="font-size:26px;color:var(--amber-500)"></i>
        <div>
            <h1><?= htmlspecialchars($co['change_order_id']) ?></h1>
            <p class="subtitle">Change Order #<?= $co['sequence_num'] ?> for <?= htmlspecialchars($co['wo_doc_id'] ?? '') ?></p>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <?= get_status_badge($co['status']) ?>
        <a href="?page=work-orders&action=view&id=<?= $co['work_order_id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to WO</a>
    </div>
</div>

<!-- Chain -->
<div style="display:flex;align-items:center;gap:6px;margin-bottom:16px;font-size:12px">
    <a href="?page=service-requests&action=view&id=<?= $co['service_request_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($co['ticket_number'] ?? 'SR') ?></a>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <a href="?page=work-orders&action=view&id=<?= $co['work_order_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-clipboard-check"></i> <?= htmlspecialchars($co['wo_doc_id'] ?? 'WO') ?></a>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <span style="color:var(--text-primary);font-weight:600;padding:4px 10px;background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:4px"><i class="fas fa-exchange-alt"></i> <?= htmlspecialchars($co['change_order_id']) ?></span>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <!-- Impact card -->
        <div class="co-impact">
            <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;letter-spacing:0.5px">Net Cost Impact</div>
            <div class="co-impact-value <?= $co['net_cost_impact'] >= 0 ? 'positive' : 'negative' ?>"><?= $co['net_cost_impact'] >= 0 ? '+' : '' ?>$<?= number_format($co['net_cost_impact'], 2) ?></div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:4px">Revised WO Total: <strong style="font-family:'JetBrains Mono',monospace">$<?= number_format($co['revised_wo_total'], 2) ?></strong> (was $<?= number_format($co['authorized_total'] ?? 0, 2) ?>)</div>
        </div>

        <!-- Detail -->
        <div class="card mb-3">
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                    <span class="co-reason-badge co-reason-<?= $co['change_reason'] ?>"><?= str_replace('_', ' ', $co['change_reason']) ?></span>
                    <?php if ($co['must_pause_work']): ?>
                        <span style="font-size:11px;color:var(--red-500)"><i class="fas fa-pause-circle"></i> Work paused</span>
                    <?php endif; ?>
                </div>
                <h5 style="font-size:13px;font-weight:600;margin-bottom:6px">Reason Detail</h5>
                <p style="font-size:13px;color:var(--text-primary)"><?= nl2br(htmlspecialchars($co['reason_detail'])) ?></p>
                <?php if ($co['technician_justification']): ?>
                    <h5 style="font-size:13px;font-weight:600;margin:12px 0 6px">Technician Justification</h5>
                    <p style="font-size:13px;color:var(--text-secondary)"><?= nl2br(htmlspecialchars($co['technician_justification'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Line items -->
        <?php if (!empty($coLineItems)): ?>
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px;margin-bottom:16px">
            <h3 style="font-size:14px;font-weight:600;margin:0 0 12px"><i class="fas fa-list" style="color:var(--amber-500);margin-right:6px"></i> Change Order Items</h3>
            <table class="est-line-items" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr>
                        <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">#</th>
                        <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">TYPE</th>
                        <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">DESCRIPTION</th>
                        <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium)">QTY</th>
                        <th style="background:var(--bg-secondary);padding:8px;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-tertiary);border-bottom:1px solid var(--border-medium);text-align:right">EXTENDED</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coLineItems as $li): ?>
                    <tr style="border-bottom:1px solid var(--border-subtle)">
                        <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)"><?= $li['line_number'] ?></td>
                        <td style="padding:8px"><span style="font-size:10px;padding:2px 8px;border-radius:3px;text-transform:uppercase;font-weight:600;background:rgba(245,158,11,0.1);color:var(--amber-500)"><?= str_replace('_', ' ', $li['item_type']) ?></span></td>
                        <td style="padding:8px;font-size:13px"><?= htmlspecialchars($li['description']) ?></td>
                        <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px"><?= number_format($li['quantity'], 2) ?></td>
                        <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;text-align:right">$<?= number_format($li['extended_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <!-- Actions -->
        <?php if (in_array($co['status'], ['proposed', 'presented'])): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 style="font-size:13px;font-weight:600;margin-bottom:12px">Actions</h5>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <?php if ($co['status'] === 'proposed'): ?>
                        <button class="btn btn-primary btn-sm" onclick="updateCoStatus(<?= $co['id'] ?>,'presented')"><i class="fas fa-paper-plane"></i> Present to Customer</button>
                    <?php endif; ?>
                    <button class="btn btn-success btn-sm" onclick="approveCo(<?= $co['id'] ?>)"><i class="fas fa-check"></i> Approve</button>
                    <button class="btn btn-danger btn-sm" onclick="declineCo(<?= $co['id'] ?>)"><i class="fas fa-times"></i> Decline</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Info -->
        <div class="card">
            <div class="card-body">
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Customer</div>
                <div style="font-size:13px;color:var(--text-primary);margin-bottom:10px"><?= htmlspecialchars($co['customer_name'] ?? '') ?></div>
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Technician</div>
                <div style="font-size:13px;color:var(--text-primary);margin-bottom:10px"><?= htmlspecialchars(($co['tech_first'] ?? '') . ' ' . ($co['tech_last'] ?? '')) ?></div>
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Created</div>
                <div style="font-size:13px;color:var(--text-primary)"><?= format_datetime($co['created_at']) ?></div>
                <?php if ($co['approved_at']): ?>
                    <div style="margin-top:10px;font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Approved</div>
                    <div style="font-size:13px;color:var(--green-500)"><?= format_datetime($co['approved_at']) ?> (<?= $co['approval_method'] ?? 'verbal' ?>)</div>
                <?php endif; ?>
                <?php if ($co['declined_at']): ?>
                    <div style="margin-top:10px;font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Declined</div>
                    <div style="font-size:13px;color:var(--red-500)"><?= format_datetime($co['declined_at']) ?></div>
                    <?php if ($co['decline_reason']): ?>
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:2px"><?= htmlspecialchars($co['decline_reason']) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
const API_W = 'api/workflow.php';
let coItems = [];
let coLineNum = 0;

function addCoItem() {
    const type = document.getElementById('coItemType').value;
    const desc = document.getElementById('coItemDesc').value.trim();
    const qty = parseFloat(document.getElementById('coItemQty').value) || 1;
    const price = parseFloat(document.getElementById('coItemPrice').value) || 0;
    if (!desc) { alert('Description required'); return; }
    coLineNum++;
    const ext = Math.round(qty * price * 100) / 100;
    coItems.push({ line_number: coLineNum, item_type: type, description: desc, quantity: qty, unit_price: price, extended_price: ext, taxable: 1 });
    renderCoItems();
    document.getElementById('coItemDesc').value = '';
    document.getElementById('coItemPrice').value = '0.00';
    updateNetImpact();
}

function removeCoItem(idx) {
    coItems.splice(idx, 1);
    coItems.forEach((li, i) => li.line_number = i + 1);
    coLineNum = coItems.length;
    renderCoItems();
    updateNetImpact();
}

function renderCoItems() {
    const tbody = document.getElementById('coItemsBody');
    if (!coItems.length) { tbody.innerHTML = '<tr id="coEmptyRow"><td colspan="7" style="text-align:center;padding:20px;color:var(--text-tertiary)">No items.</td></tr>'; return; }
    tbody.innerHTML = coItems.map((li, idx) => `
        <tr style="border-bottom:1px solid var(--border-subtle)">
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px">${li.line_number}</td>
            <td style="padding:8px"><span style="font-size:10px;padding:2px 8px;border-radius:3px;text-transform:uppercase;font-weight:600;background:rgba(245,158,11,0.1);color:var(--amber-500)">${li.item_type.replace(/_/g,' ')}</span></td>
            <td style="padding:8px;font-size:13px">${li.description}</td>
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px">${li.quantity.toFixed(2)}</td>
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px">${li.unit_price.toFixed(2)}</td>
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;text-align:right">$${li.extended_price.toFixed(2)}</td>
            <td style="padding:8px"><span style="cursor:pointer;color:var(--text-tertiary);font-size:12px" onclick="removeCoItem(${idx})"><i class="fas fa-times"></i></span></td>
        </tr>
    `).join('');
}

function updateNetImpact() {
    const total = coItems.reduce((sum, li) => sum + li.extended_price, 0);
    document.getElementById('netImpactInput').value = total.toFixed(2);
}

async function submitCO() {
    const form = document.getElementById('coForm');
    const fd = new FormData(form);
    fd.append('action', 'create_change_order');
    fd.append('line_items', JSON.stringify(coItems));
    try {
        const res = await fetch(API_W, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) window.location.href = `?page=change-orders&action=view&id=${json.data.id}`;
        else alert('Error: ' + (json.error || 'Failed'));
    } catch(e) { alert('Network error'); }
}

async function updateCoStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_change_order_status');
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function approveCo(id) {
    const method = prompt('Approval method (verbal, signature, digital):', 'verbal');
    if (!method) return;
    const fd = new FormData();
    fd.append('action', 'update_change_order_status');
    fd.append('id', id);
    fd.append('status', 'approved');
    fd.append('approval_method', method);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function declineCo(id) {
    const reason = prompt('Decline reason:');
    if (reason === null) return;
    const fd = new FormData();
    fd.append('action', 'update_change_order_status');
    fd.append('id', id);
    fd.append('status', 'declined');
    fd.append('decline_reason', reason);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}
</script>
