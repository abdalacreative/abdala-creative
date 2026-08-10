<?php
/**
 * Sidebar navigation - links shown depend on $_SESSION['role']
 */
$role = $_SESSION['role'] ?? '';
$current = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function navActive($dir, $currentDir) {
    return $dir === $currentDir ? 'active' : '';
}
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-staff-snake"></i>
        <span><?= APP_NAME ?></span>
    </div>

    <ul class="sidebar-nav">
        <li><a href="<?= APP_URL ?>/dashboard/index.php" class="<?= navActive('dashboard', $currentDir) ?>">
            <i class="fa-solid fa-gauge"></i> Dashboard</a></li>

        <?php if ($role === 'admin'): ?>
            <li class="nav-section">Management</li>
            <li><a href="<?= APP_URL ?>/patients/index.php" class="<?= navActive('patients', $currentDir) ?>"><i class="fa-solid fa-user-injured"></i> Patients</a></li>
            <li><a href="<?= APP_URL ?>/doctors/index.php" class="<?= navActive('doctors', $currentDir) ?>"><i class="fa-solid fa-user-doctor"></i> Doctors</a></li>
            <li><a href="<?= APP_URL ?>/nurses/index.php" class="<?= navActive('nurses', $currentDir) ?>"><i class="fa-solid fa-user-nurse"></i> Nurses</a></li>
            <li><a href="<?= APP_URL ?>/receptionists/index.php" class="<?= navActive('receptionists', $currentDir) ?>"><i class="fa-solid fa-bell-concierge"></i> Receptionists</a></li>
            <li class="nav-section">Operations</li>
            <li><a href="<?= APP_URL ?>/appointments/index.php" class="<?= navActive('appointments', $currentDir) ?>"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            <li><a href="<?= APP_URL ?>/medical_records/index.php" class="<?= navActive('medical_records', $currentDir) ?>"><i class="fa-solid fa-file-medical"></i> Medical Records</a></li>
            <li><a href="<?= APP_URL ?>/lab_tests/index.php" class="<?= navActive('lab_tests', $currentDir) ?>"><i class="fa-solid fa-flask-vial"></i> Lab Tests</a></li>
            <li><a href="<?= APP_URL ?>/billing/index.php" class="<?= navActive('billing', $currentDir) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
            <li><a href="<?= APP_URL ?>/payments/index.php" class="<?= navActive('payments', $currentDir) ?>"><i class="fa-solid fa-money-bill-wave"></i> Payments</a></li>
            <li><a href="<?= APP_URL ?>/pharmacy/index.php" class="<?= navActive('pharmacy', $currentDir) ?>"><i class="fa-solid fa-pills"></i> Pharmacy</a></li>
            <li class="nav-section">Insights</li>
            <li><a href="<?= APP_URL ?>/reports/index.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
            <li><a href="<?= APP_URL ?>/reports/patient_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-user-injured"></i> Patient Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/doctor_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-user-doctor"></i> Doctor Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/appointment_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-calendar-check"></i> Appointment Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/medical_record_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-file-medical"></i> Medical Record Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/laboratory_test_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-flask-vial"></i> Laboratory Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/pharmacy_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-pills"></i> Pharmacy Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/billing_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Billing Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/payment_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-receipt"></i> Payment Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/revenue_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-sack-dollar"></i> Revenue Report</a></li>
        <?php endif; ?>

        <?php if ($role === 'doctor'): ?>
            <li class="nav-section">Clinical</li>
            <li><a href="<?= APP_URL ?>/appointments/index.php" class="<?= navActive('appointments', $currentDir) ?>"><i class="fa-solid fa-calendar-check"></i> My Appointments</a></li>
            <li><a href="<?= APP_URL ?>/patients/index.php" class="<?= navActive('patients', $currentDir) ?>"><i class="fa-solid fa-user-injured"></i> My Patients</a></li>
            <li><a href="<?= APP_URL ?>/medical_records/index.php" class="<?= navActive('medical_records', $currentDir) ?>"><i class="fa-solid fa-file-medical"></i> Medical Records</a></li>
            <li><a href="<?= APP_URL ?>/lab_tests/index.php" class="<?= navActive('lab_tests', $currentDir) ?>"><i class="fa-solid fa-flask-vial"></i> Lab Tests</a></li>
            <li><a href="<?= APP_URL ?>/reports/index.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
            <li><a href="<?= APP_URL ?>/reports/patient_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-user-injured"></i> Patient Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/appointment_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-calendar-check"></i> Appointment Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/medical_record_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-file-medical"></i> Medical Record Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/laboratory_test_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-flask-vial"></i> Laboratory Report</a></li>
        <?php endif; ?>

        <?php if ($role === 'nurse'): ?>
            <li class="nav-section">Care</li>
            <li><a href="<?= APP_URL ?>/patients/index.php" class="<?= navActive('patients', $currentDir) ?>"><i class="fa-solid fa-user-injured"></i> Patients</a></li>
            <li><a href="<?= APP_URL ?>/appointments/index.php" class="<?= navActive('appointments', $currentDir) ?>"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            <li><a href="<?= APP_URL ?>/medical_records/index.php" class="<?= navActive('medical_records', $currentDir) ?>"><i class="fa-solid fa-file-medical"></i> Medical Records</a></li>
            <li><a href="<?= APP_URL ?>/lab_tests/index.php" class="<?= navActive('lab_tests', $currentDir) ?>"><i class="fa-solid fa-flask-vial"></i> Lab Tests</a></li>
        <?php endif; ?>

        <?php if ($role === 'receptionist'): ?>
            <li class="nav-section">Front Desk</li>
            <li><a href="<?= APP_URL ?>/patients/index.php" class="<?= navActive('patients', $currentDir) ?>"><i class="fa-solid fa-user-injured"></i> Patients</a></li>
            <li><a href="<?= APP_URL ?>/appointments/index.php" class="<?= navActive('appointments', $currentDir) ?>"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
        <?php endif; ?>

        <?php if ($role === 'accountant'): ?>
            <li class="nav-section">Finance</li>
            <li><a href="<?= APP_URL ?>/billing/index.php" class="<?= navActive('billing', $currentDir) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Billing</a></li>
            <li><a href="<?= APP_URL ?>/payments/index.php" class="<?= navActive('payments', $currentDir) ?>"><i class="fa-solid fa-money-bill-wave"></i> Payments</a></li>
            <li><a href="<?= APP_URL ?>/reports/index.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-chart-line"></i> Financial Reports</a></li>
            <li><a href="<?= APP_URL ?>/reports/billing_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Billing Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/payment_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-receipt"></i> Payment Report</a></li>
            <li><a href="<?= APP_URL ?>/reports/revenue_report.php" class="<?= navActive('reports', $currentDir) ?>"><i class="fa-solid fa-sack-dollar"></i> Revenue Report</a></li>
        <?php endif; ?>

        <?php if ($role === 'patient'): ?>
            <li class="nav-section">My Care</li>
            <li><a href="<?= APP_URL ?>/appointments/index.php" class="<?= navActive('appointments', $currentDir) ?>"><i class="fa-solid fa-calendar-check"></i> My Appointments</a></li>
            <li><a href="<?= APP_URL ?>/medical_records/index.php" class="<?= navActive('medical_records', $currentDir) ?>"><i class="fa-solid fa-file-medical"></i> Medical History</a></li>
            <li><a href="<?= APP_URL ?>/lab_tests/index.php" class="<?= navActive('lab_tests', $currentDir) ?>"><i class="fa-solid fa-flask-vial"></i> Lab Test Results</a></li>
            <li><a href="<?= APP_URL ?>/billing/index.php" class="<?= navActive('billing', $currentDir) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> My Bills</a></li>
        <?php endif; ?>

        <?php if (in_array($role, ['admin'])): ?>
            <li class="nav-section">Pharmacy Staff</li>
        <?php endif; ?>

        <li class="nav-section">Account</li>
        <li><a href="<?= APP_URL ?>/dashboard/profile.php" class="<?= navActive('dashboard', $currentDir) === 'active' && $current === 'profile.php' ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Profile</a></li>
        <li><a href="<?= APP_URL ?>/dashboard/settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        <li><a href="<?= APP_URL ?>/auth/logout.php" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>
