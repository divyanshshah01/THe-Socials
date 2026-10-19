<?php
/**
 * ============================================================
 * THE SOCIALS — Voice GC Coordination API
 * Manages active voice presence, mute states, and active users
 * ============================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Authenticate session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Include configuration
require_once __DIR__ . '/config.php';

// Parse input
$method = $_SERVER['REQUEST_METHOD'];
$inputData = [];

if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $rawPayload = file_get_contents('php://input');
        $inputData = json_decode($rawPayload, true) ?? [];
    } else {
        $inputData = $_POST;
    }
} else {
    $inputData = $_GET;
}

$clubId = isset($inputData['club_id']) ? intval($inputData['club_id']) : 0;
$action = isset($inputData['action']) ? trim($inputData['action']) : 'poll'; // join, leave, mute, poll

if ($clubId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid Club ID is required.']);
    exit;
}

try {
    // ── Garbage Collection: Remove inactive voice users ──────
    // Users who haven't updated their heartbeat in 12 seconds are removed
    $pdo->exec("DELETE FROM voice_channel_users WHERE updated_at < (NOW() - INTERVAL 12 SECOND)");

    // ── Execute Actions ──────────────────────────────────────
    if ($action === 'join') {
        // Upsert presence in this voice room
        $stmt = $pdo->prepare("
            INSERT INTO voice_channel_users (user_id, club_id, is_muted, updated_at)
            VALUES (:user_id, :club_id, 0, NOW())
            ON DUPLICATE KEY UPDATE club_id = :club_id_up, is_muted = 0, updated_at = NOW()
        ");
        $stmt->execute([
            ':user_id'     => $userId,
            ':club_id'     => $clubId,
            ':club_id_up'  => $clubId
        ]);

    } elseif ($action === 'leave') {
        // Remove presence
        $stmt = $pdo->prepare("DELETE FROM voice_channel_users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);

    } elseif ($action === 'mute') {
        $isMuted = isset($inputData['is_muted']) ? intval($inputData['is_muted']) : 0;
        
        // Update mute state
        $stmt = $pdo->prepare("
            UPDATE voice_channel_users
            SET is_muted = :is_muted, updated_at = NOW()
            WHERE user_id = :user_id AND club_id = :club_id
        ");
        $stmt->execute([
            ':is_muted' => $isMuted,
            ':user_id'  => $userId,
            ':club_id'  => $clubId
        ]);

    } elseif ($action === 'heartbeat') {
        // Keep user active (called during voice polling)
        $stmt = $pdo->prepare("
            UPDATE voice_channel_users
            SET updated_at = NOW()
            WHERE user_id = :user_id AND club_id = :club_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':club_id' => $clubId
        ]);
    }

    // ── Retrieve Active Callers ──────────────────────────────
    // Query users in this club's voice channel
    $stmt = $pdo->prepare("
        SELECT v.user_id, u.full_name, v.is_muted
        FROM voice_channel_users v
        INNER JOIN users u ON v.user_id = u.id
        WHERE v.club_id = :club_id
    ");
    $stmt->execute([':club_id' => $clubId]);
    $activeCallers = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'callers' => $activeCallers
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database error in sync_voice.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An internal database error occurred.']);
    exit;
}
