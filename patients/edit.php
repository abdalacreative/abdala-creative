<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'receptionist']);

$pageTitle = 'Edit Patient';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.email, u.phone, u.user_id FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    setFlash('error', 'Patient not found.');
    redirect(APP_URL . '/patients/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $emergencyName = trim($_POST['emergency_contact_name'] ?? '');
    $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');

    if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid name and email.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check->execute([$email, $patient['user_id']]);
        if ($check->fetch()) {
            $errors[] = 'That email is used by another account.';
        } else {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
            $upd->execute([$fullName, $email, $phone, $patient['user_id']]);

            $upd2 = $pdo->prepare("UPDATE patients SET date_of_birth = ?, gender = ?, blood_group = ?, address = ?, city = ?, emergency_contact_name = ?, emergency_contact_phone = ?, allergies = ? WHERE patient_id = ?");
            $upd2->execute([$dob ?: null, $gender ?: null, $bloodGroup, $address, $city, $emergencyName, $emergencyPhone, $allergies, $id]);
            $pdo->commit();

            logActivity("Updated patient #{$id}", 'patients');
            setFlash('success', 'Patient updated successfully.');
            redirect(APP_URL . '/patients/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">Edit Patient: <?= clean($patient['full_name']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= clean($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" id="editPatientForm" onsubmit="return validateForm('editPatientForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= clean($patient['full_name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= clean($patient['phone']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= clean($patient['email']) ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= clean($patient['date_of_birth']) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select</option>
                        <option value="male" <?= $patient['gender']==='male'?'selected':'' ?>>Male</option>
                        <option value="female" <?= $patient['gender']==='female'?'selected':'' ?>>Female</option>
                        <option value="other" <?= $patient['gender']==='other'?'selected':'' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Blood Group</label>
                    <input type="text" name="blood_group" class="form-control" value="<?= clean($patient['blood_group']) ?>">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= clean($patient['address']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= clean($patient['city']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control" value="<?= clean($patient['emergency_contact_name']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" value="<?= clean($patient['emergency_contact_phone']) ?>">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Known Allergies</label>
                    <textarea name="allergies" class="form-control" rows="2"><?= clean($patient['allergies']) ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Patient</button>
            <a href="<?= APP_URL ?>/patients/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
