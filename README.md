# Political Donation Network Analysis. Version 1.

Team: BinaryBrains

**This is a separate, leaner build from the fuller prototype delivered earlier in
this project's history.** This prompt asked for a fresh Version 1 with no
assumed context, a small hand-built dataset, and Version 2 features (audit
ledger, priority score, Why Flagged, evidence) explicitly left out as
placeholders. It lives in its own folder so it does not overwrite or get
confused with the larger build.

## Purpose

An investigation and network-analysis prototype for synthetic political
donation data. It lets a user search donors, companies, and political
parties, see what an entity is connected to, and explore those connections
as a graph, including indirect (multi-hop) connections.

This is an investigation and transparency tool. It never claims that a
person, company, or party is guilty of fraud, corruption, or money
laundering. Language throughout is neutral: "relationship detected",
"network identified", "pattern highlighted for investigation".

## What Version 1 includes

- Working frontend (HTML, CSS, vanilla JavaScript)
- Working MySQL database with a small, hand-built synthetic dataset
- PHP APIs backed by MySQL (nothing hard-coded into JavaScript arrays)
- Working search, Entity Overview, Network Analysis graph, and a basic
  Investigation Dashboard

## What Version 1 shipped as a placeholder (now implemented - see below)

The original Network Analysis page shipped with a labeled placeholder for:
Blockchain-inspired audit ledger, SHA-256 verification, priority scoring,
Why Flagged, and the evidence engine, saying plainly they were planned for
Version 2. That placeholder has now been replaced by the real, working
features described in "Version 2 additions" directly below.

## Version 2 additions (this extension)

Added on top of the Version 1 build above, without changing the landing
page, search, search results, Entity Overview, or the Network Analysis
graph itself:

- **Blockchain-Inspired Tamper-Evident Audit Ledger** (`pages/audit.php`) -
  one block per real donation, chained with native PHP SHA-256
  (`config/audit_logic.php`). Reached via a "View Audit Ledger" button
  added below the existing graph. Blocks are clickable; a "Verify Ledger
  Integrity" button recomputes every hash from the data actually stored
  in `audit_ledger` and reports a genuine match/mismatch - editing a row
  in that table directly is a simple way to see a real ✕ INTEGRITY
  MISMATCH.
- **Priority Score & Evidence** (`pages/priority.php`) - a transparent,
  rule-based 0-100 score (`config/priority_logic.php`), never machine
  learning. Five documented signals: direct relationships, multi-hop
  depth, network complexity, temporal concentration, and contribution
  total. Score >= 80 shows a "FLAGGED FOR REVIEW / HIGH PRIORITY"
  indicator; below that shows "NORMAL". The same page shows the real
  supporting evidence for the score: the actual relationships, the
  actual multi-hop path, the actual date gap, the actual connected
  contribution total, and the relevant transaction's block/ledger status.
- **Investigation Dashboard**, extended - `api/dashboard.php` now also
  returns each entity's hop depth, priority score, and status, and two
  new stats (Connected Networks, High Priority Cases). `pages/dashboard.php`
  gained Entity Type / Priority / Relationship Type filters.
- A small "Investigation Dashboard" link was added to the shared header
  (`config/helpers.php`) - it wasn't reachable from the UI before.

New database object: `database/migration_v2.sql` adds only the
`audit_ledger` table. No Version 1 table was altered.

## Technology stack

HTML, CSS, vanilla JavaScript. PHP backend. MySQL database. Graph drawn
with native Canvas and vanilla JavaScript. No frameworks of any kind.

## Folder structure

```
political-donation-network-v1/
├── index.php                  - landing page
├── config/
│   ├── database.php            - PDO connection (edit credentials here)
│   ├── helpers.php              - shared utilities, render_header/footer
│   ├── relationship_logic.php   - direct relationship detection
│   ├── network_logic.php        - multi-hop traversal (breadth-first, cycle-safe)
│   ├── audit_logic.php          - Version 2: SHA-256 hash chain, build + verify
│   └── priority_logic.php       - Version 2: rule-based 0-100 priority score
├── api/
│   ├── search.php, entity.php, relationships.php, network.php, dashboard.php
│   └── audit.php, audit_verify.php, priority.php   (Version 2)
├── pages/
│   ├── search.php, entity.php, network.php, dashboard.php
│   └── audit.php, priority.php   (Version 2)
├── assets/
│   ├── css/style.css            - dark investigation theme
│   └── js/app.js, search.js, entity.js, network.js, dashboard.js, audit.js, priority.js
└── database/
    ├── schema.sql
    ├── seed.sql
    └── migration_v2.sql         - Version 2: adds the audit_ledger table
```

## Install with XAMPP

1. Install XAMPP if you do not already have it, then start Apache and
   MySQL from the XAMPP control panel.
2. Copy this whole `political-donation-network-v1` folder into your
   XAMPP `htdocs` folder.
3. Open `http://localhost/phpmyadmin`, create nothing manually. Instead:
   - Click Import, choose `database/schema.sql`, and run it. This
     creates the `political_donation_network` database and all tables.
   - Click Import again, choose `database/seed.sql`, and run it. This
     loads the synthetic demo data.
   - Click Import once more, choose `database/migration_v2.sql`, and
     run it. This adds the one new table (`audit_ledger`) used by the
     Version 2 audit ledger below - it does not touch any Version 1
     table. The ledger's blocks are then built automatically (and
     persisted) the first time you open the Audit Ledger page.
4. Open `config/database.php` and confirm the host/user/password match
   your XAMPP MySQL setup (defaults are `localhost` / `root` / empty
   password, which matches a stock XAMPP install).
5. Visit `http://localhost/political-donation-network-v1/` in your
   browser.

## Demonstration cases

The seed data intentionally contains all 10 required cases:

| Case | Where to look |
|---|---|
| 1. Individual to Political Party | Search "F. Kaur" or "H. Bansal" |
| 2. Individual to Company to Party | Search "S. Iyer" (shares address and phone with Meridian Ventures) |
| 3. Individual to Individual to Company to Party | Search "Rahul Mehta": Rahul Mehta shares an address with Vikram Singh (donor), who shares a name with the director of Trident Traders, which shares a phone number with K. Verma. A 3+ hop chain. |
| 4. Company to Company to Party | Search "Orion Distributors" (shares a director with Vertex Commodities) |
| 5. Shared Address | Search "R. Kapoor" (shares an address with A. Kapoor) |
| 6. Shared Phone | Search "D. Joshi" (shares a phone number with G. Verma) |
| 7. Common Director | Search "Meridian Ventures" (shares director Meera Nair with Meridian Holdings) |
| 8. Multiple related entities to the same party | The Rahul Mehta cluster (case 3) all donate to Political Party Horizon |
| 9. Temporal concentration | Orion Distributors and Vertex Commodities both donate within two days of each other |
| 10. Normal, non-flagged relationship | Search "A. Sethi" (shares a building address with two other donors, nothing else connects them) |

## How relationship detection works

`config/relationship_logic.php` only ever returns a relationship when two
records share an actual value in the database: the same `address_id`, the
same `phone_id`, the same `director_id`, or a real donation record.
Nothing is guessed or randomly connected. Every relationship shown carries
its real supporting value (the actual address text, phone number, or
director name), never a bare label.

## How the graph works

`config/network_logic.php` runs a breadth-first search outward from the
selected entity, following relationships up to 4 hops, using a
visited-node set so a cycle can never cause an infinite loop.
`assets/js/network.js` draws it on a plain `<canvas>`: node color by type,
edge color by relationship type (Donation = blue, Shared Address = green,
Shared Phone = teal, Common Director = orange, Company Relationship /
Associated Entity = purple), with zoom in, zoom out, reset, and fit
controls. Clicking a node opens its entity page; clicking an edge shows
the relationship type, the real supporting value, and both endpoints.

## Data consistency

Every donor, company, party, and politician has one stable `entity_id`
(e.g. `IND-001`, `CMP-001`, `PP-001`, `POL-001`) stored directly in its
table and used as the lookup key everywhere. Every donation has one
`transaction_id` (e.g. `TX001`) that stays the same wherever it is shown.
Nothing is hard-coded separately per page; every page reads the same
MySQL tables.

## Notes on the schema

The spec this build follows did not list a dedicated `politicians` table,
but the landing page needs a working "Politicians" category, so a small
`politicians` table (entity_id, name, affiliated party, designation) was
added. Politicians are searchable and have their own entity page, but are
not currently wired into the relationship graph. Everything else follows
the schema as specified.

## All data is synthetic

Every name, company, party, address, and phone number in this dataset is
fictional, written for demonstration purposes. None of it refers to a
real person, company, or political party.

## Limitations

- Priority scoring is rule-based only (see "Version 2 additions" above) -
  it is not machine learning and is not a finding of fraud or guilt.
- Relationship matching is exact-value only; no fuzzy name or address
  matching.
- The dataset is intentionally small (about 18 donors, 6 companies, 4
  parties, 30 donations) to keep this version focused on the frontend and
  the working data flow, not on dataset scale.
