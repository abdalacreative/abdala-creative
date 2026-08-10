<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Edit Nurse';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT n.*, u.full_name, u.email, u.phone, u.user_id, u.status FROM nurses n JOIN users u ON n.user_id = u.user_id WHERE n.nurse_id = ?");
$stmt->execute([$id]);
$nurse = $stmt->fetch();

if (!$nurse) {
    setFlash('error', 'Nurse not found.');
    redirect(APP_URL . '/nurses/index.php');
}

$doctors = $pdo->query("SELECT d.doctor_id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id ORDER BY u.full_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $shift = $_POST['shift'] ?? 'morning';
    $assignedDoctor = $_POST['assigned_doctor_id'] ?? null;
    $status = $_POST['status'] ?? 'active';

    if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid name and email.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check->execute([$email, $nurse['user_id']]);
        if ($check->fetch()) {
            $errors[] = 'Email is used by another account.';
        } else {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, status=? WHERE user_id=?");
            $upd->execute([$fullName, $email, $phone, $status, $nurse['user_id']]);
            $upd2 = $pdo->prepare("UPDATE nurses SET department=?, shift=?, assigned_doctor_id=? WHERE nurse_id=?");
            $upd2->execute([$department, $shift, $assignedDoctor ?: null, $id]);
            $pdo->commit();

            logActivity("Updated nurse #{$id}", 'nurses');
            setFlash('success', 'Nurse updated successfully.');
            redirect(APP_URL . '/nurses/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Edit Nurse: <?= clean($nurse['full_name']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="editNurseForm" onsubmit="return validateForm('editNurseForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= clean($nurse['full_name']) ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= clean($nurse['phone']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= clean($nurse['email']) ?>" required></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $nurse['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $nurse['status']==='inactive'?'selected':'' ?>>Inactive</option>
                        <option value="suspended" <?= $nurse['status']==='suspended'?'selected':'' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="<?= clean($nurse['department']) ?>"></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Shift</label>
                    <select name="shift" class="form-select">
                        <option value="morning" <?= $nurse['shift']==='morning'?'selected':'' ?>>Morning</option>
                        <option value="evening" <?= $nurse['shift']==='evening'?'selected':'' ?>>Evening</option>
                        <option value="night" <?= $nurse['shift']==='night'?'selected':'' ?>>Night</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assigned Doctor</label>
                    <select name="assigned_doctor_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['doctor_id'] ?>" <?= $nurse['assigned_doctor_id'] == $d['doctor_id'] ? 'selected' : '' ?>>Dr. <?= clean($d['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Nurse</button>
            <a href="<?= APP_URL ?>/nurses/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
