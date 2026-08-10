<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'receptionist', 'patient']);

$pageTitle = 'New Appointment';
$pdo = getDB();
$role = $_SESSION['role'];
$errors = [];
$old = [];

// Patients can only book for themselves; admin/receptionist pick from a list
if ($role === 'patient') {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $myPatientId = $stmt->fetch()['patient_id'] ?? 0;
} else {
    $patients = $pdo->query("SELECT p.patient_id, u.full_name, p.patient_code FROM patients p JOIN users u ON p.user_id = u.user_id ORDER BY u.full_name")->fetchAll();
}

$doctors = $pdo->query("SELECT d.doctor_id, u.full_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = cleanArray($_POST);

    $patientId = $role === 'patient' ? $myPatientId : (int)($_POST['patient_id'] ?? 0);
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    if (empty($patientId) || empty($doctorId) || empty($date) || empty($time)) {
        $errors[] = 'Please fill in all required fields.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Appointment date cannot be in the past.';
    } else {
        // Prevent double-booking the same doctor at the same date/time
        $check = $pdo->prepare("SELECT appointment_id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled','no_show')");
        $check->execute([$doctorId, $date, $time]);
        if ($check->fetch()) {
            $errors[] = 'This doctor already has an appointment at the selected date and time. Please choose another slot.';
        } else {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO appointments (appointment_code, patient_id, doctor_id, scheduled_by, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            // Insert placeholder code first, then update with real ID-based code
            $stmt->execute(['TEMP', $patientId, $doctorId, $_SESSION['user_id'], $date, $time, $reason]);
            $newId = $pdo->lastInsertId();
            $code = 'APT-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE appointments SET appointment_code = ? WHERE appointment_id = ?")->execute([$code, $newId]);
            $pdo->commit();

            logActivity("Created appointment {$code}", 'appointments');
            setFlash('success', 'Appointment booked successfully.');
            redirect(APP_URL . '/appointments/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">Book New Appointment</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>

        <form method="POST" id="apptForm" onsubmit="return validateForm('apptForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <?php if ($role !== 'patient'): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>" <?= (($old['patient_id'] ?? '') == $p['patient_id']) ? 'selected' : '' ?>><?= clean($p['full_name']) ?> (<?= clean($p['patient_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor *</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['doctor_id'] ?>" <?= (($old['doctor_id'] ?? '') == $d['doctor_id']) ? 'selected' : '' ?>>Dr. <?= clean($d['full_name']) ?> — <?= clean($d['specialization']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date *</label>
                    <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= clean($old['appointment_date'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Time *</label>
                    <input type="time" name="appointment_time" class="form-control" value="<?= clean($old['appointment_time'] ?? '') ?>" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Reason for Visit</label>
                    <textarea name="reason" class="form-control" rows="3"><?= clean($old['reason'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calendar-plus me-1"></i> Book Appointment</button>
            <a href="<?= APP_URL ?>/appointments/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
