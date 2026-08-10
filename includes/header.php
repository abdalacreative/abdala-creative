<?php
/**
 * Shared page header: <head>, opening body tags, topbar
 * Requires $pageTitle to be set before including this file.
 */
$user = currentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pageTitle) ?> | <?= APP_NAME ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
    <?php include BASE_PATH . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
                <h1 class="page-title"><?= clean($pageTitle) ?></h1>
            </div>
            <div class="dropdown">
                <div class="topbar-user dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?= !empty($user['profile_image']) ? UPLOAD_URL . '/' . $user['profile_image'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=2563EB&color=fff' ?>" alt="avatar">
                    <div class="d-none d-md-block">
                        <div style="font-weight:600;font-size:13px;"><?= clean($user['full_name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);text-transform:capitalize;"><?= clean($user['role']) ?></div>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/dashboard/profile.php"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="<?= APP_URL ?>/dashboard/settings.php"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>

        <div class="content-area">
            <?php renderFlash(); ?>
