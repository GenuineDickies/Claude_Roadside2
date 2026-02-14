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
    <a href="?page=service-requests&action=view&id=<?= $co['service_request_id'] ?>" style="color:var(--navy-300);text-decoration:none;padding:4px 10px;background:rgba(43,94,167,0.08);border-radius:4px"><i class="fas fa-ticket-alt"></i> <?= htmlspecialchars(format_ticket_number($co['ticket_number'] ?? '')) ?></a>
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
