<?php
require_once __DIR__ . '/../php-backend/config/database.php';

try {
    $db = getDB();
    echo "=== SYNCING DB hrconnect_clone TO data/hr-data.json ===\n";

    // Read existing JSON file to preserve non-user keys if any
    $jsonFile = __DIR__ . '/../data/hr-data.json';
    $jsonContent = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

    // Fetch all 615 users from MySQL database hrconnect_clone
    $stmt = $db->query("SELECT * FROM users ORDER BY id ASC");
    $dbUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Fetched " . count($dbUsers) . " users from MySQL database.\n";

    $formattedUsers = [];
    foreach ($dbUsers as $u) {
        $formattedUsers[] = [
            'id' => (int)$u['id'],
            'employeeId' => $u['employee_id'] ?? ("EMP" . str_pad((string)$u['id'], 3, '0', STR_PAD_LEFT)),
            'username' => $u['username'],
            'password' => $u['password'],
            'email' => $u['email'],
            'firstName' => $u['first_name'],
            'lastName' => $u['last_name'],
            'dateOfBirth' => $u['date_of_birth'],
            'gender' => $u['gender'],
            'maritalStatus' => $u['marital_status'],
            'photoUrl' => $u['photo_url'],
            'role' => $u['role'] ?? 'employee',
            'departmentId' => $u['department_id'] ? (int)$u['department_id'] : null,
            'position' => $u['position'] ?? 'Employee',
            'joinDate' => $u['join_date'] ?? date('Y-m-d\TH:i:s.000\Z'),
            'workLocation' => $u['work_location'],
            'reportingTo' => $u['reporting_to'] ? (int)$u['reporting_to'] : null,
            'phoneNumber' => $u['phone_number'],
            'address' => $u['address'],
            'uanNumber' => $u['uan_number'],
            'esicNumber' => $u['esic_number'],
            'aadhaarCard' => $u['aadhaar_card'],
            'panCard' => $u['pan_card'],
            'employmentType' => $u['employment_type'] ?? 'permanent',
            'pfApplicable' => (bool)($u['pf_applicable'] ?? true),
            'esicApplicable' => (bool)($u['esic_applicable'] ?? true),
            'ptApplicable' => (bool)($u['pt_applicable'] ?? true),
            'incomeTaxApplicable' => (bool)($u['income_tax_applicable'] ?? false),
            'mlwfApplicable' => (bool)($u['mlwf_applicable'] ?? false),
            'overtimeApplicable' => (bool)($u['overtime_applicable'] ?? false),
            'bonusApplicable' => (bool)($u['bonus_applicable'] ?? false),
            'bankName' => $u['bank_name'],
            'bankAccountNumber' => $u['bank_account_number'],
            'bankIFSCCode' => $u['bank_ifsc_code'],
            'bankAccountType' => $u['bank_account_type'],
            'salary' => (int)($u['salary'] ?? 0),
            'isActive' => (bool)($u['is_active'] ?? true),
            'status' => $u['status'] ?? 'active',
            'customPermissions' => !empty($u['custom_permissions']) ? json_decode($u['custom_permissions'], true) : [],
            'documents' => !empty($u['documents']) ? json_decode($u['documents'], true) : []
        ];
    }

    $jsonContent['users'] = $formattedUsers;
    $jsonContent['currentUserId'] = max(array_column($formattedUsers, 'id')) + 1;

    // Fetch and export units from MySQL
    $dbUnits = $db->query("SELECT * FROM units ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $formattedUnits = array_map(function($u) {
        return [
            'id' => (int)$u['id'],
            'code' => $u['code'],
            'name' => $u['name'],
            'description' => $u['description'] ?? ''
        ];
    }, $dbUnits);
    $jsonContent['units'] = $formattedUnits;
    $jsonContent['currentUnitId'] = max(array_column($formattedUnits, 'id')) + 1;

    // Fetch and export departments from MySQL
    $dbDepts = $db->query("SELECT * FROM departments ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $formattedDepts = array_map(function($d) {
        return [
            'id' => (int)$d['id'],
            'name' => $d['name'],
            'code' => $d['code'],
            'manager' => $d['manager'] ?? null,
            'location' => $d['location'] ?? null,
            'description' => $d['description'] ?? null,
            'unitId' => $d['unit_id'] ? (int)$d['unit_id'] : null
        ];
    }, $dbDepts);
    $jsonContent['departments'] = $formattedDepts;
    $jsonContent['currentDepartmentId'] = max(array_column($formattedDepts, 'id')) + 1;

    // Save updated JSON
    file_put_contents($jsonFile, json_encode($jsonContent, JSON_PRETTY_PRINT));
    echo "Successfully updated data/hr-data.json with " . count($formattedUsers) . " users, " . count($formattedUnits) . " units, and " . count($formattedDepts) . " departments!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
