<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant', 'patient']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Payment Report';
$pdo = getDB();
$role = $_SESSION['role'];
[$dateFrom, $dateTo] = reportDateRange();
$search = reportSearch();
$patientId = reportPatientId($pdo);
$page = (int)($_GET['page'] ?? 1);
$sql = "SELECT pay.*, b.bill_code, pu.full_name AS patient_name, ru.full_name AS received_by_name
        FROM payments pay
        JOIN billing b ON pay.bill_id = b.bill_id
        JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        LEFT JOIN users ru ON pay.received_by = ru.user_id
        WHERE pay.payment_date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
if ($role === 'patient') { $sql .= " AND b.patient_id = ?"; $params[] = $patientId; }
if ($search !== '') { $sql .= " AND (pay.payment_code LIKE ? OR b.bill_code LIKE ? OR pu.full_name LIKE ?)"; $like = "%{$search}%"; array_push($params, $like, $like, $like); }
$sql .= " ORDER BY pay.payment_date DESC";
$result = paginate($sql, $params, $page);
$totalPaid = 0; foreach ($result['data'] as $p) { $totalPaid += (float)$p['amount_paid']; }
include __DIR__ . '/../includes/header.php';
reportFilterForm('Receipt, bill, or patient...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Payments', $result['totalRecords'], 'fa-receipt'); ?><?php reportStatCard('Total On Page', formatMoney($totalPaid), 'fa-money-bill-wave', '#10B981'); ?></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table-modern"><thead><tr><th>Receipt #</th><th>Bill #</th><th>Patient</th><th>Date</th><th>Method</th><th>Amount</th><th>Received By</th></tr></thead><tbody>
<?php foreach ($result['data'] as $p): ?><tr><td><?= clean($p['payment_code']) ?></td><td><?= clean($p['bill_code']) ?></td><td><?= clean($p['patient_name']) ?></td><td><?= formatDate($p['payment_date']) ?></td><td><?= ucwords(str_replace('_', ' ', clean($p['payment_method']))) ?></td><td><?= formatMoney($p['amount_paid']) ?></td><td><?= clean($p['received_by_name'] ?: '-') ?></td></tr><?php endforeach; if (empty($result['data'])) reportEmptyRow(7); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
