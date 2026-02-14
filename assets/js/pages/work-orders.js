const WO_API = 'api/workflow.php';

document.addEventListener('DOMContentLoaded', function() {
    const clock = document.getElementById('elapsedClock');
    if (clock && clock.dataset.start) initWoTimer(clock.dataset.start);
});

function initWoTimer(startTime) {
    const startMs = new Date(startTime).getTime();
    const el = document.getElementById('elapsedClock');
    if (!el) return;
    function tick() {
        const diff = Math.floor((Date.now() - startMs) / 1000);
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        el.textContent = [h,m,s].map(v => String(v).padStart(2,'0')).join(':');
    }
    tick(); setInterval(tick, 1000);
}

async function updateWoStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'update_work_order_status');
    fd.append('id', id);
    fd.append('status', status);
    const res = await fetch(WO_API, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) location.reload();
    else alert('Error: ' + (json.error || 'Failed'));
}

async function completeWork(id) {
    if (!confirm('Mark work as complete? This will auto-generate an invoice.')) return;
    await updateWoStatus(id, 'completed');
}

async function customerSignoff(id) {
    if (!confirm('Record customer signoff for this work order?')) return;
    const fd = new FormData();
    fd.append('action', 'wo_customer_signoff');
    fd.append('work_order_id', id);
    const res = await fetch(WO_API, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

async function addProgressNote(woId, techId) {
    const inp = document.getElementById('logNoteInput');
    const content = inp.value.trim();
    if (!content) return;
    const fd = new FormData();
    fd.append('action', 'wo_progress_log');
    fd.append('work_order_id', woId);
    fd.append('entry_type', 'note');
    fd.append('content', content);
    fd.append('technician_id', techId);
    const res = await fetch(WO_API, { method: 'POST', body: fd });
    if ((await res.json()).success) location.reload();
}

function toggleDiagnosisEdit() {
    const view = document.getElementById('diagView');
    const edit = document.getElementById('diagEdit');
    const btn = document.getElementById('diagEditBtn');
    if (!view || !edit) return;
    const isEditing = edit.style.display !== 'none';
    view.style.display = isEditing ? '' : 'none';
    edit.style.display = isEditing ? 'none' : '';
    if (btn) btn.style.display = isEditing ? '' : 'none';
}

async function saveDiagnosis(woId) {
    const summary = document.getElementById('diagSummaryInput').value.trim();
    const codesRaw = document.getElementById('diagCodesInput').value.trim();
    const codes = codesRaw ? codesRaw.split(',').map(c => c.trim()).filter(Boolean) : [];
    const fd = new FormData();
    fd.append('action', 'save_wo_diagnosis');
    fd.append('work_order_id', woId);
    fd.append('diagnosis_summary', summary);
    fd.append('diagnostic_codes', JSON.stringify(codes));
    const res = await fetch(WO_API, { method: 'POST', body: fd });
    const json = await res.json();
    if (json.success) location.reload();
    else alert('Error: ' + (json.error || 'Failed to save diagnosis'));
}
