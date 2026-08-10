<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'receptionist']);

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("SELECT appointment_code FROM appointments WHERE appointment_id = ?");
$stmt->execute([$id]);
$appt = $stmt->fetch();

if ($appt) {
    $del = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
    $del->execute([$id]);
    logActivity("Deleted appointment {$appt['appointment_code']}", 'appointments');
    setFlash('success', 'Appointment cancelled and removed.');
} else {
    setFlash('error', 'Appointment not found.');
}

redirect(APP_URL . '/appointments/index.php');
