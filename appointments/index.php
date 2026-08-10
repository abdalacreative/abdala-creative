<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'receptionist', 'patient']);

$pageTitle = 'Appointments';
$pdo = getDB();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT a.*, pu.full_name AS patient_name, du.full_name AS doctor_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
        WHERE 1=1";
$params = [];

if ($role === 'doctor') {
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $doctorId = $stmt->fetch()['doctor_id'] ?? 0;
    $sql .= " AND a.doctor_id = ?";
    $params[] = $doctorId;
}
if ($role === 'patient') {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $patientId = $stmt->fetch()['patient_id'] ?? 0;
    $sql .= " AND a.patient_id = ?";
    $params[] = $patientId;
}
if ($search !== '') {
    $sql .= " AND (pu.full_name LIKE ? OR du.full_name LIKE ? OR a.appointment_code LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like);
}
if ($statusFilter !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= clean($search) ?>" style="width:220px;">
            <select name="status" class="form-select" style="width:160px;">
                <option value="">All Statuses</option>
                <?php foreach (['pending','confirmed','completed','cancelled','no_show'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <?php if (in_array($role, ['admin', 'receptionist', 'patient'])): ?>
            <a href="<?= APP_URL ?>/appointments/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Appointment</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Code</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $a): ?>
                <tr>
                    <td><?= clean($a['appointment_code']) ?></td>
                    <td><?= clean($a['patient_name']) ?></td>
                    <td>Dr. <?= clean($a['doctor_name']) ?></td>
                    <td><?= formatDate($a['appointment_date']) ?></td>
                    <td><?= clean($a['appointment_time']) ?></td>
                    <td><?= statusBadge($a['status']) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/appointments/view.php?id=<?= $a['appointment_id'] ?>" class="btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if (in_array($role, ['admin','receptionist','doctor'])): ?>
                        <a href="<?= APP_URL ?>/appointments/edit.php?id=<?= $a['appointment_id'] ?>" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php endif; ?>
                        <?php if (in_array($role, ['admin','receptionist'])): ?>
                        <a href="<?= APP_URL ?>/appointments/delete.php?id=<?= $a['appointment_id'] ?>" class="btn-icon text-danger" title="Cancel" data-confirm-delete="Cancel this appointment?"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="7" class="text-center text-muted py-4">No appointments found.</td></tr><?php endif; ?>
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
