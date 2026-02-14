const API_W = 'api/workflow.php';
const API_T = 'api/service-tickets.php';
let lineItems = [];
let lineNum = 0;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('estimateForm');
    if (!form) return;

    if (Array.isArray(window.initialEstimateLineItems) && window.initialEstimateLineItems.length) {
        lineItems = window.initialEstimateLineItems.map(li => ({
            line_number: li.line_number,
            item_type: li.item_type,
            description: li.description || '',
            quantity: Number(li.quantity || 0),
            unit_price: Number(li.unit_price || 0),
            markup_pct: li.markup_pct !== null ? Number(li.markup_pct) : null,
            extended_price: Number(li.extended_price || 0),
            taxable: Number(li.taxable || 0),
            notes: li.notes || null,
        }));
        lineNum = lineItems.length;
        renderLineItems();
    }

    if (window.initialEstimate) {
        const techSelect = form.querySelector('[name="technician_id"]');
        if (techSelect && window.initialEstimate.technician_id) {
            techSelect.value = String(window.initialEstimate.technician_id);
        }

        const taxInput = form.querySelector('[name="tax_rate_pct"]');
        if (taxInput && typeof window.initialEstimate.tax_rate === 'number' && !Number.isNaN(window.initialEstimate.tax_rate)) {
            taxInput.value = (window.initialEstimate.tax_rate * 100).toFixed(2);
        }

        const notesField = form.querySelector('[name="internal_notes"]');
        if (notesField && window.initialEstimate.internal_notes) {
            notesField.value = window.initialEstimate.internal_notes;
        }

        updateTotals();
    }
});

// ─── Add Line Item ──────────────────────────────────────────────────
function addLineItem() {
    const type = document.getElementById('newItemType').value;
    const desc = document.getElementById('newItemDesc').value.trim();
    const qty = parseFloat(document.getElementById('newItemQty').value) || 1;
    const price = parseFloat(document.getElementById('newItemPrice').value) || 0;
    const markup = document.getElementById('newItemMarkup').value ? parseFloat(document.getElementById('newItemMarkup').value) : null;
    if (!desc) { alert('Description required'); return; }
    lineNum++;
    const ext = Math.round(qty * price * (1 + (markup || 0) / 100) * 100) / 100;
    lineItems.push({ line_number: lineNum, item_type: type, description: desc, quantity: qty, unit_price: price, markup_pct: markup, extended_price: ext, taxable: 1 });
    renderLineItems();
    document.getElementById('newItemDesc').value = '';
    document.getElementById('newItemPrice').value = '0.00';
    document.getElementById('newItemMarkup').value = '';
    document.getElementById('newItemDesc').focus();
}

function removeLineItem(idx) {
    lineItems.splice(idx, 1);
    lineItems.forEach((li, i) => li.line_number = i + 1);
    lineNum = lineItems.length;
    renderLineItems();
}

function renderLineItems() {
    const tbody = document.getElementById('lineItemsBody');
    if (!lineItems.length) {
        tbody.innerHTML = '<tr id="emptyRow"><td colspan="8" style="text-align:center;padding:30px;color:var(--text-tertiary)">No line items yet.</td></tr>';
        updateTotals();
        return;
    }
    tbody.innerHTML = lineItems.map((li, idx) => `
        <tr>
            <td style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-tertiary)">${li.line_number}</td>
            <td><span class="type-badge type-${li.item_type}">${li.item_type.replace(/_/g,' ')}</span></td>
            <td>${li.description}</td>
            <td class="money">${li.quantity.toFixed(2)}</td>
            <td class="money">$${li.unit_price.toFixed(2)}</td>
            <td class="money">${li.markup_pct !== null ? li.markup_pct + '%' : '—'}</td>
            <td class="money" style="font-weight:600">$${li.extended_price.toFixed(2)}</td>
            <td><span class="remove-btn" onclick="removeLineItem(${idx})"><i class="fas fa-times"></i></span></td>
        </tr>
    `).join('');
    updateTotals();
}

function updateTotals() {
    let labor = 0, parts = 0, services = 0, tow = 0;
    lineItems.forEach(li => {
        switch(li.item_type) {
            case 'labor': labor += li.extended_price; break;
            case 'parts': parts += li.extended_price; break;
            case 'service_fee': services += li.extended_price; break;
            case 'tow_mileage': tow += li.extended_price; break;
        }
    });
    const subtotal = labor + parts + services + tow;
    const taxRate = parseFloat(document.getElementById('taxRateInput')?.value || 8.25) / 100;
    const taxable = lineItems.reduce((sum, li) => sum + (li.taxable ? li.extended_price : 0), 0);
    const tax = Math.round(taxable * taxRate * 100) / 100;
    const grand = subtotal + tax;
    document.getElementById('totLabor').textContent = '$' + labor.toFixed(2);
    document.getElementById('totParts').textContent = '$' + parts.toFixed(2);
    document.getElementById('totServices').textContent = '$' + services.toFixed(2);
    document.getElementById('totTow').textContent = '$' + tow.toFixed(2);
    document.getElementById('totSubtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('totTaxRate').textContent = (taxRate * 100).toFixed(2);
    document.getElementById('totTax').textContent = '$' + tax.toFixed(2);
    document.getElementById('totGrand').textContent = '$' + grand.toFixed(2);
}

document.getElementById('taxRateInput')?.addEventListener('input', updateTotals);

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ─── Save Estimate ──────────────────────────────────────────────────
async function saveEstimate(initialStatus = 'draft') {
    const form = document.getElementById('estimateForm');
    const versionField = form.querySelector('[name="version"]');
    if (versionField) {
        let versionVal = parseInt(versionField.value, 10);
        if (!versionVal || versionVal < 1) {
            alert('Version must be at least 1');
            versionField.focus();
            return;
        }
        versionVal = Math.max(1, versionVal);
        versionField.value = versionVal;
    }
    const fd = new FormData(form);
    fd.append('action', 'create_estimate');
    fd.append('tax_rate', (parseFloat(fd.get('tax_rate_pct')) / 100).toFixed(4));
    fd.append('line_items', JSON.stringify(lineItems));
    if (versionField) {
        fd.set('version', versionField.value);
    }
    try {
        const res = await fetch(API_W, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            if (initialStatus === 'presented') {
                const fd2 = new FormData();
                fd2.append('action', 'update_estimate_status');
                fd2.append('id', json.data.id);
                fd2.append('status', 'presented');
                await fetch(API_W, { method: 'POST', body: fd2 });
            }
            window.location.href = `?page=estimates&action=view&id=${json.data.id}`;
        } else {
            alert('Error: ' + (json.error || 'Failed'));
        }
    } catch(e) { alert('Network error'); }
}

// ─── Status Actions ─────────────────────────────────────────────────
async function updateEstStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_estimate_status');
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) location.reload();
    else alert('Error updating status');
}

async function approveEstimate(id) {
    const method = prompt('Approval method (verbal, signature, digital):', 'verbal');
    if (!method) return;
    const name = prompt('Approver name:');
    const fd = new FormData();
    fd.append('action', 'update_estimate_status');
    fd.append('id', id);
    fd.append('status', 'approved');
    fd.append('approval_method', method);
    if (name) fd.append('approver_name', name);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function declineEstimate(id) {
    const reason = prompt('Decline reason:');
    if (reason === null) return;
    const fd = new FormData();
    fd.append('action', 'update_estimate_status');
    fd.append('id', id);
    fd.append('status', 'declined');
    fd.append('decline_reason', reason);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function createWoFromEstimate(estId, srId, techId) {
    const fd = new FormData();
    fd.append('action', 'create_work_order');
    fd.append('service_request_id', srId);
    fd.append('estimate_id', estId);
    fd.append('technician_id', techId);
    const res = await fetch(API_W, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) {
        window.location.href = `?page=work-orders&action=view&id=${json.data.id}`;
    } else {
        alert('Error: ' + (json.error || 'Failed'));
    }
}
