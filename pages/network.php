<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
render_header('Network Analysis');
?>
<h2>Network Analysis</h2>
<div id="network-summary" data-type="<?= h($type) ?>" data-id="<?= h($id) ?>">
    <p class="muted">Loading...</p>
</div>

<div class="graph-panel" style="margin-top:12px;">
    <div class="graph-canvas-wrap">
        <div class="graph-controls">
            <button type="button" id="zoom-in" title="Zoom in">+</button>
            <button type="button" id="zoom-out" title="Zoom out">-</button>
            <button type="button" id="zoom-reset" title="Reset">Reset</button>
            <button type="button" id="zoom-fit" title="Fit network">Fit</button>
        </div>
        <canvas id="network-canvas" width="700" height="420" style="width:100%;height:420px;"></canvas>
        <div class="graph-legend">
            <span><span class="legend-swatch" style="background:var(--edge-donation);"></span>Donation</span>
            <span><span class="legend-swatch" style="background:var(--edge-address);"></span>Shared Address</span>
            <span><span class="legend-swatch" style="background:var(--edge-phone);"></span>Shared Phone</span>
            <span><span class="legend-swatch" style="background:var(--edge-director);"></span>Common Director</span>
            <span><span class="legend-swatch" style="background:var(--edge-company);"></span>Company Relationship / Associated Entity</span>
        </div>
        <div id="node-detail" class="muted" style="padding:8px 12px;border-top:1px solid var(--border-subtle);min-height:18px;"></div>
    </div>
    <div class="panel" id="side-panel">
        <h3 style="font-size:14px;">Direct Connections</h3>
        <div id="direct-connections"><p class="muted">Loading...</p></div>
        <h3 style="font-size:14px;margin-top:14px;">Multi-Hop Connections</h3>
        <div id="multihop-connections"><p class="muted">Loading...</p></div>
    </div>
</div>

<div class="next-stage-cta" style="margin-top:16px;">
    <p class="muted" style="margin:0 0 10px;">Continue the investigation: trace these transactions through the tamper-evident audit ledger, see the rule-based priority score, and review the supporting evidence.</p>
    <a class="btn btn-primary" href="audit.php?type=<?= h($type) ?>&id=<?= h($id) ?>">View Audit Ledger</a>
</div>

<?php render_footer(); ?>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/network.js"></script>
