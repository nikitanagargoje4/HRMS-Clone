<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hrconnect_clone;charset=utf8mb4', 'root', '');
$stmt = $pdo->prepare('SELECT id, username, email, password, role FROM users WHERE email LIKE ? OR username LIKE ?');
$stmt->execute(['%nk%', '%nk%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "FOUND USERS WITH 'nk':\n";
foreach ($rows as $row) {
    echo "ID: {$row['id']} | Username: {$row['username']} | Email: {$row['email']} | Hash: {$row['password']}\n";
    $testPw = "Admin@1234";
    if (password_verify($testPw, $row['password'])) {
        echo "  --> Password 'Admin@1234' MATCHES for {$row['email']}\n";
    } else {
        echo "  --> Password 'Admin@1234' does NOT match\n";
    }
}
