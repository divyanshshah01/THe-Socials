<?php
/**
 * ============================================================
 * THE SOCIALS — Send Message API
 * Stores a general channel text or private DM in database
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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in.']);
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

$clubId      = isset($inputData['club_id']) ? intval($inputData['club_id']) : null;
$recipientId = isset($inputData['recipient_id']) ? intval($inputData['recipient_id']) : null;
$messageText = isset($inputData['message_text']) ? trim($inputData['message_text']) : '';

// Validate text
if (empty($messageText)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Message content cannot be empty.']);
    exit;
}

// Enforce that it is EITHER a club general message OR a DM
if (($clubId === null && $recipientId === null) || ($clubId !== null && $recipientId !== null)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Must specify either a Club ID (general chat) or a Recipient ID (DM), not both or neither.']);
    exit;
}

try {
    // ── Enforce Club Membership check ───────────────────────
    if ($clubId !== null) {
        $verifyStmt = $pdo->prepare("SELECT id FROM memberships WHERE user_id = :user_id AND club_id = :club_id LIMIT 1");
        $verifyStmt->execute([':user_id' => $userId, ':club_id' => $clubId]);
        if (!$verifyStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'You are not a member of this club.']);
            exit;
        }
    }

    // ── Enforce Shared Club Membership check for DMs ──────────
    if ($recipientId !== null) {
        // Ensure recipient exists
        $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = :recipient_id LIMIT 1");
        $userCheck->execute([':recipient_id' => $recipientId]);
        if (!$userCheck->fetch()) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Recipient user not found.']);
            exit;
        }

        // Verify sender and recipient share at least one club in common
        $commonClub = $pdo->prepare("
            SELECT m1.club_id
            FROM memberships m1
            INNER JOIN memberships m2 ON m1.club_id = m2.club_id
            WHERE m1.user_id = :sender_id AND m2.user_id = :recipient_id
            LIMIT 1
        ");
        $commonClub->execute([':sender_id' => $userId, ':recipient_id' => $recipientId]);
        if (!$commonClub->fetch()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'You can only message users with whom you share a club.']);
            exit;
        }
    }

    // ── Insert Message ───────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, club_id, recipient_id, message_text, created_at)
        VALUES (:sender_id, :club_id, :recipient_id, :message_text, NOW())
    ");
    $stmt->execute([
        ':sender_id'    => $userId,
        ':club_id'      => $clubId,
        ':recipient_id' => $recipientId,
        ':message_text' => $messageText
    ]);

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully.',
        'message_id' => $pdo->lastInsertId()
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database error in send_message.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
