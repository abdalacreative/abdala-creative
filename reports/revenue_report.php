<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Revenue Report';
$pdo = getDB();
[$dateFrom, $dateTo] = reportDateRange();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) c FROM payments WHERE payment_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$totalRevenue = $stmt->fetch()['c'];
$stmt = $pdo->prepare("SELECT payment_method, COUNT(*) payments_count, COALESCE(SUM(amount_paid),0) total FROM payments WHERE payment_date BETWEEN ? AND ? GROUP BY payment_method ORDER BY total DESC");
$stmt->execute([$dateFrom, $dateTo]);
$byMethod = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT payment_date, COUNT(*) payments_count, COALESCE(SUM(amount_paid),0) total FROM payments WHERE payment_date BETWEEN ? AND ? GROUP BY payment_date ORDER BY payment_date DESC");
$stmt->execute([$dateFrom, $dateTo]);
$byDay = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
reportFilterForm('Revenue search is not used here...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Revenue', formatMoney($totalRevenue), 'fa-sack-dollar', '#10B981'); ?><?php reportStatCard('Payment Days', count($byDay), 'fa-calendar-days', '#0EA5E9'); ?></div>
<div class="row g-3">
<div class="col-lg-5"><div class="card"><div class="card-header">Revenue by Method</div><div class="card-body p-0"><table class="table-modern"><thead><tr><th>Method</th><th>Payments</th><th class="text-end">Total</th></tr></thead><tbody>
<?php foreach ($byMethod as $m): ?><tr><td><?= ucwords(str_replace('_', ' ', clean($m['payment_method']))) ?></td><td><?= (int)$m['payments_count'] ?></td><td class="text-end"><?= formatMoney($m['total']) ?></td></tr><?php endforeach; if (empty($byMethod)) reportEmptyRow(3); ?>
</tbody></table></div></div></div>
<div class="col-lg-7"><div class="card"><div class="card-header">Daily Revenue</div><div class="card-body p-0"><table class="table-modern"><thead><tr><th>Date</th><th>Payments</th><th class="text-end">Revenue</th></tr></thead><tbody>
<?php foreach ($byDay as $d): ?><tr><td><?= formatDate($d['payment_date']) ?></td><td><?= (int)$d['payments_count'] ?></td><td class="text-end"><?= formatMoney($d['total']) ?></td></tr><?php endforeach; if (empty($byDay)) reportEmptyRow(3); ?>
</tbody></table></div></div></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
