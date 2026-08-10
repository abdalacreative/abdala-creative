<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant', 'patient']);

$pageTitle = 'Payments';
$pdo = getDB();
$role = $_SESSION['role'];
$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);

$sql = "SELECT pay.*, b.bill_code, pu.full_name AS patient_name
        FROM payments pay
        JOIN billing b ON pay.bill_id = b.bill_id
        JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
        WHERE 1=1";
$params = [];

if ($role === 'patient') {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $patientId = $stmt->fetch()['patient_id'] ?? 0;
    $sql .= " AND b.patient_id = ?";
    $params[] = $patientId;
}
if ($search !== '') {
    $sql .= " AND (pu.full_name LIKE ? OR pay.payment_code LIKE ? OR b.bill_code LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like);
}
$sql .= " ORDER BY pay.payment_date DESC";

$result = paginate($sql, $params, $page);

include __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form class="d-flex gap-2" method="GET">
            <input type="text" name="search" class="form-control" placeholder="Search payments..." value="<?= clean($search) ?>" style="width:280px;">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table-modern">
            <thead><tr><th>Receipt #</th><th>Bill #</th><th>Patient</th><th>Date</th><th>Method</th><th>Amount</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['data'] as $p): ?>
                <tr>
                    <td><?= clean($p['payment_code']) ?></td>
                    <td><a href="<?= APP_URL ?>/billing/view.php?id=<?= $p['bill_id'] ?>"><?= clean($p['bill_code']) ?></a></td>
                    <td><?= clean($p['patient_name']) ?></td>
                    <td><?= formatDate($p['payment_date']) ?></td>
                    <td><?= ucwords(str_replace('_',' ',$p['payment_method'])) ?></td>
                    <td><?= formatMoney($p['amount_paid']) ?></td>
                    <td><a href="<?= APP_URL ?>/payments/view.php?id=<?= $p['payment_id'] ?>" class="btn-icon" title="View / Print Receipt"><i class="fa-solid fa-receipt"></i></a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($result['data'])): ?><tr><td colspan="7" class="text-center text-muted py-4">No payments found.</td></tr><?php endif; ?>
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
