<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Add Receptionist';
$pdo = getDB();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = cleanArray($_POST);

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $shift = $_POST['shift'] ?? 'morning';

    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $errors[] = 'Please fill in all required fields.';
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!empty($password) && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $errors[] = 'Email or username already exists.';
        } else {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password_hash, role, phone, status) VALUES (?, ?, ?, ?, 'receptionist', ?, 'active')");
            $stmt->execute([$fullName, $email, $username, $hash, $phone]);
            $userId = $pdo->lastInsertId();

            $code = 'REC-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO receptionists (user_id, receptionist_code, shift) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $code, $shift]);
            $pdo->commit();

            logActivity("Created receptionist {$code}", 'receptionists');
            setFlash('success', 'Receptionist added successfully.');
            redirect(APP_URL . '/receptionists/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Add New Receptionist</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="recForm" onsubmit="return validateForm('recForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= clean($old['full_name'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= clean($old['phone'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= clean($old['email'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Username *</label><input type="text" name="username" class="form-control" value="<?= clean($old['username'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Shift</label>
                    <select name="shift" class="form-select">
                        <option value="morning">Morning</option><option value="evening">Evening</option><option value="night">Night</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Save Receptionist</button>
            <a href="<?= APP_URL ?>/receptionists/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
