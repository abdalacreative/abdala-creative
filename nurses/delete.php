<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT user_id, nurse_code FROM nurses WHERE nurse_id = ?");
$stmt->execute([$id]);
$nurse = $stmt->fetch();

if ($nurse) {
    $del = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $del->execute([$nurse['user_id']]);
    logActivity("Deleted nurse {$nurse['nurse_code']}", 'nurses');
    setFlash('success', 'Nurse deleted successfully.');
} else {
    setFlash('error', 'Nurse not found.');
}

redirect(APP_URL . '/nurses/index.php');
