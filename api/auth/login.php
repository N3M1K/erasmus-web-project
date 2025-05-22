<?php
require_once("../db/resolve.php");
require_once("../utils/tsid.php");

header("Content-Type: application/json");

$company = $_POST['company'] ?? '';
$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

try {
    $pdo = resolveDb($company);

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Úspěšné přihlášení
        $tsid = generateTsid();

        $insert = $pdo->prepare("INSERT INTO sessions (tsid, user_id, created_at) VALUES (?, ?, NOW())");
        $insert->execute([$tsid, $user['id']]);

        setcookie("tsid", $tsid, [
            'expires' => time() + 3600 * 168, // 1 týden
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        // Reset login tries & status
        setcookie("login_tries", '', time() - 3600, "/");
        setcookie("login_status", "ok", [
            'expires' => time() + 600,
            'path' => '/',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);

        echo json_encode(['status' => 'ok', 'user_id' => $user['id']]);
    } else {
        // Neúspěšné přihlášení
        $tries = isset($_COOKIE['login_tries']) ? (int)$_COOKIE['login_tries'] + 1 : 1;

        setcookie("login_tries", $tries, [
            'expires' => time() + 60 * 6, // 6 hours
            'path' => '/',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);

        setcookie("login_status", "fail", [
            'expires' => time() + 600,
            'path' => '/',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);

        echo json_encode(['status' => 'fail', 'tries' => $tries]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
