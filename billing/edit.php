<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant']);

$pageTitle = 'Edit Bill';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT b.*, pu.full_name AS patient_name FROM billing b JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id WHERE b.bill_id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if (!$bill) {
    setFlash('error', 'Bill not found.');
    redirect(APP_URL . '/billing/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $billDate = $_POST['bill_date'] ?? $bill['bill_date'];
    $consultationFee = (float)($_POST['consultation_fee'] ?? 0);
    $medicineFee = (float)($_POST['medicine_fee'] ?? 0);
    $labFee = (float)($_POST['lab_fee'] ?? 0);
    $otherFee = (float)($_POST['other_fee'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $status = $_POST['status'] ?? $bill['status'];

    $subtotal = $consultationFee + $medicineFee + $labFee + $otherFee;
    $total = max(0, $subtotal - $discount + $tax);

    $upd = $pdo->prepare("UPDATE billing SET bill_date=?, consultation_fee=?, medicine_fee=?, lab_fee=?, other_fee=?, discount=?, tax=?, total_amount=?, status=? WHERE bill_id=?");
    $upd->execute([$billDate, $consultationFee, $medicineFee, $labFee, $otherFee, $discount, $tax, $total, $status, $id]);

    logActivity("Updated bill #{$id}", 'billing');
    setFlash('success', 'Bill updated successfully.');
    redirect(APP_URL . '/billing/view.php?id=' . $id);
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Edit Bill: <?= clean($bill['bill_code']) ?> — <?= clean($bill['patient_name']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="editBillForm" onsubmit="return validateForm('editBillForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Bill Date *</label><input type="date" name="bill_date" class="form-control" value="<?= clean($bill['bill_date']) ?>" required></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['unpaid','partially_paid','paid','cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $bill['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3"><label class="form-label">Consultation Fee</label><input type="number" step="0.01" min="0" name="consultation_fee" class="form-control calc-field" value="<?= clean($bill['consultation_fee']) ?>"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Medicine Fee</label><input type="number" step="0.01" min="0" name="medicine_fee" class="form-control calc-field" value="<?= clean($bill['medicine_fee']) ?>"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Lab Fee</label><input type="number" step="0.01" min="0" name="lab_fee" class="form-control calc-field" value="<?= clean($bill['lab_fee']) ?>"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Other Fee</label><input type="number" step="0.01" min="0" name="other_fee" class="form-control calc-field" value="<?= clean($bill['other_fee']) ?>"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Discount</label><input type="number" step="0.01" min="0" name="discount" class="form-control calc-field" value="<?= clean($bill['discount']) ?>"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Tax</label><input type="number" step="0.01" min="0" name="tax" class="form-control calc-field" value="<?= clean($bill['tax']) ?>"></div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="w-100"><label class="form-label">Total Amount</label><input type="text" id="totalDisplay" class="form-control fw-bold" value="<?= formatMoney($bill['total_amount']) ?>" disabled></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Bill</button>
            <a href="<?= APP_URL ?>/billing/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<script>
function recalcTotal() {
    const fields = document.querySelectorAll('.calc-field');
    let consultation=0, medicine=0, lab=0, other=0, discount=0, tax=0;
    fields.forEach(f => {
        const val = parseFloat(f.value) || 0;
        if (f.name === 'consultation_fee') consultation = val;
        if (f.name === 'medicine_fee') medicine = val;
        if (f.name === 'lab_fee') lab = val;
        if (f.name === 'other_fee') other = val;
        if (f.name === 'discount') discount = val;
        if (f.name === 'tax') tax = val;
    });
    const total = Math.max(0, (consultation + medicine + lab + other) - discount + tax);
    document.getElementById('totalDisplay').value = '$' + total.toFixed(2);
}
document.querySelectorAll('.calc-field').forEach(f => f.addEventListener('input', recalcTotal));
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
