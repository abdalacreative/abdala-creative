<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pageTitle = 'My Profile';
$pdo = getDB();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($fullName) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid name and email.';
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            $errors[] = 'That email is already used by another account.';
        } else {
            $imageField = $user['profile_image'];

            if (!empty($_FILES['profile_image']['name'])) {
                $allowed = ['jpg','jpeg','png'];
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed) && $_FILES['profile_image']['size'] <= 2 * 1024 * 1024) {
                    $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_PATH . '/' . $newName)) {
                        $imageField = $newName;
                    }
                } else {
                    $errors[] = 'Profile image must be JPG/PNG and under 2MB.';
                }
            }

            if (empty($errors)) {
                $upd = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, email = ?, profile_image = ? WHERE user_id = ?");
                $upd->execute([$fullName, $phone, $email, $imageField, $userId]);

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['profile_image'] = $imageField;

                setFlash('success', 'Profile updated successfully.');
                redirect(APP_URL . '/dashboard/profile.php');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Profile Information</div>
            <div class="card-body">
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger"><?= clean($error) ?></div>
                <?php endforeach; ?>

                <form method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>
                    <div class="text-center mb-4">
                        <img src="<?= !empty($user['profile_image']) ? UPLOAD_URL . '/' . $user['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=2563EB&color=fff&size=128' ?>"
                             style="width:100px;height:100px;border-radius:50%;object-fit:cover;" class="mb-3">
                        <div><input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png" style="max-width:280px;margin:0 auto;"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= clean($user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= clean($user['phone']) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= clean($user['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?= ucfirst(clean($user['role'])) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?= APP_URL ?>/dashboard/settings.php" class="btn btn-outline-secondary">Change Password</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
