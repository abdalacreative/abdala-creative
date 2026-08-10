<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Medicine Details';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT p.*, u.full_name AS managed_by_name FROM pharmacy p LEFT JOIN users u ON p.managed_by = u.user_id WHERE p.medicine_id = ?");
$stmt->execute([$id]);
$medicine = $stmt->fetch();

if (!$medicine) {
    setFlash('error', 'Medicine not found.');
    redirect(APP_URL . '/pharmacy/index.php');
}

$isExpired = strtotime($medicine['expiry_date']) < strtotime('today');
$isExpiringSoon = !$isExpired && strtotime($medicine['expiry_date']) <= strtotime('+30 days');
$isLowStock = $medicine['stock_quantity'] <= $medicine['reorder_level'];

include __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Medicine Details — <?= clean($medicine['medicine_code']) ?></span>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print me-1"></i> Print Label</button>
            <a href="<?= APP_URL ?>/pharmacy/edit.php?id=<?= $medicine['medicine_id'] ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen me-1"></i> Edit</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3"><strong>Medicine Name:</strong> <?= clean($medicine['medicine_name']) ?></div>
            <div class="col-md-6 mb-3"><strong>Code:</strong> <?= clean($medicine['medicine_code']) ?></div>
            <div class="col-md-6 mb-3"><strong>Category:</strong> <?= clean($medicine['category'] ?: '—') ?></div>
            <div class="col-md-6 mb-3"><strong>Manufacturer:</strong> <?= clean($medicine['manufacturer'] ?: '—') ?></div>
            <div class="col-md-6 mb-3"><strong>Batch Number:</strong> <?= clean($medicine['batch_number'] ?: '—') ?></div>
            <div class="col-md-6 mb-3"><strong>Unit Price:</strong> <?= formatMoney($medicine['unit_price']) ?></div>
            <div class="col-md-6 mb-3">
                <strong>Stock Quantity:</strong> <?= $medicine['stock_quantity'] ?>
                <?php if ($isLowStock): ?><span class="badge bg-danger ms-1">Low Stock</span><?php endif; ?>
            </div>
            <div class="col-md-6 mb-3"><strong>Reorder Level:</strong> <?= $medicine['reorder_level'] ?></div>
            <div class="col-md-6 mb-3">
                <strong>Expiry Date:</strong> <?= formatDate($medicine['expiry_date']) ?>
                <?php if ($isExpired): ?><span class="badge bg-danger ms-1">Expired</span>
                <?php elseif ($isExpiringSoon): ?><span class="badge bg-warning ms-1">Expiring Soon</span><?php endif; ?>
            </div>
            <div class="col-md-6 mb-3"><strong>Managed By:</strong> <?= clean($medicine['managed_by_name'] ?: '—') ?></div>
            <div class="col-md-6 mb-3"><strong>Added On:</strong> <?= formatDate($medicine['created_at']) ?></div>
        </div>
    </div>
</div>

<!-- Printable pharmacy label (compact format for sticker/label printing) -->
<div class="card print-only-label" style="max-width:400px;">
    <div class="card-header">Pharmacy Label Preview</div>
    <div class="card-body text-center" style="border:2px dashed var(--border-color);border-radius:8px;margin:10px;padding:16px;">
        <h5 class="mb-1"><?= clean($medicine['medicine_name']) ?></h5>
        <p class="mb-1" style="font-size:13px;">Batch: <?= clean($medicine['batch_number'] ?: 'N/A') ?></p>
        <p class="mb-1" style="font-size:13px;">Expiry: <?= formatDate($medicine['expiry_date']) ?></p>
        <p class="mb-0" style="font-size:13px;">Price: <?= formatMoney($medicine['unit_price']) ?></p>
        <hr>
        <p class="mb-0" style="font-size:11px;color:var(--text-muted);"><?= APP_NAME ?> Pharmacy</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
