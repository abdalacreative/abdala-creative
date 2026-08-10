<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard/index.php');
} else {
    redirect(APP_URL . '/auth/login.php');
}
