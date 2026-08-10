<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard/index.php');
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$errors = [];
$success = false;

if (empty($token)) {
    die('Invalid or missing reset token.');
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die('This password reset link is invalid or has expired. Please request a new one.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?");
        $upd->execute([$hash, $user['user_id']]);
        logActivity('Password reset completed', 'auth');
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-staff-snake"></i> <span><?= APP_NAME ?></span></div>
        <p class="auth-subtitle">Choose a new password</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= clean($error) ?></div>
        <?php endforeach; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">Your password has been reset successfully.</div>
            <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary w-100 py-2">Go to Login</a>
        <?php else: ?>
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="token" value="<?= clean($token) ?>">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8" autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
