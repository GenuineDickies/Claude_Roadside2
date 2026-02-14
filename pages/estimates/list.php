<!-- ═══ ESTIMATES LIST ════════════════════════════════════════════════ -->
<div class="est-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-file-invoice" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Estimates</h1>
            <p class="subtitle">Cost proposals for customer approval</p>
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
                            <?php $estTicket = format_ticket_number($est['ticket_number'] ?? ''); ?>
                            <td><a href="?page=service-requests&action=view&id=<?= $est['service_request_id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px"><?= htmlspecialchars($estTicket ?: '#' . $est['service_request_id']) ?></a></td>
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
