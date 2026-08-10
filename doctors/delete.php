<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT user_id, doctor_code FROM doctors WHERE doctor_id = ?");
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if ($doctor) {
    $del = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $del->execute([$doctor['user_id']]);
    logActivity("Deleted doctor {$doctor['doctor_code']}", 'doctors');
    setFlash('success', 'Doctor deleted successfully.');
} else {
    setFlash('error', 'Doctor not found.');
}

redirect(APP_URL . '/doctors/index.php');
