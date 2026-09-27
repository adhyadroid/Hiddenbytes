# Political Donation Network Analysis

An investigation and network-analysis tool for political donation data. It lets you search donors, companies, and political parties; view an entity's full profile; explore how entities are connected ,directly and through multi-hop chains ,on an interactive graph; walk a tamper-evident audit ledger of every transaction; see a transparent, rule-based priority score with supporting evidence; and browse it all from a filterable investigation dashboard.

All data in this repository is 100% synthetic. No name, company, address, phone number, or party in the dataset refers to a real person, organization, or political party. This is a transparency/investigation tool, not an accusation engine — it never asserts guilt, fraud, or wrongdoing. Language throughout is neutral: "relationship detected," "network identified," "pattern highlighted for investigation."

## Features

- **Search** — find any donor, company, party, or politician by name or ID.
- **Entity Overview** — an entity's profile: contact details, donation history, and totals.
- **Relationship / Network Graph** — a breadth-first, cycle-safe traversal (up to 4 hops) rendered on an interactive `<canvas>`, with zoom, pan, fit-to-screen, and clickable nodes/edges. Every edge is backed by a real shared value (address, phone, director, or donation) — nothing is inferred or randomly connected.
- **Blockchain-Inspired Tamper-Evident Audit Ledger** — one hash-linked block per real donation, chained with native PHP SHA-256 (not a decentralized blockchain — no mining or consensus, just a tamper-evident, hash-linked log). Blocks are clickable for full transaction detail, and a "Verify Ledger Integrity" check recomputes every hash live against what's actually stored, so tampering with a row is genuinely detectable.
- **Priority Score & Evidence** — a transparent, rule-based 0–100 score (never machine learning) built from five documented signals: direct relationships, multi-hop depth, network complexity, temporal concentration, and contribution total. Every score is shown alongside the real evidence behind it.
- **Investigation Dashboard** — a filterable overview (by entity type, priority, and relationship type) with aggregate stats including connected networks and high-priority cases.

## Tech stack

- **Frontend:** HTML, CSS, vanilla JavaScript (no frameworks). The network graph is drawn with the native Canvas API.
- **Backend:** PHP, talking to MySQL through PDO. No hard-coded data in JavaScript , every page reads from the database through a PHP API.
- **Database:** MySQL.

## Project structure

```
political-donation-network-v1/
├── index.php                    - landing page
├── config/
│   ├── database.php             - PDO connection (edit credentials here)
│   ├── helpers.php               - shared utilities, render_header/footer, entity lookups
│   ├── relationship_logic.php    - direct relationship detection (shared address/phone/director, donations)
│   ├── network_logic.php         - multi-hop traversal (breadth-first, cycle-safe)
│   ├── audit_logic.php           - SHA-256 hash chain: build, verify, self-healing table creation
│   └── priority_logic.php        - rule-based 0-100 priority score
├── api/
│   ├── search.php, entity.php, relationships.php, network.php, dashboard.php
│   └── audit.php, audit_verify.php, priority.php
├── pages/
│   ├── search.php, entity.php, network.php, dashboard.php
│   └── audit.php, priority.php
├── assets/
│   ├── css/style.css             - dark investigation theme
│   └── js/app.js, search.js, entity.js, network.js, dashboard.js, audit.js, priority.js
└── database/
    ├── schema.sql                - core tables (donors, companies, parties, politicians, donations, relationships)
    ├── seed.sql                  - synthetic demo dataset
    └── migration_v2.sql          - adds the audit_ledger table used by the Audit Ledger feature
```

## Getting started

### Prerequisites

- PHP 8+ with the `pdo_mysql` extension
- MySQL (or MariaDB)
- Any local web server stack that runs PHP against a document root ,these steps use [XAMPP](https://www.apachefriends.org/) as an example, but any equivalent (MAMP, WAMP, `php -S` with a MySQL server, etc.) works the same way.

### Installation

1. Clone or download this repository.
2. Copy the `political-donation-network-v1` folder into your web server's document root (e.g. XAMPP's `htdocs`).
3. Create the database and import the schema. Using phpMyAdmin (`http://localhost/phpmyadmin`), or the `mysql` CLI:
   - Import `database/schema.sql` — creates the `political_donation_network` database and its core tables.
   - Import `database/seed.sql` — loads the synthetic demo dataset.
   - Import `database/migration_v2.sql` — adds the `audit_ledger` table used by the Audit Ledger feature.

   Via the CLI, equivalently:
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/seed.sql
   mysql -u root -p < database/migration_v2.sql
   ```
4. Open `config/database.php` and set `$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS` to match your MySQL setup (defaults are `localhost` / `political_donation_network` / `root` / empty password, matching a stock XAMPP install).
5. Visit the app in your browser, e.g. `http://localhost/political-donation-network-v1/`.

> **Note:** Step 3's `migration_v2.sql` import is optional in practice — `config/audit_logic.php` automatically creates the `audit_ledger` table (`CREATE TABLE IF NOT EXISTS`, identical schema) the first time the Audit Ledger page is opened, so the feature still works even if that import is skipped. Running the migration explicitly is still the recommended path since it documents the schema change.

## How relationship detection works

`config/relationship_logic.php` only ever returns a relationship when two records share an actual value in the database: the same `address_id`, the same `phone_id`, the same `director_id`, or a real donation record. Nothing is guessed or randomly connected, and every relationship shown carries its real supporting value (the actual address text, phone number, or director name), never a bare label.

## How the network graph works

`config/network_logic.php` runs a breadth-first search outward from the selected entity, following relationships up to 4 hops, using a visited-node set so a cycle can never cause an infinite loop. `assets/js/network.js` draws it on a `<canvas>`: node color by type, edge color by relationship type (Donation = blue, Shared Address = green, Shared Phone = teal, Common Director = orange, Company Relationship / Associated Entity = purple), with zoom in, zoom out, reset, and fit controls. Clicking a node opens its entity page; clicking an edge shows the relationship type, the real supporting value, and both endpoints.

## How the audit ledger works

`config/audit_logic.php` builds one ledger block per real donation, in transaction order. Each block's `current_hash` is computed with SHA-256 over its own frozen data plus the previous block's hash, so the chain can detect if any block's stored data — or the chain linkage itself — is altered after the fact. The ledger is built once and persisted in `audit_ledger`; it is intentionally left untouched on later page loads so that manually editing a row (to simulate tampering) and then clicking "Verify Ledger Integrity" produces a genuine ✕ INTEGRITY MISMATCH, not a silently-rebuilt clean ledger. This is a hash-linked log for tamper-evidence, not a decentralized blockchain — there's no mining, consensus, or peer network involved.

## Data consistency

Every donor, company, party, and politician has one stable `entity_id` (e.g. `IND-001`, `CMP-001`, `PP-001`, `POL-001`) stored directly in its table and used as the lookup key everywhere. Every donation has one `transaction_id` (e.g. `TX001`) that stays the same wherever it's shown. Nothing is hard-coded separately per page — every page reads from the same MySQL tables.

## Demonstration cases

The seed data intentionally contains a scenario for each of the following:

| Case | Where to look |
|---|---|
| Individual → Political Party | Search "F. Kaur" or "H. Bansal" |
| Individual → Company → Party | Search "S. Iyer" (shares address and phone with Meridian Ventures) |
| Individual → Individual → Company → Party (3+ hop chain) | Search "Rahul Mehta": shares an address with Vikram Singh, who shares a name with the director of Trident Traders, which shares a phone number with K. Verma |
| Company → Company → Party | Search "Orion Distributors" (shares a director with Vertex Commodities) |
| Shared Address | Search "R. Kapoor" (shares an address with A. Kapoor) |
| Shared Phone | Search "D. Joshi" (shares a phone number with G. Verma) |
| Common Director | Search "Meridian Ventures" (shares director Meera Nair with Meridian Holdings) |
| Multiple related entities to the same party | The Rahul Mehta cluster above — all donate to Political Party Horizon |
| Temporal concentration | Orion Distributors and Vertex Commodities both donate within two days of each other |
| Normal, non-flagged relationship | Search "A. Sethi" (shares a building address with two other donors, nothing else connects them) |

## Notes on the schema

A `politicians` table (entity_id, name, affiliated party, designation) exists to back the landing page's "Politicians" search category. Politicians are searchable and have their own entity page but are not currently wired into the relationship graph.

## Troubleshooting

- **Audit Ledger page stuck on "Loading..."** — this means `api/audit.php` didn't return valid JSON, most often because the `audit_ledger` table wasn't available yet. `config/audit_logic.php` now creates that table automatically on first use, and `api/audit.php`, `api/audit_verify.php`, and `assets/js/audit.js` all surface backend errors as a visible message instead of hanging silently. If it still fails, open your browser's Network tab on `api/audit.php` and check the returned `error` field for the actual cause (e.g. a MySQL permissions issue).
- **"Database connection failed"** on any page — check `$DB_HOST`, `$DB_NAME`, `$DB_USER`, `$DB_PASS` in `config/database.php` against your local MySQL setup.
- **A page shows "Entity not found"** — the `id` in the URL must be a real `entity_id` from the seed data (e.g. `IND-001`, `CMP-001`, `PP-001`, `POL-001`), and `type` must match what that ID actually is (`donor`, `company`, `party`, or `politician`).

## Limitations

- Priority scoring is rule-based only — it is not machine learning and is not a finding of fraud or guilt.
- Relationship matching is exact-value only; there's no fuzzy name or address matching.
- The dataset is intentionally small (about 18 donors, 6 companies, 4 parties, 30 donations) to keep the focus on the frontend and the working data flow rather than dataset scale.

## Disclaimer

All data shown is synthetic. This tool highlights patterns for investigation and does not determine guilt or wrongdoing.
