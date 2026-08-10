<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);
require_once __DIR__ . '/_helpers.php';

$pageTitle = 'Pharmacy Report';
$pdo = getDB();
$search = reportSearch();
$page = (int)($_GET['page'] ?? 1);
$sql = "SELECT * FROM pharmacy WHERE 1=1";
$params = [];
if ($search !== '') { $sql .= " AND (medicine_code LIKE ? OR medicine_name LIKE ? OR category LIKE ? OR manufacturer LIKE ?)"; $like = "%{$search}%"; array_push($params, $like, $like, $like, $like); }
$sql .= " ORDER BY expiry_date ASC, medicine_name ASC";
$result = paginate($sql, $params, $page);
$totalMedicines = $pdo->query("SELECT COUNT(*) c FROM pharmacy")->fetch()['c'];
$lowStock = $pdo->query("SELECT COUNT(*) c FROM pharmacy WHERE stock_quantity <= reorder_level")->fetch()['c'];
$expiring = $pdo->query("SELECT COUNT(*) c FROM pharmacy WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch()['c'];
include __DIR__ . '/../includes/header.php';
reportFilterForm('Medicine, code, category...');
?>
<div class="row g-3 mb-4"><?php reportStatCard('Medicines', $totalMedicines, 'fa-pills'); ?><?php reportStatCard('Low Stock', $lowStock, 'fa-arrow-trend-down', '#F59E0B'); ?><?php reportStatCard('Expiring Soon', $expiring, 'fa-triangle-exclamation', '#EF4444'); ?></div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table-modern"><thead><tr><th>Code</th><th>Medicine</th><th>Category</th><th>Unit Price</th><th>Stock</th><th>Reorder</th><th>Expiry</th></tr></thead><tbody>
<?php foreach ($result['data'] as $m): ?><tr><td><?= clean($m['medicine_code']) ?></td><td><?= clean($m['medicine_name']) ?></td><td><?= clean($m['category'] ?: '-') ?></td><td><?= formatMoney($m['unit_price']) ?></td><td><?= (int)$m['stock_quantity'] ?></td><td><?= (int)$m['reorder_level'] ?></td><td><?= formatDate($m['expiry_date']) ?></td></tr><?php endforeach; if (empty($result['data'])) reportEmptyRow(7); ?>
</tbody></table></div></div><?php reportPagination($result); ?></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
