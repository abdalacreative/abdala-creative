<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant', 'patient']);

$pageTitle = 'Payment Receipt';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];

$stmt = $pdo->prepare("
    SELECT pay.*, b.bill_code, b.total_amount, b.patient_id, pu.full_name AS patient_name, p.patient_code, p.user_id AS patient_user_id,
           ru.full_name AS received_by_name
    FROM payments pay
    JOIN billing b ON pay.bill_id = b.bill_id
    JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
    LEFT JOIN users ru ON pay.received_by = ru.user_id
    WHERE pay.payment_id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    setFlash('error', 'Payment record not found.');
    redirect(APP_URL . '/payments/index.php');
}

if ($role === 'patient' && $payment['patient_user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>You can only view your own payment receipts.</p>');
}

// Running balance on the parent bill, as of all payments made
$paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) AS total FROM payments WHERE bill_id = ?");
$paidStmt->execute([$payment['bill_id']]);
$totalPaidOnBill = (float)$paidStmt->fetch()['total'];
$remainingBalance = $payment['total_amount'] - $totalPaidOnBill;

include __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width:560px;margin:0 auto;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Receipt <?= clean($payment['payment_code']) ?></span>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary no-print"><i class="fa-solid fa-print me-1"></i> Print Receipt</button>
    </div>
    <div class="card-body">
        <div class="text-center mb-4">
            <h5 class="mb-0"><?= APP_NAME ?></h5>
            <p class="text-muted mb-0" style="font-size:13px;">Official Payment Receipt</p>
        </div>

        <table class="table-modern mb-3">
            <tbody>
                <tr><td><strong>Receipt No.</strong></td><td class="text-end"><?= clean($payment['payment_code']) ?></td></tr>
                <tr><td><strong>Bill No.</strong></td><td class="text-end"><a href="<?= APP_URL ?>/billing/view.php?id=<?= $payment['bill_id'] ?>"><?= clean($payment['bill_code']) ?></a></td></tr>
                <tr><td><strong>Patient</strong></td><td class="text-end"><?= clean($payment['patient_name']) ?> (<?= clean($payment['patient_code']) ?>)</td></tr>
                <tr><td><strong>Payment Date</strong></td><td class="text-end"><?= formatDate($payment['payment_date']) ?></td></tr>
                <tr><td><strong>Payment Method</strong></td><td class="text-end"><?= ucwords(str_replace('_',' ',$payment['payment_method'])) ?></td></tr>
                <?php if (!empty($payment['reference_number'])): ?>
                <tr><td><strong>Reference No.</strong></td><td class="text-end"><?= clean($payment['reference_number']) ?></td></tr>
                <?php endif; ?>
                <tr style="font-weight:700;font-size:16px;"><td>Amount Paid</td><td class="text-end"><?= formatMoney($payment['amount_paid']) ?></td></tr>
                <tr><td><strong>Bill Total</strong></td><td class="text-end"><?= formatMoney($payment['total_amount']) ?></td></tr>
                <tr style="color:<?= $remainingBalance > 0 ? '#EF4444' : '#10B981' ?>;font-weight:600;"><td>Remaining Balance</td><td class="text-end"><?= formatMoney(max(0, $remainingBalance)) ?></td></tr>
            </tbody>
        </table>

        <?php if (!empty($payment['notes'])): ?>
        <p class="mb-3"><strong>Notes:</strong> <?= clean($payment['notes']) ?></p>
        <?php endif; ?>

        <p class="text-muted mb-0" style="font-size:12px;">
            Received by: <?= clean($payment['received_by_name'] ?: 'System') ?> &middot;
            Issued: <?= formatDate($payment['created_at'], 'M d, Y h:i A') ?>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
