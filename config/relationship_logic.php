<?php
/**
 * config/relationship_logic.php
 * Every relationship returned here is derived from an actual shared
 * value in the database (same address_id, same phone_id, same
 * director_id, or a real donation record) - nothing is invented or
 * randomly connected.
 */

/** Cache one relationship row into entity_relationships if not already present. */
function cache_relationship(PDO $pdo, $srcType, $srcId, $tgtType, $tgtId, $relType, $supportingValue) {
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO entity_relationships
         (source_entity_type, source_entity_id, target_entity_type, target_entity_id, relationship_type, supporting_value)
         VALUES (?,?,?,?,?,?)"
    );
    $stmt->execute([$srcType, $srcId, $tgtType, $tgtId, $relType, $supportingValue]);
}

/**
 * Direct relationships for one donor or company: shared address,
 * shared phone, common director / company relationship, associated
 * entity (donor name matches a company director), and donation links
 * to political parties.
 * Returns rows: ['type','id','name','relationship_type','supporting_value']
 */
function get_direct_relationships(PDO $pdo, $type, $id) {
    $out = [];

    if ($type === 'donor') {
        $stmt = $pdo->prepare("SELECT name, address_id, phone_id FROM donors WHERE id=?");
        $stmt->execute([$id]);
        $self = $stmt->fetch();
        if (!$self) return [];
        $addressId = $self['address_id']; $phoneId = $self['phone_id']; $name = $self['name'];
    } else { // company
        $stmt = $pdo->prepare("SELECT company_name AS name, address_id, phone_id, director_id FROM companies WHERE id=?");
        $stmt->execute([$id]);
        $self = $stmt->fetch();
        if (!$self) return [];
        $addressId = $self['address_id']; $phoneId = $self['phone_id']; $name = $self['name'];
        $directorId = $self['director_id'];
    }

    // Shared Address
    if ($addressId) {
        $addrText = $pdo->query("SELECT address_text FROM addresses WHERE id=" . (int)$addressId)->fetchColumn();
        $val = $addrText ?: 'Unknown address';
        $stmt = $pdo->prepare("SELECT id, name FROM donors WHERE address_id=?" . ($type === 'donor' ? " AND id<>?" : ""));
        $type === 'donor' ? $stmt->execute([$addressId, $id]) : $stmt->execute([$addressId]);
        foreach ($stmt->fetchAll() as $row) {
            $out[] = ['type'=>'donor','id'=>(int)$row['id'],'name'=>$row['name'],'relationship_type'=>'Shared Address','supporting_value'=>$val];
            cache_relationship($pdo, $type, $id, 'donor', $row['id'], 'Shared Address', $val);
        }
        $stmt2 = $pdo->prepare("SELECT id, company_name AS name FROM companies WHERE address_id=?" . ($type === 'company' ? " AND id<>?" : ""));
        $type === 'company' ? $stmt2->execute([$addressId, $id]) : $stmt2->execute([$addressId]);
        foreach ($stmt2->fetchAll() as $row) {
            $relType = $type === 'company' ? 'Company Relationship' : 'Shared Address';
            $out[] = ['type'=>'company','id'=>(int)$row['id'],'name'=>$row['name'],'relationship_type'=>$relType,'supporting_value'=>$val];
            cache_relationship($pdo, $type, $id, 'company', $row['id'], $relType, $val);
        }
    }

    // Shared Phone
    if ($phoneId) {
        $phoneText = $pdo->query("SELECT phone_number FROM phone_numbers WHERE id=" . (int)$phoneId)->fetchColumn();
        $val = $phoneText ?: 'Unknown number';
        $stmt = $pdo->prepare("SELECT id, name FROM donors WHERE phone_id=?" . ($type === 'donor' ? " AND id<>?" : ""));
        $type === 'donor' ? $stmt->execute([$phoneId, $id]) : $stmt->execute([$phoneId]);
        foreach ($stmt->fetchAll() as $row) {
            $out[] = ['type'=>'donor','id'=>(int)$row['id'],'name'=>$row['name'],'relationship_type'=>'Shared Phone','supporting_value'=>$val];
            cache_relationship($pdo, $type, $id, 'donor', $row['id'], 'Shared Phone', $val);
        }
        $stmt2 = $pdo->prepare("SELECT id, company_name AS name FROM companies WHERE phone_id=?" . ($type === 'company' ? " AND id<>?" : ""));
        $type === 'company' ? $stmt2->execute([$phoneId, $id]) : $stmt2->execute([$phoneId]);
        foreach ($stmt2->fetchAll() as $row) {
            $relType = $type === 'company' ? 'Company Relationship' : 'Shared Phone';
            $out[] = ['type'=>'company','id'=>(int)$row['id'],'name'=>$row['name'],'relationship_type'=>$relType,'supporting_value'=>$val];
            cache_relationship($pdo, $type, $id, 'company', $row['id'], $relType, $val);
        }
    }

    // Common Director (company only)
    if ($type === 'company' && !empty($directorId)) {
        $dirName = $pdo->query("SELECT name FROM directors WHERE id=" . (int)$directorId)->fetchColumn();
        $val = 'Director: ' . ($dirName ?: 'Unknown');
        $stmt = $pdo->prepare("SELECT id, company_name AS name FROM companies WHERE director_id=? AND id<>?");
        $stmt->execute([$directorId, $id]);
        foreach ($stmt->fetchAll() as $row) {
            $out[] = ['type'=>'company','id'=>(int)$row['id'],'name'=>$row['name'],'relationship_type'=>'Common Director','supporting_value'=>$val];
            cache_relationship($pdo, 'company', $id, 'company', $row['id'], 'Common Director', $val);
        }

        // Associated Entity: a donor whose name matches this director's name
        if ($dirName) {
            $stmt3 = $pdo->prepare("SELECT id, name FROM donors WHERE LOWER(name)=LOWER(?)");
            $stmt3->execute([$dirName]);
            foreach ($stmt3->fetchAll() as $row) {
                $val2 = $dirName . ' is listed as a director of this company';
                $out[] = ['type'=>'donor','id'=>(int)$row['id'],'name'=>$row['name'],'relationship_type'=>'Associated Entity','supporting_value'=>$val2];
                cache_relationship($pdo, 'company', $id, 'donor', $row['id'], 'Associated Entity', $val2);
            }
        }
    }

    // Associated Entity, other direction: this donor's name matches a director
    if ($type === 'donor') {
        $stmt = $pdo->prepare("SELECT id, name FROM directors WHERE LOWER(name)=LOWER(?)");
        $stmt->execute([$name]);
        foreach ($stmt->fetchAll() as $dir) {
            $stmt2 = $pdo->prepare("SELECT id, company_name AS name FROM companies WHERE director_id=?");
            $stmt2->execute([$dir['id']]);
            foreach ($stmt2->fetchAll() as $c) {
                $val = $name . ' is listed as a director of this company';
                $out[] = ['type'=>'company','id'=>(int)$c['id'],'name'=>$c['name'],'relationship_type'=>'Associated Entity','supporting_value'=>$val];
                cache_relationship($pdo, 'donor', $id, 'company', $c['id'], 'Associated Entity', $val);
            }
        }
    }

    // Donation links to political parties
    $stmt = $pdo->prepare(
        "SELECT don.transaction_id, don.amount, pp.id AS party_id, pp.party_name
         FROM donations don JOIN political_parties pp ON pp.id = don.recipient_party_id
         WHERE don.donor_type = ? AND don.donor_ref_id = ?"
    );
    $stmt->execute([$type, $id]);
    foreach ($stmt->fetchAll() as $row) {
        $val = 'Transaction ' . $row['transaction_id'] . ', ' . format_inr($row['amount']);
        $out[] = ['type'=>'party','id'=>(int)$row['party_id'],'name'=>$row['party_name'],'relationship_type'=>'Donation','supporting_value'=>$val];
        cache_relationship($pdo, $type, $id, 'party', $row['party_id'], 'Donation', $val);
    }

    return $out;
}
