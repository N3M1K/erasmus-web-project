<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId = $_POST['company_id'];
    $adminLogin = $_POST['admin_login'];
    $adminPassword = $_POST['admin_password'];

    if (!preg_match('/^[a-z0-9_.]+$/i', $companyId)) {
        exit("Unauthorised characters in the company id");
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $adminLogin)) {
        exit("Invalid administrator login.");
    }


    $dbName = strtolower($companyId) . '_dbx';
    $dsn = "mysql:host=localhost;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // 🔍 Ověření existence databáze
        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.schemata WHERE SCHEMA_NAME = ?");
        $stmt->execute([$dbName]);

        if ($stmt->fetch()) {
            exit("Company with this id already exists");
        }

        // 1. Vytvoření databáze
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

        // 2. Připojení k nové databázi
        $pdo->exec("USE `$dbName`");

        // 3. Vytvoření základních tabulek
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                login VARCHAR(50) UNIQUE,
                password_hash VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 4. Uložení admina
        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash) VALUES (?, ?)");
        $stmt->execute([$adminLogin, $hash]);

        echo "Company '$companyId' succesfully registrated.";
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
}
