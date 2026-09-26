/**
 * assets/js/priority.js
 * Renders the rule-based priority score panel and the supporting
 * evidence sections, all sourced from api/priority.php.
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('priority-content');
    if (!container) return;
    const type = container.dataset.type;
    const id = container.dataset.id;

    fetch('../api/priority.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id))
        .then((r) => r.json())
        .then((data) => {
            if (data.error) { container.innerHTML = '<p class="muted">' + escapeHtml(data.error) + '</p>'; return; }
            render(data);
        });

    function render(data) {
        const e = data.entity;
        let html = '<h3>' + escapeHtml(e.name) + '</h3>';
        html += '<p class="muted">Entity Type: ' + escapeHtml(capitalize(e.type)) + '. Entity ID: ' + escapeHtml(e.entity_id) + '</p>';

        // Score panel
        html += '<div class="panel priority-panel">';
        html += '<div class="muted">PRIORITY SCORE</div>';
        html += '<div class="priority-score-value">' + data.score + ' / 100</div>';
        if (data.score >= 80) {
            html += '<div class="priority-flag"><div class="flag-title">&#9873; FLAGGED FOR REVIEW</div><div>HIGH PRIORITY</div></div>';
        } else {
            html += '<div class="priority-normal">NORMAL &mdash; BELOW HIGH-PRIORITY THRESHOLD</div>';
        }
        html += '</div>';

        // Breakdown
        html += '<div class="panel" style="margin-top:12px;"><h3 style="font-size:14px;">Score Breakdown</h3>';
        html += signalRow('Relationship Signal', data.breakdown.relationship_signal.points, data.breakdown.relationship_signal.max,
            data.breakdown.relationship_signal.count + ' direct non-donation relationship(s)');
        html += signalRow('Multi-Hop Signal', data.breakdown.multi_hop_signal.points, data.breakdown.multi_hop_signal.max,
            'Deepest connection: ' + data.breakdown.multi_hop_signal.max_hop + ' hop(s)');
        html += signalRow('Network Complexity', data.breakdown.network_complexity.points, data.breakdown.network_complexity.max,
            data.breakdown.network_complexity.connected_count + ' connected entities');
        html += signalRow('Temporal Concentration', data.breakdown.temporal_signal.points, data.breakdown.temporal_signal.max,
            data.breakdown.temporal_signal.min_gap_days !== null ? ('Closest two contributions: ' + data.breakdown.temporal_signal.min_gap_days + ' day(s) apart') : 'Not enough contributions to compare');
        html += signalRow('Contribution Signal', data.breakdown.contribution_signal.points, data.breakdown.contribution_signal.max,
            data.breakdown.contribution_signal.donation_count + ' connected contribution(s), ' + formatINR(data.breakdown.contribution_signal.total_amount) + ' total');
        html += '</div>';

        // Evidence
        html += '<h3 style="margin-top:22px;">Why This Network Received This Score</h3>';

        html += '<div class="panel"><h3 style="font-size:13px;">Relationship Signals</h3>';
        if (data.relationships.length) {
            html += '<div class="table-wrap"><table class="data-table"><thead><tr><th>Entity</th><th>Relationship</th><th>Supporting Value</th></tr></thead><tbody>';
            data.relationships.forEach((r) => {
                html += '<tr><td>' + escapeHtml(r.name) + '</td><td>' + escapeHtml(r.relationship_type) + '</td><td>' + escapeHtml(r.supporting_value) + '</td></tr>';
            });
            html += '</tbody></table></div>';
        } else {
            html += '<p class="muted">No direct relationship signals for this entity.</p>';
        }
        html += '</div>';

        html += '<div class="panel" style="margin-top:12px;"><h3 style="font-size:13px;">Multi-Hop Path</h3>';
        if (data.path.length > 1) {
            html += data.path.map((n, i) => {
                const arrow = i > 0 ? '<div class="muted" style="text-align:center;">&#8595; ' + escapeHtml(data.path[i].via || '') + '</div>' : '';
                return arrow + '<p style="margin:2px 0;">' + escapeHtml(n.name) + ' <span class="muted">(' + escapeHtml(n.type) + ')</span></p>';
            }).join('');
        } else {
            html += '<p class="muted">No multi-hop path beyond direct connections.</p>';
        }
        html += '</div>';

        html += '<div class="panel" style="margin-top:12px;"><h3 style="font-size:13px;">Temporal Signal</h3>';
        if (data.breakdown.temporal_signal.closest_pair) {
            html += '<p style="margin:2px 0;">Related contributions ' + escapeHtml(data.breakdown.temporal_signal.closest_pair[0]) +
                    ' and ' + escapeHtml(data.breakdown.temporal_signal.closest_pair[1]) + ' occurred ' +
                    data.breakdown.temporal_signal.min_gap_days + ' day(s) apart.</p>';
        } else {
            html += '<p class="muted">Not enough connected contributions to establish a temporal pattern.</p>';
        }
        html += '</div>';

        html += '<div class="panel" style="margin-top:12px;"><h3 style="font-size:13px;">Contribution Signal</h3>';
        html += '<p style="margin:2px 0;">Number of connected contributions: ' + data.breakdown.contribution_signal.donation_count + '</p>';
        html += '<p style="margin:2px 0;">Total connected contribution: ' + formatINR(data.breakdown.contribution_signal.total_amount) + '</p>';
        html += '</div>';

        html += '<div class="panel" style="margin-top:12px;"><h3 style="font-size:13px;">Audit Signal</h3>';
        if (data.own_blocks.length) {
            html += data.own_blocks.map((b) =>
                '<p style="margin:2px 0;">Transaction: ' + escapeHtml(b.transaction_id) + ' &mdash; Block: ' + String(b.block_number).padStart(3, '0') + '</p>'
            ).join('');
        } else {
            html += '<p class="muted">No transactions recorded directly for this entity.</p>';
        }
        html += '<p style="margin:6px 0 0;">Ledger: ' + (data.ledger_status.valid ?
            '<span class="ledger-status valid" style="display:inline;">&#10003; Verified</span>' :
            '<span class="ledger-status invalid" style="display:inline;">&#10007; Integrity Mismatch</span>') + '</p>';
        html += '</div>';

        html += '<div style="margin-top:18px;display:flex;gap:10px;">';
        html += '<a class="btn btn-secondary" href="audit.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id) + '">Back to Audit Ledger</a>';
        html += '<a class="btn btn-primary" href="dashboard.php">Investigation Dashboard</a>';
        html += '</div>';

        container.innerHTML = html;
    }

    function signalRow(label, points, max, detail) {
        const pct = Math.round((points / max) * 100);
        return '<div class="signal-row-wrap" style="margin-bottom:10px;">' +
            '<div class="signal-row"><span>' + escapeHtml(label) + '</span><span>' + points + ' / ' + max + '</span></div>' +
            '<div class="signal-bar"><div class="signal-bar-fill" style="width:' + pct + '%;"></div></div>' +
            '<div class="muted" style="font-size:11px;margin-top:2px;">' + escapeHtml(detail) + '</div>' +
            '</div>';
    }
    function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
});
