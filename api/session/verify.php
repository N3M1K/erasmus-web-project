<?php
require_once("../db/resolve.php");

$company = $_GET['company'] ?? '';
$tsid = $_COOKIE['tsid'] ?? '';

try {
    $pdo = resolveDb($company);

    $stmt = $pdo->prepare("SELECT user_id FROM sessions WHERE tsid = ?");
    $stmt->execute([$tsid]);
    $userId = $stmt->fetchColumn();

    echo json_encode(['valid' => (bool)$userId, 'user_id' => $userId]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
