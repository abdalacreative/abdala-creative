<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Add Doctor';
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
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $license = trim($_POST['license_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $fee = $_POST['consultation_fee'] ?? 0;
    $days = trim($_POST['available_days'] ?? '');
    $startTime = $_POST['available_time_start'] ?? null;
    $endTime = $_POST['available_time_end'] ?? null;
    $bio = trim($_POST['bio'] ?? '');

    if (empty($fullName) || empty($email) || empty($username) || empty($password) || empty($specialization)) {
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
            try {
                $pdo->beginTransaction();
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password_hash, role, phone, status) VALUES (?, ?, ?, ?, 'doctor', ?, 'active')");
                $stmt->execute([$fullName, $email, $username, $hash, $phone]);
                $userId = $pdo->lastInsertId();

                $doctorCode = 'DOC-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO doctors
                    (user_id, doctor_code, specialization, qualification, license_number, department, consultation_fee, available_days, available_time_start, available_time_end, bio)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $doctorCode, $specialization, $qualification, $license, $department, $fee, $days, $startTime ?: null, $endTime ?: null, $bio]);

                $pdo->commit();
                logActivity("Created doctor {$doctorCode}", 'doctors');
                setFlash('success', 'Doctor added successfully.');
                redirect(APP_URL . '/doctors/index.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $errors[] = (strpos($e->getMessage(), 'license_number') !== false) ? 'License number already exists.' : 'Failed to add doctor. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">Add New Doctor</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>

        <form method="POST" id="doctorForm" onsubmit="return validateForm('doctorForm')" novalidate>
            <?php csrf_field(); ?>
            <h6 class="text-muted mb-3">Account Details</h6>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= clean($old['full_name'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= clean($old['phone'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= clean($old['email'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Username *</label><input type="text" name="username" class="form-control" value="<?= clean($old['username'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
            </div>

            <h6 class="text-muted mb-3 mt-2">Professional Details</h6>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Specialization *</label><input type="text" name="specialization" class="form-control" value="<?= clean($old['specialization'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="<?= clean($old['department'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?= clean($old['qualification'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">License Number</label><input type="text" name="license_number" class="form-control" value="<?= clean($old['license_number'] ?? '') ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Consultation Fee</label><input type="number" step="0.01" min="0" name="consultation_fee" class="form-control" value="<?= clean($old['consultation_fee'] ?? '0') ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Available Time Start</label><input type="time" name="available_time_start" class="form-control"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Available Time End</label><input type="time" name="available_time_end" class="form-control"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Available Days</label><input type="text" name="available_days" class="form-control" placeholder="e.g. Mon,Tue,Wed,Thu,Fri" value="<?= clean($old['available_days'] ?? '') ?>"></div>
                <div class="col-12 mb-3"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="3"><?= clean($old['bio'] ?? '') ?></textarea></div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Save Doctor</button>
            <a href="<?= APP_URL ?>/doctors/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
