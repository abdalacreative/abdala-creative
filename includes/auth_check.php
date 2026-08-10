<?php
/**
 * Authentication / authorization guards.
 * Include this AFTER session_start() (handled by config.php).
 */

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id'   => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'email'     => $_SESSION['email'],
        'profile_image' => $_SESSION['profile_image'] ?? null,
    ];
}

/** Force login; call at the top of every protected page */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirect(APP_URL . '/auth/login.php');
    }
}

/** Restrict a page to specific roles. Usage: requireRole(['admin','doctor']) */
function requireRole(array $roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('<h2>403 Forbidden</h2><p>You do not have permission to access this page.</p><a href="' . APP_URL . '/dashboard/index.php">Return to Dashboard</a>');
    }
}

/** Simple session-based login throttling could be added here if needed */
