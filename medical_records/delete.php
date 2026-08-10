<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT record_code FROM medical_records WHERE record_id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if ($record) {
    $del = $pdo->prepare("DELETE FROM medical_records WHERE record_id = ?");
    $del->execute([$id]);
    logActivity("Deleted medical record {$record['record_code']}", 'medical_records');
    setFlash('success', 'Medical record deleted successfully.');
} else {
    setFlash('error', 'Medical record not found.');
}

redirect(APP_URL . '/medical_records/index.php');
