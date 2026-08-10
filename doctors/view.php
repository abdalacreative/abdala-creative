<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'receptionist', 'patient', 'nurse']);

$pageTitle = 'Doctor Profile';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT d.*, u.full_name, u.email, u.phone, u.status FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?");
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    setFlash('error', 'Doctor not found.');
    redirect(APP_URL . '/doctors/index.php');
}

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id = ?");
$stmt->execute([$id]);
$totalAppointments = $stmt->fetch()['c'];

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($doctor['full_name']) ?>&background=0F766E&color=fff&size=96" class="rounded-circle mb-3" width="90" height="90">
                <h5 class="mb-0">Dr. <?= clean($doctor['full_name']) ?></h5>
                <p class="text-muted mb-2"><?= clean($doctor['specialization']) ?></p>
                <?= statusBadge($doctor['status']) ?>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Doctor Information</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><p><strong>Code:</strong> <?= clean($doctor['doctor_code']) ?></p></div>
                    <div class="col-md-6"><p><strong>Department:</strong> <?= clean($doctor['department'] ?: '—') ?></p></div>
                    <div class="col-md-6"><p><strong>Email:</strong> <?= clean($doctor['email']) ?></p></div>
                    <div class="col-md-6"><p><strong>Phone:</strong> <?= clean($doctor['phone'] ?: '—') ?></p></div>
                    <div class="col-md-6"><p><strong>Qualification:</strong> <?= clean($doctor['qualification'] ?: '—') ?></p></div>
                    <div class="col-md-6"><p><strong>License #:</strong> <?= clean($doctor['license_number'] ?: '—') ?></p></div>
                    <div class="col-md-6"><p><strong>Consultation Fee:</strong> <?= formatMoney($doctor['consultation_fee']) ?></p></div>
                    <div class="col-md-6"><p><strong>Available Days:</strong> <?= clean($doctor['available_days'] ?: '—') ?></p></div>
                    <div class="col-md-12"><p class="mb-0"><strong>Bio:</strong> <?= clean($doctor['bio'] ?: 'No bio available.') ?></p></div>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="icon-box" style="background:#0F766E;"><i class="fa-solid fa-calendar-check"></i></div>
            <div><p class="stat-value"><?= $totalAppointments ?></p><p class="stat-label">Total Appointments</p></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
