<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'patient']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Medical Record Report';
$pdo = getDB();
$role = $_SESSION['role'];
[$dateFrom, $dateTo] = reportDateRange();
$search = reportSearch();
$doctorId = reportDoctorId($pdo);
$patientId = reportPatientId($pdo);
$page = (int)($_GET['page'] ?? 1);
$sql = "SELECT mr.*, pu.full_name AS patient_name, du.full_name AS doctor_name
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
        WHERE mr.visit_date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
if ($role === 'doctor') { $sql .= " AND mr.doctor_id = ?"; $params[] = $doctorId; }
if ($role === 'patient') { $sql .= " AND mr.patient_id = ?"; $params[] = $patientId; }
if ($search !== '') { $sql .= " AND (mr.record_code LIKE ? OR pu.full_name LIKE ? OR du.full_name LIKE ? OR mr.diagnosis LIKE ?)"; $like = "%{$search}%"; array_push($params, $like, $like, $like, $like); }
$sql .= " ORDER BY mr.visit_date DESC";
$result = paginate($sql, $params, $page);
include __DIR__ . '/../includes/header.php';
reportFilterForm('Record code, patient, diagnosis...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Medical Records', $result['totalRecords'], 'fa-file-medical'); ?></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table-modern"><thead><tr><th>Code</th><th>Patient</th><th>Doctor</th><th>Visit Date</th><th>Diagnosis</th><th>Prescription</th></tr></thead><tbody>
<?php foreach ($result['data'] as $r): ?><tr><td><?= clean($r['record_code']) ?></td><td><?= clean($r['patient_name']) ?></td><td>Dr. <?= clean($r['doctor_name']) ?></td><td><?= formatDate($r['visit_date']) ?></td><td><?= clean(mb_strimwidth($r['diagnosis'] ?: '-', 0, 80, '...')) ?></td><td><?= clean(mb_strimwidth($r['prescription'] ?: '-', 0, 70, '...')) ?></td></tr><?php endforeach; if (empty($result['data'])) reportEmptyRow(6); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
