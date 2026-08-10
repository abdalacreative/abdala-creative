<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT medicine_code FROM pharmacy WHERE medicine_id = ?");
$stmt->execute([$id]);
$medicine = $stmt->fetch();

if ($medicine) {
    $del = $pdo->prepare("DELETE FROM pharmacy WHERE medicine_id = ?");
    $del->execute([$id]);
    logActivity("Deleted medicine {$medicine['medicine_code']}", 'pharmacy');
    setFlash('success', 'Medicine deleted successfully.');
} else {
    setFlash('error', 'Medicine not found.');
}

redirect(APP_URL . '/pharmacy/index.php');
