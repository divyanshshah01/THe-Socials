<?php
/**
 * ============================================================
 * THE SOCIALS — Get Club Members API
 * Verifies session, returns list of all members in the club
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

// Check session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$userId = $_SESSION['user_id'];
$clubId = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;

if ($clubId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid Club ID is required.']);
    exit;
}

require_once __DIR__ . '/config.php';

try {
    // Verify the requesting user is actually a member of the club
    $verifyStmt = $pdo->prepare("SELECT id FROM memberships WHERE user_id = :user_id AND club_id = :club_id LIMIT 1");
    $verifyStmt->execute([':user_id' => $userId, ':club_id' => $clubId]);
    if (!$verifyStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You are not a member of this club.']);
        exit;
    }

    // Retrieve all members of this club (including their names, emails, roles)
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, m.role, m.joined_at
        FROM memberships m
        INNER JOIN users u ON m.user_id = u.id
        WHERE m.club_id = :club_id
        ORDER BY u.full_name ASC
    ");
    $stmt->execute([':club_id' => $clubId]);
    $members = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'members' => $members
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database error in get_club_members.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
