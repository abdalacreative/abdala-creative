<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard/index.php');
}

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'danger';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same message regardless of whether the email exists,
        // to avoid leaking which emails are registered.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $upd = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE user_id = ?");
            $upd->execute([$token, $expires, $user['user_id']]);

            // In production: send an email containing
            // APP_URL . '/auth/reset_password.php?token=' . $token
            // Here we log it for development/demo purposes instead of sending mail.
            error_log("Password reset link for {$email}: " . APP_URL . "/auth/reset_password.php?token={$token}");
        }

        $message = 'If an account exists with that email, a password reset link has been sent.';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo"><i class="fa-solid fa-staff-snake"></i> <span><?= APP_NAME ?></span></div>
        <p class="auth-subtitle">Reset your password</p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>"><?= clean($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Send Reset Link</button>
        </form>

        <p class="text-center mt-4" style="font-size:13px;color:var(--text-muted);">
            <a href="<?= APP_URL ?>/auth/login.php" style="color:var(--primary);"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>
        </p>
    </div>
</div>
</body>
</html>
