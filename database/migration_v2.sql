-- ================================================================
-- Political Donation Network Analysis - Version 2 migration
-- Adds ONLY the audit_ledger table used by the new Blockchain-
-- Inspired Tamper-Evident Audit Ledger. Nothing here touches,
-- redefines, or drops any Version 1 table. Safe to import after
-- schema.sql and seed.sql on an existing Version 1 database.
-- Re-importable: drops only audit_ledger first, if present.
-- ================================================================
USE political_donation_network;

DROP TABLE IF EXISTS audit_ledger;

-- One block per real donation record (donations.id). Each row is a
-- frozen snapshot of that donation at the time the ledger block was
-- created, plus the SHA-256 hash chain fields. Verification
-- recomputes current_hash from the snapshot fields stored HERE (not
-- by re-reading the donations table), so editing a row in this
-- table - simulating tampering - is what the "Verify Ledger
-- Integrity" check is designed to catch.
CREATE TABLE audit_ledger (
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
);
