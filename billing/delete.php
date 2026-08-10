<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT bill_code FROM billing WHERE bill_id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if ($bill) {
    $del = $pdo->prepare("DELETE FROM billing WHERE bill_id = ?");
    $del->execute([$id]);
    logActivity("Deleted bill {$bill['bill_code']}", 'billing');
    setFlash('success', 'Bill deleted successfully.');
} else {
    setFlash('error', 'Bill not found.');
}

redirect(APP_URL . '/billing/index.php');
