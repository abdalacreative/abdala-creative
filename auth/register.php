<?php
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard/index.php');
}

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
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $dob = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';

    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $errors[] = 'Please fill in all required fields.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getDB();

        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email or username already exists.';
        } else {
            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password_hash, role, phone, status) VALUES (?, ?, ?, ?, 'patient', ?, 'active')");
                $stmt->execute([$fullName, $email, $username, $hash, $phone]);
                $userId = $pdo->lastInsertId();

                $patientCode = 'PAT-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO patients (user_id, patient_code, date_of_birth, gender) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $patientCode, $dob ?: null, $gender ?: null]);

                $pdo->commit();

                setFlash('success', 'Account created successfully. Please log in.');
                redirect(APP_URL . '/auth/login.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | <?= APP_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-logo"><i class="fa-solid fa-staff-snake"></i> <span><?= APP_NAME ?></span></div>
        <p class="auth-subtitle">Create your patient account</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= clean($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" id="registerForm" onsubmit="return validateForm('registerForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= clean($old['full_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= clean($old['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= clean($old['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?= clean($old['username'] ?? '') ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= clean($old['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="8">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 mt-2">Create Account</button>
        </form>

        <p class="text-center mt-4" style="font-size:13px;color:var(--text-muted);">
            Already have an account? <a href="<?= APP_URL ?>/auth/login.php" style="color:var(--primary);">Sign in</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
