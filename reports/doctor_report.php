<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Doctor Report';
$pdo = getDB();
[$dateFrom, $dateTo] = reportDateRange();
$search = reportSearch();
$page = (int)($_GET['page'] ?? 1);
$sql = "SELECT d.*, u.full_name, u.email, u.phone, u.status,
        (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.doctor_id AND a.appointment_date BETWEEN ? AND ?) AS appointments_count,
        (SELECT COUNT(*) FROM medical_records mr WHERE mr.doctor_id = d.doctor_id AND mr.visit_date BETWEEN ? AND ?) AS records_count,
        (SELECT COUNT(*) FROM lab_tests lt WHERE lt.doctor_id = d.doctor_id AND lt.test_date BETWEEN ? AND ?) AS lab_tests_count
        FROM doctors d JOIN users u ON d.user_id = u.user_id
        WHERE 1=1";
$params = [$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo];
if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR d.doctor_code LIKE ? OR d.specialization LIKE ? OR d.department LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}
$sql .= " ORDER BY appointments_count DESC, u.full_name ASC";
$result = paginate($sql, $params, $page);
$totalDoctors = $pdo->query("SELECT COUNT(*) c FROM doctors")->fetch()['c'];
include __DIR__ . '/../includes/header.php';
reportFilterForm('Doctor, code, specialization...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Total Doctors', $totalDoctors, 'fa-user-doctor'); ?><?php reportStatCard('Report Rows', $result['totalRecords'], 'fa-table-list', '#0EA5E9'); ?></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table-modern"><thead><tr><th>Code</th><th>Name</th><th>Specialization</th><th>Department</th><th>Appointments</th><th>Records</th><th>Lab Tests</th><th>Status</th></tr></thead><tbody>
<?php foreach ($result['data'] as $d): ?>
<tr><td><?= clean($d['doctor_code']) ?></td><td>Dr. <?= clean($d['full_name']) ?></td><td><?= clean($d['specialization']) ?></td><td><?= clean($d['department'] ?: '-') ?></td><td><?= (int)$d['appointments_count'] ?></td><td><?= (int)$d['records_count'] ?></td><td><?= (int)$d['lab_tests_count'] ?></td><td><?= statusBadge($d['status']) ?></td></tr>
<?php endforeach; if (empty($result['data'])) reportEmptyRow(8); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
