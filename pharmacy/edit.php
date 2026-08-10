<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Edit Medicine';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT * FROM pharmacy WHERE medicine_id = ?");
$stmt->execute([$id]);
$medicine = $stmt->fetch();

if (!$medicine) {
    setFlash('error', 'Medicine not found.');
    redirect(APP_URL . '/pharmacy/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['medicine_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $price = (float)($_POST['unit_price'] ?? 0);
    $stock = (int)($_POST['stock_quantity'] ?? 0);
    $reorder = (int)($_POST['reorder_level'] ?? 10);
    $expiry = $_POST['expiry_date'] ?? '';
    $batch = trim($_POST['batch_number'] ?? '');

    if (empty($name) || empty($expiry)) {
        $errors[] = 'Medicine name and expiry date are required.';
    } elseif ($price < 0 || $stock < 0) {
        $errors[] = 'Price and stock cannot be negative.';
    } else {
        $upd = $pdo->prepare("UPDATE pharmacy SET medicine_name=?, category=?, manufacturer=?, unit_price=?, stock_quantity=?, reorder_level=?, expiry_date=?, batch_number=? WHERE medicine_id=?");
        $upd->execute([$name, $category, $manufacturer, $price, $stock, $reorder, $expiry, $batch, $id]);
        logActivity("Updated medicine #{$id}", 'pharmacy');
        setFlash('success', 'Medicine updated successfully.');
        redirect(APP_URL . '/pharmacy/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Edit Medicine: <?= clean($medicine['medicine_name']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="editMedForm" onsubmit="return validateForm('editMedForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Medicine Name *</label><input type="text" name="medicine_name" class="form-control" value="<?= clean($medicine['medicine_name']) ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="<?= clean($medicine['category']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Manufacturer</label><input type="text" name="manufacturer" class="form-control" value="<?= clean($medicine['manufacturer']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Batch Number</label><input type="text" name="batch_number" class="form-control" value="<?= clean($medicine['batch_number']) ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Unit Price *</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control" value="<?= clean($medicine['unit_price']) ?>" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Stock Quantity *</label><input type="number" min="0" name="stock_quantity" class="form-control" value="<?= clean($medicine['stock_quantity']) ?>" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Reorder Level</label><input type="number" min="0" name="reorder_level" class="form-control" value="<?= clean($medicine['reorder_level']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Expiry Date *</label><input type="date" name="expiry_date" class="form-control" value="<?= clean($medicine['expiry_date']) ?>" required></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Medicine</button>
            <a href="<?= APP_URL ?>/pharmacy/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
