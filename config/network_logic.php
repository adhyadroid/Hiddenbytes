<?php
/**
 * config/network_logic.php
 * Breadth-first traversal outward from one entity, following the
 * relationships in config/relationship_logic.php, up to 4 hops.
 * Uses a visited-node set so a cycle can never cause an infinite loop.
 */

require_once __DIR__ . '/relationship_logic.php';

function build_cluster(PDO $pdo, $startType, $startId, $maxHops = 4) {
    $key = fn($t, $i) => $t . ':' . $i;
    $visited = [$key($startType, $startId) => true];
    $nodes = [$key($startType, $startId) => ['type' => $startType, 'id' => (int)$startId, 'hop' => 0]];
    $edges = [];
    $edgeSeen = [];
    $queue = [[$startType, $startId, 0]];

    while (!empty($queue)) {
        [$curType, $curId, $depth] = array_shift($queue);
        if ($depth >= $maxHops) continue;
        if ($curType === 'party') continue; // parties are endpoints, not traversed further

        $rels = get_direct_relationships($pdo, $curType, $curId);
        foreach ($rels as $r) {
            $otherKey = $key($r['type'], $r['id']);
            $edgeKey = implode('|', [min($key($curType,$curId), $otherKey), max($key($curType,$curId), $otherKey), $r['relationship_type']]);
            if (!isset($edgeSeen[$edgeKey])) {
                $edges[] = [
                    'from_type' => $curType, 'from_id' => (int)$curId,
                    'to_type' => $r['type'], 'to_id' => (int)$r['id'],
                    'relationship_type' => $r['relationship_type'],
                    'supporting_value' => $r['supporting_value'],
                ];
                $edgeSeen[$edgeKey] = true;
            }
            if (!isset($visited[$otherKey])) {
                $visited[$otherKey] = true;
                // parent_type/parent_id/via are additive (Version 2): they let the audit/priority
                // pages reconstruct a multi-hop path without changing any existing node field.
                $nodes[$otherKey] = [
                    'type' => $r['type'], 'id' => (int)$r['id'], 'name' => $r['name'], 'hop' => $depth + 1,
                    'parent_type' => $curType, 'parent_id' => (int)$curId, 'via' => $r['relationship_type'],
                ];
                $queue[] = [$r['type'], $r['id'], $depth + 1];
            }
        }
    }

    if (!isset($nodes[$key($startType,$startId)]['name'])) {
        $self = get_entity_by_type_id($pdo, $startType, $startId);
        $nodes[$key($startType,$startId)]['name'] = $self ? $self['name'] : 'Unknown';
    }

    return ['nodes' => array_values($nodes), 'edges' => $edges];
}
