<?php
/** api/network.php?type=donor|company&id=IND-001&hops=4 -> graph nodes+edges JSON */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/network_logic.php';

$type = $_GET['type'] ?? '';
$entityId = $_GET['id'] ?? '';
$hops = min(4, max(1, (int)($_GET['hops'] ?? 4)));
$entity = find_entity_by_entity_id($pdo, $entityId);

if (!$entity || $entity['type'] !== $type || !in_array($type, ['donor','company'])) {
    json_response(['error' => 'Entity not found or not a donor/company']);
}

$cluster = build_cluster($pdo, $type, $entity['id'], $hops);

// attach public entity_id + resolved names to every node for the frontend
foreach ($cluster['nodes'] as &$n) {
    $full = get_entity_by_type_id($pdo, $n['type'], $n['id']);
    if ($full) { $n['entity_id'] = $full['entity_id']; $n['name'] = $full['name']; }
}
unset($n);

json_response($cluster);
