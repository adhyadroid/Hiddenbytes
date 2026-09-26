/**
 * assets/js/app.js
 * Small shared helpers used by the other page scripts.
 */
function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}
function formatINR(n) {
    n = Math.round(Number(n));
    return 'Rs. ' + n.toLocaleString('en-IN');
}
function apiBase() {
    return window.location.pathname.includes('/pages/') ? '../api/' : 'api/';
}
