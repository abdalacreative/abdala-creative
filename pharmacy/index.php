<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Pharmacy Inventory';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT * FROM pharmacy WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (medicine_name LIKE ? OR medicine_code LIKE ? OR category LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY expiry_date ASC";

$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search medicines..." value="<?= clean($search) ?>" style="width:280px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <a href="<?= APP_URL ?>/pharmacy/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Medicine</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Expiry</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $m):
                $isLowStock = $m['stock_quantity'] <= $m['reorder_level'];
                $isExpiringSoon = strtotime($m['expiry_date']) <= strtotime('+30 days');
                $isExpired = strtotime($m['expiry_date']) < strtotime('today');
            ?>
                <tr>
                    <td><?= clean($m['medicine_code']) ?></td>
                    <td><?= clean($m['medicine_name']) ?></td>
                    <td><?= clean($m['category'] ?: '—') ?></td>
                    <td><?= formatMoney($m['unit_price']) ?></td>
                    <td>
                        <?= $m['stock_quantity'] ?>
                        <?php if ($isLowStock): ?><span class="badge bg-danger ms-1">Low</span><?php endif; ?>
                    </td>
                    <td>
                        <?= formatDate($m['expiry_date']) ?>
                        <?php if ($isExpired): ?><span class="badge bg-danger ms-1">Expired</span>
                        <?php elseif ($isExpiringSoon): ?><span class="badge bg-warning ms-1">Soon</span><?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= APP_URL ?>/pharmacy/view.php?id=<?= $m['medicine_id'] ?>" class="btn-icon" title="View / Print"><i class="fa-solid fa-eye"></i></a>
                        <a href="<?= APP_URL ?>/pharmacy/edit.php?id=<?= $m['medicine_id'] ?>" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <a href="<?= APP_URL ?>/pharmacy/delete.php?id=<?= $m['medicine_id'] ?>" class="btn-icon text-danger" title="Delete" data-confirm-delete="Delete this medicine record?"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="7" class="text-center text-muted py-4">No medicines found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php if ($result['totalPages'] > 1): ?>
    <div class="card-body border-top"><nav><ul class="pagination justify-content-center mb-0">
        <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <li class="page-item <?= $i === $result['currentPage'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul></nav></div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
