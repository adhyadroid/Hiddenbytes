
-- Political Donation Network Analysis - Version 1 schema
-- Database: political_donation_network
-- All data is 100% synthetic. No real people, companies or parties.
-- Safe to re-import: drops tables first, in dependency order.


CREATE DATABASE IF NOT EXISTS political_donation_network
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE political_donation_network;

DROP TABLE IF EXISTS entity_relationships;
DROP TABLE IF EXISTS donations;
DROP TABLE IF EXISTS politicians;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS donors;
DROP TABLE IF EXISTS political_parties;
DROP TABLE IF EXISTS directors;
DROP TABLE IF EXISTS phone_numbers;
DROP TABLE IF EXISTS addresses;


CREATE TABLE addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    address_id VARCHAR(20) NOT NULL UNIQUE,
    address_text VARCHAR(255) NOT NULL
);

CREATE TABLE phone_numbers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone_id VARCHAR(20) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL
);

CREATE TABLE directors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    director_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL
);

CREATE TABLE political_parties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    party_id VARCHAR(20) NOT NULL UNIQUE,
    party_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_id VARCHAR(20) NOT NULL UNIQUE,
    company_name VARCHAR(150) NOT NULL,
    phone_id INT,
    address_id INT,
    director_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone_id) REFERENCES phone_numbers(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id),
    FOREIGN KEY (director_id) REFERENCES directors(id),
    INDEX (company_name)
);

CREATE TABLE donors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    phone_id INT,
    address_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phone_id) REFERENCES phone_numbers(id),
    FOREIGN KEY (address_id) REFERENCES addresses(id),
    INDEX (name)
);

-- Politicians: a lightweight extra entity type so the landing page's
-- "Politicians" category has something real behind it. Not in the
-- table list given in the spec, added because the spec's own landing
-- category requires it (see README, "Notes on the schema").
CREATE TABLE politicians (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    party_id INT,
    designation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (party_id) REFERENCES political_parties(id)
);

CREATE TABLE donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id VARCHAR(20) NOT NULL UNIQUE,
    donor_type ENUM('donor','company') NOT NULL,
    donor_ref_id INT NOT NULL,          -- points to donors.id or companies.id, per donor_type
    recipient_party_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    donation_date DATE NOT NULL,
    description VARCHAR(255) DEFAULT 'Donation',
    FOREIGN KEY (recipient_party_id) REFERENCES political_parties(id),
    INDEX (donor_type, donor_ref_id)
);

-- entity_relationships: cache table, populated by
-- api/relationships.php from actual shared-attribute data. Never
-- hand-filled with invented connections.
CREATE TABLE entity_relationships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_entity_type ENUM('donor','company') NOT NULL,
    source_entity_id INT NOT NULL,
    target_entity_type ENUM('donor','company','party') NOT NULL,
    target_entity_id INT NOT NULL,
    relationship_type ENUM('Donation','Shared Address','Shared Phone','Common Director','Company Relationship','Associated Entity') NOT NULL,
    supporting_value VARCHAR(255) NOT NULL,
    UNIQUE KEY uniq_rel (source_entity_type, source_entity_id, target_entity_type, target_entity_id, relationship_type)
);
