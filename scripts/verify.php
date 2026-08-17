<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=hrconnect_clone;charset=utf8mb4", "root", "");
echo "CONNECTED_TO_DB: hrconnect_clone\n";
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "USERS_COUNT: $userCount\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "TOTAL_TABLES: " . count($tables) . "\n";
echo "TABLES: " . implode(", ", $tables) . "\n";
