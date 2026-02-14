const EXP_API = 'api/expenses.php';
let expenseModal;
let searchTimer;
let catViewOpen = false;

document.addEventListener('DOMContentLoaded', function() {
    expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    loadDashboard();
    loadExpenses();
});

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
            const pct = d.month_budget > 0 ? Math.min((d.month_expenses / d.month_budget) * 100, 100) : 0;
            const fill = document.getElementById('budgetFill');
            fill.style.width = pct + '%';
            fill.style.background = pct > 90 ? '#EF4444' : pct > 70 ? '#F59E0B' : 'var(--navy-500)';
            if (d.last_month_expenses > 0) {
                const change = ((d.month_expenses - d.last_month_expenses) / d.last_month_expenses * 100).toFixed(0);
                const arrow = change >= 0 ? '\u25B2' : '\u25BC';
                const color = change >= 0 ? '#EF4444' : '#22C55E';
                document.getElementById('statMonthChange').innerHTML = `<span style="color:${color}">${arrow} ${Math.abs(change)}% vs last month</span>`;
            }
        }
        if (sum.success) {
            document.getElementById('statTaxDeductible').textContent = '$' + sum.data.tax_deductible_ytd.toLocaleString('en-US', {minimumFractionDigits:2});
            const grid = document.getElementById('catGrid');
            grid.innerHTML = sum.data.by_category.map(cat => {
                const pct = cat.budget_monthly > 0 ? Math.min((cat.spent / cat.budget_monthly) * 100, 100) : 0;
                const barColor = pct > 90 ? '#EF4444' : pct > 70 ? '#F59E0B' : cat.color;
                return `<div class="expenses-cat-card"><div class="cat-head"><div class="cat-icon" style="background:${cat.color}15;color:${cat.color}"><i class="${cat.icon}"></i></div><div class="cat-name">${cat.name}</div></div><div class="cat-spent">$${parseFloat(cat.spent).toLocaleString('en-US',{minimumFractionDigits:2})}</div><div class="cat-budget">of $${parseFloat(cat.budget_monthly).toLocaleString('en-US',{minimumFractionDigits:2})} budget \u00B7 ${cat.transaction_count} txns</div><div class="expenses-budget-bar"><div class="expenses-budget-fill" style="width:${pct}%;background:${barColor}"></div></div></div>`;
            }).join('');
        }
    } catch(e) { console.error('Dashboard load error:', e); }
}

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
                tbody.innerHTML = json.data.map(e => `<tr><td class="mono" style="color:var(--text-tertiary)">${e.expense_date}</td><td><span class="cat-badge" style="background:${e.category_color}12;color:${e.category_color};border:1px solid ${e.category_color}30"><i class="${e.category_icon}" style="font-size:10px"></i> ${e.category_name || 'Other'}</span></td><td style="font-weight:600;color:var(--text-primary)">${escHtml(e.vendor)}</td><td style="color:var(--text-secondary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(e.description || '')}</td><td style="text-transform:uppercase;font-size:11px;color:var(--text-tertiary)">${e.payment_method}</td><td class="mono" style="text-align:right;color:var(--text-primary)">$${parseFloat(e.amount).toFixed(2)}</td><td class="mono" style="text-align:right;color:var(--text-tertiary)">$${parseFloat(e.tax_amount).toFixed(2)}</td><td class="mono" style="text-align:right;font-weight:600;color:#EF4444">$${parseFloat(e.total_amount).toFixed(2)}</td><td><div class="btn-group btn-group-sm"><button class="btn btn-outline-primary btn-sm" onclick="editExpense(${e.id})"><i class="fas fa-edit"></i></button><button class="btn btn-outline-danger btn-sm" onclick="deleteExpense(${e.id})"><i class="fas fa-trash"></i></button></div></td></tr>`).join('');
            }
            document.getElementById('expenseCountBadge').textContent = `${json.totals.count} expenses \u00B7 $${parseFloat(json.totals.total).toLocaleString('en-US',{minimumFractionDigits:2})}`;
        }
    } catch(e) { console.error('Load error:', e); }
}

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
    if (!document.getElementById('expTaxDeductible').checked) fd.set('is_tax_deductible', '0');
    try {
        const res = await fetch(EXP_API, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) { expenseModal.hide(); loadExpenses(); loadDashboard(); }
        else alert('Error: ' + json.error);
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

function calcTotal() {
    const amt = parseFloat(document.getElementById('expAmount').value) || 0;
    const tax = parseFloat(document.getElementById('expTax').value) || 0;
    document.getElementById('expTotal').value = (amt + tax).toFixed(2);
}
function toggleRecurring() {
    document.getElementById('recurringFields').style.display = document.getElementById('expRecurring').checked ? '' : 'none';
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
function debounceSearch() { clearTimeout(searchTimer); searchTimer = setTimeout(loadExpenses, 400); }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
