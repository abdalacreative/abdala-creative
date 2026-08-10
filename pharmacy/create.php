<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Add Medicine';
$pdo = getDB();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = cleanArray($_POST);

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
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO pharmacy (medicine_code, medicine_name, category, manufacturer, unit_price, stock_quantity, reorder_level, expiry_date, batch_number, managed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['TEMP', $name, $category, $manufacturer, $price, $stock, $reorder, $expiry, $batch, $_SESSION['user_id']]);
        $newId = $pdo->lastInsertId();
        $code = 'MED-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE pharmacy SET medicine_code = ? WHERE medicine_id = ?")->execute([$code, $newId]);
        $pdo->commit();

        logActivity("Added medicine {$code}", 'pharmacy');
        setFlash('success', 'Medicine added to inventory successfully.');
        redirect(APP_URL . '/pharmacy/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Add Medicine</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="medForm" onsubmit="return validateForm('medForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Medicine Name *</label><input type="text" name="medicine_name" class="form-control" value="<?= clean($old['medicine_name'] ?? '') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="<?= clean($old['category'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Manufacturer</label><input type="text" name="manufacturer" class="form-control" value="<?= clean($old['manufacturer'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Batch Number</label><input type="text" name="batch_number" class="form-control" value="<?= clean($old['batch_number'] ?? '') ?>"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Unit Price *</label><input type="number" step="0.01" min="0" name="unit_price" class="form-control" value="<?= clean($old['unit_price'] ?? '0') ?>" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Stock Quantity *</label><input type="number" min="0" name="stock_quantity" class="form-control" value="<?= clean($old['stock_quantity'] ?? '0') ?>" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Reorder Level</label><input type="number" min="0" name="reorder_level" class="form-control" value="<?= clean($old['reorder_level'] ?? '10') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Expiry Date *</label><input type="date" name="expiry_date" class="form-control" value="<?= clean($old['expiry_date'] ?? '') ?>" required></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Save Medicine</button>
            <a href="<?= APP_URL ?>/pharmacy/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
