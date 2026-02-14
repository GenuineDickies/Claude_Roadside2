<!-- RIGHT: Sidebar -->
<div>
    <!-- Quick Actions -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
        </div>
        <div class="card-body" style="padding:12px">
            <div class="dash-quick-actions">
                <a href="?page=service-intake" class="dash-quick-btn"><i class="fas fa-plus-circle"></i> New Request</a>
                <a href="?page=customers&action=add" class="dash-quick-btn"><i class="fas fa-user-plus"></i> Add Customer</a>
                <a href="?page=expenses" class="dash-quick-btn"><i class="fas fa-wallet"></i> Log Expense</a>
                <a href="?page=compliance" class="dash-quick-btn"><i class="fas fa-file-alt"></i> Documents</a>
            </div>
        </div>
    </div>

    <!-- Expense Breakdown -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-wallet"></i> Expenses This Month</h5>
            <a href="?page=expenses" class="btn btn-sm btn-outline-primary" style="font-size:11px">Details</a>
        </div>
        <div class="card-body">
            <?php if (empty($topExpenseCats) || $monthExpenses == 0): ?>
                <p style="font-size:12px;color:var(--text-secondary);text-align:center;padding:12px">No expenses recorded this month.<br><a href="?page=expenses">Start tracking</a></p>
            <?php else: ?>
                <?php foreach ($topExpenseCats as $cat): ?>
                    <?php if ($cat['spent'] > 0): ?>
                    <div class="dash-expense-item">
                        <div class="cat"><i class="<?= $cat['icon'] ?>" style="color:<?= $cat['color'] ?>"></i> <?= htmlspecialchars($cat['name']) ?></div>
                        <div class="amt"><?= format_currency($cat['spent']) ?></div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div style="text-align:right;margin-top:8px;padding-top:8px;border-top:1px solid var(--border-medium)">
                    <span style="font-size:12px;color:var(--text-tertiary)">Total:</span>
                    <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700;color:var(--text-primary)"><?= format_currency($monthExpenses) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- YTD Summary -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-calendar-alt"></i> <?= date('Y') ?> Year-to-Date</h5>
        </div>
        <div class="card-body">
            <div class="dash-expense-item">
                <div class="cat"><i class="fas fa-arrow-up" style="color:#22C55E"></i> Total Revenue</div>
                <div class="amt" style="color:#22C55E;font-weight:600"><?= format_currency($totalRevenue) ?></div>
            </div>
            <div class="dash-expense-item">
                <div class="cat"><i class="fas fa-arrow-down" style="color:#EF4444"></i> Total Expenses</div>
                <div class="amt" style="color:#EF4444;font-weight:600"><?= format_currency($ytdExpenses) ?></div>
            </div>
            <div style="text-align:right;margin-top:8px;padding-top:8px;border-top:1px solid var(--border-medium)">
                <span style="font-size:12px;color:var(--text-tertiary)">Net:</span>
                <?php $ytdNet = $totalRevenue - $ytdExpenses; ?>
                <span style="font-family:'JetBrains Mono',monospace;font-size:14px;font-weight:700;color:<?= $ytdNet >= 0 ? '#22C55E' : '#EF4444' ?>"><?= format_currency($ytdNet) ?></span>
            </div>
        </div>
    </div>
</div>
