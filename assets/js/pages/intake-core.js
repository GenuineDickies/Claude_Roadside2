// ═════════════════════════════════════════════════════════════════════
// Intake Form Controller — Core (accordion, category, priority, UI)
// ═════════════════════════════════════════════════════════════════════
const API_TICKETS = 'api/service-tickets.php';
const API_WORKFLOW = 'api/workflow.php';
let selectedCategory = '';
let isRapidMode = false;
let customerData = null;

// ─── Section Accordion ──────────────────────────────────────────────
function toggleSection(head) {
    const sec = head.closest('.intake-section');
    sec.classList.toggle('open');
}

// ─── Rapid Dispatch Toggle ──────────────────────────────────────────
document.getElementById('rapidToggle').addEventListener('click', function() {
    isRapidMode = !isRapidMode;
    this.classList.toggle('active', isRapidMode);
    const conditionals = document.querySelectorAll('.intake-conditional');
    conditionals.forEach(el => {
        if (isRapidMode) {
            el.classList.remove('visible');
            el.style.display = 'none';
        } else {
            el.classList.add('visible');
            el.style.display = '';
        }
    });
    updateSectionProgress();
});

// ─── Show non-rapid sections on load ────────────────────────────────
document.querySelectorAll('.intake-conditional').forEach(el => {
    el.classList.add('visible');
    el.style.display = '';
});

// ─── Category Selection ─────────────────────────────────────────────
function selectCategory(card) {
    document.querySelectorAll('.intake-cat-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    selectedCategory = card.dataset.val;
    document.getElementById('serviceCategoryInput').value = selectedCategory;

    // Show tow destination if towing
    const towFields = document.getElementById('towDestFields');
    if (towFields) towFields.classList.toggle('visible', selectedCategory === 'towing');

    // Load specific services
    loadSpecificServices(selectedCategory);
    updateSummary();
    updateEstimate();
}

async function loadSpecificServices(category) {
    const panel = document.getElementById('specificServicesPanel');
    const select = document.getElementById('specificServiceSelect');
    try {
        const res = await fetch(`${API_TICKETS}?action=get_services&category=${category}`);
        const json = await res.json();
        if (json.success && json.data.length) {
            select.innerHTML = '<option value="">Select specific service...</option>';
            json.data.forEach(s => {
                const rate = parseFloat(s.base_rate);
                select.innerHTML += `<option value="${s.id}" data-rate="${rate}">${s.name} — $${rate.toFixed(2)}</option>`;
            });
            panel.style.display = 'block';
        } else {
            panel.style.display = 'none';
        }
    } catch(e) { panel.style.display = 'none'; }
}

document.getElementById('specificServiceSelect')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.rate) {
        document.getElementById('estimatedCost').value = parseFloat(opt.dataset.rate).toFixed(2);
        updateSummary();
    }
});

// ─── Priority Selection ─────────────────────────────────────────────
function selectPriority(card) {
    document.querySelectorAll('.intake-priority-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    document.getElementById('priorityInput').value = card.dataset.val;
    updateSummary();
}

// ─── Safety Toggle ──────────────────────────────────────────────────
document.querySelectorAll('input[name="safe_location"]').forEach(r => {
    r.addEventListener('change', function() {
        const warn = document.getElementById('safetyWarning');
        if (this.value === '0') {
            warn.classList.add('visible');
            selectPriority(document.querySelector('.intake-priority-card[data-val="P1"]'));
        } else {
            warn.classList.remove('visible');
        }
    });
});

// ─── Location Type → show highway fields ────────────────────────────
document.getElementById('locationType')?.addEventListener('change', function() {
    const isHwy = this.value === 'highway' || this.value === 'roadside';
    document.getElementById('highwayFields')?.classList.toggle('visible', isHwy);
    document.getElementById('directionField')?.classList.toggle('visible', isHwy);
});

// ─── ETA → show scheduled field ─────────────────────────────────────
document.querySelector('select[name="requested_eta"]')?.addEventListener('change', function() {
    document.getElementById('scheduledDateField')?.classList.toggle('visible', this.value === 'scheduled');
});

// ─── Radio / Checkbox styling ───────────────────────────────────────
document.querySelectorAll('.intake-radio input').forEach(r => {
    r.addEventListener('change', function() {
        this.closest('.intake-radio-group,.intake-section-body').querySelectorAll('.intake-radio').forEach(l => l.classList.remove('selected'));
        this.closest('.intake-radio').classList.add('selected');
    });
});
document.querySelectorAll('.intake-check input').forEach(c => {
    c.addEventListener('change', function() {
        this.closest('.intake-check').classList.toggle('selected', this.checked);
    });
});
