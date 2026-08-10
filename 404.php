<?php
require_once __DIR__ . '/config/config.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 Not Found | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card text-center">
        <h1 style="font-size:64px;color:var(--primary);font-weight:700;">404</h1>
        <p class="mb-4">The page you're looking for doesn't exist.</p>
        <a href="<?= APP_URL ?>/index.php" class="btn btn-primary">Go Home</a>
    </div>
</div>
</body>
</html>
