<?php
require_once('../utils/tsid.php');  // for generateTsid()

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'error', 'error' => 'Method not allowed']));
}

// 1. Get & validate company ID from cookie
if (empty($_COOKIE['cmp']) || !preg_match('/^[a-zA-Z0-9_-]+$/', $_COOKIE['cmp'])) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'error' => 'Invalid company identifier']));
}
$companyId = $_COOKIE['cmp'];
$dbName = strtolower($companyId) . '_dbx';

// 2. Collect & validate form inputs
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPass = $_POST['confirmPassword'] ?? '';
$email = trim($_POST['email'] ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');



if (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username)) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'error' => 'Username must be 3–32 characters: letters, numbers, ., _, –']));
}

if ($password === '' || strlen($password) < 6) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'error' => 'Password must be at least 6 characters']));
}

if ($password !== $confirmPass) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'error' => 'Passwords do not match']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'error' => 'Invalid email address']));
}

if (
    !preg_match('/^[\p{L}\'\- ]{1,50}$/u', $firstName) ||
    !preg_match('/^[\p{L}\'\- ]{1,50}$/u', $lastName)
) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'error' => 'Invalid first or last name']));
}

try {
    // 3. Connect to the company database
    $dsn = "mysql:host=localhost;dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 4. Ensure username/email aren’t already taken
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409);
        exit(json_encode(['status' => 'error', 'error' => 'Username or email already in use']));
    }

    // 5. Insert new user
    $tsid = generateTsid();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (
            tsid, username, password, email, firstname, lastname,
            created_date, active, enabled, terms, photo_path
        ) VALUES (
            ?, ?, ?, ?, ?, ?, NOW(), 1, 1, 1, ?
        )
    ");
    $stmt->execute([
        $tsid,
        $username,
        $hash,
        $email,
        $firstName,
        $lastName,
        $thumbnailUrl
    ]);

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (PDOException $e) {
    // log $e->getMessage() somewhere secure
    http_response_code(500);
    exit(json_encode(['status' => 'error', 'error' => 'Database error']));
}
