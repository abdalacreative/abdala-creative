<?php
/**
 * Global application configuration
 */
define('APP_NAME', 'MediCare HMS');
define('APP_URL', 'http://localhost/hms');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads');
define('UPLOAD_URL', APP_URL . '/assets/uploads');

// Pagination
define('PAGE_SIZE', 10);

// Session security settings (must run before session_start())
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    // Uncomment the next line when serving over HTTPS in production
    // ini_set('session.cookie_secure', 1);
    session_start();
}

date_default_timezone_set('Africa/Mogadishu');

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Set to '1' during local development only
ini_set('log_errors', '1');

require_once __DIR__ . '/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/auth_check.php';
