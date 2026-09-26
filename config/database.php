<?php
/**
 * config/database.php
 * Default XAMPP configuration. Edit these four lines if your local
 * setup differs.
 */

$DB_HOST = "localhost";
$DB_NAME = "political_donation_network";
$DB_USER = "root";
$DB_PASS = "";

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed. Check config/database.php and confirm the database was imported. Details: " . $e->getMessage());
}
