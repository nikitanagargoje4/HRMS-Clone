<?php
require_once __DIR__ . '/../php-backend/config/database.php';

try {
    $pdo = getDB();
    echo "=== USER SEARCH FOR nk@asn.com ===\n";
    $stmt = $pdo->prepare("SELECT id, username, email, password, role, is_active, status FROM users WHERE email = ? OR username = ?");
    $stmt->execute(['nk@asn.com', 'nk@asn.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "USER NOT FOUND by email/username nk@asn.com!\n";
        echo "\nSearching for similar emails or users...\n";
        $stmt2 = $pdo->query("SELECT id, username, email, role FROM users WHERE email LIKE '%nk%' OR username LIKE '%nk%' LIMIT 10");
        $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        print_r($results);
    } else {
        echo "User Found:\n";
        print_r($user);

        $inputPw = 'Admin@1234';
        $hash = $user['password'];
        echo "\nTesting password_verify('$inputPw', '$hash')...\n";
        if (password_verify($inputPw, $hash)) {
            echo "MATCH: password_verify SUCCESSFUL!\n";
        } else {
            echo "MISMATCH: password_verify FAILED!\n";
            echo "Current Hash in DB: " . $hash . "\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
