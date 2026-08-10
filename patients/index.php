<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'receptionist']);

$pageTitle = 'Patients';
$pdo = getDB();
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT p.*, u.full_name, u.email, u.phone, u.status FROM patients p
        JOIN users u ON p.user_id = u.user_id WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR p.patient_code LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY p.created_at DESC";

$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search by name, email, or code..." value="<?= clean($search) ?>" style="width:280px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <?php if (in_array($_SESSION['role'], ['admin', 'receptionist'])): ?>
            <a href="<?= APP_URL ?>/patients/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Patient</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Code</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($result['data'] as $p): ?>
                <tr>
                    <td><?= clean($p['patient_code']) ?></td>
                    <td><?= clean($p['full_name']) ?></td>
                    <td><?= clean($p['email']) ?></td>
                    <td><?= clean($p['phone'] ?: '—') ?></td>
                    <td><?= ucfirst(clean($p['gender'] ?: '—')) ?></td>
                    <td><?= statusBadge($p['status']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/patients/view.php?id=<?= $p['patient_id'] ?>" class="btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if (in_array($_SESSION['role'], ['admin', 'receptionist'])): ?>
                        <a href="<?= APP_URL ?>/patients/edit.php?id=<?= $p['patient_id'] ?>" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="<?= APP_URL ?>/patients/delete.php?id=<?= $p['patient_id'] ?>" class="btn-icon text-danger" title="Delete" data-confirm-delete="Delete this patient record? This cannot be undone."><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No patients found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php if ($result['totalPages'] > 1): ?>
    <div class="card-body border-top">
        <nav><ul class="pagination justify-content-center mb-0">
            <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
                <li class="page-item <?= $i === $result['currentPage'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
