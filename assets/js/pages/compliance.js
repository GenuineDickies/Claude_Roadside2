const COMP_API = 'api/compliance.php';
let compModal;
let currentFilter = 'all';
let searchTimer;

document.addEventListener('DOMContentLoaded', function() {
    compModal = new bootstrap.Modal(document.getElementById('itemModal'));
    loadReminders();
    loadItems();
});

// --- Reminders ---
async function loadReminders() {
    try {
        const res = await fetch(COMP_API + '?action=reminders');
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

        const res = await fetch(COMP_API + '?' + params);
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
    compModal.show();
}

async function editItem(id) {
    try {
        const res = await fetch(COMP_API + '?action=get&id=' + id);
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
        const res = await fetch(COMP_API, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            compModal.hide();
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
        const res = await fetch(COMP_API, { method: 'POST', body: fd });
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
