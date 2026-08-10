<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant']);

$pageTitle = 'Record Payment';
$pdo = getDB();
$errors = [];
$old = [];

$billId = (int)($_GET['bill_id'] ?? ($_POST['bill_id'] ?? 0));

$stmt = $pdo->prepare("SELECT b.*, pu.full_name AS patient_name FROM billing b JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id WHERE b.bill_id = ?");
$stmt->execute([$billId]);
$bill = $stmt->fetch();

if (!$bill) {
    setFlash('error', 'Please select a valid bill to record a payment against.');
    redirect(APP_URL . '/billing/index.php');
}

$paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) AS total FROM payments WHERE bill_id = ?");
$paidStmt->execute([$billId]);
$alreadyPaid = (float)$paidStmt->fetch()['total'];
$balance = $bill['total_amount'] - $alreadyPaid;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $amount = (float)($_POST['amount_paid'] ?? 0);
    $method = $_POST['payment_method'] ?? 'cash';
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $reference = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($amount <= 0) {
        $errors[] = 'Payment amount must be greater than zero.';
    } elseif ($amount > $balance + 0.01) {
        $errors[] = 'Payment amount cannot exceed the remaining balance of ' . formatMoney($balance) . '.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO payments (payment_code, bill_id, received_by, amount_paid, payment_method, payment_date, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['TEMP', $billId, $_SESSION['user_id'], $amount, $method, $paymentDate, $reference, $notes]);
        $newId = $pdo->lastInsertId();
        $code = 'PAY-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE payments SET payment_code = ? WHERE payment_id = ?")->execute([$code, $newId]);

        // Update bill status based on total paid so far
        $newTotalPaid = $alreadyPaid + $amount;
        $newStatus = $newTotalPaid >= $bill['total_amount'] ? 'paid' : 'partially_paid';
        $pdo->prepare("UPDATE billing SET status = ? WHERE bill_id = ?")->execute([$newStatus, $billId]);

        $pdo->commit();
        logActivity("Recorded payment {$code} for bill {$bill['bill_code']}", 'payments');
        setFlash('success', 'Payment recorded successfully.');
        redirect(APP_URL . '/billing/view.php?id=' . $billId);
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Record Payment — <?= clean($bill['bill_code']) ?> (<?= clean($bill['patient_name']) ?>)</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>

        <div class="alert alert-info">
            Total: <strong><?= formatMoney($bill['total_amount']) ?></strong> &nbsp;|&nbsp;
            Already Paid: <strong><?= formatMoney($alreadyPaid) ?></strong> &nbsp;|&nbsp;
            Balance Due: <strong><?= formatMoney($balance) ?></strong>
        </div>

        <form method="POST" id="paymentForm" onsubmit="return validateForm('paymentForm')" novalidate>
            <?php csrf_field(); ?>
            <input type="hidden" name="bill_id" value="<?= $billId ?>">
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Amount *</label><input type="number" step="0.01" min="0.01" max="<?= $balance ?>" name="amount_paid" class="form-control" value="<?= clean($old['amount_paid'] ?? number_format($balance, 2, '.', '')) ?>" required></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Payment Method *</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="insurance">Insurance</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Payment Date *</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Reference Number</label><input type="text" name="reference_number" class="form-control" placeholder="Transaction/Receipt ID"></div>
                <div class="col-12 mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Record Payment</button>
            <a href="<?= APP_URL ?>/billing/view.php?id=<?= $billId ?>" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
