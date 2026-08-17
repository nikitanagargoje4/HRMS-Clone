<?php
require_once __DIR__ . '/../php-backend/config/database.php';

try {
    $db = getDB();
    echo "=== COMPANY & EMPLOYEE DISTRIBUTION ANALYSIS ===\n\n";

    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total Users in Database: $totalUsers\n\n";

    // 1. Distribution by Email Domain
    echo "--- Distribution by Email Domain ---\n";
    $stmt = $db->query("
        SELECT 
            SUBSTRING_INDEX(email, '@', -1) as domain,
            COUNT(*) as count
        FROM users
        GROUP BY domain
        ORDER BY count DESC
    ");
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($domains as $d) {
        echo sprintf("  %-35s : %d employees\n", "@" . $d['domain'], $d['count']);
    }

    echo "\n--- Distribution by Units / Companies ---\n";
    $unitStmt = $db->query("
        SELECT 
            u.id, u.name, u.code,
            COUNT(usr.id) as emp_count
        FROM units u
        LEFT JOIN departments d ON d.unit_id = u.id
        LEFT JOIN users usr ON usr.department_id = d.id
        GROUP BY u.id, u.name, u.code
    ");
    $units = $unitStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($units as $u) {
        echo sprintf("  Unit ID %d: %-30s (%s) : %d employees\n", $u['id'], $u['name'], $u['code'], $u['emp_count']);
    }

    // Users with null/unassigned department
    $unassigned = $db->query("SELECT COUNT(*) FROM users WHERE department_id IS NULL")->fetchColumn();
    echo sprintf("  Unassigned (No Department)              : %d employees\n", $unassigned);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
