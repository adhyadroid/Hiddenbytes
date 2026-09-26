<?php
/**
 * config/priority_logic.php
 * Transparent, RULE-BASED priority score (0-100) for a donor or
 * company. NOT machine learning, NOT a prediction of guilt - it is a
 * fixed formula over five signals already produced by
 * relationship_logic.php / network_logic.php / the donations table.
 * Every point awarded is explained in the returned breakdown so the
 * Evidence page can show exactly why a score was given.
 *
 * Score >= 80  -> "FLAGGED FOR REVIEW" (high priority for investigation)
 * Score <  80  -> "NORMAL"
 * This is a priority-for-review flag, never a finding of wrongdoing.
 */

require_once __DIR__ . '/relationship_logic.php';
require_once __DIR__ . '/network_logic.php';

/**
 * Scoring formula (five signals, each capped, summing to at most 100):
 *   1. Relationship signal   (max 30) - 8 points per direct non-donation
 *      relationship (Shared Address / Shared Phone / Common Director /
 *      Company Relationship / Associated Entity), capped at 30.
 *   2. Multi-hop signal      (max 25) - deepest hop reached in the up-to-
 *      4-hop cluster: 3+ hops = 25, 2 hops = 15, 1 hop = 5, 0 hops = 0.
 *   3. Network complexity    (max 15) - 3 points per distinct connected
 *      entity in the cluster (excluding the entity itself), capped at 15.
 *   4. Temporal concentration(max 15) - smallest gap, in days, between any
 *      two donations made by the entity or a connected entity: <=3 days
 *      = 15, <=7 days = 8, otherwise 0 (fewer than two donations = 0).
 *   5. Contribution signal   (max 15) - total amount donated by the
 *      entity and every connected entity: >=Rs.5,00,000 = 15,
 *      >=Rs.2,00,000 = 10, >=Rs.50,000 = 5, otherwise 0.
 */
function calculate_priority_score(PDO $pdo, $type, $id) {
    $cluster = build_cluster($pdo, $type, $id, 4);
    $connectedNodes = array_values(array_filter($cluster['nodes'], fn($n) => !($n['type'] === $type && $n['id'] === (int)$id)));

    // 1. Relationship signal
    $directRels = get_direct_relationships($pdo, $type, $id);
    $nonDonationRels = array_values(array_filter($directRels, fn($r) => $r['relationship_type'] !== 'Donation'));
    $relPoints = min(30, count($nonDonationRels) * 8);

    // 2. Multi-hop signal
    $maxHop = 0;
    foreach ($connectedNodes as $n) { $maxHop = max($maxHop, (int)$n['hop']); }
    if ($maxHop >= 3) $hopPoints = 25;
    elseif ($maxHop === 2) $hopPoints = 15;
    elseif ($maxHop === 1) $hopPoints = 5;
    else $hopPoints = 0;

    // 3. Network complexity
    $connectedCount = count($connectedNodes);
    $complexityPoints = min(15, $connectedCount * 3);

    // Gather every donation made by self + every connected donor/company node (for signals 4 and 5)
    $entityPairs = [[$type, (int)$id]];
    foreach ($connectedNodes as $n) {
        if (in_array($n['type'], ['donor', 'company'], true)) $entityPairs[] = [$n['type'], (int)$n['id']];
    }
    $allDonations = [];
    if (!empty($entityPairs)) {
        $where = implode(' OR ', array_fill(0, count($entityPairs), "(donor_type=? AND donor_ref_id=?)"));
        $params = [];
        foreach ($entityPairs as $p) { $params[] = $p[0]; $params[] = $p[1]; }
        $stmt = $pdo->prepare("SELECT transaction_id, amount, donation_date FROM donations WHERE $where ORDER BY donation_date ASC");
        $stmt->execute($params);
        $allDonations = $stmt->fetchAll();
    }

    // 4. Temporal concentration signal
    $minGapDays = null;
    $closestPair = null;
    for ($i = 1; $i < count($allDonations); $i++) {
        $d1 = new DateTime($allDonations[$i - 1]['donation_date']);
        $d2 = new DateTime($allDonations[$i]['donation_date']);
        $gap = (int)$d1->diff($d2)->days;
        if ($minGapDays === null || $gap < $minGapDays) {
            $minGapDays = $gap;
            $closestPair = [$allDonations[$i - 1]['transaction_id'], $allDonations[$i]['transaction_id']];
        }
    }
    if ($minGapDays !== null && $minGapDays <= 3) $temporalPoints = 15;
    elseif ($minGapDays !== null && $minGapDays <= 7) $temporalPoints = 8;
    else $temporalPoints = 0;

    // 5. Contribution concentration signal
    $totalAmount = array_reduce($allDonations, fn($carry, $d) => $carry + (float)$d['amount'], 0.0);
    if ($totalAmount >= 500000) $contribPoints = 15;
    elseif ($totalAmount >= 200000) $contribPoints = 10;
    elseif ($totalAmount >= 50000) $contribPoints = 5;
    else $contribPoints = 0;

    $score = $relPoints + $hopPoints + $complexityPoints + $temporalPoints + $contribPoints;
    $status = $score >= 80 ? 'FLAGGED FOR REVIEW' : 'NORMAL';

    return [
        'score' => $score,
        'status' => $status,
        'breakdown' => [
            'relationship_signal'    => ['points' => $relPoints, 'max' => 30, 'count' => count($nonDonationRels)],
            'multi_hop_signal'       => ['points' => $hopPoints, 'max' => 25, 'max_hop' => $maxHop],
            'network_complexity'     => ['points' => $complexityPoints, 'max' => 15, 'connected_count' => $connectedCount],
            'temporal_signal'        => ['points' => $temporalPoints, 'max' => 15, 'min_gap_days' => $minGapDays, 'closest_pair' => $closestPair],
            'contribution_signal'    => ['points' => $contribPoints, 'max' => 15, 'total_amount' => $totalAmount, 'donation_count' => count($allDonations)],
        ],
        'cluster' => $cluster,
        'non_donation_relationships' => $nonDonationRels,
        'connected_count' => $connectedCount,
        'max_hop' => $maxHop,
        'all_donations' => $allDonations,
    ];
}

/** Reconstruct one representative multi-hop path from the anchor entity out to its deepest connected node, using the parent pointers network_logic.php now attaches to each node. */
function get_representative_path(array $cluster, $anchorType, $anchorId) {
    $byKey = [];
    foreach ($cluster['nodes'] as $n) { $byKey[$n['type'] . ':' . $n['id']] = $n; }

    $deepest = null;
    foreach ($cluster['nodes'] as $n) {
        if ($deepest === null || $n['hop'] > $deepest['hop']) $deepest = $n;
    }
    if (!$deepest || $deepest['hop'] === 0) return [];

    $path = [];
    $cur = $deepest;
    while ($cur && !($cur['type'] === $anchorType && $cur['id'] === (int)$anchorId)) {
        array_unshift($path, $cur);
        if (!isset($cur['parent_type'])) break;
        $cur = $byKey[$cur['parent_type'] . ':' . $cur['parent_id']] ?? null;
    }
    if ($cur) array_unshift($path, $cur);
    return $path;
}
