<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'receptionist']);

$pageTitle = 'Add Patient';
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
    $dob = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $emergencyName = trim($_POST['emergency_contact_name'] ?? '');
    $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');

    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $errors[] = 'Please fill in all required fields (name, email, username, password).';
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
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, username, password_hash, role, phone, status) VALUES (?, ?, ?, ?, 'patient', ?, 'active')");
                $stmt->execute([$fullName, $email, $username, $hash, $phone]);
                $userId = $pdo->lastInsertId();

                $patientCode = 'PAT-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
                $stmt = $pdo->prepare("INSERT INTO patients
                    (user_id, patient_code, date_of_birth, gender, blood_group, address, city, emergency_contact_name, emergency_contact_phone, allergies, registered_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $patientCode, $dob ?: null, $gender ?: null, $bloodGroup, $address, $city, $emergencyName, $emergencyPhone, $allergies, $_SESSION['user_id']]);

                $pdo->commit();
                logActivity("Created patient {$patientCode}", 'patients');
                setFlash('success', 'Patient registered successfully.');
                redirect(APP_URL . '/patients/index.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log($e->getMessage());
                $errors[] = 'Failed to register patient. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">Patient Registration</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= clean($error) ?></div>
        <?php endforeach; ?>

        <form method="POST" id="patientForm" onsubmit="return validateForm('patientForm')" novalidate>
            <?php csrf_field(); ?>
            <h6 class="text-muted mb-3">Account Details</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= clean($old['full_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= clean($old['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" value="<?= clean($old['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" value="<?= clean($old['username'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
            </div>

            <h6 class="text-muted mb-3 mt-2">Personal Details</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?= clean($old['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Blood Group</label>
                    <input type="text" name="blood_group" class="form-control" placeholder="e.g. O+" value="<?= clean($old['blood_group'] ?? '') ?>">
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= clean($old['address'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?= clean($old['city'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="form-control" value="<?= clean($old['emergency_contact_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" value="<?= clean($old['emergency_contact_phone'] ?? '') ?>">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Known Allergies</label>
                    <textarea name="allergies" class="form-control" rows="2"><?= clean($old['allergies'] ?? '') ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Save Patient</button>
            <a href="<?= APP_URL ?>/patients/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
