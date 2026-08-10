<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Patient Report';
$pdo = getDB();
$role = $_SESSION['role'];
[$dateFrom, $dateTo] = reportDateRange();
$search = reportSearch();
$doctorId = reportDoctorId($pdo);
$page = (int)($_GET['page'] ?? 1);

$doctorAppointmentSql = $role === 'doctor' ? " AND doctor_id = " . (int)$doctorId : "";
$doctorRecordSql = $role === 'doctor' ? " AND doctor_id = " . (int)$doctorId : "";

$sql = "SELECT p.*, u.full_name, u.email, u.phone, u.status,
        (SELECT COUNT(*) FROM appointments a2 WHERE a2.patient_id = p.patient_id AND a2.appointment_date BETWEEN ? AND ?{$doctorAppointmentSql}) AS appointments_count,
        (SELECT MAX(mr2.visit_date) FROM medical_records mr2 WHERE mr2.patient_id = p.patient_id{$doctorRecordSql}) AS last_visit
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE DATE(p.created_at) <= ?";
$params = [$dateFrom, $dateTo, $dateTo];
if ($role === 'doctor') {
    $sql .= " AND (
        EXISTS (SELECT 1 FROM appointments a3 WHERE a3.patient_id = p.patient_id AND a3.doctor_id = ?)
        OR EXISTS (SELECT 1 FROM medical_records mr3 WHERE mr3.patient_id = p.patient_id AND mr3.doctor_id = ?)
    )";
    array_push($params, $doctorId, $doctorId);
}
if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR p.patient_code LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like);
}
$sql .= " ORDER BY p.created_at DESC";
$result = paginate($sql, $params, $page);

$stmt = $pdo->prepare("SELECT COUNT(*) c FROM patients WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$newPatients = $stmt->fetch()['c'];
$totalPatients = $pdo->query("SELECT COUNT(*) c FROM patients")->fetch()['c'];

include __DIR__ . '/../includes/header.php';
reportFilterForm('Patient name, email, or code...');
?>
<div class="row g-3 mb-4">
    <?php reportStatCard('Total Patients', $totalPatients, 'fa-user-injured'); ?>
    <?php reportStatCard('New Patients', $newPatients, 'fa-user-plus', '#10B981'); ?>
    <?php reportStatCard('Report Rows', $result['totalRecords'], 'fa-table-list', '#0EA5E9'); ?>
</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive">
<table class="table-modern"><thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Gender</th><th>Appointments</th><th>Last Visit</th><th>Status</th></tr></thead><tbody>
<?php foreach ($result['data'] as $p): ?>
<tr><td><?= clean($p['patient_code']) ?></td><td><?= clean($p['full_name']) ?></td><td><?= clean($p['phone'] ?: '-') ?></td><td><?= ucfirst(clean($p['gender'] ?: '-')) ?></td><td><?= (int)$p['appointments_count'] ?></td><td><?= formatDate($p['last_visit']) ?></td><td><?= statusBadge($p['status']) ?></td></tr>
<?php endforeach; if (empty($result['data'])) reportEmptyRow(7); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
