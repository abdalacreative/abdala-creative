<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Receptionists';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT r.*, u.full_name, u.email, u.phone, u.status FROM receptionists r JOIN users u ON r.user_id = u.user_id WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR r.receptionist_code LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like];
}
$sql .= " ORDER BY r.created_at DESC";
$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search receptionists..." value="<?= clean($search) ?>" style="width:280px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <a href="<?= APP_URL ?>/receptionists/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Receptionist</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Shift</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $r): ?>
                <tr>
                    <td><?= clean($r['receptionist_code']) ?></td>
                    <td><?= clean($r['full_name']) ?></td>
                    <td><?= clean($r['email']) ?></td>
                    <td><?= ucfirst(clean($r['shift'])) ?></td>
                    <td><?= statusBadge($r['status']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/receptionists/edit.php?id=<?= $r['receptionist_id'] ?>" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <a href="<?= APP_URL ?>/receptionists/delete.php?id=<?= $r['receptionist_id'] ?>" class="btn-icon text-danger" title="Delete" data-confirm-delete="Delete this receptionist record?"><i class="fa-solid fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="6" class="text-center text-muted py-4">No receptionists found.</td></tr><?php endif; ?>
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
