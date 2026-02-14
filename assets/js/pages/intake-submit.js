// ═════════════════════════════════════════════════════════════════════
// Intake Form — Summary, section progress, form submission
// ═════════════════════════════════════════════════════════════════════

// ─── Summary Panel Updates ──────────────────────────────────────────
function updateSummary() {
    const name = document.querySelector('input[name="customer_name"]')?.value;
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = phoneInput ? phoneGetRawDigits(phoneInput) : '';
    const phone = phoneDigits.length > 0 ? phoneInput.value : '';
    const year = document.querySelector('input[name="vehicle_year"]')?.value;
    const make = document.querySelector('input[name="vehicle_make"]')?.value;
    const model = document.querySelector('input[name="vehicle_model"]')?.value;
    const addr = document.getElementById('serviceAddress')?.value;
    const cost = document.getElementById('estimatedCost')?.value;
    const priority = document.getElementById('priorityInput')?.value;
    const payment = document.querySelector('select[name="payment_method"]')?.selectedOptions?.[0]?.text;

    document.getElementById('sumCustomer').textContent = name || '—';
    document.getElementById('sumPhone').textContent = phone || '—';
    document.getElementById('sumVehicle').textContent = (year || make || model) ? `${year || ''} ${make || ''} ${model || ''}`.trim() : '—';
    document.getElementById('sumLocation').textContent = addr ? (addr.length > 30 ? addr.substring(0,30) + '…' : addr) : '—';
    document.getElementById('sumService').textContent = selectedCategory ? selectedCategory.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '—';
    document.getElementById('sumPriority').textContent = priority || 'P3';
    document.getElementById('sumPayment').textContent = payment || 'Card';
    document.getElementById('sumCost').textContent = cost ? '$' + parseFloat(cost).toFixed(2) : '$0.00';
}

['customer_name','service_address'].forEach(n => {
    document.querySelector(`input[name="${n}"]`)?.addEventListener('input', () => updateSummary());
});
document.querySelector('input[name="customer_phone"]')?.addEventListener('input', () => updateSummary());
document.querySelector('select[name="payment_method"]')?.addEventListener('change', updateSummary);
document.getElementById('estimatedCost')?.addEventListener('input', updateSummary);

// ─── Section Progress ───────────────────────────────────────────────
function updateSectionProgress() {
    const sections = isRapidMode ? 3 : 7;
    let complete = 0;
    if (phoneGetRawDigits(document.querySelector('input[name="customer_phone"]')).length >= 7) complete++;
    if (!isRapidMode && document.querySelector('input[name="vehicle_make"]')?.value) complete++;
    if (document.getElementById('serviceAddress')?.value) complete++;
    if (selectedCategory) complete++;
    if (document.getElementById('priorityInput')?.value) complete++;
    if (!isRapidMode) complete += 2;

    document.getElementById('sectionProgress').textContent = `${Math.min(complete, sections)} of ${sections} sections complete`;
}

// ─── Form Submission ────────────────────────────────────────────────
async function submitTicket() {
    const form = document.getElementById('intakeForm');
    const fd = new FormData(form);
    fd.append('action', 'create_ticket');
    if (isRapidMode) fd.set('rapid_dispatch', '1');

    const hazards = [];
    document.querySelectorAll('input[name="hazard[]"]:checked').forEach(h => hazards.push(h.value));
    fd.set('hazard_conditions', hazards.join(','));

    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = phoneGetRawDigits(phoneInput);
    fd.set('customer_phone', phoneDigits);
    const altPhoneInput = document.querySelector('input[name="alt_phone"]');
    if (altPhoneInput) fd.set('alt_phone', phoneGetRawDigits(altPhoneInput));

    const errors = [];
    if (phoneDigits.length < 7) errors.push('Phone number is required (min 7 digits)');
    if (!fd.get('customer_name')?.trim()) errors.push('Customer name is required');
    if (!fd.get('service_address')?.trim()) errors.push('Service address is required');
    if (!fd.get('service_category')?.trim()) errors.push('Service category is required');
    if (!fd.get('issue_description')?.trim() && !isRapidMode) errors.push('Issue description is required');
    if (isRapidMode && !fd.get('issue_description')?.trim()) fd.set('issue_description', selectedCategory.replace(/_/g, ' '));

    if (errors.length) {
        alert('Please fix:\n\n' + errors.join('\n'));
        return;
    }

    try {
        const res = await fetch(API_TICKETS, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            window.location.href = `?page=service-requests&created=${json.data.ticket_number}`;
        } else {
            alert('Error: ' + (json.error || 'Unknown error') + (json.fields ? '\nMissing: ' + json.fields.join(', ') : ''));
        }
    } catch(e) {
        alert('Network error. Please try again.');
    }
}

function saveDraft() {
    alert('Draft saving coming soon. For now, use Create Ticket to save.');
}
