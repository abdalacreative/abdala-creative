<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor']);

$pageTitle = 'Update Lab Test';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];
$errors = [];

$stmt = $pdo->prepare("
    SELECT lt.*, pu.full_name AS patient_name, p.patient_code, du.full_name AS doctor_name, d.user_id AS doctor_user_id
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
    JOIN doctors d ON lt.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
    WHERE lt.test_id = ?");
$stmt->execute([$id]);
$test = $stmt->fetch();

if (!$test) {
    setFlash('error', 'Lab test not found.');
    redirect(APP_URL . '/lab_tests/index.php');
}

if ($role === 'doctor' && $test['doctor_user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>You can only manage lab tests you ordered.</p>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $status = $_POST['status'] ?? $test['status'];
    $resultValue = trim($_POST['result_value'] ?? '');
    $referenceRange = trim($_POST['reference_range'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $resultFlag = $_POST['result_flag'] ?? 'pending';
    $technicianNotes = trim($_POST['technician_notes'] ?? '');
    $doctorRemarks = trim($_POST['doctor_remarks'] ?? '');
    $sampleCollectedAt = $_POST['sample_collected_at'] ?? null;

    $completedBy = $test['completed_by'];
    if ($status === 'completed' && empty($completedBy)) {
        $completedBy = $_SESSION['user_id'];
    }

    $upd = $pdo->prepare("UPDATE lab_tests SET status=?, result_value=?, reference_range=?, unit=?, result_flag=?, technician_notes=?, doctor_remarks=?, sample_collected_at=?, completed_by=? WHERE test_id=?");
    $upd->execute([$status, $resultValue, $referenceRange, $unit, $resultFlag, $technicianNotes, $doctorRemarks, $sampleCollectedAt ?: null, $completedBy, $id]);

    logActivity("Updated lab test #{$id} to status {$status}", 'lab_tests');
    setFlash('success', 'Lab test updated successfully.');
    redirect(APP_URL . '/lab_tests/view.php?id=' . $id);
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Lab Test: <?= clean($test['test_code']) ?> — <?= clean($test['test_name']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>

        <div class="row mb-3">
            <div class="col-md-6"><strong>Patient:</strong> <?= clean($test['patient_name']) ?> (<?= clean($test['patient_code']) ?>)</div>
            <div class="col-md-6"><strong>Ordering Doctor:</strong> Dr. <?= clean($test['doctor_name']) ?></div>
        </div>

        <form method="POST" id="editLabTestForm" onsubmit="return validateForm('editLabTestForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Workflow Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['ordered','sample_collected','in_progress','completed','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $test['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sample Collected At</label>
                    <input type="datetime-local" name="sample_collected_at" class="form-control" value="<?= $test['sample_collected_at'] ? date('Y-m-d\TH:i', strtotime($test['sample_collected_at'])) : '' ?>">
                </div>
            </div>

            <h6 class="text-muted mb-3 mt-2">Results</h6>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Result Value / Findings</label><textarea name="result_value" class="form-control" rows="3" placeholder="e.g. Hemoglobin: 13.5, WBC: 7200, Platelets: 250000"><?= clean($test['result_value']) ?></textarea></div>
                <div class="col-md-3 mb-3"><label class="form-label">Reference Range</label><input type="text" name="reference_range" class="form-control" placeholder="e.g. 12-16 g/dL" value="<?= clean($test['reference_range']) ?>"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Unit</label><input type="text" name="unit" class="form-control" placeholder="e.g. g/dL" value="<?= clean($test['unit']) ?>"></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Result Flag</label>
                    <select name="result_flag" class="form-select">
                        <option value="pending" <?= $test['result_flag']==='pending'?'selected':'' ?>>Pending</option>
                        <option value="normal" <?= $test['result_flag']==='normal'?'selected':'' ?>>Normal</option>
                        <option value="abnormal" <?= $test['result_flag']==='abnormal'?'selected':'' ?>>Abnormal</option>
                        <option value="critical" <?= $test['result_flag']==='critical'?'selected':'' ?>>Critical</option>
                    </select>
                </div>
                <div class="col-12 mb-3"><label class="form-label">Technician Notes</label><textarea name="technician_notes" class="form-control" rows="2"><?= clean($test['technician_notes']) ?></textarea></div>
                <div class="col-12 mb-3"><label class="form-label">Doctor Remarks / Interpretation</label><textarea name="doctor_remarks" class="form-control" rows="2"><?= clean($test['doctor_remarks']) ?></textarea></div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Lab Test</button>
            <a href="<?= APP_URL ?>/lab_tests/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
