<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$q = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$tab = $_GET['tab'] ?? 'all';

$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT entity_id, name, 'Donor' AS type_label, 'donor' AS type FROM donors WHERE name LIKE ? OR entity_id LIKE ?");
    $stmt->execute([$like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT entity_id, company_name AS name, 'Company' AS type_label, 'company' AS type FROM companies WHERE company_name LIKE ? OR entity_id LIKE ?");
    $stmt->execute([$like, $like]);
    $results = array_merge($results, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT party_id AS entity_id, party_name AS name, 'Political Party' AS type_label, 'party' AS type FROM political_parties WHERE party_name LIKE ? OR party_id LIKE ?");
    $stmt->execute([$like, $like]);
    $results = array_merge($results, $stmt->fetchAll());
} elseif ($category === 'donor') {
    $results = $pdo->query("SELECT entity_id, name, 'Donor' AS type_label, 'donor' AS type FROM donors ORDER BY name")->fetchAll();
} elseif ($category === 'party') {
    $results = $pdo->query("SELECT party_id AS entity_id, party_name AS name, 'Political Party' AS type_label, 'party' AS type FROM political_parties ORDER BY party_name")->fetchAll();
} elseif ($category === 'politician') {
    $results = $pdo->query("SELECT entity_id, name, 'Politician' AS type_label, 'politician' AS type FROM politicians ORDER BY name")->fetchAll();
}

if ($tab === 'people') $results = array_filter($results, fn($r) => $r['type'] === 'donor');
if ($tab === 'companies') $results = array_filter($results, fn($r) => $r['type'] === 'company');
if ($tab === 'parties') $results = array_filter($results, fn($r) => $r['type'] === 'party');

render_header('Search');
?>
<h2>Search</h2>
<form action="search.php" method="get" style="display:flex;gap:8px;max-width:520px;margin:14px 0 18px;">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search donor, company or political party..." style="flex:1;padding:10px 12px;border:1px solid var(--border-subtle);border-radius:var(--radius);background:var(--dark-navy);color:var(--text-primary);">
    <button class="btn btn-primary" type="submit">Search</button>
</form>

<div style="display:flex;gap:8px;margin-bottom:16px;">
    <?php $qs = $q !== '' ? 'q=' . urlencode($q) : 'category=' . urlencode($category); ?>
    <a class="btn btn-secondary" href="search.php?<?= $qs ?>&tab=all">All</a>
    <a class="btn btn-secondary" href="search.php?<?= $qs ?>&tab=people">People</a>
    <a class="btn btn-secondary" href="search.php?<?= $qs ?>&tab=companies">Companies</a>
    <a class="btn btn-secondary" href="search.php?<?= $qs ?>&tab=parties">Parties</a>
</div>

<p class="muted"><?= count($results) ?> result<?= count($results) === 1 ? '' : 's' ?></p>

<?php foreach ($results as $r): ?>
    <a class="result-item" href="entity.php?type=<?= h($r['type']) ?>&id=<?= h($r['entity_id']) ?>">
        <div>
            <div class="result-name"><?= h($r['name']) ?></div>
            <div class="result-meta"><?= h($r['type_label']) ?> . <?= h($r['entity_id']) ?></div>
        </div>
        <span class="badge">View Entity</span>
    </a>
<?php endforeach; ?>
<?php if (empty($results)): ?>
    <p class="muted">No results. Try a different search term.</p>
<?php endif; ?>

<?php render_footer(); ?>
