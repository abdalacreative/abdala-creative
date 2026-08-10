<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = 'Please enter both username/email and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

       if (!$user) {
    $errors[] = 'Invalid username or password.';
    logActivity("Failed login attempt for '{$username}'", 'auth');
} elseif ($password !== $user['password_hash']) {
    $errors[] = 'Invalid username or password.';
    logActivity("Failed login attempt for '{$username}'", 'auth');
} elseif ($user['status'] !== 'active') {
    $errors[] = 'Your account is ' . $user['status'] . '. Please contact the administrator.';
} else {

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['profile_image'] = $user['profile_image'];

    $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $upd->execute([$user['user_id']]);

    logActivity('User logged in', 'auth');

    redirect(APP_URL . '/dashboard/index.php');
}
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-staff-snake"></i> <span><?= APP_NAME ?></span></div>
        <p class="auth-subtitle">Sign in to manage hospital operations</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= clean($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" id="loginForm" onsubmit="return validateForm('loginForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="<?= APP_URL ?>/auth/forgot_password.php" style="font-size:13px;color:var(--primary);">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>
        </form>

        <p class="text-center mt-4" style="font-size:13px;color:var(--text-muted);">
            New patient? <a href="<?= APP_URL ?>/auth/register.php" style="color:var(--primary);">Create an account</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
