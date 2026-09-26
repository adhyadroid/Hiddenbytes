<?php
/** api/relationships.php?type=donor|company&id=IND-001 -> direct relationships for one entity */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/relationship_logic.php';

$type = $_GET['type'] ?? '';
$entityId = $_GET['id'] ?? '';
$entity = find_entity_by_entity_id($pdo, $entityId);

if (!$entity || $entity['type'] !== $type || !in_array($type, ['donor','company'])) {
    json_response(['error' => 'Entity not found or not a donor/company']);
}

json_response(['entity' => $entity, 'relationships' => get_direct_relationships($pdo, $type, $entity['id'])]);
