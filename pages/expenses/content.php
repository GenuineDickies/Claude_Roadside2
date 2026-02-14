<!-- Header -->
<div class="expenses-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-wallet" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Expenses</h1>
            <p class="subtitle">Track every dollar going out — fuel, parts, insurance, tools, and more</p>
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-secondary btn-sm" onclick="toggleCategoryView()">
            <i class="fas fa-th-large"></i> <span id="catViewLabel">Category View</span>
        </button>
        <button class="btn btn-primary" onclick="openExpenseModal()">
            <i class="fas fa-plus"></i> Add Expense
        </button>
    </div>
</div>

<!-- Stats -->
<div class="expenses-stats" id="expenseStats">
    <div class="expenses-stat navy">
        <div class="label">This Month</div>
        <div class="value" id="statMonthExpenses">$0</div>
        <div class="sub" id="statMonthBudget">Budget: $0</div>
        <div class="expenses-budget-bar"><div class="expenses-budget-fill" id="budgetFill" style="width:0;background:var(--navy-500)"></div></div>
    </div>
    <div class="expenses-stat amber">
        <div class="label">Last Month</div>
        <div class="value" id="statLastMonth">$0</div>
        <div class="sub" id="statMonthChange">—</div>
    </div>
    <div class="expenses-stat green">
        <div class="label">Year-to-Date</div>
        <div class="value" id="statYTD">$0</div>
        <div class="sub"><?php echo date('Y'); ?> total</div>
    </div>
    <div class="expenses-stat red">
        <div class="label">Tax Deductible (YTD)</div>
        <div class="value" id="statTaxDeductible">$0</div>
        <div class="sub">Deductible expenses</div>
    </div>
</div>

<!-- Category Breakdown (togglable) -->
<div id="categoryBreakdown" style="display:none">
    <h5 style="font-size:14px;font-weight:600;color:var(--text-primary);margin-bottom:12px"><i class="fas fa-chart-pie" style="color:var(--navy-300);margin-right:6px"></i> Budget vs. Actual by Category</h5>
    <div class="expenses-cat-grid" id="catGrid"></div>
</div>

<!-- Filters -->
<div class="expenses-filters">
    <div class="filter-group">
        <label>Category</label>
        <select id="filterCategory" onchange="loadExpenses()">
            <option value="">All Categories</option>
            <?php foreach ($expenseCats as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>From</label>
        <input type="date" id="filterDateFrom" value="<?= date('Y-m-01') ?>" onchange="loadExpenses()">
    </div>
    <div class="filter-group">
        <label>To</label>
        <input type="date" id="filterDateTo" value="<?= date('Y-m-t') ?>" onchange="loadExpenses()">
    </div>
    <div class="filter-group">
        <label>Payment</label>
        <select id="filterPayment" onchange="loadExpenses()">
            <option value="">All</option>
            <option value="card">Card</option>
            <option value="cash">Cash</option>
            <option value="check">Check</option>
            <option value="ach">ACH</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Search</label>
        <input type="text" id="filterSearch" placeholder="Vendor or note..." style="width:160px" oninput="debounceSearch()">
    </div>
    <div class="filter-group" style="margin-left:auto">
        <button class="btn btn-sm btn-secondary" onclick="resetFilters()"><i class="fas fa-undo"></i> Reset</button>
    </div>
</div>

<!-- Expense Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-list"></i> Expense Log</h5>
        <span class="badge bg-info" id="expenseCountBadge">0 expenses · $0.00</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="expenses-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Vendor</th>
                        <th>Description</th>
                        <th>Payment</th>
                        <th style="text-align:right">Amount</th>
                        <th style="text-align:right">Tax</th>
                        <th style="text-align:right">Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="expenseTableBody">
                    <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-secondary)">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
