<?php
/**
 * Invoice Generator (v2) — Workflow chain invoices
 * Auto-generated from completed WOs. Payment recording, discounts, delivery.
 */
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load invoice for view
$invoice = null; $lineItems = []; $payments = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT iv.*, wo.work_order_id as wo_doc_id, wo.technician_id, st.ticket_number, st.customer_name, st.customer_phone, st.customer_email, st.service_address, st.service_category, st.vehicle_year, st.vehicle_make, st.vehicle_model, t.first_name as tech_first, t.last_name as tech_last FROM invoices_v2 iv LEFT JOIN work_orders wo ON iv.work_order_id = wo.id LEFT JOIN service_tickets st ON iv.service_request_id = st.id LEFT JOIN technicians t ON wo.technician_id = t.id WHERE iv.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
    if ($invoice) {
        $liStmt = $pdo->prepare("SELECT * FROM document_line_items WHERE document_type='invoice' AND document_id=? ORDER BY line_number");
        $liStmt->execute([$id]);
        $lineItems = $liStmt->fetchAll();
        $ptStmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE invoice_id=? ORDER BY created_at");
        $ptStmt->execute([$id]);
        $payments = $ptStmt->fetchAll();
    }
}

// List
$invoices = [];
if ($action === 'list') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['status'])) { $where .= ' AND iv.status = ?'; $params[] = $_GET['status']; }
    $stmt = $pdo->prepare("SELECT iv.*, st.ticket_number, st.customer_name, st.customer_phone FROM invoices_v2 iv LEFT JOIN service_tickets st ON iv.service_request_id = st.id WHERE {$where} ORDER BY iv.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
}
?>

<style>
.inv-header { background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-secondary) 100%); border-bottom: 2px solid var(--navy-500); padding: 20px 28px; margin: -28px -28px 24px -28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.inv-header h1 { font-size: 24px; font-weight: 700; color: var(--navy-300); letter-spacing: -0.5px; margin: 0; }
.inv-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

.inv-amount-card { background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px; padding: 16px; text-align: center; }
.inv-amount-card .lbl { font-size: 11px; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.5px; }
.inv-amount-card .val { font-family: 'JetBrains Mono', monospace; font-size: 22px; font-weight: 700; margin-top: 4px; }

.inv-payment-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-subtle); font-size: 13px; }
.inv-payment-row:last-child { border-bottom: none; }
</style>

<?php if ($action === 'list'): ?>

<div class="inv-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice-dollar" style="font-size:26px;color:var(--navy-300)"></i>
        <div><h1>Invoices</h1><p class="subtitle">Billing from completed work orders</p></div>
    </div>
    <select onchange="location.href='?page=invoices-v2&status='+this.value" style="padding:6px 12px;font-size:12px;background:var(--bg-primary);border:1px solid var(--border-medium);border-radius:6px;color:var(--text-primary)">
        <option value="">All Statuses</option>
        <option value="generated" <?= ($_GET['status'] ?? '') === 'generated' ? 'selected' : '' ?>>Generated</option>
        <option value="sent" <?= ($_GET['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="partial" <?= ($_GET['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Partial</option>
        <option value="paid" <?= ($_GET['status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
        <option value="overdue" <?= ($_GET['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
    </select>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0">
                <thead>
                    <tr><th>INVOICE</th><th>TICKET</th><th>CUSTOMER</th><th>TOTAL</th><th>PAID</th><th>BALANCE</th><th>STATUS</th><th>DUE DATE</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-tertiary)">No invoices. Invoices auto-generate when work orders complete.</td></tr>
                    <?php else: foreach ($invoices as $inv): ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--navy-300)"><?= htmlspecialchars($inv['invoice_id']) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($inv['ticket_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($inv['customer_name'] ?? '') ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600">$<?= number_format($inv['grand_total'], 2) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--green-500)">$<?= number_format($inv['amount_paid'], 2) ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:<?= $inv['balance_due'] > 0 ? 'var(--amber-500)' : 'var(--green-500)' ?>">$<?= number_format($inv['balance_due'], 2) ?></td>
                            <td><?= get_status_badge($inv['status']) ?></td>
                            <td style="font-size:12px;color:<?= strtotime($inv['due_date']) < time() && $inv['balance_due'] > 0 ? 'var(--red-500)' : 'var(--text-secondary)' ?>"><?= format_date($inv['due_date']) ?></td>
                            <td><a href="?page=invoices-v2&action=view&id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'view' && $invoice): ?>

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
    <a href="?page=service-requests&action=view&id=<?= $invoice['service_request_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars($invoice['ticket_number'] ?? 'SR') ?></a>
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
    </div>

    <div class="col-lg-4">
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
    </div>
</div>

<?php endif; ?>

<script>
const API_W = 'api/workflow.php';

async function updateInvStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_invoice_status');
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function recordPayment(invId) {
    const amount = prompt('Payment amount ($):');
    if (!amount) return;
    const method = prompt('Payment method (card, cash, check, insurance_claim, fleet_po):', 'card');
    if (!method) return;
    const txnId = prompt('Transaction/reference ID (optional):');

    const fd = new FormData();
    fd.append('action', 'record_payment');
    fd.append('invoice_id', invId);
    fd.append('amount', parseFloat(amount));
    fd.append('payment_method', method);
    if (txnId) fd.append('processor_txn_id', txnId);

    const res = await fetch(API_W, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        if (json.data.receipt) {
            if (confirm('Payment recorded! Receipt generated. View receipt?')) {
                window.location.href = `?page=receipts&action=view&id=${json.data.receipt.id}`;
                return;
            }
        }
        location.reload();
    } else {
        alert('Error: ' + (json.error || 'Failed'));
    }
}

async function applyDiscount(invId) {
    const amount = prompt('Discount amount ($):');
    if (!amount) return;
    const reason = prompt('Discount reason:');

    const fd = new FormData();
    fd.append('action', 'apply_discount');
    fd.append('invoice_id', invId);
    fd.append('discount_amount', parseFloat(amount));
    fd.append('discount_reason', reason || '');

    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}
</script>
