// ═════════════════════════════════════════════════════════════════════
// Intake Form — Summary, section progress, form submission
// ═════════════════════════════════════════════════════════════════════

// ─── Summary Panel Updates ──────────────────────────────────────────
function updateSummary() {
    const firstName = document.querySelector('input[name="first_name"]')?.value || '';
    const lastName = document.querySelector('input[name="last_name"]')?.value || '';
    const name = (firstName + ' ' + lastName).trim();
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = phoneInput ? phoneGetRawDigits(phoneInput) : '';
    const phone = phoneDigits.length > 0 ? phoneInput.value : '';
    const year = document.querySelector('input[name="vehicle_year"]')?.value;
    const make = document.querySelector('input[name="vehicle_make"]')?.value;
    const model = document.querySelector('input[name="vehicle_model"]')?.value;
    // Build address from split fields
    const street1 = document.getElementById('streetAddress1')?.value || '';
    const city = document.getElementById('serviceCity')?.value || '';
    const state = document.getElementById('serviceState')?.value || '';
    const addr = [street1, city, state].filter(Boolean).join(', ');
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

['first_name','last_name','street_address_1','city'].forEach(n => {
    document.querySelector(`input[name="${n}"]`)?.addEventListener('input', () => updateSummary());
});
document.getElementById('serviceState')?.addEventListener('change', updateSummary);
document.querySelector('input[name="customer_phone"]')?.addEventListener('input', () => updateSummary());
document.querySelector('select[name="payment_method"]')?.addEventListener('change', updateSummary);
document.getElementById('estimatedCost')?.addEventListener('input', updateSummary);

// ─── Section Progress ───────────────────────────────────────────────
function updateSectionProgress() {
    const sections = isRapidMode ? 3 : 7;
    let complete = 0;
    if (phoneGetRawDigits(document.querySelector('input[name="customer_phone"]')).length >= 7) complete++;
    if (!isRapidMode && document.querySelector('input[name="vehicle_make"]')?.value) complete++;
    if (document.getElementById('streetAddress1')?.value && document.getElementById('serviceCity')?.value) complete++;
    if (selectedCategory) complete++;
    if (document.getElementById('priorityInput')?.value) complete++;
    if (!isRapidMode) complete += 2;

    document.getElementById('sectionProgress').textContent = `${Math.min(complete, sections)} of ${sections} sections complete`;
}

// ─── SMS Consent Modal ──────────────────────────────────────────────
let smsConsentModal = null;

function showSmsConsentModal() {
    // First validate form before showing consent
    const form = document.getElementById('intakeForm');
    const fd = new FormData(form);
    
    const phoneInput = document.querySelector('input[name="customer_phone"]');
    const phoneDigits = phoneGetRawDigits(phoneInput);
    
    const errors = [];
    if (phoneDigits.length < 7) errors.push('Phone number is required (min 7 digits)');
    if (!fd.get('first_name')?.trim()) errors.push('First name is required');
    if (!fd.get('last_name')?.trim()) errors.push('Last name is required');
    if (!fd.get('street_address_1')?.trim()) errors.push('Street address is required');
    if (!fd.get('city')?.trim()) errors.push('City is required');
    if (!fd.get('state')?.trim()) errors.push('State is required');
    if (!selectedCategory) errors.push('Service category is required');
    if (!fd.get('issue_description')?.trim() && !isRapidMode) errors.push('Issue description is required');
    if (!fd.get('vehicle_year')?.trim()) errors.push('Vehicle year is required');
    if (!fd.get('vehicle_make')?.trim()) errors.push('Vehicle make is required');
    if (!fd.get('vehicle_model')?.trim()) errors.push('Vehicle model is required');
    if (!fd.get('vehicle_color')?.trim()) errors.push('Vehicle color is required');
    
    if (errors.length) {
        alert('Please fix the following before creating ticket:\n\n' + errors.join('\n'));
        return;
    }
    
    // Show SMS consent modal
    if (!smsConsentModal) {
        smsConsentModal = new bootstrap.Modal(document.getElementById('smsConsentModal'));
    }

    const phoneTarget = document.getElementById('smsConsentPhone');
    if (phoneTarget) {
        phoneTarget.textContent = phoneInput?.value?.trim() || 'this number';
    }
    smsConsentModal.show();
}

function confirmSmsConsentAndSubmit() {
    // Close modal and submit
    if (smsConsentModal) {
        smsConsentModal.hide();
    }
    // Add SMS consent flag to form
    const form = document.getElementById('intakeForm');
    let consentInput = form.querySelector('input[name="sms_consent"]');
    if (!consentInput) {
        consentInput = document.createElement('input');
        consentInput.type = 'hidden';
        consentInput.name = 'sms_consent';
        form.appendChild(consentInput);
    }
    consentInput.value = '1';
    
    // Now submit the ticket
    submitTicket();
}

function confirmNoSmsConsentAndSubmit() {
    // Close modal and submit (no SMS consent)
    if (smsConsentModal) {
        smsConsentModal.hide();
    }

    // Ensure hidden consent input exists and explicitly set to 0
    const form = document.getElementById('intakeForm');
    let consentInput = form.querySelector('input[name="sms_consent"]');
    if (!consentInput) {
        consentInput = document.createElement('input');
        consentInput.type = 'hidden';
        consentInput.name = 'sms_consent';
        form.appendChild(consentInput);
    }
    consentInput.value = '0';

    // Submit ticket (no SMS consent)
    submitTicket();
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

    // Build full service address from split fields for backend
    const street1 = fd.get('street_address_1') || '';
    const street2 = fd.get('street_address_2') || '';
    const city = fd.get('city') || '';
    const state = fd.get('state') || '';
    const fullAddress = [street1, street2, city, state].filter(Boolean).join(', ');
    fd.set('service_address', fullAddress);

    // Build customer_name for backend compatibility
    const firstName = fd.get('first_name') || '';
    const lastName = fd.get('last_name') || '';
    fd.set('customer_name', (firstName + ' ' + lastName).trim());

    const errors = [];
    if (phoneDigits.length < 7) errors.push('Phone number is required (min 7 digits)');
    if (!fd.get('first_name')?.trim()) errors.push('First name is required');
    if (!fd.get('last_name')?.trim()) errors.push('Last name is required');
    if (!fd.get('street_address_1')?.trim()) errors.push('Street address is required');
    if (!fd.get('city')?.trim()) errors.push('City is required');
    if (!fd.get('state')?.trim()) errors.push('State is required');
    if (!fd.get('service_category')?.trim()) errors.push('Service category is required');
    if (!fd.get('issue_description')?.trim() && !isRapidMode) errors.push('Issue description is required');
    if (!fd.get('vehicle_year')?.trim()) errors.push('Vehicle year is required');
    if (!fd.get('vehicle_make')?.trim()) errors.push('Vehicle make is required');
    if (!fd.get('vehicle_model')?.trim()) errors.push('Vehicle model is required');
    if (!fd.get('vehicle_color')?.trim()) errors.push('Vehicle color is required');
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
