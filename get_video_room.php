<?php
/**
 * ============================================================
 * THE SOCIALS — Get Video Room Endpoint
 * Returns a secure Jitsi room identifier for a club's
 * Virtual Lounge. Only verified club members may access.
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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId   = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Guest';
$clubId   = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;

if ($clubId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid Club ID is required.']);
    exit;
}

require_once __DIR__ . '/config.php';

try {
    // ── Verify the requesting user is a member of this club ──
    $verifyStmt = $pdo->prepare(
        "SELECT m.id, c.club_name
         FROM memberships m
         INNER JOIN clubs c ON m.club_id = c.id
         WHERE m.user_id = :user_id AND m.club_id = :club_id
         LIMIT 1"
    );
    $verifyStmt->execute([':user_id' => $userId, ':club_id' => $clubId]);
    $membership = $verifyStmt->fetch();

    if (!$membership) {
        http_response_code(403);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Access denied. You are not a member of this club.'
        ]);
        exit;
    }

    // ── Generate deterministic room name ──
    // Format: TheSocials-Club-{id}  (clean, URL-safe for Jitsi)
    $roomName = 'TheSocials-Club-' . $clubId;

    http_response_code(200);
    echo json_encode([
        'status'       => 'success',
        'room_name'    => $roomName,
        'display_name' => $fullName,
        'club_name'    => $membership['club_name']
    ]);
    exit;

} catch (PDOException $e) {
    error_log('Database error in get_video_room.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
