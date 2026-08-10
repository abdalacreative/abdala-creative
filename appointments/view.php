<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'receptionist', 'patient']);

$pageTitle = 'Appointment Details';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT a.*, pu.full_name AS patient_name, p.patient_code, du.full_name AS doctor_name, d.specialization, d.consultation_fee
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

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Appointment <?= clean($appt['appointment_code']) ?></span>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fa-solid fa-print me-1"></i> Print</button>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3"><strong>Patient:</strong> <?= clean($appt['patient_name']) ?> (<?= clean($appt['patient_code']) ?>)</div>
            <div class="col-md-6 mb-3"><strong>Doctor:</strong> Dr. <?= clean($appt['doctor_name']) ?> — <?= clean($appt['specialization']) ?></div>
            <div class="col-md-6 mb-3"><strong>Date:</strong> <?= formatDate($appt['appointment_date']) ?></div>
            <div class="col-md-6 mb-3"><strong>Time:</strong> <?= clean($appt['appointment_time']) ?></div>
            <div class="col-md-6 mb-3"><strong>Status:</strong> <?= statusBadge($appt['status']) ?></div>
            <div class="col-md-6 mb-3"><strong>Consultation Fee:</strong> <?= formatMoney($appt['consultation_fee']) ?></div>
            <div class="col-12 mb-3"><strong>Reason:</strong> <?= clean($appt['reason'] ?: '—') ?></div>
            <div class="col-12 mb-0"><strong>Notes:</strong> <?= clean($appt['notes'] ?: '—') ?></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
