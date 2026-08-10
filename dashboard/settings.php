<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle = 'Settings';
$pdo = getDB();
$userId = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match.';
    } else {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $upd->execute([$hash, $userId]);
        logActivity('Password changed', 'settings');
        setFlash('success', 'Password updated successfully.');
        redirect(APP_URL . '/dashboard/settings.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Change Password</div>
            <div class="card-body">
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= clean($error) ?></div>
                <?php endforeach; ?>
                <form method="POST">
                    <?php csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
