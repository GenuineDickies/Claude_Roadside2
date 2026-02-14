/**
 * RoadRunner — Shared UI utilities
 * Loaded before all page scripts via index.php
 */
var RR = window.RR || {};

/** HTML-escape a string for safe DOM insertion */
function escHtml(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

/** Format ISO date string to readable "Feb 12, 2026 3:45 PM" */
RR.formatDate = function(d) {
    if (!d) return '\u2014';
    var dt = new Date(d);
    return dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
        ' ' + dt.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

/** Relative time: "3m ago", "2h ago", "5d ago" */
RR.timeAgo = function(d) {
    if (!d) return '';
    var diff = Math.floor((Date.now() - new Date(d).getTime()) / 1000);
    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
};

/** Format number as dollar amount: formatMoney(12.5) → "$12.50" */
RR.formatMoney = function(amount) {
    return '$' + (parseFloat(amount) || 0).toFixed(2);
};

window.RR = RR;
