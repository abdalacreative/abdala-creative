<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Nurses';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT n.*, u.full_name, u.email, u.phone, u.status, du.full_name AS doctor_name
        FROM nurses n JOIN users u ON n.user_id = u.user_id
        LEFT JOIN doctors d ON n.assigned_doctor_id = d.doctor_id
        LEFT JOIN users du ON d.user_id = du.user_id WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR n.nurse_code LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like];
}
$sql .= " ORDER BY n.created_at DESC";
$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search nurses..." value="<?= clean($search) ?>" style="width:280px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <a href="<?= APP_URL ?>/nurses/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Nurse</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Shift</th><th>Assigned Doctor</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $n): ?>
                <tr>
                    <td><?= clean($n['nurse_code']) ?></td>
                    <td><?= clean($n['full_name']) ?></td>
                    <td><?= clean($n['department'] ?: '—') ?></td>
                    <td><?= ucfirst(clean($n['shift'])) ?></td>
                    <td><?= $n['doctor_name'] ? 'Dr. '.clean($n['doctor_name']) : '—' ?></td>
                    <td><?= statusBadge($n['status']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/nurses/edit.php?id=<?= $n['nurse_id'] ?>" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <a href="<?= APP_URL ?>/nurses/delete.php?id=<?= $n['nurse_id'] ?>" class="btn-icon text-danger" title="Delete" data-confirm-delete="Delete this nurse record?"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="7" class="text-center text-muted py-4">No nurses found.</td></tr><?php endif; ?>
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
