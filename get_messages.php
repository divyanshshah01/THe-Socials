<?php
/**
 * ============================================================
 * THE SOCIALS — Retrieve Messages API
 * Fetches general chat logs or direct messages (DMs)
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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Include configuration
require_once __DIR__ . '/config.php';

$clubId        = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;
$recipientId   = isset($_GET['recipient_id']) ? intval($_GET['recipient_id']) : 0;
$lastMessageId = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;

// Must specify either club_id or recipient_id
if ($clubId <= 0 && $recipientId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Either club_id or recipient_id is required.']);
    exit;
}

try {
    if ($clubId > 0) {
        // ── 1. General Club Chat ─────────────────────────────
        // Verify membership first
        $verify = $pdo->prepare("SELECT id FROM memberships WHERE user_id = :user_id AND club_id = :club_id LIMIT 1");
        $verify->execute([':user_id' => $userId, ':club_id' => $clubId]);
        if (!$verify->fetch()) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden. Not a member.']);
            exit;
        }

        // Fetch messages
        $stmt = $pdo->prepare("
            SELECT m.id, m.sender_id, u.full_name as sender_name, m.message_text, m.created_at
            FROM messages m
            INNER JOIN users u ON m.sender_id = u.id
            WHERE m.club_id = :club_id AND m.id > :last_id
            ORDER BY m.id ASC
            LIMIT 100
        ");
        $stmt->execute([
            ':club_id' => $clubId,
            ':last_id' => $lastMessageId
        ]);
        $messages = $stmt->fetchAll();

    } else {
        // ── 2. Direct Messages (DMs) ─────────────────────────
        // Fetch private messages between current user and the recipient
        $stmt = $pdo->prepare("
            SELECT m.id, m.sender_id, u.full_name as sender_name, m.message_text, m.created_at
            FROM messages m
            INNER JOIN users u ON m.sender_id = u.id
            WHERE (
              (m.sender_id = :user_id AND m.recipient_id = :recipient_id) OR
              (m.sender_id = :recipient_id AND m.recipient_id = :user_id)
            )
            AND m.club_id IS NULL
            AND m.id > :last_id
            ORDER BY m.id ASC
            LIMIT 100
        ");
        $stmt->execute([
            ':user_id'      => $userId,
            ':recipient_id' => $recipientId,
            ':last_id'      => $lastMessageId
        ]);
        $messages = $stmt->fetchAll();
    }

    http_response_code(200);
    echo json_encode([
        'status'   => 'success',
        'messages' => $messages
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database error in get_messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
