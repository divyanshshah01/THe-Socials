<?php
/**
 * ============================================================
 * THE SOCIALS — Retrieve Club Posts API
 * Fetches all media wall posts for a specific club
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
$clubId = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;

if ($clubId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid Club ID is required.']);
    exit;
}

require_once __DIR__ . '/config.php';

try {
    // Verify membership
    $verifyStmt = $pdo->prepare("SELECT id FROM memberships WHERE user_id = :user_id AND club_id = :club_id LIMIT 1");
    $verifyStmt->execute([':user_id' => $userId, ':club_id' => $clubId]);
    if (!$verifyStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You are not a member of this club.']);
        exit;
    }

    // Retrieve posts
    $stmt = $pdo->prepare("
        SELECT p.id, p.user_id, u.full_name as author_name, p.media_path, p.media_type, p.caption, p.created_at
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.club_id = :club_id
        ORDER BY p.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([':club_id' => $clubId]);
    $posts = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'posts'  => $posts
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database error in get_posts.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
