<?php
/**
 * ============================================================
 * THE SOCIALS — User Registration Endpoint
 * Accepts AJAX POST, hashes password, auto-joins club
 * ============================================================
 */

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

// ── Parse Input ─────────────────────────────────────────────
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$input = [];

if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$fullName  = isset($input['full_name']) ? trim($input['full_name']) : '';
$email     = isset($input['email'])     ? trim(strtolower($input['email'])) : '';
$password  = isset($input['password'])  ? $input['password'] : '';
$joinCode  = isset($input['join_code']) ? trim($input['join_code']) : '';

// ── Validate ────────────────────────────────────────────────
if (empty($fullName)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Full name is required.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'A valid email address is required.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
    exit;
}

try {
    // ── 1. Check if email already exists ─────────────────────
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $checkStmt->execute([':email' => $email]);

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists.']);
        exit;
    }

    // ── 2. Verify join code if provided ──────────────────────
    $club = null;
    if (!empty($joinCode)) {
        $clubStmt = $pdo->prepare("SELECT id, club_name FROM clubs WHERE join_code = :join_code LIMIT 1");
        $clubStmt->execute([':join_code' => $joinCode]);
        $club = $clubStmt->fetch();

        if (!$club) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Invalid join code. Please check and try again.']);
            exit;
        }
    }

    // ── 3. Hash password (bcrypt) ────────────────────────────
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // ── 4. Insert user ──────────────────────────────────────
    $insertUser = $pdo->prepare("
        INSERT INTO users (full_name, email, password_hash, created_at)
        VALUES (:full_name, :email, :password_hash, NOW())
    ");
    $insertUser->execute([
        ':full_name'     => $fullName,
        ':email'         => $email,
        ':password_hash' => $passwordHash,
    ]);

    $userId = $pdo->lastInsertId();

    // ── 5. Auto-join the club if join code was valid ─────────
    if ($club) {
        $insertMembership = $pdo->prepare("
            INSERT INTO memberships (user_id, club_id, role, joined_at)
            VALUES (:user_id, :club_id, 'member', NOW())
        ");
        $insertMembership->execute([
            ':user_id' => $userId,
            ':club_id' => $club['id'],
        ]);

        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Account created! You\'ve joined ' . $club['club_name'] . '.',
        ]);
        exit;
    }

    // ── Success without join code ───────────────────────────
    http_response_code(201);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Account created successfully! Welcome to The Socials.',
    ]);
    exit;

} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error. Please try again later.']);
    exit;
}
