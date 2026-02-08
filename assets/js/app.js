// App JavaScript functionality

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Confirm delete actions
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            var requiredFields = form.querySelectorAll('[required]');
            var isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    });
    
    // Real-time search functionality
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.searchable-row');
            
            tableRows.forEach(function(row) {
                const rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});

// Utility functions

// ═══════════════════════════════════════════════════════════════════
// Global Phone Mask: (___) ___-____
// Add class="phone-masked" to any input to enable
// ═══════════════════════════════════════════════════════════════════
const PHONE_MASK = '(   )    -    ';
const PHONE_SLOTS = [1, 2, 3, 6, 7, 8, 10, 11, 12, 13];

function phoneMaskApply(input, digits) {
    let chars = PHONE_MASK.split('');
    for (let i = 0; i < PHONE_SLOTS.length && i < digits.length; i++) {
        chars[PHONE_SLOTS[i]] = digits[i];
    }
    input.value = chars.join('');
    let cursorPos;
    if (digits.length >= PHONE_SLOTS.length) {
        cursorPos = 14;
    } else {
        cursorPos = PHONE_SLOTS[digits.length];
    }
    try { input.setSelectionRange(cursorPos, cursorPos); } catch(e) {}
}

function phoneGetRawDigits(input) {
    if (!input || !input.value) return '';
    return input.value.replace(/\D/g, '');
}

function phoneInitMask(input) {
    // If field has existing digits (from DB), format them into the mask
    const existingDigits = (input.value || '').replace(/\D/g, '');
    if (existingDigits.length > 0) {
        phoneMaskApply(input, existingDigits.slice(0, 10));
    } else {
        input.value = PHONE_MASK;
    }

    input.addEventListener('keydown', function(e) {
        const digits = phoneGetRawDigits(this);
        if (['Tab', 'Enter', 'Escape'].includes(e.key)) return;
        if (e.key === 'Backspace' || e.key === 'Delete') {
            e.preventDefault();
            if (digits.length > 0) {
                phoneMaskApply(this, digits.slice(0, -1));
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }
            return;
        }
        if (e.key >= '0' && e.key <= '9') {
            e.preventDefault();
            if (digits.length < 10) {
                phoneMaskApply(this, digits + e.key);
                this.dispatchEvent(new Event('input', { bubbles: true }));
            }
            return;
        }
        e.preventDefault();
    });

    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text');
        const pastedDigits = pasted.replace(/\D/g, '').slice(0, 10);
        if (pastedDigits.length > 0) {
            phoneMaskApply(this, pastedDigits);
            this.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    input.addEventListener('focus', function() {
        const digits = phoneGetRawDigits(this);
        requestAnimationFrame(() => phoneMaskApply(this, digits));
    });

    input.addEventListener('click', function(e) {
        e.preventDefault();
        const digits = phoneGetRawDigits(this);
        phoneMaskApply(this, digits);
    });
}

// Auto-init all phone-masked inputs on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.phone-masked').forEach(phoneInitMask);
});

// Strip mask before form submit — send raw digits for phone-masked fields
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            form.querySelectorAll('.phone-masked').forEach(function(input) {
                const digits = phoneGetRawDigits(input);
                input.value = digits;
            });
        });
    });
});

function updateServiceRequestStatus(id, status) {
    if (confirm('Are you sure you want to update this service request status?')) {
        // AJAX call to update status
        fetch('api/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                status: status,
                type: 'service_request'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the status.');
        });
    }
}

function assignTechnician(requestId, technicianId) {
    if (confirm('Are you sure you want to assign this technician?')) {
        fetch('api/assign_technician.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                request_id: requestId,
                technician_id: technicianId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error assigning technician: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while assigning the technician.');
        });
    }
}
