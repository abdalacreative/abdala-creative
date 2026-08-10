<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant', 'patient']);

$pageTitle = 'Billing';
$pdo = getDB();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT b.*, pu.full_name AS patient_name FROM billing b
        JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id WHERE 1=1";
$params = [];

if ($role === 'patient') {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $patientId = $stmt->fetch()['patient_id'] ?? 0;
    $sql .= " AND b.patient_id = ?";
    $params[] = $patientId;
}
if ($search !== '') {
    $sql .= " AND (pu.full_name LIKE ? OR b.bill_code LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like);
}
if ($statusFilter !== '') {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY b.bill_date DESC";

$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search bills..." value="<?= clean($search) ?>" style="width:220px;">
            <select name="status" class="form-select" style="width:170px;">
                <option value="">All Statuses</option>
                <?php foreach (['unpaid','partially_paid','paid','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <?php if (in_array($role, ['admin', 'accountant'])): ?>
            <a href="<?= APP_URL ?>/billing/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Bill</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Bill #</th><th>Patient</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $b): ?>
                <tr>
                    <td><?= clean($b['bill_code']) ?></td>
                    <td><?= clean($b['patient_name']) ?></td>
                    <td><?= formatDate($b['bill_date']) ?></td>
                    <td><?= formatMoney($b['total_amount']) ?></td>
                    <td><?= statusBadge($b['status']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/billing/view.php?id=<?= $b['bill_id'] ?>" class="btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if (in_array($role, ['admin','accountant'])): ?>
                        <a href="<?= APP_URL ?>/billing/edit.php?id=<?= $b['bill_id'] ?>" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php if ($b['status'] !== 'paid'): ?>
                        <a href="<?= APP_URL ?>/payments/create.php?bill_id=<?= $b['bill_id'] ?>" class="btn-icon text-success" title="Record Payment"><i class="fa-solid fa-money-bill-wave"></i></a>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($role === 'admin'): ?>
                        <a href="<?= APP_URL ?>/billing/delete.php?id=<?= $b['bill_id'] ?>" class="btn-icon text-danger" title="Delete" data-confirm-delete="Delete this bill? This cannot be undone."><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="6" class="text-center text-muted py-4">No bills found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php if ($result['totalPages'] > 1): ?>
    <div class="card-body border-top"><nav><ul class="pagination justify-content-center mb-0">
        <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <li class="page-item <?= $i === $result['currentPage'] ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul></nav></div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
