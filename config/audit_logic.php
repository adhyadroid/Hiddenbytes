<?php
/**
 * config/audit_logic.php
 * Blockchain-Inspired Tamper-Evident Audit Ledger.
 *
 * This is NOT a decentralized blockchain. It is a hash-linked ledger:
 * one block per real donation record, each block's current_hash
 * computed from its own frozen data plus the previous block's
 * current_hash, using native PHP hash('sha256', ...). That chain
 * lets a "Verify Ledger Integrity" check detect if any block's
 * stored data, or the chain linkage itself, was altered after the
 * fact - the same tamper-evidence idea blockchains use, without any
 * decentralization, mining, or consensus.
 *
 * genesis previous_hash = 64 zeros, the conventional placeholder for
 * "no prior block".
 */

function audit_genesis_hash() {
    return str_repeat('0', 64);
}

/**
 * Guarantees the audit_ledger table exists before it is queried.
 * The table is defined in database/migration_v2.sql, which must be
 * imported manually as a second step after schema.sql (see README).
 * That manual step is easy to miss on a fresh install, which is what
 * left this page stuck on "Loading..." - every query below against
 * audit_ledger threw an uncaught PDOException (table doesn't exist),
 * so the API never returned JSON at all. Running the exact same
 * CREATE TABLE here (IF NOT EXISTS) makes the existing migration
 * self-healing without altering its schema or introducing any new
 * table/column that migration_v2.sql didn't already define.
 */
function ensure_audit_ledger_table(PDO $pdo) {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS audit_ledger (
            id INT PRIMARY KEY AUTO_INCREMENT,
            block_number INT NOT NULL UNIQUE,
            donation_id INT NOT NULL UNIQUE,
            transaction_id VARCHAR(20) NOT NULL UNIQUE,
            donor_type ENUM('donor','company') NOT NULL,
            donor_ref_id INT NOT NULL,
            donor_label VARCHAR(150) NOT NULL,
            recipient_party_id INT NOT NULL,
            recipient_label VARCHAR(150) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            donation_date DATE NOT NULL,
            description VARCHAR(255) NOT NULL,
            block_timestamp DATETIME NOT NULL,
            previous_hash CHAR(64) NOT NULL,
            current_hash CHAR(64) NOT NULL,
            FOREIGN KEY (donation_id) REFERENCES donations(id),
            FOREIGN KEY (recipient_party_id) REFERENCES political_parties(id)
        )"
    );
}

/** Canonical string of a block's data - this is what gets hashed. Order and format must stay fixed for verification to be meaningful. */
function audit_block_payload($row) {
    return implode('|', [
        $row['transaction_id'],
        $row['donor_type'],
        $row['donor_label'],
        $row['recipient_label'],
        number_format((float)$row['amount'], 2, '.', ''),
        $row['donation_date'],
        $row['description'],
    ]);
}

/**
 * Build the ledger once from the donations table (chronological by
 * donation id, matching transaction_id order: TX001, TX002, ...) and
 * persist it into audit_ledger. If the ledger already has one block
 * per donation, it is left untouched - this is what lets a person
 * manually edit a row in audit_ledger to simulate tampering and then
 * see "Verify Ledger Integrity" catch it, instead of the ledger
 * silently rebuilding itself from the (unedited) donations table on
 * every page load.
 */
function ensure_ledger_built(PDO $pdo) {
    ensure_audit_ledger_table($pdo);
    $donationCount = (int)$pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn();
    $ledgerCount = (int)$pdo->query("SELECT COUNT(*) FROM audit_ledger")->fetchColumn();
    if ($donationCount > 0 && $ledgerCount === $donationCount) {
        return; // already built - do not touch, tampering demos must survive a page reload
    }

    $pdo->exec("DELETE FROM audit_ledger");

    $stmt = $pdo->query(
        "SELECT don.id AS donation_id, don.transaction_id, don.donor_type, don.donor_ref_id,
                don.recipient_party_id, don.amount, don.donation_date, don.description,
                CASE don.donor_type WHEN 'donor' THEN d.name ELSE c.company_name END AS donor_label,
                pp.party_name AS recipient_label
         FROM donations don
         LEFT JOIN donors d ON don.donor_type='donor' AND d.id=don.donor_ref_id
         LEFT JOIN companies c ON don.donor_type='company' AND c.id=don.donor_ref_id
         JOIN political_parties pp ON pp.id=don.recipient_party_id
         ORDER BY don.id ASC"
    );
    $donations = $stmt->fetchAll();

    $insert = $pdo->prepare(
        "INSERT INTO audit_ledger
         (block_number, donation_id, transaction_id, donor_type, donor_ref_id, donor_label,
          recipient_party_id, recipient_label, amount, donation_date, description,
          block_timestamp, previous_hash, current_hash)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );

    $previousHash = audit_genesis_hash();
    $blockNumber = 1;
    foreach ($donations as $row) {
        $timestamp = date('Y-m-d H:i:s'); // frozen at build time; stored so verification recomputes against the same value
        $payload = audit_block_payload($row);
        $currentHash = hash('sha256', $payload . '|' . $timestamp . '|' . $previousHash);

        $insert->execute([
            $blockNumber, $row['donation_id'], $row['transaction_id'], $row['donor_type'], $row['donor_ref_id'],
            $row['donor_label'], $row['recipient_party_id'], $row['recipient_label'], $row['amount'],
            $row['donation_date'], $row['description'], $timestamp, $previousHash, $currentHash,
        ]);

        $previousHash = $currentHash;
        $blockNumber++;
    }
}

/** All blocks, in chain order. */
function get_all_blocks(PDO $pdo) {
    ensure_ledger_built($pdo);
    return $pdo->query("SELECT * FROM audit_ledger ORDER BY block_number ASC")->fetchAll();
}

/**
 * Recompute every block's hash from its own stored snapshot fields
 * and confirm it matches the stored current_hash, and confirm each
 * block's previous_hash matches the prior block's current_hash.
 * Nothing here is faked - a change to any stored field, or to a
 * hash value itself, is what breaks this.
 */
function verify_ledger(PDO $pdo) {
    $blocks = get_all_blocks($pdo);
    $expectedPrev = audit_genesis_hash();
    $valid = true;
    $issues = [];

    foreach ($blocks as $row) {
        $payload = audit_block_payload($row);
        $recomputed = hash('sha256', $payload . '|' . $row['block_timestamp'] . '|' . $row['previous_hash']);

        if ($recomputed !== $row['current_hash']) {
            $valid = false;
            $issues[] = "Block {$row['block_number']} ({$row['transaction_id']}): stored data does not match its recorded hash.";
        }
        if ($row['previous_hash'] !== $expectedPrev) {
            $valid = false;
            $issues[] = "Block {$row['block_number']} ({$row['transaction_id']}): previous_hash does not match the prior block's current_hash - chain link broken.";
        }
        $expectedPrev = $row['current_hash'];
    }

    return ['valid' => $valid, 'issues' => $issues, 'blocks_checked' => count($blocks)];
}

/** Blocks for one entity's own donations (donor_type+donor_ref_id) - used to anchor the audit view on an entity. */
function get_blocks_for_entity(PDO $pdo, $type, $id) {
    if (!in_array($type, ['donor', 'company'], true)) return [];
    ensure_ledger_built($pdo);
    $stmt = $pdo->prepare("SELECT * FROM audit_ledger WHERE donor_type=? AND donor_ref_id=? ORDER BY block_number ASC");
    $stmt->execute([$type, $id]);
    return $stmt->fetchAll();
}

/** Blocks belonging to any donor/company node in a cluster (self + connected entities), used for the entity's audit ledger view. */
function get_blocks_for_cluster(PDO $pdo, array $clusterNodes) {
    ensure_ledger_built($pdo);
    $pairs = [];
    foreach ($clusterNodes as $n) {
        if (in_array($n['type'], ['donor', 'company'], true)) {
            $pairs[] = [$n['type'], $n['id']];
        }
    }
    if (empty($pairs)) return [];

    $where = implode(' OR ', array_fill(0, count($pairs), "(donor_type=? AND donor_ref_id=?)"));
    $params = [];
    foreach ($pairs as $p) { $params[] = $p[0]; $params[] = $p[1]; }
    $stmt = $pdo->prepare("SELECT * FROM audit_ledger WHERE $where ORDER BY block_number ASC");
    $stmt->execute($params);
    return $stmt->fetchAll();
}
