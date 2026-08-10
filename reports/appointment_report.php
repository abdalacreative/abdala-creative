<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'receptionist']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Appointment Report';
$pdo = getDB();
$role = $_SESSION['role'];
[$dateFrom, $dateTo] = reportDateRange();
$search = reportSearch();
$doctorId = reportDoctorId($pdo);
$page = (int)($_GET['page'] ?? 1);
$sql = "SELECT a.*, pu.full_name AS patient_name, du.full_name AS doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
        WHERE a.appointment_date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
if ($role === 'doctor') { $sql .= " AND a.doctor_id = ?"; $params[] = $doctorId; }
if ($search !== '') { $sql .= " AND (a.appointment_code LIKE ? OR pu.full_name LIKE ? OR du.full_name LIKE ?)"; $like = "%{$search}%"; array_push($params, $like, $like, $like); }
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = paginate($sql, $params, $page);
$completed = 0; $pending = 0;
foreach ($result['data'] as $row) { if ($row['status'] === 'completed') $completed++; if ($row['status'] === 'pending') $pending++; }
include __DIR__ . '/../includes/header.php';
reportFilterForm('Appointment code, patient, doctor...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Appointments', $result['totalRecords'], 'fa-calendar-check'); ?><?php reportStatCard('Completed On Page', $completed, 'fa-circle-check', '#10B981'); ?><?php reportStatCard('Pending On Page', $pending, 'fa-clock', '#F59E0B'); ?></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table-modern"><thead><tr><th>Code</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead><tbody>
<?php foreach ($result['data'] as $a): ?><tr><td><?= clean($a['appointment_code']) ?></td><td><?= clean($a['patient_name']) ?></td><td>Dr. <?= clean($a['doctor_name']) ?></td><td><?= formatDate($a['appointment_date']) ?></td><td><?= clean($a['appointment_time']) ?></td><td><?= clean($a['reason'] ?: '-') ?></td><td><?= statusBadge($a['status']) ?></td></tr><?php endforeach; if (empty($result['data'])) reportEmptyRow(7); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
