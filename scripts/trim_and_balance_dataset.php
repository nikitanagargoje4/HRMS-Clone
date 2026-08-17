<?php
require_once __DIR__ . '/../php-backend/config/database.php';

try {
    $db = getDB();
    echo "=== DATASET BALANCING & TRIMMING (TARGET ~150-160 USERS) ===\n\n";

    // Define target counts per email domain
    $domainLimits = [
        'cybaemtech.com'     => 25,
        'nexorasolutions.com'=> 20,
        'finolex.com'        => 35,
        'praj.in'            => 30,
        'titan.co.in'        => 25,
        'sunpharma.com'      => 20,
        'asn.com'            => 10,
        'hrms.demo'          => 10,
    ];

    $selectedUserIds = [];

    foreach ($domainLimits as $domain => $limit) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email LIKE ? ORDER BY id ASC LIMIT ?");
        $stmt->bindValue(1, "%@$domain", PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $selectedUserIds = array_merge($selectedUserIds, $ids);
        echo sprintf("Selected %d users for domain @%s\n", count($ids), $domain);
    }

    $selectedUserIds = array_unique($selectedUserIds);
    $totalSelected = count($selectedUserIds);
    echo "\nTotal Selected Users: $totalSelected\n";

    // Disable Foreign Key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Delete unselected users
    $inClause = implode(',', array_fill(0, count($selectedUserIds), '?'));
    $deleteStmt = $db->prepare("DELETE FROM users WHERE id NOT IN ($inClause)");
    $deleteStmt->execute(array_values($selectedUserIds));
    $deletedCount = $deleteStmt->rowCount();
    echo "Deleted $deletedCount unselected user records from `users` table.\n";

    // Clean orphaned related records
    $db->prepare("DELETE FROM attendance_records WHERE user_id NOT IN ($inClause)")->execute(array_values($selectedUserIds));
    $db->prepare("DELETE FROM leave_requests WHERE user_id NOT IN ($inClause)")->execute(array_values($selectedUserIds));
    $db->prepare("DELETE FROM payment_records WHERE employee_id NOT IN ($inClause)")->execute(array_values($selectedUserIds));
    $db->prepare("DELETE FROM notifications WHERE user_id NOT IN ($inClause)")->execute(array_values($selectedUserIds));

    // Re-enable Foreign Key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Populate company_masters with 6 companies
    $db->exec("TRUNCATE TABLE company_masters");
    $companies = [
        ['company_code' => 'CYB001', 'company_name' => 'Cybaem Tech Pvt Ltd', 'address' => 'Baner, Pune', 'state' => 'Maharashtra', 'pin_code' => '411045', 'email' => 'contact@cybaemtech.com', 'nature_of_business' => 'IT Services & Software'],
        ['company_code' => 'NXR001', 'company_name' => 'Nexora Solutions Pvt Ltd', 'address' => 'Viman Nagar, Pune', 'state' => 'Maharashtra', 'pin_code' => '411014', 'email' => 'info@nexorasolutions.com', 'nature_of_business' => 'Cloud & AI Solutions'],
        ['company_code' => 'FINO001', 'company_name' => 'Finolex Cables Ltd', 'address' => 'Pimpri, Pune', 'state' => 'Maharashtra', 'pin_code' => '411018', 'email' => 'hr@finolex.com', 'nature_of_business' => 'Manufacturing & Cables'],
        ['company_code' => 'PRAJ001', 'company_name' => 'Praj Industries Ltd', 'address' => 'Hinjewadi, Pune', 'state' => 'Maharashtra', 'pin_code' => '411057', 'email' => 'info@praj.in', 'nature_of_business' => 'Process & Plant Engineering'],
        ['company_code' => 'TITA001', 'company_name' => 'Titan Company Ltd', 'address' => 'Koregaon Park, Pune', 'state' => 'Maharashtra', 'pin_code' => '411001', 'email' => 'support@titan.co.in', 'nature_of_business' => 'Retail & Lifestyle'],
        ['company_code' => 'SUNP001', 'company_name' => 'Sun Pharmaceutical Industries', 'address' => 'Thane West, Mumbai', 'state' => 'Maharashtra', 'pin_code' => '400601', 'email' => 'careers@sunpharma.com', 'nature_of_business' => 'Pharmaceuticals']
    ];

    $compStmt = $db->prepare("
        INSERT INTO company_masters (company_code, company_name, address, state, pin_code, email, nature_of_business)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($companies as $c) {
        $compStmt->execute([$c['company_code'], $c['company_name'], $c['address'], $c['state'], $c['pin_code'], $c['email'], $c['nature_of_business']]);
    }
    echo "Populated `company_masters` table with " . count($companies) . " companies.\n";

    // Sync updated users to data/hr-data.json
    require_once __DIR__ . '/sync_db_to_json.php';

    echo "\n=== DATASET BALANCING COMPLETED SUCCESSFULLY! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
