/**
 * assets/js/entity.js
 * Fetches api/entity.php and renders ONE reusable layout that adapts
 * its fields depending on entity type (donor, company, party, politician).
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('entity-content');
    if (!container) return;
    const type = container.dataset.type;
    const id = container.dataset.id;

    fetch('../api/entity.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id))
        .then((r) => r.json())
        .then((data) => {
            if (data.error) { container.innerHTML = '<p class="muted">' + escapeHtml(data.error) + '</p>'; return; }
            render(data);
        });

    function render(data) {
        const e = data.entity;
        const d = data.detail || {};
        let html = '<h2>' + escapeHtml(e.name) + '</h2>';
        html += '<p class="muted">Entity Type: ' + escapeHtml(capitalize(e.type)) + '. Entity ID: ' + escapeHtml(e.entity_id) + '</p>';

        html += '<div class="panel">';
        if (e.type === 'donor') {
            if (d.address_text) html += fieldRow('Address', d.address_text);
            if (d.phone_number) html += fieldRow('Phone', d.phone_number);
        } else if (e.type === 'company') {
            if (d.address_text) html += fieldRow('Address', d.address_text);
            if (d.phone_number) html += fieldRow('Phone', d.phone_number);
            if (d.director_name) html += fieldRow('Associated Director', d.director_name);
        } else if (e.type === 'politician') {
            if (d.designation) html += fieldRow('Designation', d.designation);
            if (d.party_name) html += fieldRow('Party', d.party_name);
        }
        html += '</div>';

        if (e.type !== 'politician') {
            const s = data.stats;
            html += '<div class="stat-row" style="justify-content:flex-start;margin-top:16px;">';
            html += statBlock(e.type === 'party' ? 'Donations Received' : 'Donation Count', s.cnt);
            html += statBlock(e.type === 'party' ? 'Total Received' : 'Total Contribution', formatINR(s.total));
            html += statBlock('Date Range', s.first_date ? (s.first_date + ' to ' + s.last_date) : 'None');
            if (e.type !== 'party') html += statBlock('Connected Entities', (data.relationships || []).filter(r => r.relationship_type !== 'Donation').length);
            html += '</div>';
        }

        if (e.type === 'donor' || e.type === 'company') {
            html += '<div style="margin-top:14px;"><a class="btn btn-primary" href="network.php?type=' + encodeURIComponent(e.type) + '&id=' + encodeURIComponent(e.entity_id) + '">Analyze Network</a></div>';
        }

        if (data.donations && data.donations.length) {
            html += '<h3 style="margin-top:20px;">' + (e.type === 'party' ? 'Donations received' : 'Donation records') + '</h3>';
            html += '<div class="table-wrap"><table class="data-table"><thead><tr><th>Transaction ID</th><th>Date</th><th>Amount</th><th>' +
                    (e.type === 'party' ? 'Donor' : 'Recipient') + '</th></tr></thead><tbody>';
            data.donations.forEach((r) => {
                html += '<tr><td>' + escapeHtml(r.transaction_id) + '</td><td>' + escapeHtml(r.donation_date) + '</td><td>' +
                        formatINR(r.amount) + '</td><td>' + escapeHtml(r.party_name || r.donor_name || '') + '</td></tr>';
            });
            html += '</tbody></table></div>';
        }

        if (data.relationships && data.relationships.length) {
            const nonDonation = data.relationships.filter((r) => r.relationship_type !== 'Donation');
            if (nonDonation.length) {
                html += '<h3 style="margin-top:20px;">Relationship Summary</h3>';
                html += '<div class="table-wrap"><table class="data-table"><thead><tr><th>Entity</th><th>Relationship</th><th>Supporting Value</th></tr></thead><tbody>';
                nonDonation.forEach((r) => {
                    html += '<tr><td>' + escapeHtml(r.name) + '</td><td>' + escapeHtml(r.relationship_type) + '</td><td>' + escapeHtml(r.supporting_value) + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
        }

        container.innerHTML = html;
    }

    function fieldRow(label, value) {
        return '<p style="margin:4px 0;font-size:13px;"><span class="muted">' + escapeHtml(label) + ':</span> ' + escapeHtml(value) + '</p>';
    }
    function statBlock(label, value) {
        return '<div class="stat-block"><div class="stat-label">' + escapeHtml(label) + '</div><div class="stat-value" style="font-size:16px;">' + escapeHtml(String(value)) + '</div></div>';
    }
    function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
});
