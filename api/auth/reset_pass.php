<?php
require_once('../db/resolve.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use regular POST data from FormData
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validate inputs
    if (!$token || !$password || !$confirmPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields.']);
        exit();
    }

    // Check passwords match
    if ($password !== $confirmPassword) {
        http_response_code(400);
        echo json_encode(['error' => 'Passwords do not match.']);
        exit();
    }

    // Decode token to get company and optionally verify
    $decoded = decodeResetToken($token);
    if (!$decoded) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid token format.']);
        exit();
    }
    list($companyId, $createdAt) = $decoded;

    // Optional: check token expiration
    if (time() > strtotime($createdAt) + 3600) {
        http_response_code(400);
        echo json_encode(['error' => 'Token expired.']);
        exit();
    }

    // Hash the new password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo = resolveDb($companyId);
        // Update user by matching token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, token = NULL WHERE token = ?");
        $stmt->execute([$hash, $token]);

        echo json_encode(['message' => 'Password has been reset successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
    exit();
}

/**
 * Decode the reset token into companyId and timestamp
 */
function decodeResetToken(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3)
        return null;

    list($encodedCompanyId, $randomHex, $encodedTimestamp) = $parts;
    $companyId = base64_decode($encodedCompanyId, true);
    $createdAt = base64_decode($encodedTimestamp, true);
    if ($companyId === false || $createdAt === false)
        return null;

    return [$companyId, $createdAt];
}
