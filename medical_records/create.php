<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['doctor']);

$pageTitle = 'New Medical Record';
$pdo = getDB();
$errors = [];
$old = [];

$stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetch()['doctor_id'] ?? 0;

// Only patients this doctor has appointments with
$patients = $pdo->prepare("
    SELECT DISTINCT p.patient_id, u.full_name, p.patient_code FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id JOIN users u ON p.user_id = u.user_id
    WHERE a.doctor_id = ? ORDER BY u.full_name");
$patients->execute([$doctorId]);
$patients = $patients->fetchAll();

$appointmentId = (int)($_GET['appointment_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = cleanArray($_POST);

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    $visitDate = $_POST['visit_date'] ?? date('Y-m-d');
    $symptoms = trim($_POST['symptoms'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $treatmentNotes = trim($_POST['treatment_notes'] ?? '');
    $labResults = trim($_POST['lab_results'] ?? '');

    if (empty($patientId) || empty($diagnosis)) {
        $errors[] = 'Patient and diagnosis are required.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO medical_records (record_code, patient_id, doctor_id, appointment_id, visit_date, diagnosis, prescription, symptoms, treatment_notes, lab_results) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['TEMP', $patientId, $doctorId, $apptId ?: null, $visitDate, $diagnosis, $prescription, $symptoms, $treatmentNotes, $labResults]);
        $newId = $pdo->lastInsertId();
        $code = 'MR-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE medical_records SET record_code = ? WHERE record_id = ?")->execute([$code, $newId]);
        $pdo->commit();

        logActivity("Created medical record {$code}", 'medical_records');
        setFlash('success', 'Medical record created successfully.');
        redirect(APP_URL . '/medical_records/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">New Medical Record</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="recordForm" onsubmit="return validateForm('recordForm')" novalidate>
            <?php csrf_field(); ?>
            <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>"><?= clean($p['full_name']) ?> (<?= clean($p['patient_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Visit Date *</label>
                    <input type="date" name="visit_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-12 mb-3"><label class="form-label">Symptoms</label><textarea name="symptoms" class="form-control" rows="2"></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Diagnosis *</label><textarea name="diagnosis" class="form-control" rows="2" required></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Prescription</label><textarea name="prescription" class="form-control" rows="3" placeholder="Medicine name, dosage, frequency, duration"></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Treatment Notes</label><textarea name="treatment_notes" class="form-control" rows="2"></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Lab Results</label><textarea name="lab_results" class="form-control" rows="2"></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Save Record</button>
            <a href="<?= APP_URL ?>/medical_records/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
