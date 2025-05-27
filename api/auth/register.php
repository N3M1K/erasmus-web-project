<?php
require_once('../utils/tsid.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyId = $_POST['company_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $firstname = $_POST['firstname'];
    $surname = $_POST['surname'];

    if (!preg_match('/^[a-z0-9_.]+$/i', $companyId)) {
        exit("Unauthorised characters in the company id");
    }

    if (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username)) {
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
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tsid VARCHAR(255),
                username VARCHAR(255),
                firstname VARCHAR(255),
                lastname VARCHAR(255),
                email VARCHAR(255),
                password VARCHAR(255),
                terms TINYINT(1),
                active TINYINT(1),
                locked TINYINT(1),
                photo_path VARCHAR(255),
                logged_date DATETIME(6),
                last_login DATETIME(6),
                login_failed_attempts SMALLINT,
                enabled TINYINT(1),
                created_date DATETIME(6),
                created_by BIGINT,
                updated_date DATETIME(6),
                updated_by BIGINT,
                token VARCHAR(255) DEFAULT NULL
            ) ENGINE=InnoDB;
        ");


        // 4. Uložení admina
        $tsid = generateTsid();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (
                tsid, username, password, email, firstname, lastname, created_date, active, enabled, terms
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, 1, 1)
        ");
        $stmt->execute([$tsid, $username, $hash, $email, $firstname, $surname]);

        echo "Company '$companyId' succesfully registrated.";
    } catch (PDOException $e) {
        exit("Error: " . $e->getMessage());
    }
}
