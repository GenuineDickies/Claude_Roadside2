<?php
/**
 * Receipts — Payment confirmation & service closure
 * Auto-generated on full invoice payment. Final document in chain.
 */
require_once __DIR__ . '/../config/workflow_schema.php';
bootstrap_workflow_schema($pdo);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Load receipt
$receipt = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT r.*, iv.invoice_id as inv_doc_id, iv.grand_total, iv.payment_terms, iv.work_order_id, wo.work_order_id as wo_doc_id, st.ticket_number, st.customer_name, st.customer_phone, st.customer_email, st.service_address, st.service_category, st.vehicle_year, st.vehicle_make, st.vehicle_model, t.first_name as tech_first, t.last_name as tech_last FROM receipts r LEFT JOIN invoices_v2 iv ON r.invoice_id = iv.id LEFT JOIN work_orders wo ON iv.work_order_id = wo.id LEFT JOIN service_tickets st ON r.service_request_id = st.id LEFT JOIN technicians t ON wo.technician_id = t.id WHERE r.id = ?");
    $stmt->execute([$id]);
    $receipt = $stmt->fetch();
}

// List
$receipts = [];
if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT r.*, iv.invoice_id as inv_doc_id, st.ticket_number, st.customer_name FROM receipts r LEFT JOIN invoices_v2 iv ON r.invoice_id = iv.id LEFT JOIN service_tickets st ON r.service_request_id = st.id ORDER BY r.created_at DESC LIMIT 100");
    $stmt->execute();
    $receipts = $stmt->fetchAll();
}
?>

<style>
.rct-header { background: linear-gradient(135deg, rgba(34,197,94,0.05) 0%, var(--bg-secondary) 100%); border-bottom: 2px solid var(--green-500); padding: 20px 28px; margin: -28px -28px 24px -28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.rct-header h1 { font-size: 24px; font-weight: 700; color: var(--green-500); letter-spacing: -0.5px; margin: 0; }
.rct-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

.rct-confirmation { background: var(--bg-surface); border: 2px solid rgba(34,197,94,0.3); border-radius: 12px; padding: 28px; text-align: center; margin-bottom: 20px; }
.rct-check { width: 60px; height: 60px; border-radius: 50%; background: rgba(34,197,94,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.rct-check i { font-size: 28px; color: var(--green-500); }
</style>

<?php if ($action === 'list'): ?>

<div class="rct-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-receipt" style="font-size:26px;color:var(--green-500)"></i>
        <div><h1>Receipts</h1><p class="subtitle">Payment confirmations and service closure records</p></div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0">
                <thead>
                    <tr><th>RECEIPT</th><th>INVOICE</th><th>TICKET</th><th>CUSTOMER</th><th>AMOUNT</th><th>METHOD</th><th>DATE</th><th>DELIVERED</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($receipts)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-tertiary)">No receipts. Receipts auto-generate on full invoice payment.</td></tr>
                    <?php else: foreach ($receipts as $r): ?>
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--green-500)"><?= htmlspecialchars($r['receipt_id']) ?></td>
                            <td><a href="?page=invoices-v2&action=view&id=<?= $r['invoice_id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($r['inv_doc_id'] ?? '') ?></a></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars(format_ticket_number($r['ticket_number'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($r['customer_name'] ?? '') ?></td>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:var(--green-500)">$<?= number_format($r['amount_paid'], 2) ?></td>
                            <td style="font-size:12px"><?= ucfirst(str_replace('_', ' ', $r['payment_method_used'])) ?></td>
                            <td style="font-size:12px;color:var(--text-secondary)"><?= format_datetime($r['payment_date']) ?></td>
                            <td><?= $r['delivered_at'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-clock text-warning"></i>' ?></td>
                            <td><a href="?page=receipts&action=view&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-success">View</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($action === 'view' && $receipt): ?>

<div class="rct-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-receipt" style="font-size:26px;color:var(--green-500)"></i>
        <div>
            <h1><?= htmlspecialchars($receipt['receipt_id']) ?></h1>
            <p class="subtitle">Payment confirmation for <?= htmlspecialchars($receipt['customer_name'] ?? '') ?></p>
        </div>
    </div>
    <div style="display:flex;gap:8px">
        <?php if ($receipt['delivered_at']): ?>
            <span class="badge bg-success">Delivered</span>
        <?php else: ?>
            <span class="badge bg-warning">Pending Delivery</span>
        <?php endif; ?>
        <a href="?page=receipts" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<!-- Full chain -->
<div style="display:flex;align-items:center;gap:6px;margin-bottom:16px;font-size:12px;flex-wrap:wrap">
    <a href="?page=service-requests&action=view&id=<?= $receipt['service_request_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars(format_ticket_number($receipt['ticket_number'] ?? '')) ?></a>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <a href="?page=work-orders&action=view&id=<?= $receipt['work_order_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-clipboard-check"></i> <?= htmlspecialchars($receipt['wo_doc_id'] ?? 'WO') ?></a>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <a href="?page=invoices-v2&action=view&id=<?= $receipt['invoice_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-file-invoice-dollar"></i> <?= htmlspecialchars($receipt['inv_doc_id'] ?? 'INV') ?></a>
    <span style="color:var(--text-tertiary)"><i class="fas fa-chevron-right"></i></span>
    <span style="color:var(--green-500);font-weight:600;padding:4px 10px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3);border-radius:4px"><i class="fas fa-receipt"></i> <?= htmlspecialchars($receipt['receipt_id']) ?></span>
</div>

<!-- Confirmation card -->
<div class="rct-confirmation">
    <div class="rct-check"><i class="fas fa-check-circle"></i></div>
    <h2 style="font-size:20px;font-weight:700;color:var(--green-500);margin:0 0 4px">Payment Confirmed</h2>
    <div style="font-family:'JetBrains Mono',monospace;font-size:28px;font-weight:700;color:var(--text-primary);margin:8px 0">$<?= number_format($receipt['amount_paid'], 2) ?></div>
    <div style="font-size:13px;color:var(--text-secondary)"><?= ucfirst(str_replace('_', ' ', $receipt['payment_method_used'])) ?> — <?= format_datetime($receipt['payment_date']) ?></div>
    <?php if ($receipt['payment_reference']): ?>
        <div style="font-size:12px;color:var(--text-tertiary);margin-top:4px">Ref: <span style="font-family:'JetBrains Mono',monospace"><?= htmlspecialchars($receipt['payment_reference']) ?></span></div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <!-- Service Summary -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 style="font-size:13px;font-weight:600;margin-bottom:10px"><i class="fas fa-file-alt" style="color:var(--navy-300)"></i> Service Summary</h5>
                <p style="font-size:13px;color:var(--text-primary)"><?= nl2br(htmlspecialchars($receipt['service_summary'])) ?></p>
            </div>
        </div>

        <!-- Service details -->
        <div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:16px 20px;margin-bottom:16px">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Customer</div><div style="font-size:13px;color:var(--text-primary);font-weight:500;margin-top:2px"><?= htmlspecialchars($receipt['customer_name'] ?? '') ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Phone</div><div style="font-family:'JetBrains Mono',monospace;font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars($receipt['customer_phone'] ?? '') ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Vehicle</div><div style="font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars(trim(($receipt['vehicle_year'] ?? '') . ' ' . ($receipt['vehicle_make'] ?? '') . ' ' . ($receipt['vehicle_model'] ?? ''))) ?: '—' ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Service</div><div style="font-size:13px;color:var(--text-primary);margin-top:2px"><?= ucfirst(str_replace('_', ' ', $receipt['service_category'] ?? '')) ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Technician</div><div style="font-size:13px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars(($receipt['tech_first'] ?? '') . ' ' . ($receipt['tech_last'] ?? '')) ?></div></div>
                <div><div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase">Location</div><div style="font-size:12px;color:var(--text-primary);margin-top:2px"><?= htmlspecialchars(substr($receipt['service_address'] ?? '', 0, 50)) ?></div></div>
            </div>
        </div>

        <?php if ($receipt['warranty_terms']): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 style="font-size:13px;font-weight:600;margin-bottom:8px"><i class="fas fa-shield-alt" style="color:var(--navy-300)"></i> Warranty Terms</h5>
                <p style="font-size:13px;color:var(--text-primary)"><?= nl2br(htmlspecialchars($receipt['warranty_terms'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <!-- Delivery actions -->
        <div class="card mb-3">
            <div class="card-body">
                <h5 style="font-size:13px;font-weight:600;margin-bottom:12px">Delivery</h5>
                <?php if ($receipt['delivered_at']): ?>
                    <div style="color:var(--green-500);font-size:13px"><i class="fas fa-check-circle"></i> Delivered <?= format_datetime($receipt['delivered_at']) ?></div>
                <?php else: ?>
                    <button class="btn btn-success btn-sm w-100" onclick="deliverReceipt(<?= $receipt['id'] ?>)"><i class="fas fa-paper-plane"></i> Mark as Delivered</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment details -->
        <div class="card">
            <div class="card-body">
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Amount Paid</div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:700;color:var(--green-500);margin-bottom:10px">$<?= number_format($receipt['amount_paid'], 2) ?></div>
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Payment Method</div>
                <div style="font-size:13px;color:var(--text-primary);margin-bottom:10px"><?= ucfirst(str_replace('_', ' ', $receipt['payment_method_used'])) ?></div>
                <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Reference</div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-primary);margin-bottom:10px"><?= htmlspecialchars($receipt['payment_reference'] ?? '—') ?></div>
                <?php if ($receipt['processor_txn_id']): ?>
                    <div style="font-size:11px;color:var(--text-tertiary);text-transform:uppercase;margin-bottom:4px">Processor TXN</div>
                    <div style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-primary)"><?= htmlspecialchars($receipt['processor_txn_id']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
const API_W = 'api/workflow.php';

async function deliverReceipt(id) {
    if (!confirm('Mark receipt as delivered to customer?')) return;
    const fd = new FormData();
    fd.append('action', 'deliver_receipt');
    fd.append('id', id);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}
</script>
