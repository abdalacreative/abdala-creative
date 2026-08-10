<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT user_id, receptionist_code FROM receptionists WHERE receptionist_id = ?");
$stmt->execute([$id]);
$rec = $stmt->fetch();

if ($rec) {
    $del = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $del->execute([$rec['user_id']]);
    logActivity("Deleted receptionist {$rec['receptionist_code']}", 'receptionists');
    setFlash('success', 'Receptionist deleted successfully.');
} else {
    setFlash('error', 'Receptionist not found.');
}

redirect(APP_URL . '/receptionists/index.php');
