<?php
function resolveDb(string $companyIdentifier): PDO {
    if (!preg_match('/^[a-z0-9_.]+$/i', $companyIdentifier)) {
        throw new Exception("Unknown company");
    }

    $dbName = strtolower($companyIdentifier) . '_dbx';

    $dsn = "mysql:host=localhost;dbname=$dbName;charset=utf8mb4";

    try {
        return new PDO($dsn, "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        throw new Exception("Unknown company");
    }
}
