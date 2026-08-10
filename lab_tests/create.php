<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor']);

$pageTitle = 'Order Lab Test';
$pdo = getDB();
$role = $_SESSION['role'];
$errors = [];
$old = [];

if ($role === 'doctor') {
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $myDoctorId = $stmt->fetch()['doctor_id'] ?? 0;

    // Only patients this doctor has seen
    $patients = $pdo->prepare("
        SELECT DISTINCT p.patient_id, u.full_name, p.patient_code FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id JOIN users u ON p.user_id = u.user_id
        WHERE a.doctor_id = ? ORDER BY u.full_name");
    $patients->execute([$myDoctorId]);
    $patients = $patients->fetchAll();
    $doctors = null;
} else {
    $patients = $pdo->query("SELECT p.patient_id, u.full_name, p.patient_code FROM patients p JOIN users u ON p.user_id = u.user_id ORDER BY u.full_name")->fetchAll();
    $doctors = $pdo->query("SELECT d.doctor_id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name")->fetchAll();
}

$commonTests = [
    'Complete Blood Count (CBC)' => 'blood',
    'Blood Glucose (Fasting)' => 'blood',
    'Lipid Profile' => 'blood',
    'Liver Function Test (LFT)' => 'blood',
    'Kidney Function Test (KFT)' => 'blood',
    'Urinalysis' => 'urine',
    'Pregnancy Test (Urine)' => 'urine',
    'X-Ray' => 'imaging',
    'Ultrasound' => 'imaging',
    'CT Scan' => 'imaging',
    'ECG' => 'cardiology',
    'Malaria Test' => 'microbiology',
    'COVID-19 PCR' => 'microbiology',
    'Other (specify below)' => 'other',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = cleanArray($_POST);

    $patientId = (int)($_POST['patient_id'] ?? 0);
    $doctorId = $role === 'doctor' ? $myDoctorId : (int)($_POST['doctor_id'] ?? 0);
    $testName = trim($_POST['test_name'] ?? '');
    $customTestName = trim($_POST['custom_test_name'] ?? '');
    $category = $_POST['test_category'] ?? 'blood';
    $testDate = $_POST['test_date'] ?? date('Y-m-d');

    if ($testName === 'Other (specify below)' && !empty($customTestName)) {
        $testName = $customTestName;
    }

    if (empty($patientId) || empty($doctorId) || empty($testName)) {
        $errors[] = 'Please select a patient, doctor, and test name.';
    } else {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO lab_tests (test_code, patient_id, doctor_id, test_name, test_category, test_date, status, result_flag, requested_by) VALUES (?, ?, ?, ?, ?, ?, 'ordered', 'pending', ?)");
        $stmt->execute(['TEMP', $patientId, $doctorId, $testName, $category, $testDate, $_SESSION['user_id']]);
        $newId = $pdo->lastInsertId();
        $code = 'LAB-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE lab_tests SET test_code = ? WHERE test_id = ?")->execute([$code, $newId]);
        $pdo->commit();

        logActivity("Ordered lab test {$code}", 'lab_tests');
        setFlash('success', 'Lab test ordered successfully.');
        redirect(APP_URL . '/lab_tests/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Order New Lab Test</div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="labTestForm" onsubmit="return validateForm('labTestForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Patient *</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select Patient</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['patient_id'] ?>" <?= (($old['patient_id'] ?? '') == $p['patient_id']) ? 'selected' : '' ?>><?= clean($p['full_name']) ?> (<?= clean($p['patient_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($role === 'admin'): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor *</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">Select Doctor</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['doctor_id'] ?>" <?= (($old['doctor_id'] ?? '') == $d['doctor_id']) ? 'selected' : '' ?>>Dr. <?= clean($d['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Test Name *</label>
                    <select name="test_name" class="form-select" id="testNameSelect" required>
                        <option value="">Select Test</option>
                        <?php foreach ($commonTests as $name => $cat): ?>
                            <option value="<?= clean($name) ?>" data-category="<?= $cat ?>" <?= (($old['test_name'] ?? '') === $name) ? 'selected' : '' ?>><?= clean($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="customTestWrapper" style="display:none;">
                    <label class="form-label">Specify Test Name</label>
                    <input type="text" name="custom_test_name" class="form-control" value="<?= clean($old['custom_test_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category</label>
                    <select name="test_category" id="testCategorySelect" class="form-select">
                        <option value="blood">Blood</option>
                        <option value="urine">Urine</option>
                        <option value="imaging">Imaging</option>
                        <option value="cardiology">Cardiology</option>
                        <option value="microbiology">Microbiology</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Test Date *</label>
                    <input type="date" name="test_date" class="form-control" value="<?= clean($old['test_date'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-flask-vial me-1"></i> Order Test</button>
            <a href="<?= APP_URL ?>/lab_tests/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>

<script>
// Auto-select category based on chosen test, and reveal custom name field for "Other"
const testSelect = document.getElementById('testNameSelect');
const categorySelect = document.getElementById('testCategorySelect');
const customWrapper = document.getElementById('customTestWrapper');

function syncTestFields() {
    const selected = testSelect.options[testSelect.selectedIndex];
    const category = selected ? selected.getAttribute('data-category') : null;
    if (category) categorySelect.value = category;
    customWrapper.style.display = (testSelect.value === 'Other (specify below)') ? 'block' : 'none';
}
testSelect.addEventListener('change', syncTestFields);
syncTestFields();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
