<?php
function resolveDb(string $companyIdentifier): PDO {

    $dbName = strtolower($companyIdentifier) . '_dbx';

    $dsn = "mysql:host=localhost;dbname=$dbName;charset=utf8mb4";

    try {
        return new PDO($dsn, "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        throw new Exception("Unknown company, cID:" . $dbName);
    }
}
