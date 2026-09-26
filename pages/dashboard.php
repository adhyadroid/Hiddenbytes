<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
render_header('Investigation Dashboard');
?>
<h2>Investigation Dashboard</h2>
<div id="dashboard-stats"><p class="muted">Loading...</p></div>

<div class="dashboard-filters">
    <select id="filter-type">
        <option value="">Entity Type: All</option>
        <option value="Donor">Donor</option>
        <option value="Company">Company</option>
    </select>
    <select id="filter-priority">
        <option value="0">Priority: All</option>
        <option value="50">Priority &ge; 50</option>
        <option value="75">Priority &ge; 75</option>
        <option value="80">Priority &ge; 80 (High Priority)</option>
        <option value="90">Priority &ge; 90</option>
    </select>
    <select id="filter-relationship">
        <option value="">Relationship Type: All</option>
        <option value="Shared Address">Shared Address</option>
        <option value="Shared Phone">Shared Phone</option>
        <option value="Common Director">Common Director</option>
        <option value="Company Relationship">Company Relationship</option>
        <option value="Associated Entity">Associated Entity</option>
    </select>
</div>

<div id="dashboard-table" style="margin-top:8px;"><p class="muted">Loading...</p></div>
<?php render_footer(); ?>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/dashboard.js"></script>
