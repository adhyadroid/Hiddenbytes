<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
render_header('Audit Ledger');
?>
<h2>Blockchain-Inspired Tamper-Evident Audit Ledger</h2>
<p class="muted">A hash-linked ledger of donation transactions - not a decentralized blockchain. Each block's hash is computed with SHA-256 over its own data plus the previous block's hash, so any later change to a block's stored data or to the chain linkage can be detected below. This ledger tracks recorded synthetic transaction data only; it is not connected to any real bank or payment system.</p>

<div id="audit-content" data-type="<?= h($type) ?>" data-id="<?= h($id) ?>">
    <p class="muted">Loading...</p>
</div>

<?php render_footer(); ?>
<script src="../assets/js/app.js"></script>
<script src="../assets/js/audit.js"></script>
