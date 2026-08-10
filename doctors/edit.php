<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Edit Doctor';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email, u.phone, u.user_id, u.status FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?");
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    setFlash('error', 'Doctor not found.');
    redirect(APP_URL . '/doctors/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $license = trim($_POST['license_number'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $fee = $_POST['consultation_fee'] ?? 0;
    $days = trim($_POST['available_days'] ?? '');
    $startTime = $_POST['available_time_start'] ?? null;
    $endTime = $_POST['available_time_end'] ?? null;
    $bio = trim($_POST['bio'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($specialization)) {
        $errors[] = 'Please provide valid name, email, and specialization.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check->execute([$email, $doctor['user_id']]);
        if ($check->fetch()) {
            $errors[] = 'Email is used by another account.';
        } else {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, status=? WHERE user_id=?");
            $upd->execute([$fullName, $email, $phone, $status, $doctor['user_id']]);

            $upd2 = $pdo->prepare("UPDATE doctors SET specialization=?, qualification=?, license_number=?, department=?, consultation_fee=?, available_days=?, available_time_start=?, available_time_end=?, bio=? WHERE doctor_id=?");
            $upd2->execute([$specialization, $qualification, $license, $department, $fee, $days, $startTime ?: null, $endTime ?: null, $bio, $id]);
            $pdo->commit();

            logActivity("Updated doctor #{$id}", 'doctors');
            setFlash('success', 'Doctor updated successfully.');
            redirect(APP_URL . '/doctors/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">Edit Doctor: Dr. <?= clean($doctor['full_name']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>

        <form method="POST" id="editDoctorForm" onsubmit="return validateForm('editDoctorForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= clean($doctor['full_name']) ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= clean($doctor['phone']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= clean($doctor['email']) ?>" required></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $doctor['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $doctor['status']==='inactive'?'selected':'' ?>>Inactive</option>
                        <option value="suspended" <?= $doctor['status']==='suspended'?'selected':'' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Specialization *</label><input type="text" name="specialization" class="form-control" value="<?= clean($doctor['specialization']) ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="<?= clean($doctor['department']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Qualification</label><input type="text" name="qualification" class="form-control" value="<?= clean($doctor['qualification']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">License Number</label><input type="text" name="license_number" class="form-control" value="<?= clean($doctor['license_number']) ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Consultation Fee</label><input type="number" step="0.01" min="0" name="consultation_fee" class="form-control" value="<?= clean($doctor['consultation_fee']) ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Available Time Start</label><input type="time" name="available_time_start" class="form-control" value="<?= clean($doctor['available_time_start']) ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Available Time End</label><input type="time" name="available_time_end" class="form-control" value="<?= clean($doctor['available_time_end']) ?>"></div>
                <div class="col-md-12 mb-3"><label class="form-label">Available Days</label><input type="text" name="available_days" class="form-control" value="<?= clean($doctor['available_days']) ?>"></div>
                <div class="col-12 mb-3"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="3"><?= clean($doctor['bio']) ?></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Doctor</button>
            <a href="<?= APP_URL ?>/doctors/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
