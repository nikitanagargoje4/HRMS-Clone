<?php
require_once __DIR__ . '/../php-backend/config/database.php';

try {
    $db = getDB();
    echo "=== UNITS ===\n";
    print_r($db->query("SELECT * FROM units")->fetchAll(PDO::FETCH_ASSOC));

    echo "\n=== COMPANY MASTERS ===\n";
    print_r($db->query("SELECT * FROM company_masters")->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
