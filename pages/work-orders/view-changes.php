<!-- Change Orders -->
<div style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:10px;padding:20px;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <h3 style="font-size:14px;font-weight:600;margin:0"><i class="fas fa-exchange-alt" style="color:var(--amber-500);margin-right:6px"></i> Change Orders</h3>
        <?php if (in_array($wo['status'], ['in_progress', 'paused'])): ?>
            <a href="?page=change-orders&action=create&wo_id=<?= $wo['id'] ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-plus"></i> New CO</a>
        <?php endif; ?>
    </div>
    <?php if (empty($changeOrders)): ?>
        <p style="text-align:center;padding:20px;color:var(--text-tertiary);font-size:13px">No change orders for this work order.</p>
    <?php else: foreach ($changeOrders as $co): ?>
        <div class="wo-co-card <?= $co['status'] ?>">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <a href="?page=change-orders&action=view&id=<?= $co['id'] ?>" style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--navy-300)"><?= htmlspecialchars($co['change_order_id']) ?></a>
                    <span style="font-size:11px;color:var(--text-tertiary);margin-left:8px">#<?= $co['sequence_num'] ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-family:'JetBrains Mono',monospace;font-size:13px;font-weight:600;color:<?= $co['net_cost_impact'] >= 0 ? 'var(--red-500)' : 'var(--green-500)' ?>"><?= $co['net_cost_impact'] >= 0 ? '+' : '' ?>$<?= number_format($co['net_cost_impact'], 2) ?></span>
                    <?= get_status_badge($co['status']) ?>
                </div>
            </div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:6px"><?= htmlspecialchars(substr($co['reason_detail'], 0, 100)) ?></div>
        </div>
    <?php endforeach; endif; ?>
</div>
