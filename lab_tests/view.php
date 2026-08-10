<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'patient']);

$pageTitle = 'Lab Test Report';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];

$stmt = $pdo->prepare("
    SELECT lt.*, pu.full_name AS patient_name, p.patient_code, p.date_of_birth, p.gender, p.user_id AS patient_user_id,
           du.full_name AS doctor_name, ru.full_name AS requested_by_name, cu.full_name AS completed_by_name
    FROM lab_tests lt
    JOIN patients p ON lt.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
    JOIN doctors d ON lt.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
    LEFT JOIN users ru ON lt.requested_by = ru.user_id
    LEFT JOIN users cu ON lt.completed_by = cu.user_id
    WHERE lt.test_id = ?");
$stmt->execute([$id]);
$test = $stmt->fetch();

if (!$test) {
    setFlash('error', 'Lab test not found.');
    redirect(APP_URL . '/lab_tests/index.php');
}

if ($role === 'patient' && $test['patient_user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>You can only view your own lab test reports.</p>');
}

include __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center no-print">
        <span>Lab Test Report</span>
        <div>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print me-1"></i> Print Report</button>
            <?php if (in_array($role, ['admin','doctor'])): ?>
            <a href="<?= APP_URL ?>/lab_tests/edit.php?id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-flask-vial me-1"></i> Update Results</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <!-- Report Header -->
        <div class="text-center mb-4 pb-3 border-bottom">
            <h4 class="mb-0"><?= APP_NAME ?></h4>
            <p class="text-muted mb-0" style="font-size:13px;">Laboratory Test Report</p>
            <p class="text-muted mb-0" style="font-size:12px;">Report No: <?= clean($test['test_code']) ?></p>
        </div>

        <!-- Patient & Test Info -->
        <div class="row mb-4">
            <div class="col-md-6 mb-2"><strong>Patient Name:</strong> <?= clean($test['patient_name']) ?></div>
            <div class="col-md-6 mb-2"><strong>Patient ID:</strong> <?= clean($test['patient_code']) ?></div>
            <div class="col-md-6 mb-2"><strong>Date of Birth:</strong> <?= formatDate($test['date_of_birth']) ?></div>
            <div class="col-md-6 mb-2"><strong>Gender:</strong> <?= ucfirst(clean($test['gender'] ?: '—')) ?></div>
            <div class="col-md-6 mb-2"><strong>Referring Doctor:</strong> Dr. <?= clean($test['doctor_name']) ?></div>
            <div class="col-md-6 mb-2"><strong>Test Date:</strong> <?= formatDate($test['test_date']) ?></div>
            <div class="col-md-6 mb-2"><strong>Sample Collected:</strong> <?= $test['sample_collected_at'] ? formatDate($test['sample_collected_at'], 'M d, Y h:i A') : 'Not yet collected' ?></div>
            <div class="col-md-6 mb-2"><strong>Status:</strong> <?= labStatusBadge($test['status']) ?></div>
        </div>

        <!-- Test Details -->
        <table class="table-modern mb-4">
            <thead><tr><th>Test Name</th><th>Category</th><th>Result</th><th>Reference Range</th><th>Unit</th><th>Flag</th></tr></thead>
            <tbody>
                <tr>
                    <td><?= clean($test['test_name']) ?></td>
                    <td><?= ucfirst(clean($test['test_category'])) ?></td>
                    <td><?= nl2br(clean($test['result_value'] ?: 'Pending')) ?></td>
                    <td><?= clean($test['reference_range'] ?: '—') ?></td>
                    <td><?= clean($test['unit'] ?: '—') ?></td>
                    <td><?= labResultBadge($test['result_flag']) ?></td>
                </tr>
            </tbody>
        </table>

        <?php if ($test['result_flag'] === 'critical'): ?>
        <div class="alert alert-danger no-print">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><strong>Critical Result:</strong> This result requires immediate clinical attention.
        </div>
        <?php endif; ?>

        <?php if (!empty($test['technician_notes'])): ?>
        <div class="mb-3"><strong>Technician Notes:</strong><p class="mb-0"><?= nl2br(clean($test['technician_notes'])) ?></p></div>
        <?php endif; ?>

        <?php if (!empty($test['doctor_remarks'])): ?>
        <div class="mb-3"><strong>Doctor's Remarks / Interpretation:</strong><p class="mb-0"><?= nl2br(clean($test['doctor_remarks'])) ?></p></div>
        <?php endif; ?>

        <hr>
        <div class="row" style="font-size:12px;color:var(--text-muted);">
            <div class="col-md-6">Requested by: <?= clean($test['requested_by_name'] ?: '—') ?></div>
            <div class="col-md-6">Verified/Completed by: <?= clean($test['completed_by_name'] ?: 'Pending') ?></div>
        </div>
        <p class="mt-3 mb-0" style="font-size:11px;color:var(--text-muted);">
            This is a computer-generated report from <?= APP_NAME ?>. For questions about these results, please consult your referring doctor.
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
