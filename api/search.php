<?php
/** api/search.php?q=term -> JSON list of matching donors/companies/parties/politicians */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$q = trim($_GET['q'] ?? '');
$results = [];
if ($q !== '') {
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare(
        "SELECT d.entity_id, d.name, a.address_text, p.phone_number
         FROM donors d
         LEFT JOIN addresses a ON a.id = d.address_id
         LEFT JOIN phone_numbers p ON p.id = d.phone_id
         WHERE d.name LIKE ? OR d.entity_id LIKE ? OR p.phone_number LIKE ? OR a.address_text LIKE ?
         LIMIT 20"
    );
    $stmt->execute([$like, $like, $like, $like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = ['type' => 'donor', 'entity_id' => $r['entity_id'], 'name' => $r['name'],
                       'meta' => $r['address_text'] ?: $r['phone_number']];
    }

    $stmt = $pdo->prepare(
        "SELECT c.entity_id, c.company_name AS name, a.address_text, p.phone_number, dr.name AS director_name
         FROM companies c
         LEFT JOIN addresses a ON a.id = c.address_id
         LEFT JOIN phone_numbers p ON p.id = c.phone_id
         LEFT JOIN directors dr ON dr.id = c.director_id
         WHERE c.company_name LIKE ? OR c.entity_id LIKE ? OR p.phone_number LIKE ? OR a.address_text LIKE ?
         LIMIT 20"
    );
    $stmt->execute([$like, $like, $like, $like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = ['type' => 'company', 'entity_id' => $r['entity_id'], 'name' => $r['name'],
                       'meta' => $r['director_name'] ? ('Director: ' . $r['director_name']) : ($r['address_text'] ?: $r['phone_number'])];
    }

    $stmt = $pdo->prepare("SELECT party_id AS entity_id, party_name AS name FROM political_parties WHERE party_name LIKE ? OR party_id LIKE ? LIMIT 10");
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = ['type' => 'party', 'entity_id' => $r['entity_id'], 'name' => $r['name'], 'meta' => 'Political party'];
    }

    $stmt = $pdo->prepare("SELECT entity_id, name, designation FROM politicians WHERE name LIKE ? OR entity_id LIKE ? LIMIT 10");
    $stmt->execute([$like, $like]);
    foreach ($stmt->fetchAll() as $r) {
        $results[] = ['type' => 'politician', 'entity_id' => $r['entity_id'], 'name' => $r['name'], 'meta' => $r['designation']];
    }
}

json_response(['query' => $q, 'results' => $results]);
