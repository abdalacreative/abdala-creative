<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle = 'Dashboard';
$pdo = getDB();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$stats = [];

if ($role === 'admin') {
    $today = date('Y-m-d'); // Use PHP's date, not MySQL CURDATE(), to avoid server timezone mismatches
    $stats['patients'] = $pdo->query("SELECT COUNT(*) c FROM patients")->fetch()['c'];
    $stats['doctors'] = $pdo->query("SELECT COUNT(*) c FROM doctors")->fetch()['c'];
    $stats['appointments'] = $pdo->query("SELECT COUNT(*) c FROM appointments")->fetch()['c'];
    $stats['records'] = $pdo->query("SELECT COUNT(*) c FROM medical_records")->fetch()['c'];
    $stats['bills'] = $pdo->query("SELECT COUNT(*) c FROM billing")->fetch()['c'];
    $stats['payments'] = $pdo->query("SELECT COUNT(*) c FROM payments")->fetch()['c'];
    $stats['revenue'] = $pdo->query("SELECT COALESCE(SUM(amount_paid),0) c FROM payments")->fetch()['c'];
    $stats['medicines'] = $pdo->query("SELECT COUNT(*) c FROM pharmacy")->fetch()['c'];
    $stats['lab_tests'] = $pdo->query("SELECT COUNT(*) c FROM lab_tests")->fetch()['c'];
    $stats['pending_lab_tests'] = $pdo->query("SELECT COUNT(*) c FROM lab_tests WHERE status NOT IN ('completed','cancelled')")->fetch()['c'];

    $recentAppointments = $pdo->query("
        SELECT a.*, pu.full_name AS patient_name, du.full_name AS doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users du ON d.user_id = du.user_id
        ORDER BY a.created_at DESC LIMIT 6
    ")->fetchAll();

    $lowStock = $pdo->query("SELECT * FROM pharmacy WHERE stock_quantity <= reorder_level OR expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date ASC LIMIT 5")->fetchAll();
}

if ($role === 'doctor') {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $doctorId = $stmt->fetch()['doctor_id'] ?? 0;

    $stats['today_appointments'] = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
    $stats['today_appointments']->execute([$doctorId, $today]);
    $stats['today_appointments'] = $stats['today_appointments']->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE doctor_id = ?");
    $stmt->execute([$doctorId]);
    $stats['total_appointments'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) c FROM appointments WHERE doctor_id = ?");
    $stmt->execute([$doctorId]);
    $stats['total_patients'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM medical_records WHERE doctor_id = ?");
    $stmt->execute([$doctorId]);
    $stats['total_records'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM lab_tests WHERE doctor_id = ? AND status NOT IN ('completed','cancelled')");
    $stmt->execute([$doctorId]);
    $stats['pending_lab_tests'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("
        SELECT a.*, pu.full_name AS patient_name FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users pu ON p.user_id = pu.user_id
        WHERE a.doctor_id = ? AND a.appointment_date >= ?
        ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 6
    ");
    $stmt->execute([$doctorId, $today]);
    $upcomingAppointments = $stmt->fetchAll();
}

if ($role === 'patient') {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $patientId = $stmt->fetch()['patient_id'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE patient_id = ?");
    $stmt->execute([$patientId]);
    $stats['total_appointments'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM medical_records WHERE patient_id = ?");
    $stmt->execute([$patientId]);
    $stats['total_records'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) c FROM billing WHERE patient_id = ? AND status != 'paid'");
    $stmt->execute([$patientId]);
    $stats['outstanding_balance'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("
        SELECT a.*, du.full_name AS doctor_name FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users du ON d.user_id = du.user_id
        WHERE a.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 6
    ");
    $stmt->execute([$patientId]);
    $myAppointments = $stmt->fetchAll();
}

if ($role === 'accountant') {
    $today = date('Y-m-d');
    $stats['total_bills'] = $pdo->query("SELECT COUNT(*) c FROM billing")->fetch()['c'];
    $stats['unpaid_bills'] = $pdo->query("SELECT COUNT(*) c FROM billing WHERE status='unpaid'")->fetch()['c'];
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) c FROM payments WHERE payment_date = ?");
    $stmt->execute([$today]);
    $stats['revenue_today'] = $stmt->fetch()['c'];
    $stats['revenue_total'] = $pdo->query("SELECT COALESCE(SUM(amount_paid),0) c FROM payments")->fetch()['c'];
}

if ($role === 'receptionist') {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM patients WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $stats['patients_today'] = $stmt->fetch()['c'];
    $stats['appointments_today'] = $pdo->query("SELECT COUNT(*) c FROM appointments")->fetch()['c'];
    $stats['total_patients'] = $pdo->query("SELECT COUNT(*) c FROM patients")->fetch()['c'];
}

if ($role === 'nurse') {
    $today = date('Y-m-d');
    $stats['total_patients'] = $pdo->query("SELECT COUNT(*) c FROM patients")->fetch()['c'];
    $stats['appointments_today'] = $pdo->query("SELECT COUNT(*) c FROM appointments")->fetch()['c'];
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($role === 'admin'): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#2563EB;"><i class="fa-solid fa-user-injured"></i></div>
            <div><p class="stat-value"><?= $stats['patients'] ?></p><p class="stat-label">Patients</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#0F766E;"><i class="fa-solid fa-user-doctor"></i></div>
            <div><p class="stat-value"><?= $stats['doctors'] ?></p><p class="stat-label">Doctors</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#F59E0B;"><i class="fa-solid fa-calendar-check"></i></div>
            <div><p class="stat-value"><?= $stats['appointments'] ?></p><p class="stat-label">Appointments</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#8B5CF6;"><i class="fa-solid fa-file-medical"></i></div>
            <div><p class="stat-value"><?= $stats['records'] ?></p><p class="stat-label">Medical Records</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#EF4444;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div><p class="stat-value"><?= $stats['bills'] ?></p><p class="stat-label">Bills</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#10B981;"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div><p class="stat-value"><?= $stats['payments'] ?></p><p class="stat-label">Payments</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#0EA5E9;"><i class="fa-solid fa-sack-dollar"></i></div>
            <div><p class="stat-value"><?= formatMoney($stats['revenue']) ?></p><p class="stat-label">Total Revenue</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#DB2777;"><i class="fa-solid fa-pills"></i></div>
            <div><p class="stat-value"><?= $stats['medicines'] ?></p><p class="stat-label">Medicines</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#06B6D4;"><i class="fa-solid fa-flask-vial"></i></div>
            <div><p class="stat-value"><?= $stats['pending_lab_tests'] ?></p><p class="stat-label">Pending Lab Tests</p></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Recent Appointments</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentAppointments as $a): ?>
                        <tr>
                            <td><?= clean($a['patient_name']) ?></td>
                            <td><?= clean($a['doctor_name']) ?></td>
                            <td><?= formatDate($a['appointment_date']) ?></td>
                            <td><?= statusBadge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentAppointments)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No appointments yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Pharmacy Alerts</div>
            <div class="card-body">
                <?php if (empty($lowStock)): ?>
                    <p class="text-muted mb-0">No low stock or expiry alerts.</p>
                <?php endif; ?>
                <?php foreach ($lowStock as $m): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <div style="font-weight:500;"><?= clean($m['medicine_name']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted);">Expires <?= formatDate($m['expiry_date']) ?> · Stock: <?= $m['stock_quantity'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'doctor'): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#2563EB;"><i class="fa-solid fa-calendar-day"></i></div>
            <div><p class="stat-value"><?= $stats['today_appointments'] ?></p><p class="stat-label">Today's Appointments</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#0F766E;"><i class="fa-solid fa-calendar-check"></i></div>
            <div><p class="stat-value"><?= $stats['total_appointments'] ?></p><p class="stat-label">Total Appointments</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#F59E0B;"><i class="fa-solid fa-user-injured"></i></div>
            <div><p class="stat-value"><?= $stats['total_patients'] ?></p><p class="stat-label">My Patients</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#8B5CF6;"><i class="fa-solid fa-file-medical"></i></div>
            <div><p class="stat-value"><?= $stats['total_records'] ?></p><p class="stat-label">Medical Records</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#06B6D4;"><i class="fa-solid fa-flask-vial"></i></div>
            <div><p class="stat-value"><?= $stats['pending_lab_tests'] ?></p><p class="stat-label">Pending Lab Tests</p></div></div>
    </div>
</div>
<div class="card">
    <div class="card-header">Upcoming Appointments</div>
    <div class="card-body p-0">
        <table class="table-modern">
            <thead><tr><th>Patient</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($upcomingAppointments as $a): ?>
                <tr>
                    <td><?= clean($a['patient_name']) ?></td>
                    <td><?= formatDate($a['appointment_date']) ?></td>
                    <td><?= clean($a['appointment_time']) ?></td>
                    <td><?= statusBadge($a['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($upcomingAppointments)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No upcoming appointments.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'patient'): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#2563EB;"><i class="fa-solid fa-calendar-check"></i></div>
            <div><p class="stat-value"><?= $stats['total_appointments'] ?></p><p class="stat-label">Appointments</p></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#8B5CF6;"><i class="fa-solid fa-file-medical"></i></div>
            <div><p class="stat-value"><?= $stats['total_records'] ?></p><p class="stat-label">Medical Records</p></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#EF4444;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div><p class="stat-value"><?= formatMoney($stats['outstanding_balance']) ?></p><p class="stat-label">Outstanding Balance</p></div></div>
    </div>
</div>
<div class="card">
    <div class="card-header">My Recent Appointments</div>
    <div class="card-body p-0">
        <table class="table-modern">
            <thead><tr><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($myAppointments as $a): ?>
                <tr>
                    <td>Dr. <?= clean($a['doctor_name']) ?></td>
                    <td><?= formatDate($a['appointment_date']) ?></td>
                    <td><?= clean($a['appointment_time']) ?></td>
                    <td><?= statusBadge($a['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($myAppointments)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No appointments yet. <a href="<?= APP_URL ?>/appointments/create.php">Book one now</a>.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'accountant'): ?>
<div class="row g-3">
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#2563EB;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div><p class="stat-value"><?= $stats['total_bills'] ?></p><p class="stat-label">Total Bills</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#EF4444;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div><p class="stat-value"><?= $stats['unpaid_bills'] ?></p><p class="stat-label">Unpaid Bills</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#10B981;"><i class="fa-solid fa-money-bill-wave"></i></div>
            <div><p class="stat-value"><?= formatMoney($stats['revenue_today']) ?></p><p class="stat-label">Revenue Today</p></div></div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card"><div class="icon-box" style="background:#0EA5E9;"><i class="fa-solid fa-sack-dollar"></i></div>
            <div><p class="stat-value"><?= formatMoney($stats['revenue_total']) ?></p><p class="stat-label">Total Revenue</p></div></div>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'receptionist'): ?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#2563EB;"><i class="fa-solid fa-user-plus"></i></div>
            <div><p class="stat-value"><?= $stats['patients_today'] ?></p><p class="stat-label">Patients Registered Today</p></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#F59E0B;"><i class="fa-solid fa-calendar-check"></i></div>
            <div><p class="stat-value"><?= $stats['appointments_today'] ?></p><p class="stat-label">Appointments</p></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#0F766E;"><i class="fa-solid fa-user-injured"></i></div>
            <div><p class="stat-value"><?= $stats['total_patients'] ?></p><p class="stat-label">Total Patients</p></div></div>
    </div>
</div>
<?php endif; ?>

<?php if ($role === 'nurse'): ?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="stat-card"><div class="icon-box" style="background:#2563EB;"><i class="fa-solid fa-user-injured"></i></div>
            <div><p class="stat-value"><?= $stats['total_patients'] ?></p><p class="stat-label">Total Patients</p></div></div>
    </div>
    <div class="col-md-6">
        <div class="stat-card"><div class="icon-box" style="background:#F59E0B;"><i class="fa-solid fa-calendar-check"></i></div>
            <div><p class="stat-value"><?= $stats['appointments_today'] ?></p><p class="stat-label">Appointments</p></div></div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
