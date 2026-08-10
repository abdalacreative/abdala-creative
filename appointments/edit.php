<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'receptionist', 'doctor']);

$pageTitle = 'Edit Appointment';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];
$errors = [];

$stmt = $pdo->prepare("
    SELECT a.*, pu.full_name AS patient_name, du.full_name AS doctor_name, d.user_id AS doctor_user_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
    JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
    WHERE a.appointment_id = ?");
$stmt->execute([$id]);
$appt = $stmt->fetch();

if (!$appt) {
    setFlash('error', 'Appointment not found.');
    redirect(APP_URL . '/appointments/index.php');
}

// Doctors may only edit their own appointments
if ($role === 'doctor' && $appt['doctor_user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>You can only manage your own appointments.</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($date) || empty($time)) {
        $errors[] = 'Date and time are required.';
    } else {
        $upd = $pdo->prepare("UPDATE appointments SET appointment_date=?, appointment_time=?, reason=?, status=?, notes=? WHERE appointment_id=?");
        $upd->execute([$date, $time, $reason, $status, $notes, $id]);
        logActivity("Updated appointment #{$id} to status {$status}", 'appointments');
        setFlash('success', 'Appointment updated successfully.');
        redirect(APP_URL . '/appointments/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Edit Appointment: <?= clean($appt['appointment_code']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="editApptForm" onsubmit="return validateForm('editApptForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row mb-3">
                <div class="col-md-6"><strong>Patient:</strong> <?= clean($appt['patient_name']) ?></div>
                <div class="col-md-6"><strong>Doctor:</strong> Dr. <?= clean($appt['doctor_name']) ?></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Date *</label><input type="date" name="appointment_date" class="form-control" value="<?= clean($appt['appointment_date']) ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Time *</label><input type="time" name="appointment_time" class="form-control" value="<?= clean($appt['appointment_time']) ?>" required></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['pending','confirmed','completed','cancelled','no_show'] as $s): ?>
                            <option value="<?= $s ?>" <?= $appt['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 mb-3"><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="2"><?= clean($appt['reason']) ?></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"><?= clean($appt['notes']) ?></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Appointment</button>
            <a href="<?= APP_URL ?>/appointments/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
