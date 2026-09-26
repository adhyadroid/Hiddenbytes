/**
 * assets/js/dashboard.js
 * Renders database-driven stats and a compact investigation table
 * (Network/Entity, Type, Connections, Hops, Transactions, Total
 * Amount, Priority Score, Status), with client-side filters over
 * the same data already returned by api/dashboard.php.
 */

document.addEventListener('DOMContentLoaded', function () {
    const statsBox = document.getElementById('dashboard-stats');
    const tableBox = document.getElementById('dashboard-table');
    if (!statsBox) return;

    let allRows = [];

    fetch('../api/dashboard.php')
        .then((r) => r.json())
        .then((data) => {
            const s = data.stats;
            statsBox.innerHTML =
                '<div class="stat-row" style="justify-content:flex-start;">' +
                statBlock('Total Entities', s.total_entities) +
                statBlock('Total Donors', s.total_donors) +
                statBlock('Total Companies', s.total_companies) +
                statBlock('Total Political Parties', s.total_parties) +
                statBlock('Total Transactions', s.total_donations) +
                statBlock('Connected Networks', s.connected_networks) +
                statBlock('High Priority Cases', s.high_priority_cases) +
                '</div>';

            allRows = data.rows;
            renderTable();
        });

    document.getElementById('filter-type')?.addEventListener('change', renderTable);
    document.getElementById('filter-priority')?.addEventListener('change', renderTable);
    document.getElementById('filter-relationship')?.addEventListener('change', renderTable);

    function renderTable() {
        const typeFilter = document.getElementById('filter-type')?.value || '';
        const minPriority = parseInt(document.getElementById('filter-priority')?.value || '0', 10);
        const relFilter = document.getElementById('filter-relationship')?.value || '';

        const rows = allRows.filter((r) => {
            if (typeFilter && r.type !== typeFilter) return false;
            if (r.priority_score < minPriority) return false;
            if (relFilter && r.relationship_types.indexOf(relFilter) === -1) return false;
            return true;
        });

        let html = '<div class="table-wrap"><table class="data-table"><thead><tr>' +
            '<th>Network / Entity</th><th>Entity Type</th><th>Connections</th><th>Hops</th>' +
            '<th>Transactions</th><th>Total Amount</th><th>Priority Score</th><th>Status</th>' +
            '</tr></thead><tbody>';
        rows.forEach((r) => {
            const typeParam = r.type === 'Donor' ? 'donor' : 'company';
            const statusClass = r.status === 'FLAGGED FOR REVIEW' ? 'flagged' : 'normal';
            html += '<tr>' +
                '<td><a href="entity.php?type=' + typeParam + '&id=' + encodeURIComponent(r.entity_id) + '">' + escapeHtml(r.name) + '</a> ' +
                '&middot; <a href="priority.php?type=' + typeParam + '&id=' + encodeURIComponent(r.entity_id) + '" style="font-size:11px;">evidence</a></td>' +
                '<td>' + escapeHtml(r.type) + '</td>' +
                '<td>' + r.connections + '</td>' +
                '<td>' + r.hops + '</td>' +
                '<td>' + r.donation_count + '</td>' +
                '<td>' + formatINR(r.total_amount) + '</td>' +
                '<td>' + r.priority_score + '</td>' +
                '<td><span class="status-badge ' + statusClass + '">' + escapeHtml(r.status) + '</span></td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        if (!rows.length) html = '<p class="muted">No entities match the selected filters.</p>';
        tableBox.innerHTML = html;
    }

    function statBlock(label, value) {
        return '<div class="stat-block"><div class="stat-label">' + escapeHtml(label) + '</div><div class="stat-value">' + value + '</div></div>';
    }
});
