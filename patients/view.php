<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'receptionist']);

$pageTitle = 'Patient Details';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.email, u.phone, u.status FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    setFlash('error', 'Patient not found.');
    redirect(APP_URL . '/patients/index.php');
}

$appointments = $pdo->prepare("
    SELECT a.*, du.full_name AS doctor_name FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
    WHERE a.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 10");
$appointments->execute([$id]);
$appointments = $appointments->fetchAll();

$records = $pdo->prepare("
    SELECT mr.*, du.full_name AS doctor_name FROM medical_records mr
    JOIN doctors d ON mr.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
    WHERE mr.patient_id = ? ORDER BY mr.visit_date DESC LIMIT 10");
$records->execute([$id]);
$records = $records->fetchAll();

$bills = $pdo->prepare("SELECT * FROM billing WHERE patient_id = ? ORDER BY bill_date DESC LIMIT 10");
$bills->execute([$id]);
$bills = $bills->fetchAll();

$labTests = $pdo->prepare("
    SELECT lt.*, du.full_name AS doctor_name FROM lab_tests lt
    JOIN doctors d ON lt.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
    WHERE lt.patient_id = ? ORDER BY lt.test_date DESC LIMIT 10");
$labTests->execute([$id]);
$labTests = $labTests->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($patient['full_name']) ?>&background=2563EB&color=fff&size=96" class="rounded-circle mb-3" width="90" height="90">
                <h5 class="mb-0"><?= clean($patient['full_name']) ?></h5>
                <p class="text-muted mb-2"><?= clean($patient['patient_code']) ?></p>
                <?= statusBadge($patient['status']) ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Patient Information</div>
            <div class="card-body">
                <p><strong>Email:</strong> <?= clean($patient['email']) ?></p>
                <p><strong>Phone:</strong> <?= clean($patient['phone'] ?: '—') ?></p>
                <p><strong>DOB:</strong> <?= formatDate($patient['date_of_birth']) ?></p>
                <p><strong>Gender:</strong> <?= ucfirst(clean($patient['gender'] ?: '—')) ?></p>
                <p><strong>Blood Group:</strong> <?= clean($patient['blood_group'] ?: '—') ?></p>
                <p><strong>Address:</strong> <?= clean($patient['address'] ?: '—') ?>, <?= clean($patient['city'] ?: '') ?></p>
                <p><strong>Emergency Contact:</strong> <?= clean($patient['emergency_contact_name'] ?: '—') ?> (<?= clean($patient['emergency_contact_phone'] ?: '—') ?>)</p>
                <p class="mb-0"><strong>Allergies:</strong> <?= clean($patient['allergies'] ?: 'None recorded') ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">Recent Appointments</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($appointments as $a): ?>
                        <tr><td>Dr. <?= clean($a['doctor_name']) ?></td><td><?= formatDate($a['appointment_date']) ?></td><td><?= clean($a['appointment_time']) ?></td><td><?= statusBadge($a['status']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?><tr><td colspan="4" class="text-center text-muted py-3">No appointments.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Medical Records</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Visit Date</th><th>Doctor</th><th>Diagnosis</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><?= formatDate($r['visit_date']) ?></td>
                            <td>Dr. <?= clean($r['doctor_name']) ?></td>
                            <td><?= clean(mb_strimwidth($r['diagnosis'] ?? '', 0, 50, '...')) ?></td>
                            <td><a href="<?= APP_URL ?>/medical_records/view.php?id=<?= $r['record_id'] ?>" class="btn-icon"><i class="fa-solid fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($records)): ?><tr><td colspan="4" class="text-center text-muted py-3">No medical records.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Lab Tests</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Test</th><th>Date</th><th>Flag</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($labTests as $t): ?>
                        <tr>
                            <td><?= clean($t['test_name']) ?></td>
                            <td><?= formatDate($t['test_date']) ?></td>
                            <td><?= labResultBadge($t['result_flag']) ?></td>
                            <td><?= labStatusBadge($t['status']) ?></td>
                            <td><a href="<?= APP_URL ?>/lab_tests/view.php?id=<?= $t['test_id'] ?>" class="btn-icon"><i class="fa-solid fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($labTests)): ?><tr><td colspan="5" class="text-center text-muted py-3">No lab tests.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Billing History</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Bill #</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($bills as $b): ?>
                        <tr><td><?= clean($b['bill_code']) ?></td><td><?= formatDate($b['bill_date']) ?></td><td><?= formatMoney($b['total_amount']) ?></td><td><?= statusBadge($b['status']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($bills)): ?><tr><td colspan="4" class="text-center text-muted py-3">No bills.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
