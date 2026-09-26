<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
render_header('Priority Score & Evidence');
?>
<h2>Priority Score &amp; Evidence</h2>
<p class="muted">A transparent, rule-based score from 0-100 built from signals already produced by the relationship and network analysis above. This is not machine learning and is not a finding of fraud, guilt, or wrongdoing - it is a priority-for-review indicator only.</p>

<div id="priority-content" data-type="<?= h($type) ?>" data-id="<?= h($id) ?>">
    <p class="muted">Loading...</p>
</div>

<?php render_footer(); ?>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/priority.js"></script>
