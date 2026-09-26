<?php
/** api/dashboard.php -> stats + a compact investigation table, all from MySQL */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/priority_logic.php';

$stats = [
    'total_entities' => (int)$pdo->query("SELECT (SELECT COUNT(*) FROM donors)+(SELECT COUNT(*) FROM companies) c")->fetchColumn(),
    'total_donors' => (int)$pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn(),
    'total_companies' => (int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'total_parties' => (int)$pdo->query("SELECT COUNT(*) FROM political_parties")->fetchColumn(),
    'total_donations' => (int)$pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn(),
];

// Compact investigation table: every donor + company, with live connection count, donation count,
// total amount, multi-hop depth, and rule-based priority score/status (see config/priority_logic.php).
$rows = [];

$donors = $pdo->query("SELECT id, entity_id, name FROM donors")->fetchAll();
foreach ($donors as $d) {
    $rows[] = build_dashboard_row($pdo, 'donor', $d['id'], $d['entity_id'], $d['name'], 'Donor');
}
$companies = $pdo->query("SELECT id, entity_id, company_name AS name FROM companies")->fetchAll();
foreach ($companies as $c) {
    $rows[] = build_dashboard_row($pdo, 'company', $c['id'], $c['entity_id'], $c['name'], 'Company');
}

$connectedNetworks = 0;
$highPriorityCases = 0;
foreach ($rows as $r) {
    if ($r['connections'] > 0) $connectedNetworks++;
    if ($r['status'] === 'FLAGGED FOR REVIEW') $highPriorityCases++;
}
$stats['connected_networks'] = $connectedNetworks;
$stats['high_priority_cases'] = $highPriorityCases;

usort($rows, fn($a, $b) => $b['priority_score'] <=> $a['priority_score']);

json_response(['stats' => $stats, 'rows' => $rows]);

function build_dashboard_row(PDO $pdo, $type, $id, $entityId, $name, $typeLabel) {
    $result = calculate_priority_score($pdo, $type, $id);
    $relTypes = array_values(array_unique(array_map(fn($r) => $r['relationship_type'], $result['non_donation_relationships'])));
    return [
        'entity_id' => $entityId,
        'name' => $name,
        'type' => $typeLabel,
        'connections' => $result['breakdown']['relationship_signal']['count'],
        'hops' => $result['max_hop'],
        'donation_count' => $result['breakdown']['contribution_signal']['donation_count'],
        'total_amount' => $result['breakdown']['contribution_signal']['total_amount'],
        'priority_score' => $result['score'],
        'status' => $result['status'],
        'relationship_types' => $relTypes,
    ];
}
