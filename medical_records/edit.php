<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['doctor']);

$pageTitle = 'Edit Medical Record';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctorId = $stmt->fetch()['doctor_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT mr.*, pu.full_name AS patient_name, p.patient_code FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
    WHERE mr.record_id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    setFlash('error', 'Medical record not found.');
    redirect(APP_URL . '/medical_records/index.php');
}

if ($record['doctor_id'] != $doctorId) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>You can only edit your own medical records.</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $visitDate = $_POST['visit_date'] ?? $record['visit_date'];
    $symptoms = trim($_POST['symptoms'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $treatmentNotes = trim($_POST['treatment_notes'] ?? '');
    $labResults = trim($_POST['lab_results'] ?? '');

    if (empty($diagnosis)) {
        $errors[] = 'Diagnosis is required.';
    } else {
        $upd = $pdo->prepare("UPDATE medical_records SET visit_date=?, symptoms=?, diagnosis=?, prescription=?, treatment_notes=?, lab_results=? WHERE record_id=?");
        $upd->execute([$visitDate, $symptoms, $diagnosis, $prescription, $treatmentNotes, $labResults, $id]);
        logActivity("Updated medical record #{$id}", 'medical_records');
        setFlash('success', 'Medical record updated successfully.');
        redirect(APP_URL . '/medical_records/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Edit Medical Record — <?= clean($record['patient_name']) ?> (<?= clean($record['patient_code']) ?>)</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="editRecordForm" onsubmit="return validateForm('editRecordForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Visit Date *</label><input type="date" name="visit_date" class="form-control" value="<?= clean($record['visit_date']) ?>" required></div>
                <div class="col-12 mb-3"><label class="form-label">Symptoms</label><textarea name="symptoms" class="form-control" rows="2"><?= clean($record['symptoms']) ?></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Diagnosis *</label><textarea name="diagnosis" class="form-control" rows="2" required><?= clean($record['diagnosis']) ?></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Prescription</label><textarea name="prescription" class="form-control" rows="3"><?= clean($record['prescription']) ?></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Treatment Notes</label><textarea name="treatment_notes" class="form-control" rows="2"><?= clean($record['treatment_notes']) ?></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Lab Results</label><textarea name="lab_results" class="form-control" rows="2"><?= clean($record['lab_results']) ?></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Record</button>
            <a href="<?= APP_URL ?>/medical_records/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
