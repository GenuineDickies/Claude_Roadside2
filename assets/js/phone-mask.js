/**
 * RoadRunner — Phone Mask: (___) ___-____
 * Add class="phone-masked" to any <input> to enable.
 * On form submit, the mask is stripped and raw digits are sent.
 */
var PHONE_MASK = '(   )    -    ';
var PHONE_SLOTS = [1, 2, 3, 6, 7, 8, 10, 11, 12, 13];

function phoneMaskApply(input, digits) {
    var chars = PHONE_MASK.split('');
    for (var i = 0; i < PHONE_SLOTS.length && i < digits.length; i++) {
        chars[PHONE_SLOTS[i]] = digits[i];
    }
    input.value = chars.join('');
    var cursorPos;
    if (digits.length >= PHONE_SLOTS.length) {
        cursorPos = 14;
    } else {
        cursorPos = PHONE_SLOTS[digits.length];
    }
    try { input.setSelectionRange(cursorPos, cursorPos); } catch (e) {}
}

function phoneGetRawDigits(input) {
    if (!input || !input.value) return '';
    return input.value.replace(/\D/g, '');
}

function phoneInitMask(input) {
    var existingDigits = (input.value || '').replace(/\D/g, '');
    if (existingDigits.length > 0) {
        phoneMaskApply(input, existingDigits.slice(0, 10));
    } else {
        input.value = PHONE_MASK;
    }

    input.addEventListener('keydown', function(e) {
        var digits = phoneGetRawDigits(this);
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
        var pasted = (e.clipboardData || window.clipboardData).getData('text');
        var pastedDigits = pasted.replace(/\D/g, '').slice(0, 10);
        if (pastedDigits.length > 0) {
            phoneMaskApply(this, pastedDigits);
            this.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    input.addEventListener('focus', function() {
        var digits = phoneGetRawDigits(this);
        requestAnimationFrame(function() { phoneMaskApply(input, digits); });
    });

    input.addEventListener('click', function(e) {
        e.preventDefault();
        var digits = phoneGetRawDigits(this);
        phoneMaskApply(this, digits);
    });
}

// Auto-init all phone-masked inputs on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.phone-masked').forEach(phoneInitMask);
});

// Strip mask before form submit — send raw digits
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            form.querySelectorAll('.phone-masked').forEach(function(input) {
                input.value = phoneGetRawDigits(input);
            });
        });
    });
});
