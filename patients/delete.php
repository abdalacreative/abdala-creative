<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT user_id, patient_code FROM patients WHERE patient_id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if ($patient) {
    // Deleting the user cascades to the patient row and all dependent records (FK ON DELETE CASCADE)
    $del = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $del->execute([$patient['user_id']]);
    logActivity("Deleted patient {$patient['patient_code']}", 'patients');
    setFlash('success', 'Patient deleted successfully.');
} else {
    setFlash('error', 'Patient not found.');
}

redirect(APP_URL . '/patients/index.php');
