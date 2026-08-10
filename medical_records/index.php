<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'nurse', 'patient']);

$pageTitle = 'Medical Records';
$pdo = getDB();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT mr.*, pu.full_name AS patient_name, du.full_name AS doctor_name
        FROM medical_records mr
        JOIN patients p ON mr.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        JOIN doctors d ON mr.doctor_id = d.doctor_id JOIN users du ON d.user_id = du.user_id
        WHERE 1=1";
$params = [];

if ($role === 'doctor') {
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $doctorId = $stmt->fetch()['doctor_id'] ?? 0;
    $sql .= " AND mr.doctor_id = ?";
    $params[] = $doctorId;
}
if ($role === 'patient') {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $patientId = $stmt->fetch()['patient_id'] ?? 0;
    $sql .= " AND mr.patient_id = ?";
    $params[] = $patientId;
}
if ($search !== '') {
    $sql .= " AND (pu.full_name LIKE ? OR mr.diagnosis LIKE ? OR mr.record_code LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like);
}
$sql .= " ORDER BY mr.visit_date DESC";

$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search records..." value="<?= clean($search) ?>" style="width:280px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <?php if ($role === 'doctor'): ?>
            <a href="<?= APP_URL ?>/medical_records/create.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> New Record</a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Code</th><th>Patient</th><th>Doctor</th><th>Visit Date</th><th>Diagnosis</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $r): ?>
                <tr>
                    <td><?= clean($r['record_code']) ?></td>
                    <td><?= clean($r['patient_name']) ?></td>
                    <td>Dr. <?= clean($r['doctor_name']) ?></td>
                    <td><?= formatDate($r['visit_date']) ?></td>
                    <td><?= clean(mb_strimwidth($r['diagnosis'] ?? '—', 0, 40, '...')) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/medical_records/view.php?id=<?= $r['record_id'] ?>" class="btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if ($role === 'doctor'): ?>
                        <a href="<?= APP_URL ?>/medical_records/edit.php?id=<?= $r['record_id'] ?>" class="btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php endif; ?>
                        <?php if ($role === 'admin'): ?>
                        <a href="<?= APP_URL ?>/medical_records/delete.php?id=<?= $r['record_id'] ?>" class="btn-icon text-danger" title="Delete" data-confirm-delete="Delete this medical record?"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="6" class="text-center text-muted py-4">No medical records found.</td></tr><?php endif; ?>
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
