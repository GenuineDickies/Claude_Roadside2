// ═════════════════════════════════════════════════════════════════════
// Intake Form — Quick Entry ↔ Detail Section Mirroring + Init
// ═════════════════════════════════════════════════════════════════════

(function() {
    const mirrorPairs = [
        ['qeName',        'input[name="customer_name"]'],
        ['qeAddress',     '#serviceAddress'],
        ['qeDescription', '#issueDescription'],
    ];

    mirrorPairs.forEach(([qeId, detailSel]) => {
        const qe = document.getElementById(qeId);
        const detail = document.querySelector(detailSel);
        if (!qe || !detail) return;

        let syncing = false;
        qe.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            detail.value = qe.value;
            detail.classList.add('synced');
            setTimeout(() => detail.classList.remove('synced'), 400);
            detail.dispatchEvent(new Event('input', { bubbles: true }));
            syncing = false;
        });
        detail.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            qe.value = detail.value;
            qe.classList.add('synced');
            setTimeout(() => qe.classList.remove('synced'), 400);
            syncing = false;
        });
    });

    const qePhone = document.getElementById('qePhone');
    const detailPhone = document.getElementById('customerPhone');
    if (qePhone && detailPhone) {
        let syncing = false;
        qePhone.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            const digits = phoneGetRawDigits(qePhone);
            phoneMaskApply(detailPhone, digits);
            detailPhone.classList.add('synced');
            setTimeout(() => detailPhone.classList.remove('synced'), 400);
            detailPhone.dispatchEvent(new Event('input', { bubbles: true }));
            syncing = false;
        });
        detailPhone.addEventListener('input', () => {
            if (syncing) return;
            syncing = true;
            const digits = phoneGetRawDigits(detailPhone);
            phoneMaskApply(qePhone, digits);
            qePhone.classList.add('synced');
            setTimeout(() => qePhone.classList.remove('synced'), 400);
            syncing = false;
        });
    }

    window.qeSelectCategory = function(pill) {
        document.querySelectorAll('.qe-cat-pill').forEach(p => p.classList.remove('selected'));
        pill.classList.add('selected');
        const val = pill.dataset.val;
        const detailCard = document.querySelector(`.intake-cat-card[data-val="${val}"]`);
        if (detailCard) selectCategory(detailCard);
        document.getElementById('sec-service')?.classList.add('open');
    };

    const origSelectCategory = window.selectCategory;
    window.selectCategory = function(card) {
        origSelectCategory(card);
        const val = card.dataset.val;
        document.querySelectorAll('.qe-cat-pill').forEach(p => {
            p.classList.toggle('selected', p.dataset.val === val);
        });
    };

    const origFillCustomer = window.fillCustomer;
    window.fillCustomer = function(cust) {
        origFillCustomer(cust);
        const qeN = document.getElementById('qeName');
        const qeP = document.getElementById('qePhone');
        if (qeN) qeN.value = (cust.first_name + ' ' + cust.last_name).trim();
        if (qeP) {
            const digits = (cust.phone || '').replace(/\D/g, '').slice(0, 10);
            phoneMaskApply(qeP, digits);
        }
    };
})();

// ─── Init ───────────────────────────────────────────────────────────
loadRecentCustomers();
updateSummary();
updateSectionProgress();

// Show highway fields by default for roadside
document.getElementById('highwayFields')?.classList.add('visible');
document.getElementById('directionField')?.classList.add('visible');
