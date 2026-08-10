<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$pageTitle = 'Edit Receptionist';
$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$errors = [];

$stmt = $pdo->prepare("SELECT r.*, u.full_name, u.email, u.phone, u.user_id, u.status FROM receptionists r JOIN users u ON r.user_id = u.user_id WHERE r.receptionist_id = ?");
$stmt->execute([$id]);
$rec = $stmt->fetch();

if (!$rec) {
    setFlash('error', 'Receptionist not found.');
    redirect(APP_URL . '/receptionists/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $shift = $_POST['shift'] ?? 'morning';
    $status = $_POST['status'] ?? 'active';

    if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid name and email.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check->execute([$email, $rec['user_id']]);
        if ($check->fetch()) {
            $errors[] = 'Email is used by another account.';
        } else {
            $pdo->beginTransaction();
            $upd = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, status=? WHERE user_id=?");
            $upd->execute([$fullName, $email, $phone, $status, $rec['user_id']]);
            $upd2 = $pdo->prepare("UPDATE receptionists SET shift=? WHERE receptionist_id=?");
            $upd2->execute([$shift, $id]);
            $pdo->commit();

            logActivity("Updated receptionist #{$id}", 'receptionists');
            setFlash('success', 'Receptionist updated successfully.');
            redirect(APP_URL . '/receptionists/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">Edit Receptionist: <?= clean($rec['full_name']) ?></div>
    <div class="card-body">
        <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endforeach; ?>
        <form method="POST" id="editRecForm" onsubmit="return validateForm('editRecForm')" novalidate>
            <?php csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" value="<?= clean($rec['full_name']) ?>" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= clean($rec['phone']) ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= clean($rec['email']) ?>" required></div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $rec['status']==='active'?'selected':'' ?>>Active</option>
                        <option value="inactive" <?= $rec['status']==='inactive'?'selected':'' ?>>Inactive</option>
                        <option value="suspended" <?= $rec['status']==='suspended'?'selected':'' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Shift</label>
                    <select name="shift" class="form-select">
                        <option value="morning" <?= $rec['shift']==='morning'?'selected':'' ?>>Morning</option>
                        <option value="evening" <?= $rec['shift']==='evening'?'selected':'' ?>>Evening</option>
                        <option value="night" <?= $rec['shift']==='night'?'selected':'' ?>>Night</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Update Receptionist</button>
            <a href="<?= APP_URL ?>/receptionists/index.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
