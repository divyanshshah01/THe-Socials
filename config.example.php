<?php
/**
 * ============================================================
 * THE SOCIALS — Database Configuration Template
 * Copy this file to config.php and enter your credentials.
 * ============================================================
 */

// Prevent direct browser access to this file
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    exit('Direct access forbidden.');
}

// ── Database Credentials (Environment variables or fallback defaults) ─────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'the_socials');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// ── PDO Data Source Name ────────────────────────────────────
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);

// ── PDO Options (Hardened) ──────────────────────────────────
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // Throw on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Associative arrays
    PDO::ATTR_EMULATE_PREPARES => false,                    // Real prepared statements
    PDO::ATTR_PERSISTENT => false,                    // No persistent connections
];

// ── Establish Connection ────────────────────────────────────
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
} catch (PDOException $e) {
    // In production: log the error, show a generic message
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => 'Internal server error. Please try again later.'
    ]));
}
