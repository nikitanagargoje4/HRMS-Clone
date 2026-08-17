<?php
require_once __DIR__ . '/../php-backend/config/database.php';

try {
    $db = getDB();
    echo "=== REBALANCING DEPARTMENTS & EMPLOYEES ACROSS UNITS ===\n\n";

    // 1. Get all Praj employees (@praj.in)
    $prajEmps = $db->query("SELECT id FROM users WHERE email LIKE '%@praj.in' ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    echo "Total Praj Employees: " . count($prajEmps) . "\n";

    // 2. Get all SunPharma employees (@sunpharma.com)
    $sunEmps = $db->query("SELECT id FROM users WHERE email LIKE '%@sunpharma.com' ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    echo "Total SunPharma Employees: " . count($sunEmps) . "\n";

    // Reassign Praj departments/employees to Unit 3 (HQ), Unit 4 (Urawade), Unit 5 (Bhor)
    // Create specific departments for Praj Urawade (Unit 4) and Praj Bhor (Unit 5) if needed
    $prajHQ_dept = $db->query("SELECT id FROM departments WHERE unit_id = 3 LIMIT 1")->fetchColumn();
    
    // Check or create department for Praj Urawade (Unit 4)
    $dept4 = $db->query("SELECT id FROM departments WHERE unit_id = 4 LIMIT 1")->fetchColumn();
    if (!$dept4) {
        $db->exec("INSERT INTO departments (name, code, unit_id, location) VALUES ('Manufacturing (Urawade)', 'PRJ-URW', 4, 'Urawade, Pune')");
        $dept4 = $db->lastInsertId();
    }

    // Check or create department for Praj Bhor (Unit 5)
    $dept5 = $db->query("SELECT id FROM departments WHERE unit_id = 5 LIMIT 1")->fetchColumn();
    if (!$dept5) {
        $db->exec("INSERT INTO departments (name, code, unit_id, location) VALUES ('Operations (Bhor)', 'PRJ-BHR', 5, 'Bhor, Pune')");
        $dept5 = $db->lastInsertId();
    }

    // Distribute 30 Praj employees: 10 to HQ (Unit 3), 10 to Urawade (Unit 4), 10 to Bhor (Unit 5)
    $chunk1 = array_slice($prajEmps, 0, 10);
    $chunk2 = array_slice($prajEmps, 10, 10);
    $chunk3 = array_slice($prajEmps, 20);

    if ($prajHQ_dept) {
        $in1 = implode(',', $chunk1);
        $db->exec("UPDATE users SET department_id = $prajHQ_dept WHERE id IN ($in1)");
    }

    $in2 = implode(',', $chunk2);
    $db->exec("UPDATE users SET department_id = $dept4 WHERE id IN ($in2)");

    $in3 = implode(',', $chunk3);
    $db->exec("UPDATE users SET department_id = $dept5 WHERE id IN ($in3)");

    echo "Reassigned Praj employees: 10 to Praj (HQ), 10 to Praj (Urawade), " . count($chunk3) . " to Praj (Bhor).\n";

    // Check or create department for SunPharma Thane (Unit 8)
    $dept8 = $db->query("SELECT id FROM departments WHERE unit_id = 8 LIMIT 1")->fetchColumn();
    if (!$dept8) {
        $db->exec("INSERT INTO departments (name, code, unit_id, location) VALUES ('Formulations (Thane)', 'SUN-THN', 8, 'Thane, Mumbai')");
        $dept8 = $db->lastInsertId();
    }

    $dept7 = $db->query("SELECT id FROM departments WHERE unit_id = 7 LIMIT 1")->fetchColumn();

    // Distribute 20 SunPharma employees: 10 to SunPharma Pune (Unit 7), 10 to SunPharma Thane (Unit 8)
    $sunChunk1 = array_slice($sunEmps, 0, 10);
    $sunChunk2 = array_slice($sunEmps, 10);

    if ($dept7 && count($sunChunk1) > 0) {
        $inSun1 = implode(',', $sunChunk1);
        $db->exec("UPDATE users SET department_id = $dept7 WHERE id IN ($inSun1)");
    }
    if (count($sunChunk2) > 0) {
        $inSun2 = implode(',', $sunChunk2);
        $db->exec("UPDATE users SET department_id = $dept8 WHERE id IN ($inSun2)");
    }

    echo "Reassigned SunPharma employees: 10 to SunPharma (Pune), " . count($sunChunk2) . " to SunPharma (Thane).\n";

    // Sync changes to data/hr-data.json
    require_once __DIR__ . '/sync_db_to_json.php';

    echo "\n=== REBALANCING COMPLETED SUCCESSFULLY! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
