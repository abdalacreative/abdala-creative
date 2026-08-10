<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT test_code FROM lab_tests WHERE test_id = ?");
$stmt->execute([$id]);
$test = $stmt->fetch();

if ($test) {
    $del = $pdo->prepare("DELETE FROM lab_tests WHERE test_id = ?");
    $del->execute([$id]);
    logActivity("Deleted lab test {$test['test_code']}", 'lab_tests');
    setFlash('success', 'Lab test record deleted successfully.');
} else {
    setFlash('error', 'Lab test not found.');
}

redirect(APP_URL . '/lab_tests/index.php');
