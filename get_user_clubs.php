<?php
/**
 * ============================================================
 * THE SOCIALS — Retrieve User's Clubs API Endpoint
 * Verifies session, fetches active memberships and roles
 * ============================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
    exit;
}

// Authenticate session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Include configuration
require_once __DIR__ . '/config.php';

try {
    // Fetch all clubs the user is a member of
    $stmt = $pdo->prepare("
        SELECT c.id, c.club_name, c.join_code, c.theme_color, m.role, m.joined_at
        FROM memberships m
        INNER JOIN clubs c ON m.club_id = c.id
        WHERE m.user_id = :user_id
        ORDER BY m.joined_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $clubs = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'clubs' => $clubs
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database error in get_user_clubs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
