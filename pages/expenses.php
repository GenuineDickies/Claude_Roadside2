<?php
/**
 * Expenses Page — Track all business expenses
 * Category breakdown, budget vs actual, recurring, tax deductions
 */

// Fetch categories for filters/form
$expenseCats = [];
try {
    // Bootstrap tables by hitting the API internally
    $pdo->exec("SELECT 1 FROM expense_categories LIMIT 1");
    $expenseCats = $pdo->query("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
} catch (Exception $e) {
    // Tables don't exist yet — will be created on first API call
}

// Get technicians for linking
$techsList = $pdo->query("SELECT id, first_name, last_name FROM technicians ORDER BY first_name")->fetchAll();
?>

<style>
/* ── Expenses — Scoped Styles ──────────────────────────────────────── */
.expenses-header {
    background: linear-gradient(135deg, rgba(43,94,167,0.08) 0%, rgba(18,21,27,0.95) 50%, rgba(43,94,167,0.04) 100%),
                linear-gradient(180deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 20px 28px;
    margin: -28px -28px 24px -28px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.expenses-header h1 { font-size: 24px; font-weight: 700; color: var(--navy-300); letter-spacing: -0.5px; margin: 0; }
.expenses-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

/* Stats row */
.expenses-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.expenses-stat {
    background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px;
    padding: 18px 20px; position: relative; overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.expenses-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
.expenses-stat .label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-tertiary); }
.expenses-stat .value { font-size: 28px; font-weight: 700; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); margin: 4px 0; }
.expenses-stat .sub { font-size: 12px; color: var(--text-secondary); }
.expenses-stat.navy { border-left: 4px solid var(--navy-500); }
.expenses-stat.amber { border-left: 4px solid #F59E0B; }
.expenses-stat.green { border-left: 4px solid #22C55E; }
.expenses-stat.red { border-left: 4px solid #EF4444; }

/* Budget bar */
.expenses-budget-bar { height: 6px; background: rgba(255,255,255,0.06); border-radius: 3px; margin-top: 8px; overflow: hidden; }
.expenses-budget-fill { height: 100%; border-radius: 3px; transition: width 0.5s ease; }

/* Filter bar */
.expenses-filters {
    background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px;
    padding: 16px 20px; margin-bottom: 16px;
    display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
}
.expenses-filters .filter-group { display: flex; flex-direction: column; gap: 4px; }
.expenses-filters label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-tertiary); }
.expenses-filters select, .expenses-filters input {
    background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 6px;
    padding: 6px 10px; color: var(--text-primary); font-size: 13px;
}
.expenses-filters select:focus, .expenses-filters input:focus {
    border-color: var(--navy-400); outline: none; box-shadow: 0 0 0 3px rgba(43,94,167,0.15);
}

/* Category breakdown cards */
.expenses-cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-bottom: 24px; }
.expenses-cat-card {
    background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px;
    padding: 16px; transition: border-color 0.2s;
}
.expenses-cat-card:hover { border-color: rgba(255,255,255,0.16); }
.expenses-cat-card .cat-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.expenses-cat-card .cat-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.expenses-cat-card .cat-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.expenses-cat-card .cat-spent { font-size: 20px; font-weight: 700; font-family: 'JetBrains Mono', monospace; color: var(--text-primary); }
.expenses-cat-card .cat-budget { font-size: 11px; color: var(--text-secondary); margin-top: 2px; }

/* Table */
.expenses-table { width: 100%; }
.expenses-table thead th {
    background: var(--bg-secondary); font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-tertiary);
    padding: 10px 14px; border-bottom: 1px solid var(--border-medium);
}
.expenses-table tbody td { padding: 12px 14px; border-bottom: 1px solid var(--border-subtle); font-size: 13px; vertical-align: middle; }
.expenses-table tbody tr:hover { background: var(--bg-surface-hover); }
.expenses-table .mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; }
.expenses-table .cat-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 600;
}

/* Modal form */
.expense-form-group { margin-bottom: 14px; }
.expense-form-group label { font-size: 12px; font-weight: 600; color: #BCC3D4; margin-bottom: 4px; display: block; }
.expense-form-group .req { color: var(--red-500); }
.expense-form-input, .expense-form-select, .expense-form-textarea {
    width: 100%; background: var(--bg-primary); border: 1px solid var(--border-medium);
    border-radius: 6px; padding: 8px 12px; color: var(--text-primary); font-size: 13px;
}
.expense-form-input:focus, .expense-form-select:focus, .expense-form-textarea:focus {
    border-color: var(--navy-400); outline: none; box-shadow: 0 0 0 3px rgba(43,94,167,0.15);
}
</style>

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

<!-- Add/Edit Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:14px">
            <div class="modal-header" style="border-bottom:1px solid var(--border-subtle);padding:16px 24px">
                <h5 class="modal-title" style="font-size:16px;font-weight:700;color:var(--navy-300)" id="expenseModalTitle">Add Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <form id="expenseForm">
                    <input type="hidden" name="id" id="expenseId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Category</label>
                                <select class="expense-form-select" name="category_id" id="expCategoryId" required>
                                    <option value="">Select category...</option>
                                    <?php foreach ($expenseCats as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Vendor / Payee</label>
                                <input type="text" class="expense-form-input" name="vendor" id="expVendor" placeholder="AutoZone, Shell, State Farm..." required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Amount</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="expense-form-input" name="amount" id="expAmount" step="0.01" min="0" style="padding-left:24px;font-family:'JetBrains Mono',monospace" required oninput="calcTotal()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Tax</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="expense-form-input" name="tax_amount" id="expTax" step="0.01" min="0" value="0" style="padding-left:24px;font-family:'JetBrains Mono',monospace" oninput="calcTotal()">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Total</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="expense-form-input" id="expTotal" step="0.01" style="padding-left:24px;font-family:'JetBrains Mono',monospace;color:var(--green-500)" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label><span class="req">*</span> Date</label>
                                <input type="date" class="expense-form-input" name="expense_date" id="expDate" value="<?= date('Y-m-d') ?>" required style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Payment Method</label>
                                <select class="expense-form-select" name="payment_method" id="expPayMethod">
                                    <option value="card">Card</option>
                                    <option value="cash">Cash</option>
                                    <option value="check">Check</option>
                                    <option value="ach">ACH / Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Reference #</label>
                                <input type="text" class="expense-form-input" name="reference_number" id="expRef" placeholder="Receipt #, Check #" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="expense-form-group">
                                <label>Description / Notes</label>
                                <textarea class="expense-form-textarea" name="description" id="expDesc" rows="2" placeholder="What was this expense for?"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Link to Technician</label>
                                <select class="expense-form-select" name="technician_id" id="expTechId">
                                    <option value="">None</option>
                                    <?php foreach ($techsList as $t): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group">
                                <label>Vehicle Tag</label>
                                <input type="text" class="expense-form-input" name="vehicle_tag" id="expVehicleTag" placeholder="Unit # or plate">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="expense-form-group" style="padding-top:20px">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                    <input type="checkbox" name="is_tax_deductible" id="expTaxDeductible" value="1" checked>
                                    <span>Tax Deductible</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr style="border-color:var(--border-subtle)">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;font-weight:600;color:#BCC3D4;margin-bottom:8px">
                                <input type="checkbox" name="is_recurring" id="expRecurring" onchange="toggleRecurring()">
                                <i class="fas fa-redo" style="color:var(--navy-300)"></i> This is a recurring expense
                            </label>
                            <div id="recurringFields" style="display:none" class="row g-3">
                                <div class="col-md-6">
                                    <div class="expense-form-group">
                                        <label>Frequency</label>
                                        <select class="expense-form-select" name="recurring_frequency" id="expRecurFreq">
                                            <option value="weekly">Weekly</option>
                                            <option value="biweekly">Bi-weekly</option>
                                            <option value="monthly" selected>Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="annual">Annual</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="expense-form-group">
                                        <label>Next Due Date</label>
                                        <input type="date" class="expense-form-input" name="recurring_next_date" id="expRecurNext" style="font-family:'JetBrains Mono',monospace">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-subtle);padding:12px 24px">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveExpense()"><i class="fas fa-save"></i> Save Expense</button>
            </div>
        </div>
    </div>
</div>

<script>
const EXP_API = 'api/expenses.php';
let expenseModal;
let searchTimer;
let catViewOpen = false;

document.addEventListener('DOMContentLoaded', function() {
    expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    loadDashboard();
    loadExpenses();
});

// ─── Dashboard Stats ────────────────────────────────────────────────
async function loadDashboard() {
    try {
        const [dashRes, sumRes] = await Promise.all([
            fetch(`${EXP_API}?action=dashboard`),
            fetch(`${EXP_API}?action=summary&year=${new Date().getFullYear()}&month=${new Date().getMonth()+1}`)
        ]);
        const dash = await dashRes.json();
        const sum = await sumRes.json();

        if (dash.success) {
            const d = dash.data;
            document.getElementById('statMonthExpenses').textContent = '$' + d.month_expenses.toLocaleString('en-US', {minimumFractionDigits:2});
            document.getElementById('statLastMonth').textContent = '$' + d.last_month_expenses.toLocaleString('en-US', {minimumFractionDigits:2});
            document.getElementById('statYTD').textContent = '$' + d.ytd_expenses.toLocaleString('en-US', {minimumFractionDigits:2});
            document.getElementById('statMonthBudget').textContent = 'Budget: $' + d.month_budget.toLocaleString('en-US', {minimumFractionDigits:2});

            // Budget bar
            const pct = d.month_budget > 0 ? Math.min((d.month_expenses / d.month_budget) * 100, 100) : 0;
            const fill = document.getElementById('budgetFill');
            fill.style.width = pct + '%';
            fill.style.background = pct > 90 ? '#EF4444' : pct > 70 ? '#F59E0B' : 'var(--navy-500)';

            // Month change
            if (d.last_month_expenses > 0) {
                const change = ((d.month_expenses - d.last_month_expenses) / d.last_month_expenses * 100).toFixed(0);
                const arrow = change >= 0 ? '▲' : '▼';
                const color = change >= 0 ? '#EF4444' : '#22C55E'; // up is bad for expenses
                document.getElementById('statMonthChange').innerHTML = `<span style="color:${color}">${arrow} ${Math.abs(change)}% vs last month</span>`;
            }
        }

        if (sum.success) {
            document.getElementById('statTaxDeductible').textContent = '$' + sum.data.tax_deductible_ytd.toLocaleString('en-US', {minimumFractionDigits:2});

            // Category cards
            const grid = document.getElementById('catGrid');
            grid.innerHTML = sum.data.by_category.map(cat => {
                const pct = cat.budget_monthly > 0 ? Math.min((cat.spent / cat.budget_monthly) * 100, 100) : 0;
                const barColor = pct > 90 ? '#EF4444' : pct > 70 ? '#F59E0B' : cat.color;
                return `
                    <div class="expenses-cat-card">
                        <div class="cat-head">
                            <div class="cat-icon" style="background:${cat.color}15;color:${cat.color}"><i class="${cat.icon}"></i></div>
                            <div class="cat-name">${cat.name}</div>
                        </div>
                        <div class="cat-spent">$${parseFloat(cat.spent).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                        <div class="cat-budget">of $${parseFloat(cat.budget_monthly).toLocaleString('en-US',{minimumFractionDigits:2})} budget · ${cat.transaction_count} txns</div>
                        <div class="expenses-budget-bar"><div class="expenses-budget-fill" style="width:${pct}%;background:${barColor}"></div></div>
                    </div>
                `;
            }).join('');
        }
    } catch(e) { console.error('Dashboard load error:', e); }
}

// ─── Load Expenses ──────────────────────────────────────────────────
async function loadExpenses() {
    const params = new URLSearchParams({ action: 'list' });
    const cat = document.getElementById('filterCategory').value;
    const from = document.getElementById('filterDateFrom').value;
    const to = document.getElementById('filterDateTo').value;
    const pay = document.getElementById('filterPayment').value;
    const search = document.getElementById('filterSearch').value;

    if (cat) params.set('category_id', cat);
    if (from) params.set('date_from', from);
    if (to) params.set('date_to', to);
    if (pay) params.set('payment_method', pay);
    if (search) params.set('vendor', search);

    try {
        const res = await fetch(`${EXP_API}?${params}`);
        const json = await res.json();

        if (json.success) {
            const tbody = document.getElementById('expenseTableBody');
            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-secondary)"><i class="fas fa-receipt" style="font-size:24px;display:block;margin-bottom:8px"></i>No expenses found for this period</td></tr>';
            } else {
                tbody.innerHTML = json.data.map(e => `
                    <tr>
                        <td class="mono" style="color:var(--text-tertiary)">${e.expense_date}</td>
                        <td>
                            <span class="cat-badge" style="background:${e.category_color}12;color:${e.category_color};border:1px solid ${e.category_color}30">
                                <i class="${e.category_icon}" style="font-size:10px"></i> ${e.category_name || 'Other'}
                            </span>
                        </td>
                        <td style="font-weight:600;color:var(--text-primary)">${escHtml(e.vendor)}</td>
                        <td style="color:var(--text-secondary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(e.description || '')}</td>
                        <td style="text-transform:uppercase;font-size:11px;color:var(--text-tertiary)">${e.payment_method}</td>
                        <td class="mono" style="text-align:right;color:var(--text-primary)">$${parseFloat(e.amount).toFixed(2)}</td>
                        <td class="mono" style="text-align:right;color:var(--text-tertiary)">$${parseFloat(e.tax_amount).toFixed(2)}</td>
                        <td class="mono" style="text-align:right;font-weight:600;color:#EF4444">$${parseFloat(e.total_amount).toFixed(2)}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" onclick="editExpense(${e.id})"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteExpense(${e.id})"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }

            document.getElementById('expenseCountBadge').textContent =
                `${json.totals.count} expenses · $${parseFloat(json.totals.total).toLocaleString('en-US',{minimumFractionDigits:2})}`;
        }
    } catch(e) { console.error('Load error:', e); }
}

// ─── Modal ──────────────────────────────────────────────────────────
function openExpenseModal(data = null) {
    const form = document.getElementById('expenseForm');
    form.reset();
    document.getElementById('expenseId').value = '';
    document.getElementById('expDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('expTaxDeductible').checked = true;
    document.getElementById('recurringFields').style.display = 'none';

    if (data) {
        document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
        document.getElementById('expenseId').value = data.id;
        document.getElementById('expCategoryId').value = data.category_id;
        document.getElementById('expVendor').value = data.vendor;
        document.getElementById('expAmount').value = parseFloat(data.amount).toFixed(2);
        document.getElementById('expTax').value = parseFloat(data.tax_amount).toFixed(2);
        document.getElementById('expDate').value = data.expense_date;
        document.getElementById('expPayMethod').value = data.payment_method;
        document.getElementById('expRef').value = data.reference_number || '';
        document.getElementById('expDesc').value = data.description || '';
        document.getElementById('expTechId').value = data.technician_id || '';
        document.getElementById('expVehicleTag').value = data.vehicle_tag || '';
        document.getElementById('expTaxDeductible').checked = data.is_tax_deductible == 1;
        document.getElementById('expRecurring').checked = data.is_recurring == 1;
        if (data.is_recurring == 1) {
            document.getElementById('recurringFields').style.display = '';
            document.getElementById('expRecurFreq').value = data.recurring_frequency || 'monthly';
            document.getElementById('expRecurNext').value = data.recurring_next_date || '';
        }
        calcTotal();
    } else {
        document.getElementById('expenseModalTitle').textContent = 'Add Expense';
    }

    expenseModal.show();
}

async function editExpense(id) {
    // Fetch the expense data then open modal
    const res = await fetch(`${EXP_API}?action=list&limit=1&offset=0`);
    // Just get from the table data we already have
    const allRes = await fetch(`${EXP_API}?action=list&limit=200`);
    const json = await allRes.json();
    if (json.success) {
        const exp = json.data.find(e => e.id == id);
        if (exp) openExpenseModal(exp);
    }
}

async function saveExpense() {
    const form = document.getElementById('expenseForm');
    const fd = new FormData(form);
    const id = document.getElementById('expenseId').value;
    fd.set('action', id ? 'update' : 'create');
    if (id) fd.set('id', id);

    // Handle checkbox (unchecked = not sent)
    if (!document.getElementById('expTaxDeductible').checked) {
        fd.set('is_tax_deductible', '0');
    }

    try {
        const res = await fetch(EXP_API, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            expenseModal.hide();
            loadExpenses();
            loadDashboard();
        } else {
            alert('Error: ' + json.error);
        }
    } catch(e) { alert('Network error'); }
}

async function deleteExpense(id) {
    if (!confirm('Delete this expense? This cannot be undone.')) return;
    const fd = new FormData();
    fd.set('action', 'delete');
    fd.set('id', id);
    try {
        const res = await fetch(EXP_API, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) { loadExpenses(); loadDashboard(); }
    } catch(e) {}
}

// ─── Helpers ────────────────────────────────────────────────────────
function calcTotal() {
    const amt = parseFloat(document.getElementById('expAmount').value) || 0;
    const tax = parseFloat(document.getElementById('expTax').value) || 0;
    document.getElementById('expTotal').value = (amt + tax).toFixed(2);
}

function toggleRecurring() {
    document.getElementById('recurringFields').style.display =
        document.getElementById('expRecurring').checked ? '' : 'none';
}

function toggleCategoryView() {
    catViewOpen = !catViewOpen;
    document.getElementById('categoryBreakdown').style.display = catViewOpen ? 'block' : 'none';
    document.getElementById('catViewLabel').textContent = catViewOpen ? 'Hide Categories' : 'Category View';
}

function resetFilters() {
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterDateFrom').value = new Date().toISOString().slice(0,8) + '01';
    const eom = new Date(new Date().getFullYear(), new Date().getMonth()+1, 0);
    document.getElementById('filterDateTo').value = eom.toISOString().split('T')[0];
    document.getElementById('filterPayment').value = '';
    document.getElementById('filterSearch').value = '';
    loadExpenses();
}

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadExpenses, 400);
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
</script>
