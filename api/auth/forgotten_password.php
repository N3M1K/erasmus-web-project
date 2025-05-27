<?php
require_once('../utils/reset_pwd_token.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once("../db/resolve.php"); // připojí resolveDb()


$data = json_decode(file_get_contents('php://input'), true);

$user = $data['username'] ?? '';
$company = $data['companyId'] ?? '';

if (!$user || !$company) {
    http_response_code(401);
    exit("Missing input");
}


try {
    // Připojení k DB podle firmy
    $pdo = resolveDb($company);

    // Získání e-mailu podle loginu
    $stmt = $pdo->prepare("SELECT tsid, email FROM users WHERE username = ?");
    $stmt->execute([$user]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
        http_response_code(400);
        echo json_encode(['error' => 'User not found!']);
        exit("❌ User not found");
    }

    $email = $userRow['email'];
    $userId = $userRow['tsid'];

    // Vygeneruj token
    $token = generateResetToken($company);


    // Ulož nový token
    $insert = $pdo->prepare("UPDATE users SET token = ? WHERE tsid = ? AND username = ?");
    $insert->execute([$token, $userId, $user]);

    // Vytvoř URL pro reset
    $resetUrl = $_SERVER['HTTP_HOST'] . "/er-w-p/erasmus_web_project-panel/forgotten_password/reset_password.php?token=" . urlencode($token);

    // Vytvoř a odešli e-mail
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.forpsi.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contact@xxdev.cz';
    $mail->Password   = 'nE4pQ4Ts@K'; //!!!!!!!!!!! .env !!!!!!!!!!!!
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('contact@xxdev.cz', 'xxdev.cz');
    $mail->addAddress($email, $user);

    $mail->isHTML(true);
    $mail->Subject = 'Reset your password - E-Agronomist';
    $mail->Body    = "<p>Hello <b>$user</b>,</p>
        <p>We received a request to reset your password. Click the link below to proceed:</p>
        <p><a href=http://" . $resetUrl . ">Reset your password</a></p>
        <p>This link will expire in 15 minutes. If you didn’t request a reset, you can safely ignore this message.</p>";
    $mail->AltBody = "Hello $user,\n\nReset your password using this link:\n$resetUrl\n\nThis link expires in 15 minutes.";

    $mail->send();
    echo "✅ Password reset link sent to $email";
} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage();
}
