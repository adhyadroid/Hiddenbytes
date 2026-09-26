<?php
/** api/priority.php?type=donor|company&id=IND-001 -> priority score, breakdown, and supporting evidence, all derived from real data */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/priority_logic.php';
require_once __DIR__ . '/../config/audit_logic.php';

$type = $_GET['type'] ?? '';
$entityId = $_GET['id'] ?? '';
$entity = find_entity_by_entity_id($pdo, $entityId);

if (!$entity || $entity['type'] !== $type || !in_array($type, ['donor', 'company'])) {
    json_response(['error' => 'Entity not found or not a donor/company']);
}

$result = calculate_priority_score($pdo, $type, $entity['id']);
$path = get_representative_path($result['cluster'], $type, $entity['id']);

// Audit signal: this entity's own transactions, with their real block numbers, plus the ledger's overall verification status
$ownBlocks = get_blocks_for_entity($pdo, $type, $entity['id']);
$ledgerStatus = verify_ledger($pdo);

json_response([
    'entity' => $entity,
    'score' => $result['score'],
    'status' => $result['status'],
    'breakdown' => $result['breakdown'],
    'relationships' => $result['non_donation_relationships'],
    'path' => $path,
    'connected_count' => $result['connected_count'],
    'max_hop' => $result['max_hop'],
    'donations' => $result['all_donations'],
    'own_blocks' => $ownBlocks,
    'ledger_status' => $ledgerStatus,
]);
