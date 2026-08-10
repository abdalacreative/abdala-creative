<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'patient']);

$pageTitle = 'Lab Tests';
$pdo = getDB();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT lt.*, pu.full_name AS patient_name, du.full_name AS doctor_name
        FROM lab_tests lt
        JOIN patients p ON lt.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON lt.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
        WHERE 1=1";
$params = [];

if ($role === 'doctor') {
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $doctorId = $stmt->fetch()['doctor_id'] ?? 0;
    $sql .= " AND lt.doctor_id = ?";
    $params[] = $doctorId;
}
if ($role === 'patient') {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $patientId = $stmt->fetch()['patient_id'] ?? 0;
    $sql .= " AND lt.patient_id = ?";
    $params[] = $patientId;
}
if ($search !== '') {
    $sql .= " AND (pu.full_name LIKE ? OR lt.test_name LIKE ? OR lt.test_code LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like);
}
if ($statusFilter !== '') {
    $sql .= " AND lt.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY lt.test_date DESC, lt.created_at DESC";

$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search lab tests..." value="<?= clean($search) ?>" style="width:220px;">
            <select name="status" class="form-select" style="width:170px;">
                <option value="">All Statuses</option>
                <?php foreach (['ordered','sample_collected','in_progress','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <?php if (in_array($role, ['admin', 'doctor'])): ?>
            <a href="<?= APP_URL ?>/lab_tests/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Order Lab Test</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Code</th><th>Patient</th><th>Test</th><th>Doctor</th><th>Date</th><th>Result Flag</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $t): ?>
                <tr>
                    <td><?= clean($t['test_code']) ?></td>
                    <td><?= clean($t['patient_name']) ?></td>
                    <td><?= clean($t['test_name']) ?></td>
                    <td>Dr. <?= clean($t['doctor_name']) ?></td>
                    <td><?= formatDate($t['test_date']) ?></td>
                    <td><?= labResultBadge($t['result_flag']) ?></td>
                    <td><?= labStatusBadge($t['status']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/lab_tests/view.php?id=<?= $t['test_id'] ?>" class="btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if (in_array($role, ['admin','doctor'])): ?>
                        <a href="<?= APP_URL ?>/lab_tests/edit.php?id=<?= $t['test_id'] ?>" class="btn-icon" title="Edit / Enter Results"><i class="fa-solid fa-flask-vial"></i></a>
                        <?php endif; ?>
                        <?php if ($role === 'admin'): ?>
                        <a href="<?= APP_URL ?>/lab_tests/delete.php?id=<?= $t['test_id'] ?>" class="btn-icon text-danger" title="Delete" data-confirm-delete="Delete this lab test record?"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="8" class="text-center text-muted py-4">No lab tests found.</td></tr><?php endif; ?>
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
