<?php
/** api/audit_verify.php -> recomputes and checks the SHA-256 hash chain for every block in audit_ledger */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/audit_logic.php';

try {
    json_response(verify_ledger($pdo));
} catch (Throwable $e) {
    json_response(['valid' => false, 'issues' => ['Could not verify the ledger: ' . $e->getMessage()], 'blocks_checked' => 0]);
}
