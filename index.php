<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';

$stats = [
    'entities' => (int)$pdo->query("SELECT (SELECT COUNT(*) FROM donors)+(SELECT COUNT(*) FROM companies) c")->fetchColumn(),
    'donors' => (int)$pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn(),
    'companies' => (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'parties' => (int)$pdo->query("SELECT COUNT(*) FROM political_parties")->fetchColumn(),
];

render_header('Thread Line');
?>
<div class="hero">
    <h1>Thread Line</h1>
    <p>See the connections hidden in donation data. Search entities, explore relationships, and understand why a network was highlighted.</p>
    <form class="hero-search" action="pages/search.php" method="get">
        <input type="text" name="q" placeholder="Search donor, company or political party...">
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</div>

<div class="stat-row">
    <div class="stat-block"><div class="stat-label">Total Entities</div><div class="stat-value"><?= number_format($stats['entities']) ?></div></div>
    <div class="stat-block"><div class="stat-label">Total Donors</div><div class="stat-value"><?= number_format($stats['donors']) ?></div></div>
    <div class="stat-block"><div class="stat-label">Total Companies</div><div class="stat-value"><?= number_format($stats['companies']) ?></div></div>
    <div class="stat-block"><div class="stat-label">Total Political Parties</div><div class="stat-value"><?= number_format($stats['parties']) ?></div></div>
</div>

<div class="category-row">
    <a class="category-card" href="pages/search.php?category=donor">
        <span class="category-name">Donors</span>
    </a>
    <a class="category-card" href="pages/search.php?category=party">
        <span class="category-name">Political Parties</span>
    </a>
    <a class="category-card" href="pages/search.php?category=politician">
        <span class="category-name">Politicians</span>
    </a>
</div>

<?php render_footer(); ?>
<script src="assets/js/app.js"></script>
<script src="assets/js/search.js"></script>
