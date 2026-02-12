<?php
/**
 * Compliance Page - Simple document/permit/license tracker
 * Record what you need, whether you have it, when it expires.
 * Gentle reminders only - never blocks the user from working.
 */
?>

<style>
/* -- Compliance -- Scoped Styles ------------------------------------------ */
.comp-header {
    background: linear-gradient(135deg, rgba(43,94,167,0.08) 0%, rgba(18,21,27,0.95) 50%, rgba(43,94,167,0.04) 100%),
                linear-gradient(180deg, var(--bg-surface) 0%, var(--bg-secondary) 100%);
    border-bottom: 2px solid var(--navy-500);
    padding: 20px 28px;
    margin: -28px -28px 24px -28px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
}
.comp-header h1 { font-size: 24px; font-weight: 700; color: var(--navy-300); letter-spacing: -0.5px; margin: 0; }
.comp-header .subtitle { font-size: 13px; color: var(--text-secondary); margin: 2px 0 0; }

/* Reminder banner */
.comp-reminder {
    border-radius: 10px; padding: 14px 20px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.comp-reminder.ok { background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.15); }
.comp-reminder.heads-up { background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.15); }
.comp-reminder .reminder-icon { font-size: 18px; }
.comp-reminder .reminder-text { font-size: 13px; color: var(--text-primary); flex: 1; }
.comp-reminder .reminder-count { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 600; }

/* Summary cards */
.comp-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.comp-summary-card {
    background: var(--bg-surface); border: 1px solid var(--border-medium); border-radius: 10px;
    padding: 14px 16px; text-align: center;
}
.comp-summary-card .val { font-size: 24px; font-weight: 700; font-family: 'JetBrains Mono', monospace; }
.comp-summary-card .lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-tertiary); margin-top: 2px; }

/* Filter bar */
.comp-filters {
    display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; align-items: center;
}
.comp-filter-btn {
    padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
    color: var(--text-secondary); cursor: pointer; border: 1px solid var(--border-medium);
    background: transparent; transition: all 0.2s;
}
.comp-filter-btn:hover { color: var(--text-primary); background: var(--bg-surface-hover); }
.comp-filter-btn.active { color: var(--navy-300); background: rgba(43,94,167,0.12); border-color: rgba(43,94,167,0.3); }
.comp-search {
    margin-left: auto; background: var(--bg-primary); border: 1px solid var(--border-medium);
    border-radius: 6px; padding: 6px 12px; color: var(--text-primary); font-size: 12px; width: 200px;
}
.comp-search:focus { border-color: var(--navy-400); outline: none; }

/* Items table */
.comp-table { width: 100%; border-collapse: collapse; }
.comp-table th {
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px;
    color: var(--text-tertiary); padding: 10px 14px; border-bottom: 1px solid var(--border-medium);
    text-align: left;
}
.comp-table td { padding: 12px 14px; border-bottom: 1px solid var(--border-subtle); font-size: 13px; color: var(--text-primary); vertical-align: middle; }
.comp-table tr:hover { background: rgba(255,255,255,0.02); }
.comp-table .name-cell { font-weight: 600; }
.comp-table .cat-badge {
    font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 4px;
    text-transform: uppercase; letter-spacing: 0.5px;
    background: rgba(43,94,167,0.1); color: var(--navy-300);
}
.comp-table .have-yes { color: #22C55E; }
.comp-table .have-no { color: var(--text-tertiary); }
.comp-table .expiry-ok { color: #22C55E; }
.comp-table .expiry-warn { color: #FBBF24; }
.comp-table .expiry-expired { color: #EF4444; }
.comp-table .mono { font-family: 'JetBrains Mono', monospace; font-size: 11px; }
.comp-table .actions { display: flex; gap: 4px; }

/* Empty state */
.comp-empty {
    text-align: center; padding: 60px 20px; color: var(--text-secondary);
}
.comp-empty i { font-size: 36px; display: block; margin-bottom: 12px; color: var(--text-tertiary); }
.comp-empty p { font-size: 14px; margin: 0 0 16px; }

/* Form styles */
.comp-form-group { margin-bottom: 14px; }
.comp-form-group label { font-size: 12px; font-weight: 600; color: #BCC3D4; margin-bottom: 4px; display: block; }
.comp-form-group .req { color: var(--red-500); }
.comp-form-input, .comp-form-select, .comp-form-textarea {
    width: 100%; background: var(--bg-primary); border: 1px solid var(--border-medium);
    border-radius: 6px; padding: 8px 12px; color: var(--text-primary); font-size: 13px;
}
.comp-form-input:focus, .comp-form-select:focus { border-color: var(--navy-400); outline: none; box-shadow: 0 0 0 3px rgba(43,94,167,0.15); }
.comp-have-toggle {
    display: flex; align-items: center; gap: 10px; padding: 10px 16px;
    background: var(--bg-primary); border: 1px solid var(--border-medium); border-radius: 8px;
    cursor: pointer; transition: all 0.2s;
}
.comp-have-toggle:hover { border-color: var(--navy-400); }
.comp-have-toggle input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; }
.comp-have-toggle .toggle-label { font-size: 13px; font-weight: 600; color: var(--text-primary); }
.comp-have-toggle .toggle-hint { font-size: 11px; color: var(--text-secondary); }
</style>

<!-- Header -->
<div class="comp-header">
    <div style="display:flex;align-items:center;gap:14px">
        <i class="fas fa-folder-open" style="font-size:26px;color:var(--navy-300)"></i>
        <div>
            <h1>Documents & Licenses</h1>
            <p class="subtitle">Track required permits, licenses, certifications, and insurance</p>
        </div>
    </div>
    <button class="btn btn-primary" onclick="openItemModal()">
        <i class="fas fa-plus"></i> Add Document
    </button>
</div>

<!-- Reminder Banner (populated by JS) -->
<div class="comp-reminder ok" id="reminderBanner" style="display:none"></div>

<!-- Summary Cards -->
<div class="comp-summary" id="summaryCards">
    <div class="comp-summary-card"><div class="val" id="sumTotal">0</div><div class="lbl">Total Items</div></div>
    <div class="comp-summary-card"><div class="val have-yes" id="sumHave">0</div><div class="lbl">Have</div></div>
    <div class="comp-summary-card"><div class="val have-no" id="sumMissing">0</div><div class="lbl">Don't Have</div></div>
    <div class="comp-summary-card"><div class="val expiry-warn" id="sumExpiring">0</div><div class="lbl">Expiring Soon</div></div>
    <div class="comp-summary-card"><div class="val expiry-expired" id="sumExpired">0</div><div class="lbl">Expired</div></div>
</div>

<!-- Filters -->
<div class="comp-filters">
    <button class="comp-filter-btn active" data-filter="all" onclick="setFilter('all', this)">All</button>
    <button class="comp-filter-btn" data-filter="have" onclick="setFilter('have', this)">Have It</button>
    <button class="comp-filter-btn" data-filter="missing" onclick="setFilter('missing', this)">Don't Have</button>
    <button class="comp-filter-btn" data-filter="expiring" onclick="setFilter('expiring', this)">Expiring</button>
    <input type="text" class="comp-search" id="compSearch" placeholder="Search..." oninput="debounceSearch()">
</div>

<!-- Items Table -->
<div class="card">
    <div class="card-body" style="padding:0;overflow-x:auto">
        <table class="comp-table">
            <thead>
                <tr>
                    <th>Document / License</th>
                    <th>Category</th>
                    <th>Have It?</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Cost</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-secondary)">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--bg-surface);border:1px solid var(--border-medium);border-radius:14px">
            <div class="modal-header" style="border-bottom:1px solid var(--border-subtle);padding:16px 24px">
                <h5 class="modal-title" style="font-size:16px;font-weight:700;color:var(--navy-300)" id="modalTitle">Add Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <form id="itemForm">
                    <input type="hidden" name="id" id="fId">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="comp-form-group">
                                <label><span class="req">*</span> Document Name</label>
                                <input type="text" class="comp-form-input" name="name" id="fName" placeholder="e.g. Business License, GL Insurance Policy" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Category</label>
                                <select class="comp-form-select" name="category" id="fCategory">
                                    <option value="license">License</option>
                                    <option value="permit">Permit</option>
                                    <option value="certification">Certification</option>
                                    <option value="insurance">Insurance</option>
                                    <option value="registration">Registration</option>
                                    <option value="inspection">Inspection</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="comp-have-toggle">
                                <input type="checkbox" name="have_it" id="fHaveIt" value="1">
                                <div>
                                    <div class="toggle-label">I have this document</div>
                                    <div class="toggle-hint">Check this if you currently possess a valid copy</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="comp-form-group">
                                <label>Issuing Authority</label>
                                <input type="text" class="comp-form-input" name="issuing_authority" id="fIssuer" placeholder="State DMV, insurance company, etc.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="comp-form-group">
                                <label>Document / Policy Number</label>
                                <input type="text" class="comp-form-input" name="document_number" id="fDocNum" placeholder="License #, Policy #" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Issue Date</label>
                                <input type="date" class="comp-form-input" name="issue_date" id="fIssueDate" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Expiry / Renewal Date</label>
                                <input type="date" class="comp-form-input" name="expiry_date" id="fExpiryDate" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Remind Me (days before)</label>
                                <input type="number" class="comp-form-input" name="remind_days_before" id="fRemindDays" value="30" min="0" style="font-family:'JetBrains Mono',monospace">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comp-form-group">
                                <label>Cost</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-tertiary);font-family:'JetBrains Mono',monospace">$</span>
                                    <input type="number" class="comp-form-input" name="cost" id="fCost" step="0.01" min="0" value="0" style="padding-left:24px;font-family:'JetBrains Mono',monospace">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="comp-form-group">
                                <label>Notes</label>
                                <textarea class="comp-form-textarea" name="notes" id="fNotes" rows="2" placeholder="Coverage details, renewal instructions, where the document is stored..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-subtle);padding:12px 24px">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveItem()"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script>
const API = 'api/compliance.php';
let modal;
let currentFilter = 'all';
let searchTimer;

document.addEventListener('DOMContentLoaded', function() {
    modal = new bootstrap.Modal(document.getElementById('itemModal'));
    loadReminders();
    loadItems();
});

// --- Reminders ---
async function loadReminders() {
    try {
        const res = await fetch(API + '?action=reminders');
        const json = await res.json();
        if (!json.success) return;
        const d = json.data;

        document.getElementById('sumTotal').textContent = d.total;
        document.getElementById('sumHave').textContent = d.good + d.expiring + d.expired;
        document.getElementById('sumMissing').textContent = d.missing;
        document.getElementById('sumExpiring').textContent = d.expiring;
        document.getElementById('sumExpired').textContent = d.expired;

        const banner = document.getElementById('reminderBanner');
        if (d.needs_attention > 0) {
            banner.style.display = 'flex';
            banner.className = 'comp-reminder heads-up';
            let msgs = [];
            if (d.missing > 0) msgs.push(d.missing + ' missing');
            if (d.expired > 0) msgs.push(d.expired + ' expired');
            if (d.expiring > 0) msgs.push(d.expiring + ' expiring soon');
            banner.innerHTML = '<i class="fas fa-bell reminder-icon" style="color:#FBBF24"></i>' +
                '<span class="reminder-text"><strong>Heads up:</strong> ' + msgs.join(', ') +
                '. Take a look when you get a chance.</span>' +
                '<span class="reminder-count" style="color:#FBBF24">' + d.needs_attention + ' item' + (d.needs_attention > 1 ? 's' : '') + '</span>';
        } else if (d.total > 0) {
            banner.style.display = 'flex';
            banner.className = 'comp-reminder ok';
            banner.innerHTML = '<i class="fas fa-check-circle reminder-icon" style="color:#22C55E"></i>' +
                '<span class="reminder-text">All documents are current. Nice work!</span>';
        }
    } catch(e) { console.error(e); }
}

// --- Load Items ---
async function loadItems() {
    try {
        const params = new URLSearchParams({ action: 'list' });
        if (currentFilter === 'have') params.set('have_it', '1');
        else if (currentFilter === 'missing') params.set('have_it', '0');
        const search = document.getElementById('compSearch').value.trim();
        if (search) params.set('search', search);

        const res = await fetch(API + '?' + params);
        const json = await res.json();
        if (!json.success) return;

        renderItems(json.data);
    } catch(e) { console.error(e); }
}

function renderItems(items) {
    const tbody = document.getElementById('itemsBody');

    // Client-side filter for expiring
    let filtered = items;
    if (currentFilter === 'expiring') {
        filtered = items.filter(i => i.computed_status === 'expiring' || i.computed_status === 'expired');
    }

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="comp-empty"><i class="fas fa-folder-open"></i><p>No documents found. Add your first one!</p><button class="btn btn-primary btn-sm" onclick="openItemModal()"><i class="fas fa-plus"></i> Add Document</button></div></td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(item => {
        const catIcons = { license: 'fa-id-badge', permit: 'fa-file-alt', certification: 'fa-certificate', insurance: 'fa-shield-alt', registration: 'fa-clipboard-list', inspection: 'fa-clipboard-check', other: 'fa-file' };
        const catIcon = catIcons[item.category] || 'fa-file';

        // Have it column
        const haveHtml = item.have_it == 1
            ? '<span class="have-yes"><i class="fas fa-check-circle"></i> Yes</span>'
            : '<span class="have-no"><i class="far fa-circle"></i> No</span>';

        // Expiry column
        let expiryHtml = '<span class="mono" style="color:var(--text-tertiary)">No expiry</span>';
        let statusHtml = '<span style="color:var(--text-tertiary);font-size:12px">--</span>';

        if (item.have_it == 0) {
            statusHtml = '<span style="color:var(--text-tertiary);font-size:12px">Need to obtain</span>';
        } else if (item.expiry_date) {
            expiryHtml = '<span class="mono">' + item.expiry_date + '</span>';
            const days = parseInt(item.days_until_expiry);
            if (days < 0) {
                statusHtml = '<span class="expiry-expired" style="font-size:12px"><i class="fas fa-exclamation-circle"></i> Expired ' + Math.abs(days) + 'd ago</span>';
            } else if (item.computed_status === 'expiring') {
                statusHtml = '<span class="expiry-warn" style="font-size:12px"><i class="fas fa-clock"></i> ' + days + ' days left</span>';
            } else {
                statusHtml = '<span class="expiry-ok" style="font-size:12px"><i class="fas fa-check"></i> Good (' + days + 'd)</span>';
            }
        } else if (item.have_it == 1) {
            statusHtml = '<span class="expiry-ok" style="font-size:12px"><i class="fas fa-check"></i> Current</span>';
        }

        const costHtml = parseFloat(item.cost) > 0 ? '<span class="mono">$' + parseFloat(item.cost).toFixed(2) + '</span>' : '<span style="color:var(--text-tertiary)">--</span>';

        return '<tr>' +
            '<td class="name-cell"><i class="fas ' + catIcon + '" style="color:var(--navy-300);margin-right:8px;font-size:12px"></i>' + escHtml(item.name) +
            (item.document_number ? '<br><small class="mono" style="color:var(--text-tertiary)">#' + escHtml(item.document_number) + '</small>' : '') +
            (item.notes ? '<br><small style="color:var(--text-tertiary);font-size:11px">' + escHtml(item.notes).substring(0, 60) + '</small>' : '') +
            '</td>' +
            '<td><span class="cat-badge">' + item.category + '</span></td>' +
            '<td>' + haveHtml + '</td>' +
            '<td>' + expiryHtml + '</td>' +
            '<td>' + statusHtml + '</td>' +
            '<td>' + costHtml + '</td>' +
            '<td><div class="actions">' +
            '<button class="btn btn-sm btn-outline-primary" onclick="editItem(' + item.id + ')" title="Edit"><i class="fas fa-edit"></i></button>' +
            '<button class="btn btn-sm btn-outline-danger" onclick="deleteItem(' + item.id + ')" title="Delete"><i class="fas fa-trash"></i></button>' +
            '</div></td></tr>';
    }).join('');
}

// --- Filters ---
function setFilter(filter, btn) {
    currentFilter = filter;
    document.querySelectorAll('.comp-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadItems();
}

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadItems, 300);
}

// --- Modal ---
function openItemModal(data) {
    const form = document.getElementById('itemForm');
    form.reset();
    document.getElementById('fId').value = '';
    document.getElementById('fRemindDays').value = '30';
    document.getElementById('fCost').value = '0';

    if (data) {
        document.getElementById('modalTitle').textContent = 'Edit Document';
        document.getElementById('fId').value = data.id;
        document.getElementById('fName').value = data.name || '';
        document.getElementById('fCategory').value = data.category || 'other';
        document.getElementById('fHaveIt').checked = data.have_it == 1;
        document.getElementById('fIssuer').value = data.issuing_authority || '';
        document.getElementById('fDocNum').value = data.document_number || '';
        document.getElementById('fIssueDate').value = data.issue_date || '';
        document.getElementById('fExpiryDate').value = data.expiry_date || '';
        document.getElementById('fRemindDays').value = data.remind_days_before || 30;
        document.getElementById('fCost').value = parseFloat(data.cost || 0).toFixed(2);
        document.getElementById('fNotes').value = data.notes || '';
    } else {
        document.getElementById('modalTitle').textContent = 'Add Document';
    }
    modal.show();
}

async function editItem(id) {
    try {
        const res = await fetch(API + '?action=get&id=' + id);
        const json = await res.json();
        if (json.success) openItemModal(json.data);
    } catch(e) {}
}

async function saveItem() {
    const form = document.getElementById('itemForm');
    const fd = new FormData(form);
    const id = document.getElementById('fId').value;
    fd.set('action', id ? 'update' : 'create');
    if (id) fd.set('id', id);
    if (!document.getElementById('fHaveIt').checked) fd.delete('have_it');

    try {
        const res = await fetch(API, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            modal.hide();
            loadReminders();
            loadItems();
        } else {
            alert('Error: ' + json.error);
        }
    } catch(e) { alert('Network error'); }
}

async function deleteItem(id) {
    if (!confirm('Remove this document from your tracking list?')) return;
    const fd = new FormData();
    fd.set('action', 'delete');
    fd.set('id', id);
    try {
        const res = await fetch(API, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) { loadReminders(); loadItems(); }
    } catch(e) {}
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
</script>