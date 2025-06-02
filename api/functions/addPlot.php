<?php
// addPlot.php
// Receives JSON { lat, lng, name, size } and inserts a new plot record

require_once("../db/resolve.php");
header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Authenticate via tsid cookie
if (!isset($_COOKIE['tsid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}
$tsid = $_COOKIE['tsid'];
$company = $_COOKIE['cmp'] ?? '';

// Decode JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Extract and validate
$lat = filter_var($input['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($input['lng'] ?? null, FILTER_VALIDATE_FLOAT);
$name = trim($input['name'] ?? '');
$size = filter_var($input['size'] ?? null, FILTER_VALIDATE_FLOAT);

if ($lat === false || $lng === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}
if ($size === false || $size < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid area size']);
    exit;
}
if (strlen($name) < 1 || strlen($name) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Area name must be 1-100 characters']);
    exit;
}

try {
    // Connect to company database
    $pdo = resolveDb($company);
    // Lookup current user ID by tsid
    $stmt = $pdo->prepare("SELECT id, firstname, lastname FROM users WHERE tsid = ?");
    $stmt->execute([$tsid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    $createdBy = ucfirst($user['firstname']) . " " . ucfirst($user['lastname']);

    // Insert new plot
    $insert = $pdo->prepare(
        "INSERT INTO plots (name, area, gps_lat, gps_lon, created_by) VALUES (?, ?, ?, ?, ?)"
    );
    $insert->execute([$name, $size, $lat, $lng, $createdBy]);

    echo json_encode([
        'success' => true,
        'plot_id' => $pdo->lastInsertId(),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
