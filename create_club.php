<?php
/**
 * ============================================================
 * THE SOCIALS — Create Club API Endpoint
 * Verifies session, validates input, inserts club & membership
 * ============================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
    exit;
}

// Authenticate session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in first.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Include configuration
require_once __DIR__ . '/config.php';

// Parse input
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$inputData = [];

if (stripos($contentType, 'application/json') !== false) {
    $rawPayload = file_get_contents('php://input');
    $inputData = json_decode($rawPayload, true) ?? [];
} else {
    $inputData = $_POST;
}

$clubName = isset($inputData['club_name']) ? trim($inputData['club_name']) : '';
$joinCode = isset($inputData['join_code']) ? trim($inputData['join_code']) : '';

// Validate fields
if (empty($clubName)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Club Name is required.']);
    exit;
}

if (empty($joinCode)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Join Code is required.']);
    exit;
}

// Clean up Join Code to prevent spaces/special characters if desired (e.g. uppercase, alphanumeric & hyphens)
$joinCode = strtoupper(preg_replace('/[^a-zA-Z0-9\-]/', '', $joinCode));

if (strlen($joinCode) < 3 || strlen($joinCode) > 20) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Join Code must be between 3 and 20 alphanumeric characters/hyphens.']);
    exit;
}

try {
    // ── 1. Check if join_code already exists ──────────────────
    $checkStmt = $pdo->prepare("SELECT id FROM clubs WHERE join_code = :join_code LIMIT 1");
    $checkStmt->execute([':join_code' => $joinCode]);

    if ($checkStmt->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'This Join Code is already in use. Please choose another.']);
        exit;
    }

    // ── 2. Create the club ────────────────────────────────────
    $insertClub = $pdo->prepare("
        INSERT INTO clubs (club_name, join_code, created_at)
        VALUES (:club_name, :join_code, NOW())
    ");
    $insertClub->execute([
        ':club_name' => $clubName,
        ':join_code' => $joinCode,
    ]);

    $clubId = $pdo->lastInsertId();

    // ── 3. Create Membership (Role: Admin) ────────────────────
    $insertMembership = $pdo->prepare("
        INSERT INTO memberships (user_id, club_id, role, joined_at)
        VALUES (:user_id, :club_id, 'admin', NOW())
    ");
    $insertMembership->execute([
        ':user_id' => $userId,
        ':club_id' => $clubId,
    ]);

    // Success response
    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Club "' . htmlspecialchars($clubName) . '" successfully created with code ' . $joinCode . '.',
        'club' => [
            'id' => $clubId,
            'club_name' => $clubName,
            'join_code' => $joinCode,
            'role' => 'admin'
        ]
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database error in create_club.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
