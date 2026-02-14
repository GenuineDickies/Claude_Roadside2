// ═════════════════════════════════════════════════════════════════════
// Intake Form — Customer lookup, fill, vehicle select, recent customers
// ═════════════════════════════════════════════════════════════════════

// ─── Customer Phone Lookup ──────────────────────────────────────────
let lookupTimer = null;
document.getElementById('custLookupPhone')?.addEventListener('input', function() {
    clearTimeout(lookupTimer);
    const phone = phoneGetRawDigits(this);
    if (phone.length < 4) { document.getElementById('custLookupResults').innerHTML = ''; return; }
    lookupTimer = setTimeout(() => lookupCustomer(phone), 300);
});

document.getElementById('customerPhone')?.addEventListener('input', function() {
    clearTimeout(lookupTimer);
    const phone = this.value.replace(/\D/g, '');
    if (phone.length >= 6) {
        lookupTimer = setTimeout(() => lookupCustomer(phone), 400);
    }
    updateSummary();
});

async function lookupCustomer(phone) {
    try {
        const res = await fetch(`${API_TICKETS}?action=customer_lookup&phone=${phone}`);
        const json = await res.json();
        const panel = document.getElementById('custLookupResults');
        if (json.success && json.data.length) {
            panel.innerHTML = json.data.map(c => `
                <div class="intake-cust-card" onclick="fillCustomer(${JSON.stringify(c).replace(/"/g, '&quot;')})">
                    <div class="name">${c.first_name} ${c.last_name}</div>
                    <div class="phone">${c.phone}</div>
                    <div class="meta">${c.email || ''} ${c.last_service ? '· Last: ' + c.last_service.substring(0,10) : ''}</div>
                </div>
            `).join('');
        } else {
            panel.innerHTML = '<p style="font-size:12px;color:var(--text-tertiary);padding:4px">No customers found</p>';
        }
    } catch(e) {}
}

function fillCustomer(cust) {
    customerData = cust;
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = (cust.phone || '').replace(/\D/g, '').slice(0, 10);
    phoneMaskApply(phoneInput, phoneDigits);
    document.querySelector('input[name="customer_name"]').value = cust.first_name + ' ' + cust.last_name;
    if (cust.email) document.querySelector('input[name="customer_email"]').value = cust.email;

    if (cust.vehicles && cust.vehicles.length) {
        const vSelect = document.getElementById('vehicleSelect');
        const vPanel = document.getElementById('vehicleSelectPanel');
        vSelect.innerHTML = '<option value="">+ Enter new vehicle</option>';
        cust.vehicles.forEach(v => {
            vSelect.innerHTML += `<option value="${v.id}" data-vehicle='${JSON.stringify(v)}'>${v.year || ''} ${v.make || ''} ${v.model || ''} ${v.color ? '(' + v.color + ')' : ''}</option>`;
        });
        vPanel.style.display = 'block';
    }

    updateSummary();
    document.getElementById('sec-vehicle')?.classList.add('open');
}

// Vehicle select → fill fields
document.getElementById('vehicleSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt.value && opt.dataset.vehicle) {
        const v = JSON.parse(opt.dataset.vehicle);
        const fields = document.getElementById('vehicleFields');
        if (v.year) fields.querySelector('[name="vehicle_year"]').value = v.year;
        if (v.make) fields.querySelector('[name="vehicle_make"]').value = v.make;
        if (v.model) fields.querySelector('[name="vehicle_model"]').value = v.model;
        if (v.color) fields.querySelector('[name="vehicle_color"]').value = v.color;
        if (v.license_plate) fields.querySelector('[name="vehicle_plate"]').value = v.license_plate;
        if (v.vin) fields.querySelector('[name="vehicle_vin"]').value = v.vin;
        if (v.mileage) fields.querySelector('[name="vehicle_mileage"]').value = v.mileage;
        if (v.drive_type) fields.querySelector('[name="vehicle_drive_type"]').value = v.drive_type;
        updateSummary();
    }
});

// ─── Load Recent Customers ──────────────────────────────────────────
async function loadRecentCustomers() {
    try {
        const res = await fetch(`${API_TICKETS}?action=recent_customers`);
        const json = await res.json();
        const panel = document.getElementById('recentCustomersPanel');
        if (json.success && json.data.length) {
            panel.innerHTML = json.data.slice(0, 8).map(c => `
                <div class="intake-cust-card" onclick="lookupAndFill('${c.phone}')">
                    <div class="name">${c.first_name} ${c.last_name}</div>
                    <div class="phone">${c.phone}</div>
                    <div class="meta">${c.last_service_type ? c.last_service_type.replace(/_/g,' ') : ''} ${c.last_service_date ? '· ' + c.last_service_date.substring(0,10) : ''}</div>
                </div>
            `).join('');
        } else {
            panel.innerHTML = '<p style="font-size:12px;color:var(--text-tertiary)">No recent customers</p>';
        }
    } catch(e) {}
}

async function lookupAndFill(phone) {
    const res = await fetch(`${API_TICKETS}?action=customer_lookup&phone=${phone.replace(/\D/g,'')}`);
    const json = await res.json();
    if (json.success && json.data.length) fillCustomer(json.data[0]);
}
