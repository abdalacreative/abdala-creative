<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant', 'patient']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Billing Report';
$pdo = getDB();
$role = $_SESSION['role'];
[$dateFrom, $dateTo] = reportDateRange();
$search = reportSearch();
$patientId = reportPatientId($pdo);
$page = (int)($_GET['page'] ?? 1);
$sql = "SELECT b.*, pu.full_name AS patient_name,
        COALESCE(pay_summary.paid_amount,0) AS paid_amount,
        (b.total_amount - COALESCE(pay_summary.paid_amount,0)) AS balance
        FROM billing b
        JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        LEFT JOIN (
            SELECT bill_id, SUM(amount_paid) AS paid_amount
            FROM payments
            GROUP BY bill_id
        ) pay_summary ON b.bill_id = pay_summary.bill_id
        WHERE b.bill_date BETWEEN ? AND ?";
$params = [$dateFrom, $dateTo];
if ($role === 'patient') { $sql .= " AND b.patient_id = ?"; $params[] = $patientId; }
if ($search !== '') { $sql .= " AND (b.bill_code LIKE ? OR pu.full_name LIKE ?)"; $like = "%{$search}%"; array_push($params, $like, $like); }
$sql .= " ORDER BY b.bill_date DESC";
$result = paginate($sql, $params, $page);
$totalAmount = 0; $paidAmount = 0;
foreach ($result['data'] as $b) { $totalAmount += (float)$b['total_amount']; $paidAmount += (float)$b['paid_amount']; }
include __DIR__ . '/../includes/header.php';
reportFilterForm('Bill code or patient...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Bills', $result['totalRecords'], 'fa-file-invoice-dollar'); ?><?php reportStatCard('Total On Page', formatMoney($totalAmount), 'fa-sack-dollar', '#0EA5E9'); ?><?php reportStatCard('Paid On Page', formatMoney($paidAmount), 'fa-money-bill-wave', '#10B981'); ?></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table-modern"><thead><tr><th>Bill #</th><th>Patient</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
<?php foreach ($result['data'] as $b): ?><tr><td><?= clean($b['bill_code']) ?></td><td><?= clean($b['patient_name']) ?></td><td><?= formatDate($b['bill_date']) ?></td><td><?= formatMoney($b['total_amount']) ?></td><td><?= formatMoney($b['paid_amount']) ?></td><td><?= formatMoney($b['balance']) ?></td><td><?= statusBadge($b['status']) ?></td></tr><?php endforeach; if (empty($result['data'])) reportEmptyRow(7); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
