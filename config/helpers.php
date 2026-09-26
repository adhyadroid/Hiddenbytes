<?php
/**
 * config/helpers.php
 * Small shared utilities, used by both the api/ endpoints and pages/.
 */

function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function format_inr($amount) {
    $amount = (float)$amount;
    $isNegative = $amount < 0;
    $amount = abs(round($amount));
    $num = number_format($amount, 0, '.', '');
    $lastThree = substr($num, -3);
    $rest = substr($num, 0, -3);
    if ($rest !== '') {
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $formatted = $rest . ',' . $lastThree;
    } else {
        $formatted = $lastThree;
    }
    return ($isNegative ? '-' : '') . 'Rs. ' . $formatted;
}

/**
 * Look up one entity by its public entity_id (e.g. "IND-001", "CMP-001", "PP-001").
 * Returns a normalized array with type, internal id, entity_id, and name - or null.
 */
function find_entity_by_entity_id(PDO $pdo, $entityId) {
    $entityId = trim($entityId);
    if (str_starts_with($entityId, 'IND-')) {
        $stmt = $pdo->prepare("SELECT id, entity_id, name FROM donors WHERE entity_id = ?");
        $stmt->execute([$entityId]);
        if ($row = $stmt->fetch()) return ['type' => 'donor', 'id' => (int)$row['id'], 'entity_id' => $row['entity_id'], 'name' => $row['name']];
    }
    if (str_starts_with($entityId, 'CMP-')) {
        $stmt = $pdo->prepare("SELECT id, entity_id, company_name AS name FROM companies WHERE entity_id = ?");
        $stmt->execute([$entityId]);
        if ($row = $stmt->fetch()) return ['type' => 'company', 'id' => (int)$row['id'], 'entity_id' => $row['entity_id'], 'name' => $row['name']];
    }
    if (str_starts_with($entityId, 'PP-')) {
        $stmt = $pdo->prepare("SELECT id, party_id AS entity_id, party_name AS name FROM political_parties WHERE party_id = ?");
        $stmt->execute([$entityId]);
        if ($row = $stmt->fetch()) return ['type' => 'party', 'id' => (int)$row['id'], 'entity_id' => $row['entity_id'], 'name' => $row['name']];
    }
    if (str_starts_with($entityId, 'POL-')) {
        $stmt = $pdo->prepare("SELECT id, entity_id, name FROM politicians WHERE entity_id = ?");
        $stmt->execute([$entityId]);
        if ($row = $stmt->fetch()) return ['type' => 'politician', 'id' => (int)$row['id'], 'entity_id' => $row['entity_id'], 'name' => $row['name']];
    }
    return null;
}

/** Shared page chrome - kept as two small functions rather than a template file, since there are only a few pages. */
function render_header($pageTitle = 'Political Donation Network Analysis') {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . h($pageTitle) . '</title>';
    echo '<link rel="stylesheet" href="' . (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/pages/') ? '../assets/css/style.css' : 'assets/css/style.css') . '">';
    echo '</head><body>';
    echo '<header class="site-header"><div class="header-inner">';
    echo '<a class="brand" href="' . (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/pages/') ? '../index.php' : 'index.php') . '">Political Donation Network Analysis</a>';
    $pagesPrefix = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/pages/') ? '' : 'pages/';
    echo '<div style="display:flex;align-items:center;gap:14px;">';
    echo '<a class="nav-link" href="' . $pagesPrefix . 'dashboard.php">Investigation Dashboard</a>';
    echo '<button class="subscription-btn" type="button" title="Placeholder for a future subscription model">Subscription</button>';
    echo '</div>';
    echo '</div></header><main class="page-content">';
}

function render_footer() {
    echo '</main><footer style="max-width:1200px;margin:0 auto;padding:16px 24px 40px;font-size:11px;color:var(--text-secondary);">';
    echo 'Political Donation Network Analysis. Version 1. All data shown is synthetic. This tool highlights patterns for investigation and does not determine guilt or wrongdoing.';
    echo '</footer></body></html>';
}

/** Same lookup, but by internal numeric id + known type - used once a type/id pair is already known (e.g. from a relationship row). */
function get_entity_by_type_id(PDO $pdo, $type, $id) {
    if ($type === 'donor') {
        $stmt = $pdo->prepare("SELECT id, entity_id, name FROM donors WHERE id=?");
    } elseif ($type === 'company') {
        $stmt = $pdo->prepare("SELECT id, entity_id, company_name AS name FROM companies WHERE id=?");
    } elseif ($type === 'party') {
        $stmt = $pdo->prepare("SELECT id, party_id AS entity_id, party_name AS name FROM political_parties WHERE id=?");
    } elseif ($type === 'politician') {
        $stmt = $pdo->prepare("SELECT id, entity_id, name FROM politicians WHERE id=?");
    } else {
        return null;
    }
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return ['type' => $type, 'id' => (int)$row['id'], 'entity_id' => $row['entity_id'], 'name' => $row['name']];
}
