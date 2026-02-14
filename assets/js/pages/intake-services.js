// ═════════════════════════════════════════════════════════════════════
// Intake Form — Shorthand parser, auto cost estimation
// ═════════════════════════════════════════════════════════════════════

// ─── Shorthand Parser ───────────────────────────────────────────────
let shorthandTimer = null;
document.getElementById('issueDescription')?.addEventListener('input', function() {
    clearTimeout(shorthandTimer);
    const text = this.value;
    if (text.length < 2) { document.getElementById('shorthandSuggestions').innerHTML = ''; return; }
    shorthandTimer = setTimeout(async () => {
        try {
            const fd = new FormData();
            fd.append('action', 'parse_shorthand');
            fd.append('text', text);
            const res = await fetch(API_TICKETS, { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success && json.data.suggestions.length) {
                const panel = document.getElementById('shorthandSuggestions');
                panel.innerHTML = json.data.suggestions.map(s =>
                    `<span class="intake-shorthand-suggestion" onclick="applyShorthand('${s.category}','${s.service}')"><i class="fas fa-check"></i> ${s.label}</span>`
                ).join('');
                if (json.data.tow_distance) {
                    const td = document.getElementById('towDistance');
                    if (td) td.value = json.data.tow_distance;
                }
            } else {
                document.getElementById('shorthandSuggestions').innerHTML = '';
            }
        } catch(e) {}
    }, 500);
});

function applyShorthand(category, service) {
    const card = document.querySelector(`.intake-cat-card[data-val="${category}"]`);
    if (card) selectCategory(card);
    document.getElementById('sec-service')?.classList.add('open');
}

// ─── Auto Cost Estimation ───────────────────────────────────────────
async function updateEstimate() {
    const serviceSelect = document.getElementById('specificServiceSelect');
    const serviceId = serviceSelect?.value;
    if (!serviceId) return;

    const towMiles = document.getElementById('towDistance')?.value || 0;
    const isAfterHours = new Date().getHours() < 7 || new Date().getHours() >= 19;

    const fd = new FormData();
    fd.append('action', 'estimate_cost');
    fd.append('service_ids', JSON.stringify([parseInt(serviceId)]));
    fd.append('tow_miles', towMiles);
    fd.append('after_hours', isAfterHours ? '1' : '0');

    try {
        const res = await fetch(API_TICKETS, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            document.getElementById('estimatedCost').value = json.data.estimated_cost.toFixed(2);
            updateSummary();
        }
    } catch(e) {}
}
