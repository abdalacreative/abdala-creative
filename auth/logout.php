<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    logActivity('User logged out', 'auth');
}

$_SESSION = [];
session_destroy();

// Start a fresh session to set a flash message on the login page
session_start();
setFlash('success', 'You have been logged out successfully.');
redirect(APP_URL . '/auth/login.php');
