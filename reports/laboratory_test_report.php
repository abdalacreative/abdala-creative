<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'patient']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Laboratory Test Report';
$pdo = getDB();
$role = $_SESSION['role'];
[$dateFrom, $dateTo] = reportDateRange();
$search = reportSearch();
$doctorId = reportDoctorId($pdo);
$patientId = reportPatientId($pdo);
$page = (int)($_GET['page'] ?? 1);
$sql = "SELECT lt.*, pu.full_name AS patient_name, du.full_name AS doctor_name
        FROM lab_tests lt
        JOIN patients p ON lt.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON lt.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
        WHERE lt.test_date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
if ($role === 'doctor') { $sql .= " AND lt.doctor_id = ?"; $params[] = $doctorId; }
if ($role === 'patient') { $sql .= " AND lt.patient_id = ?"; $params[] = $patientId; }
if ($search !== '') { $sql .= " AND (lt.test_code LIKE ? OR lt.test_name LIKE ? OR pu.full_name LIKE ? OR du.full_name LIKE ?)"; $like = "%{$search}%"; array_push($params, $like, $like, $like, $like); }
$sql .= " ORDER BY lt.test_date DESC, lt.created_at DESC";
$result = paginate($sql, $params, $page);
include __DIR__ . '/../includes/header.php';
reportFilterForm('Test code, test name, patient...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Lab Tests', $result['totalRecords'], 'fa-flask-vial'); ?></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table-modern"><thead><tr><th>Code</th><th>Patient</th><th>Test</th><th>Category</th><th>Doctor</th><th>Date</th><th>Result</th><th>Status</th></tr></thead><tbody>
<?php foreach ($result['data'] as $t): ?><tr><td><?= clean($t['test_code']) ?></td><td><?= clean($t['patient_name']) ?></td><td><?= clean($t['test_name']) ?></td><td><?= ucfirst(clean($t['test_category'])) ?></td><td>Dr. <?= clean($t['doctor_name']) ?></td><td><?= formatDate($t['test_date']) ?></td><td><?= labResultBadge($t['result_flag']) ?></td><td><?= labStatusBadge($t['status']) ?></td></tr><?php endforeach; if (empty($result['data'])) reportEmptyRow(8); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
