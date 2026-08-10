<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'accountant']);

$pageTitle = 'New Bill';
$pdo = getDB();
$errors = [];
$old = [];

$patients = $pdo->query("SELECT p.patient_id, u.full_name, p.patient_code FROM patients p JOIN users u ON p.user_id = u.user_id ORDER BY u.full_name")->fetchAll();

// Optional: preselect patient from an appointment
$appointmentId = (int)($_GET['appointment_id'] ?? 0);
$linkedAppointment = null;
if ($appointmentId) {
    $stmt = $pdo->prepare("SELECT a.*, d.consultation_fee FROM appointments a JOIN doctors d ON a.doctor_id = d.doctor_id WHERE a.appointment_id = ?");
    $stmt->execute([$appointmentId]);
    $linkedAppointment = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = cleanArray($_POST);

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    $billDate = $_POST['bill_date'] ?? date('Y-m-d');
    $consultationFee = (float)($_POST['consultation_fee'] ?? 0);
    $medicineFee = (float)($_POST['medicine_fee'] ?? 0);
    $labFee = (float)($_POST['lab_fee'] ?? 0);
    $otherFee = (float)($_POST['other_fee'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);

    $subtotal = $consultationFee + $medicineFee + $labFee + $otherFee;
    $total = max(0, $subtotal - $discount + $tax);

    if (empty($patientId)) {
        $errors[] = 'Please select a patient.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO billing
            (bill_code, patient_id, appointment_id, created_by, bill_date, consultation_fee, medicine_fee, lab_fee, other_fee, discount, tax, total_amount, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')");
        $stmt->execute(['TEMP', $patientId, $apptId ?: null, $_SESSION['user_id'], $billDate, $consultationFee, $medicineFee, $labFee, $otherFee, $discount, $tax, $total]);
        $newId = $pdo->lastInsertId();
        $code = 'BILL-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE billing SET bill_code = ? WHERE bill_id = ?")->execute([$code, $newId]);
        $pdo->commit();

        logActivity("Created bill {$code}", 'billing');
        setFlash('success', 'Bill created successfully.');
        redirect(APP_URL . '/billing/view.php?id=' . $newId);
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Create New Bill</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="billForm" onsubmit="return validateForm('billForm')" novalidate>
            <?php csrf_field(); ?>
            <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>" <?= ($linkedAppointment && $linkedAppointment['patient_id'] == $p['patient_id']) ? 'selected' : '' ?>><?= clean($p['full_name']) ?> (<?= clean($p['patient_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Bill Date *</label>
                    <input type="date" name="bill_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <h6 class="text-muted mb-3 mt-2">Charges</h6>
            <div class="row">
                <div class="col-md-3 mb-3"><label class="form-label">Consultation Fee</label><input type="number" step="0.01" min="0" name="consultation_fee" class="form-control calc-field" value="<?= $linkedAppointment['consultation_fee'] ?? '0.00' ?>"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Medicine Fee</label><input type="number" step="0.01" min="0" name="medicine_fee" class="form-control calc-field" value="0.00"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Lab Fee</label><input type="number" step="0.01" min="0" name="lab_fee" class="form-control calc-field" value="0.00"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Other Fee</label><input type="number" step="0.01" min="0" name="other_fee" class="form-control calc-field" value="0.00"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Discount</label><input type="number" step="0.01" min="0" name="discount" class="form-control calc-field" value="0.00"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Tax</label><input type="number" step="0.01" min="0" name="tax" class="form-control calc-field" value="0.00"></div>
                <div class="col-md-6 mb-3 d-flex align-items-end">
                    <div class="w-100">
                        <label class="form-label">Total Amount</label>
                        <input type="text" id="totalDisplay" class="form-control fw-bold" value="$0.00" disabled>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Create Bill</button>
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
recalcTotal();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
