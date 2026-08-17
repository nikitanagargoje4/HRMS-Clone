<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Creating database `hrconnect_clone` if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `hrconnect_clone` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    // Get list of tables from hrconnect
    $stmt = $pdo->query("SHOW TABLES FROM `hrconnect`");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found " . count($tables) . " tables in `hrconnect`.\n";

    // Disable foreign key checks for table creation and insertion
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    foreach ($tables as $table) {
        echo "Cloning table: $table ... ";
        
        // Drop table in clone DB if exists
        $pdo->exec("DROP TABLE IF EXISTS `hrconnect_clone`.`$table`");
        
        // Get CREATE TABLE SQL
        $createStmt = $pdo->query("SHOW CREATE TABLE `hrconnect`.`$table`")->fetch(PDO::FETCH_ASSOC);
        $createSql = $createStmt['Create Table'];
        
        // Create table in clone DB
        $pdo->exec("USE `hrconnect_clone`");
        $pdo->exec($createSql);
        
        // Copy data from source to target
        $pdo->exec("INSERT INTO `hrconnect_clone`.`$table` SELECT * FROM `hrconnect`.`$table`");

        $countStmt = $pdo->query("SELECT COUNT(*) FROM `hrconnect_clone`.`$table`")->fetchColumn();
        echo "Done ($countStmt rows).\n";
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\nDatabase cloning successfully completed!\n";

    // Count total users in clone database
    $userCount = $pdo->query("SELECT COUNT(*) FROM `hrconnect_clone`.`users`")->fetchColumn();
    echo "Total users in `hrconnect_clone`.`users`: $userCount\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
