var InvoicesV2 = (function() {
    const API_W = 'api/workflow.php';

    async function updateStatus(id, status) {
        const fd = new FormData();
        fd.append('action', 'update_invoice_status');
        fd.append('id', id);
        fd.append('status', status);
        const res = await fetch(API_W, { method: 'POST', body: fd });
        if ((await res.json()).success) location.reload();
    }

    async function recordPayment(invId) {
        const amount = prompt('Payment amount ($):');
        if (!amount) return;
        const method = prompt('Payment method (card, cash, check, insurance_claim, fleet_po):', 'card');
        if (!method) return;
        const txnId = prompt('Transaction/reference ID (optional):');

        const fd = new FormData();
        fd.append('action', 'record_payment');
        fd.append('invoice_id', invId);
        fd.append('amount', parseFloat(amount));
        fd.append('payment_method', method);
        if (txnId) fd.append('processor_txn_id', txnId);

        const res = await fetch(API_W, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            if (json.data.receipt) {
                if (confirm('Payment recorded! Receipt generated. View receipt?')) {
                    window.location.href = '?page=receipts&action=view&id=' + json.data.receipt.id;
                    return;
                }
            }
            location.reload();
        } else {
            alert('Error: ' + (json.error || 'Failed'));
        }
    }

    async function applyDiscount(invId) {
        const amount = prompt('Discount amount ($):');
        if (!amount) return;
        const reason = prompt('Discount reason:');

        const fd = new FormData();
        fd.append('action', 'apply_discount');
        fd.append('invoice_id', invId);
        fd.append('discount_amount', parseFloat(amount));
        fd.append('discount_reason', reason || '');

        const res = await fetch(API_W, { method: 'POST', body: fd });
        if ((await res.json()).success) location.reload();
    }

    return { updateStatus: updateStatus, recordPayment: recordPayment, applyDiscount: applyDiscount };
})();

// Global aliases for inline onclick handlers
function updateInvStatus(id, status) { InvoicesV2.updateStatus(id, status); }
function recordPayment(invId) { InvoicesV2.recordPayment(invId); }
function applyDiscount(invId) { InvoicesV2.applyDiscount(invId); }
