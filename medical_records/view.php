<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'patient']);

$pageTitle = 'Medical Record';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];

$stmt = $pdo->prepare("
    SELECT mr.*, pu.full_name AS patient_name, p.patient_code, p.user_id AS patient_user_id,
           du.full_name AS doctor_name, d.user_id AS doctor_user_id
    FROM medical_records mr
    JOIN patients p ON mr.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
    JOIN doctors d ON mr.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
    WHERE mr.record_id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    setFlash('error', 'Medical record not found.');
    redirect(APP_URL . '/medical_records/index.php');
}

// Patients can only view their own records
if ($role === 'patient' && $record['patient_user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>You can only view your own medical records.</p>');
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Medical Record <?= clean($record['record_code']) ?></span>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fa-solid fa-print me-1"></i> Print</button>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6"><strong>Patient:</strong> <?= clean($record['patient_name']) ?> (<?= clean($record['patient_code']) ?>)</div>
            <div class="col-md-6"><strong>Doctor:</strong> Dr. <?= clean($record['doctor_name']) ?></div>
            <div class="col-md-6"><strong>Visit Date:</strong> <?= formatDate($record['visit_date']) ?></div>
        </div>
        <hr>
        <div class="mb-3"><strong>Symptoms</strong><p class="mb-0"><?= nl2br(clean($record['symptoms'] ?: '—')) ?></p></div>
        <div class="mb-3"><strong>Diagnosis</strong><p class="mb-0"><?= nl2br(clean($record['diagnosis'] ?: '—')) ?></p></div>
        <div class="mb-3"><strong>Prescription</strong><p class="mb-0"><?= nl2br(clean($record['prescription'] ?: '—')) ?></p></div>
        <div class="mb-3"><strong>Treatment Notes</strong><p class="mb-0"><?= nl2br(clean($record['treatment_notes'] ?: '—')) ?></p></div>
        <div class="mb-0"><strong>Lab Results</strong><p class="mb-0"><?= nl2br(clean($record['lab_results'] ?: '—')) ?></p></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
