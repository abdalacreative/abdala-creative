<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant', 'patient']);

$pageTitle = 'Bill Details';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$role = $_SESSION['role'];

$stmt = $pdo->prepare("
    SELECT b.*, pu.full_name AS patient_name, p.patient_code, p.user_id AS patient_user_id
    FROM billing b JOIN patients p ON b.patient_id = p.patient_id JOIN users pu ON p.user_id = pu.user_id
    WHERE b.bill_id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if (!$bill) {
    setFlash('error', 'Bill not found.');
    redirect(APP_URL . '/billing/index.php');
}

if ($role === 'patient' && $bill['patient_user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('<h2>403 Forbidden</h2><p>You can only view your own bills.</p>');
}

$payments = $pdo->prepare("SELECT * FROM payments WHERE bill_id = ? ORDER BY payment_date DESC");
$payments->execute([$id]);
$payments = $payments->fetchAll();
$totalPaid = array_sum(array_column($payments, 'amount_paid'));
$balance = $bill['total_amount'] - $totalPaid;

include __DIR__ . '/../includes/header.php';
?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Invoice <?= clean($bill['bill_code']) ?></span>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-print me-1"></i> Print</button>
            <?php if (in_array($role, ['admin','accountant']) && $bill['status'] !== 'paid'): ?>
                <a href="<?= APP_URL ?>/payments/create.php?bill_id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-money-bill-wave me-1"></i> Record Payment</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6"><strong>Patient:</strong> <?= clean($bill['patient_name']) ?> (<?= clean($bill['patient_code']) ?>)</div>
            <div class="col-md-6"><strong>Bill Date:</strong> <?= formatDate($bill['bill_date']) ?></div>
        </div>
        <table class="table-modern mb-3">
            <thead><tr><th>Item</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                <tr><td>Consultation Fee</td><td class="text-end"><?= formatMoney($bill['consultation_fee']) ?></td></tr>
                <tr><td>Medicine Fee</td><td class="text-end"><?= formatMoney($bill['medicine_fee']) ?></td></tr>
                <tr><td>Lab Fee</td><td class="text-end"><?= formatMoney($bill['lab_fee']) ?></td></tr>
                <tr><td>Other Fee</td><td class="text-end"><?= formatMoney($bill['other_fee']) ?></td></tr>
                <tr><td>Discount</td><td class="text-end">-<?= formatMoney($bill['discount']) ?></td></tr>
                <tr><td>Tax</td><td class="text-end"><?= formatMoney($bill['tax']) ?></td></tr>
                <tr style="font-weight:700;"><td>Total Amount</td><td class="text-end"><?= formatMoney($bill['total_amount']) ?></td></tr>
                <tr><td>Amount Paid</td><td class="text-end"><?= formatMoney($totalPaid) ?></td></tr>
                <tr style="font-weight:700;color:<?= $balance > 0 ? '#EF4444' : '#10B981' ?>;"><td>Balance Due</td><td class="text-end"><?= formatMoney($balance) ?></td></tr>
            </tbody>
        </table>
        <p><strong>Status:</strong> <?= statusBadge($bill['status']) ?></p>
    </div>
</div>

<div class="card">
    <div class="card-header">Payment History</div>
    <div class="card-body p-0">
        <table class="table-modern">
            <thead><tr><th>Receipt #</th><th>Date</th><th>Method</th><th>Amount</th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= clean($p['payment_code']) ?></td>
                    <td><?= formatDate($p['payment_date']) ?></td>
                    <td><?= ucwords(str_replace('_',' ',$p['payment_method'])) ?></td>
                    <td><?= formatMoney($p['amount_paid']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?><tr><td colspan="4" class="text-center text-muted py-3">No payments recorded yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
