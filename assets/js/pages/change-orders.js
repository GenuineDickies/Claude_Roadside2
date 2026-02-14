const CO_API = 'api/workflow.php';
let coItems = [];
let coLineNum = 0;

function addCoItem() {
    const type = document.getElementById('coItemType').value;
    const desc = document.getElementById('coItemDesc').value.trim();
    const qty = parseFloat(document.getElementById('coItemQty').value) || 1;
    const price = parseFloat(document.getElementById('coItemPrice').value) || 0;
    if (!desc) { alert('Description required'); return; }
    coLineNum++;
    const ext = Math.round(qty * price * 100) / 100;
    coItems.push({ line_number: coLineNum, item_type: type, description: desc, quantity: qty, unit_price: price, extended_price: ext, taxable: 1 });
    renderCoItems();
    document.getElementById('coItemDesc').value = '';
    document.getElementById('coItemPrice').value = '0.00';
    updateNetImpact();
}

function removeCoItem(idx) {
    coItems.splice(idx, 1);
    coItems.forEach((li, i) => li.line_number = i + 1);
    coLineNum = coItems.length;
    renderCoItems();
    updateNetImpact();
}

function renderCoItems() {
    const tbody = document.getElementById('coItemsBody');
    if (!coItems.length) { tbody.innerHTML = '<tr id="coEmptyRow"><td colspan="7" style="text-align:center;padding:20px;color:var(--text-tertiary)">No items.</td></tr>'; return; }
    tbody.innerHTML = coItems.map((li, idx) => `
        <tr style="border-bottom:1px solid var(--border-subtle)">
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px">${li.line_number}</td>
            <td style="padding:8px"><span style="font-size:10px;padding:2px 8px;border-radius:3px;text-transform:uppercase;font-weight:600;background:rgba(245,158,11,0.1);color:var(--amber-500)">${li.item_type.replace(/_/g,' ')}</span></td>
            <td style="padding:8px;font-size:13px">${li.description}</td>
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px">${li.quantity.toFixed(2)}</td>
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px">${li.unit_price.toFixed(2)}</td>
            <td style="padding:8px;font-family:'JetBrains Mono',monospace;font-size:12px;font-weight:600;text-align:right">$${li.extended_price.toFixed(2)}</td>
            <td style="padding:8px"><span style="cursor:pointer;color:var(--text-tertiary);font-size:12px" onclick="removeCoItem(${idx})"><i class="fas fa-times"></i></span></td>
        </tr>
    `).join('');
}

function updateNetImpact() {
    const total = coItems.reduce((sum, li) => sum + li.extended_price, 0);
    document.getElementById('netImpactInput').value = total.toFixed(2);
}

async function submitCO() {
    const form = document.getElementById('coForm');
    const fd = new FormData(form);
    fd.append('action', 'create_change_order');
    fd.append('line_items', JSON.stringify(coItems));
    try {
        const res = await fetch(CO_API, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) window.location.href = `?page=change-orders&action=view&id=${json.data.id}`;
        else alert('Error: ' + (json.error || 'Failed'));
    } catch(e) { alert('Network error'); }
}

async function updateCoStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_change_order_status');
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch(CO_API, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function approveCo(id) {
    const method = prompt('Approval method (verbal, signature, digital):', 'verbal');
    if (!method) return;
    const fd = new FormData();
    fd.append('action', 'update_change_order_status');
    fd.append('id', id);
    fd.append('status', 'approved');
    fd.append('approval_method', method);
    const res = await fetch(CO_API, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function declineCo(id) {
    const reason = prompt('Decline reason:');
    if (reason === null) return;
    const fd = new FormData();
    fd.append('action', 'update_change_order_status');
    fd.append('id', id);
    fd.append('status', 'declined');
    fd.append('decline_reason', reason);
    const res = await fetch(CO_API, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}
