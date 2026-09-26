<?php
/** api/audit.php?type=donor|company&id=IND-001 (optional) -> ledger blocks, filtered to that entity's connected network if given, else the full ledger */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/audit_logic.php';
require_once __DIR__ . '/../config/network_logic.php';

$type = $_GET['type'] ?? '';
$entityId = $_GET['id'] ?? '';

$entity = null;
$blocks = [];

try {
    if ($type !== '' && $entityId !== '') {
        $entity = find_entity_by_entity_id($pdo, $entityId);
        if (!$entity || $entity['type'] !== $type || !in_array($type, ['donor', 'company'])) {
            json_response(['error' => 'Entity not found or not a donor/company']);
        }
        $cluster = build_cluster($pdo, $type, $entity['id'], 4);
        $blocks = get_blocks_for_cluster($pdo, $cluster['nodes']);
    } else {
        $blocks = get_all_blocks($pdo);
    }

    $totalBlocks = (int)$pdo->query("SELECT COUNT(*) FROM audit_ledger")->fetchColumn();

    json_response([
        'entity' => $entity,
        'blocks' => $blocks,
        'total_blocks' => $totalBlocks,
        'filtered' => $entity !== null,
    ]);
} catch (Throwable $e) {
    // Surface any unexpected DB/build error as JSON instead of a raw PHP
    // fatal-error page, which is what previously left the frontend fetch
    // with a non-JSON response and stuck on "Loading..." with no visible
    // error at all.
    json_response(['error' => 'Could not load the audit ledger: ' . $e->getMessage()]);
}
