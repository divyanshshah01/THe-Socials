<?php
/**
 * ============================================================
 * THE SOCIALS — Join Club API Endpoint
 * Secure, AJAX-ready membership handler using PDO
 * ============================================================
 */

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed. Only POST requests are permitted.'
    ]);
    exit;
}

session_start();

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');

// Include database configuration
require_once __DIR__ . '/config.php';

// Check auth session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Please log in first to join a club.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// ── Parse Input Data ────────────────────────────────────────
// Handle both raw JSON POST (fetch) and traditional x-www-form-urlencoded
$inputData = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($contentType, 'application/json') !== false) {
    $rawPayload = file_get_contents('php://input');
    $inputData = json_decode($rawPayload, true) ?? [];
} else {
    $inputData = $_POST;
}

$joinCode = isset($inputData['join_code']) ? trim($inputData['join_code']) : '';

// Validate basic inputs
if (empty($joinCode)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid join code provided.'
    ]);
    exit;
}

try {
    // ── 1. Verify Join Code and Fetch Club Details ───────────
    $stmt = $pdo->prepare("SELECT id, club_name FROM clubs WHERE join_code = :join_code LIMIT 1");
    $stmt->execute([':join_code' => $joinCode]);
    $club = $stmt->fetch();

    if (!$club) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid join code provided.'
        ]);
        exit;
    }

    $clubId = $club['id'];

    // ── 2. Check if membership already exists ────────────────
    $checkStmt = $pdo->prepare("SELECT id FROM memberships WHERE user_id = :user_id AND club_id = :club_id LIMIT 1");
    $checkStmt->execute([
        ':user_id' => $userId,
        ':club_id' => $clubId
    ]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode([
            'status' => 'error',
            'message' => 'You are already a member of this club.'
        ]);
        exit;
    }

    // ── 3. Create Membership ─────────────────────────────────
    $insertStmt = $pdo->prepare("
        INSERT INTO memberships (user_id, club_id, role, joined_at)
        VALUES (:user_id, :club_id, 'member', NOW())
    ");
    $insertStmt->execute([
        ':user_id' => $userId,
        ':club_id' => $clubId
    ]);

    // Success response
    http_response_code(201); // Created
    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully joined the club.'
    ]);
    exit;

} catch (PDOException $e) {
    // Log db errors server-side, hide trace details from public API
    error_log("Database error in join_club.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal database error occurred. Please try again later.'
    ]);
    exit;
}
