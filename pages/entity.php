<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
render_header('Entity Overview');
?>
<div id="entity-content" data-type="<?= h($type) ?>" data-id="<?= h($id) ?>">
    <p class="muted">Loading...</p>
</div>
<?php render_footer(); ?>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/entity.js"></script>
