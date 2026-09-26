/**
 * assets/js/audit.js
 * Renders the audit ledger as a vertical chain of clickable blocks,
 * plus a real "Verify Ledger Integrity" check against api/audit_verify.php.
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('audit-content');
    if (!container) return;
    const type = container.dataset.type;
    const id = container.dataset.id;
    const qs = (type && id) ? ('?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id)) : '';

    fetch('../api/audit.php' + qs)
        .then((r) => r.json())
        .then((data) => {
            if (data.error) { container.innerHTML = '<p class="muted">' + escapeHtml(data.error) + '</p>'; return; }
            render(data);
        })
        .catch((err) => {
            container.innerHTML = '<p class="muted">Could not load the audit ledger. Please refresh the page. (' + escapeHtml(err.message) + ')</p>';
        });

    function render(data) {
        let html = '';

        if (data.entity) {
            html += '<p class="muted">Showing the ' + data.blocks.length + ' ledger block(s) belonging to <strong>' +
                escapeHtml(data.entity.name) + '</strong> and its connected network, out of ' + data.total_blocks +
                ' block(s) in the full ledger. <a href="audit.php">View full ledger</a> - ' +
                '<a href="priority.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id) + '">View Priority Score &amp; Evidence</a></p>';
        } else {
            html += '<p class="muted">Showing all ' + data.blocks.length + ' block(s) in the ledger.</p>';
        }

        html += '<div class="ledger-toolbar">';
        html += '<button type="button" id="verify-btn" class="btn btn-primary">Verify Ledger Integrity</button>';
        html += '<span id="verify-result"></span>';
        html += '</div>';

        html += '<div class="block-chain" id="block-chain"></div>';

        container.innerHTML = html;

        document.getElementById('verify-btn').addEventListener('click', function () {
            const resultBox = document.getElementById('verify-result');
            resultBox.innerHTML = '<span class="muted">Checking...</span>';
            fetch('../api/audit_verify.php')
                .then((r) => r.json())
                .then((v) => {
                    if (v.valid) {
                        resultBox.innerHTML = '<span class="ledger-status valid">&#10003; LEDGER INTEGRITY VERIFIED (' + v.blocks_checked + ' blocks checked)</span>';
                    } else {
                        resultBox.innerHTML = '<span class="ledger-status invalid">&#10007; INTEGRITY MISMATCH</span><ul class="muted" style="margin:8px 0 0;padding-left:18px;">' +
                            v.issues.map((i) => '<li>' + escapeHtml(i) + '</li>').join('') + '</ul>';
                    }
                })
                .catch((err) => {
                    resultBox.innerHTML = '<span class="ledger-status invalid">Could not verify the ledger (' + escapeHtml(err.message) + ')</span>';
                });
        });

        renderChain(data.blocks);
    }

    function renderChain(blocks) {
        const chain = document.getElementById('block-chain');
        let html = '';
        let prevShownBlockNumber = null;

        blocks.forEach((b, i) => {
            if (prevShownBlockNumber !== null && b.block_number !== prevShownBlockNumber + 1) {
                html += '<div class="block-connector">&#8595;</div>';
                html += '<div class="block-gap-note">(' + (b.block_number - prevShownBlockNumber - 1) + ' block(s) not shown here - part of the full ledger)</div>';
            } else if (i > 0) {
                html += '<div class="block-connector">&#8595;</div>';
            }

            html += '<div class="audit-block" data-index="' + i + '">' +
                '<div class="block-title">BLOCK ' + String(b.block_number).padStart(3, '0') + ' &mdash; ' + escapeHtml(b.transaction_id) + '</div>' +
                '<div class="block-row"><span>' + escapeHtml(b.donor_label) + ' &rarr; ' + escapeHtml(b.recipient_label) + '</span><span>' + formatINR(b.amount) + '</span></div>' +
                '<div class="block-row"><span>Previous Hash</span><span class="hash-value">' + shortHash(b.previous_hash) + '</span></div>' +
                '<div class="block-row"><span>Current Hash</span><span class="hash-value">' + shortHash(b.current_hash) + '</span></div>' +
                '</div>';
            html += '<div class="block-detail" id="detail-' + i + '" style="display:none;"></div>';

            prevShownBlockNumber = b.block_number;
        });

        chain.innerHTML = html;

        blocks.forEach((b, i) => {
            const el = chain.querySelector('.audit-block[data-index="' + i + '"]');
            el.addEventListener('click', function () {
                const detail = document.getElementById('detail-' + i);
                const isOpen = detail.style.display !== 'none';
                chain.querySelectorAll('.block-detail').forEach((d) => { d.style.display = 'none'; });
                chain.querySelectorAll('.audit-block').forEach((bl) => { bl.classList.remove('open'); });
                if (!isOpen) {
                    detail.innerHTML = blockDetailHtml(b);
                    detail.style.display = 'block';
                    el.classList.add('open');
                }
            });
        });
    }

    function blockDetailHtml(b) {
        return '<div class="panel">' +
            fieldRow('Block Number', String(b.block_number).padStart(3, '0')) +
            fieldRow('Transaction ID', b.transaction_id) +
            fieldRow('From', b.donor_label) +
            fieldRow('To', b.recipient_label) +
            fieldRow('Amount', formatINR(b.amount)) +
            fieldRow('Transaction Date', b.donation_date) +
            fieldRow('Transaction Type', b.description) +
            fieldRow('Previous Hash', b.previous_hash) +
            fieldRow('Current Hash', b.current_hash) +
            fieldRow('Block Timestamp', b.block_timestamp) +
            '</div>';
    }
    function fieldRow(label, value) {
        return '<p style="margin:4px 0;font-size:12px;word-break:break-all;"><span class="muted">' + escapeHtml(label) + ':</span> ' + escapeHtml(String(value)) + '</p>';
    }
    function shortHash(h) { return h ? (h.substring(0, 10) + '...') : ''; }
});
