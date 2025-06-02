<?php
require_once("../db/resolve.php");
require_once("../utils/tsid.php");

header("Content-Type: application/json");

$company = $_POST['company'] ?? '';
$username = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';

try {
    try {
        $pdo = resolveDb($company);

    } catch (Exception $e) {
        http_response_code(401);
        exit("Company does not exist");
    }
   

    $stmt = $pdo->prepare("SELECT tsid, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Úspěšné přihlášení
        $tsid = generateTsid();

        $insert = $pdo->prepare("UPDATE users SET tsid = ?, last_login = NOW() WHERE username = ?");
        $insert->execute([$tsid, $username]);

        setcookie("tsid", $tsid, [
            'expires' => time() + 3600 * 48, // 2 dny
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        // Reset login tries & status
        setcookie("login_tries", '', time() - 3600, "/");
        setcookie("login_status", "ok", [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);
        setcookie("cmp", $company, [
            'expires' => time() + 31556952,
            'path' => '/',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);

        http_response_code(200);
        exit("ok");

        //header("Location: ../../../erasmus_web_project-panel/dashboard.php");
        } else {
        // ! Neúspěšné přihlášení
        $tries = isset($_COOKIE['login_tries']) ? (int)$_COOKIE['login_tries'] + 1 : 1;

        setcookie("login_tries", $tries, [
            'expires' => time() + 360, // 6 hours
            'path' => '/',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);

        setcookie("login_status", "fail", [
            'expires' => time() + 600,
            'path' => '/er-w-p',
            'secure' => false,
            'httponly' => false,
            'samesite' => 'Strict',
        ]);

        echo json_encode(['status' => 'fail', 'tries' => $tries]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
