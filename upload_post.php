<?php
/**
 * ============================================================
 * THE SOCIALS — Upload Post API
 * Handles file validation, transfers media, saves post in DB
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

// Parse text inputs from POST
$clubId  = isset($_POST['club_id']) ? intval($_POST['club_id']) : 0;
$caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';

if ($clubId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid Club ID is required.']);
    exit;
}

// Verify club membership
require_once __DIR__ . '/config.php';

try {
    $verifyStmt = $pdo->prepare("SELECT id FROM memberships WHERE user_id = :user_id AND club_id = :club_id LIMIT 1");
    $verifyStmt->execute([':user_id' => $userId, ':club_id' => $clubId]);
    if (!$verifyStmt->fetch()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You are not a member of this club.']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database verification failed.']);
    exit;
}

// ── Check File Upload ───────────────────────────────────────
if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['media']['error'] ?? -1;
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No media file uploaded or upload error occurred. Error Code: ' . $errCode]);
    exit;
}

$file = $_FILES['media'];

// 1. Max size: 10MB
$maxSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File exceeds maximum limit of 10MB.']);
    exit;
}

// 2. Validate Extensions & Mime-type
$allowedExtensions = [
    // Photos
    'jpg'  => 'photo',
    'jpeg' => 'photo',
    'png'  => 'photo',
    'gif'  => 'photo',
    // Videos
    'mp4'  => 'video',
    'webm' => 'video'
];

$fileName = $file['name'];
$fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!array_key_exists($fileExt, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid file format. Allowed formats: JPG, PNG, GIF, MP4, WEBM.']);
    exit;
}

$mediaType = $allowedExtensions[$fileExt];

// 3. Move File to Uploads
$uploadDir = __DIR__ . '/uploads/';

// Create directory if not exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename to prevent collisions/overwriting
$newFileName = uniqid('post_', true) . '.' . $fileExt;
$destPath    = $uploadDir . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file.']);
    exit;
}

// Relative path for database
$relativeMediaPath = 'uploads/' . $newFileName;

// 4. Save to Database
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO posts (user_id, club_id, media_path, media_type, caption, created_at)
        VALUES (:user_id, :club_id, :media_path, :media_type, :caption, NOW())
    ");
    $insertStmt->execute([
        ':user_id'    => $userId,
        ':club_id'    => $clubId,
        ':media_path' => $relativeMediaPath,
        ':media_type' => $mediaType,
        ':caption'    => $caption
    ]);

    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Post successfully published!',
        'post' => [
            'id' => $pdo->lastInsertId(),
            'media_path' => $relativeMediaPath,
            'media_type' => $mediaType,
            'caption' => $caption
        ]
    ]);
    exit;

} catch (PDOException $e) {
    // Delete file if DB insert fails
    if (file_exists($destPath)) {
        unlink($destPath);
    }
    error_log("Database error in upload_post.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save post to database.']);
    exit;
}
