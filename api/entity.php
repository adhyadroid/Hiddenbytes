<?php
/** api/entity.php?type=donor|company|party|politician&id=IND-001 -> entity detail JSON */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/relationship_logic.php';

$type = $_GET['type'] ?? '';
$entityId = $_GET['id'] ?? '';
$entity = find_entity_by_entity_id($pdo, $entityId);

if (!$entity || $entity['type'] !== $type) {
    json_response(['error' => 'Entity not found']);
}

$detail = null;
$stats = ['count' => 0, 'total' => 0, 'first_date' => null, 'last_date' => null];
$donationRows = [];
$relationships = [];

if ($entity['type'] === 'donor') {
    $stmt = $pdo->prepare(
        "SELECT a.address_text, p.phone_number FROM donors d
         LEFT JOIN addresses a ON a.id=d.address_id LEFT JOIN phone_numbers p ON p.id=d.phone_id
         WHERE d.id=?"
    );
    $stmt->execute([$entity['id']]);
    $detail = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) cnt, COALESCE(SUM(amount),0) total, MIN(donation_date) first_date, MAX(donation_date) last_date
         FROM donations WHERE donor_type='donor' AND donor_ref_id=?"
    );
    $stmt->execute([$entity['id']]);
    $stats = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT don.transaction_id, don.amount, don.donation_date, pp.party_name
         FROM donations don JOIN political_parties pp ON pp.id=don.recipient_party_id
         WHERE don.donor_type='donor' AND don.donor_ref_id=? ORDER BY don.donation_date DESC"
    );
    $stmt->execute([$entity['id']]);
    $donationRows = $stmt->fetchAll();

    $relationships = get_direct_relationships($pdo, 'donor', $entity['id']);

} elseif ($entity['type'] === 'company') {
    $stmt = $pdo->prepare(
        "SELECT a.address_text, p.phone_number, dr.name AS director_name FROM companies c
         LEFT JOIN addresses a ON a.id=c.address_id LEFT JOIN phone_numbers p ON p.id=c.phone_id
         LEFT JOIN directors dr ON dr.id=c.director_id WHERE c.id=?"
    );
    $stmt->execute([$entity['id']]);
    $detail = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) cnt, COALESCE(SUM(amount),0) total, MIN(donation_date) first_date, MAX(donation_date) last_date
         FROM donations WHERE donor_type='company' AND donor_ref_id=?"
    );
    $stmt->execute([$entity['id']]);
    $stats = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT don.transaction_id, don.amount, don.donation_date, pp.party_name
         FROM donations don JOIN political_parties pp ON pp.id=don.recipient_party_id
         WHERE don.donor_type='company' AND don.donor_ref_id=? ORDER BY don.donation_date DESC"
    );
    $stmt->execute([$entity['id']]);
    $donationRows = $stmt->fetchAll();

    $relationships = get_direct_relationships($pdo, 'company', $entity['id']);

} elseif ($entity['type'] === 'party') {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) cnt, COALESCE(SUM(amount),0) total, MIN(donation_date) first_date, MAX(donation_date) last_date
         FROM donations WHERE recipient_party_id=?"
    );
    $stmt->execute([$entity['id']]);
    $stats = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT don.transaction_id, don.amount, don.donation_date,
                CASE don.donor_type WHEN 'donor' THEN d.name ELSE c.company_name END AS donor_name
         FROM donations don
         LEFT JOIN donors d ON don.donor_type='donor' AND d.id=don.donor_ref_id
         LEFT JOIN companies c ON don.donor_type='company' AND c.id=don.donor_ref_id
         WHERE don.recipient_party_id=? ORDER BY don.donation_date DESC"
    );
    $stmt->execute([$entity['id']]);
    $donationRows = $stmt->fetchAll();

} elseif ($entity['type'] === 'politician') {
    $stmt = $pdo->prepare(
        "SELECT p.designation, pp.party_name FROM politicians p
         LEFT JOIN political_parties pp ON pp.id=p.party_id WHERE p.id=?"
    );
    $stmt->execute([$entity['id']]);
    $detail = $stmt->fetch();
}

json_response([
    'entity' => $entity,
    'detail' => $detail,
    'stats' => $stats,
    'donations' => $donationRows,
    'relationships' => $relationships,
]);
