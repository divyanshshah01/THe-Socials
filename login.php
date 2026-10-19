<?php
/**
 * ============================================================
 * THE SOCIALS — User Login Endpoint
 * Validates credentials, starts session, returns JSON
 * ============================================================
 */

session_start();

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

$email    = isset($input['email'])    ? trim(strtolower($input['email'])) : '';
$password = isset($input['password']) ? $input['password'] : '';

// ── Validate ────────────────────────────────────────────────
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

try {
    // ── Look up user ────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
        exit;
    }

    // ── Start session ───────────────────────────────────────
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];

    // Regenerate session ID to prevent fixation attacks
    session_regenerate_id(true);

    // ── Success ─────────────────────────────────────────────
    http_response_code(200);
    echo json_encode([
        'status'   => 'success',
        'message'  => 'Welcome back, ' . $user['full_name'] . '!',
        'redirect' => 'dashboard.html',
    ]);
    exit;

} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error. Please try again later.']);
    exit;
}
