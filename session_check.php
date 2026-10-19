<?php
/**
 * ============================================================
 * THE SOCIALS — Session Check Endpoint
 * Returns JSON indicating whether a user is logged in
 * ============================================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user' => [
            'id'        => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'email'     => $_SESSION['email'],
        ],
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
    ]);
}
