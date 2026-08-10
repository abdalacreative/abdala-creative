<?php
require_once __DIR__ . '/../config/config.php';
requireRole(['admin', 'doctor', 'accountant']);

$pageTitle = 'Reports & Analytics';
$pdo = getDB();
$role = $_SESSION['role'];

$dateFrom = $_GET['date_from'] ?? '2000-01-01';
$dateTo = $_GET['date_to'] ?? date('Y-m-d', strtotime('+1 year'));

// Revenue by month (last 6 months) - admin/accountant
$revenueByMonth = [];
if (in_array($role, ['admin', 'accountant'])) {
    $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(payment_date, '%Y-%m') AS ym, SUM(amount_paid) AS total
        FROM payments WHERE payment_date >= ?
        GROUP BY ym ORDER BY ym ASC");
    $stmt->execute([$sixMonthsAgo]);
    $revenueByMonth = $stmt->fetchAll();
}

// Appointments by status
$apptParams = [$dateFrom, $dateTo];
$apptSql = "SELECT status, COUNT(*) AS total FROM appointments WHERE appointment_date BETWEEN ? AND ?";
if ($role === 'doctor') {
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctorId = $stmt->fetch()['doctor_id'] ?? 0;
    $apptSql .= " AND doctor_id = ?";
    $apptParams[] = $doctorId;
}
$apptSql .= " GROUP BY status";
$stmt = $pdo->prepare($apptSql);
$stmt->execute($apptParams);
$appointmentsByStatus = $stmt->fetchAll();

// Top diagnoses (admin/doctor)
$diagSql = "SELECT diagnosis, COUNT(*) AS total FROM medical_records WHERE diagnosis IS NOT NULL AND diagnosis != ''";
$diagParams = [];
if ($role === 'doctor') {
    $diagSql .= " AND doctor_id = ?";
    $diagParams[] = $doctorId ?? 0;
}
$diagSql .= " GROUP BY diagnosis ORDER BY total DESC LIMIT 5";
$stmt = $pdo->prepare($diagSql);
$stmt->execute($diagParams);
$topDiagnoses = $stmt->fetchAll();

// Summary KPIs
$kpis = [];
if (in_array($role, ['admin', 'accountant'])) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) c FROM payments WHERE payment_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $kpis['revenue'] = $stmt->fetch()['c'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) c FROM billing WHERE status != 'paid' AND bill_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $kpis['outstanding'] = $stmt->fetch()['c'];
}
$stmt = $pdo->prepare("SELECT COUNT(*) c FROM appointments WHERE appointment_date BETWEEN ? AND ?" . ($role === 'doctor' ? " AND doctor_id = ?" : ""));
$stmt->execute($role === 'doctor' ? [$dateFrom, $dateTo, $doctorId] : [$dateFrom, $dateTo]);
$kpis['total_appointments'] = $stmt->fetch()['c'];

// Lab test KPIs
$labSql = "SELECT COUNT(*) c FROM lab_tests WHERE test_date BETWEEN ? AND ?";
$labParams = [$dateFrom, $dateTo];
if ($role === 'doctor') {
    $labSql .= " AND doctor_id = ?";
    $labParams[] = $doctorId ?? 0;
}
$stmt = $pdo->prepare($labSql);
$stmt->execute($labParams);
$kpis['total_lab_tests'] = $stmt->fetch()['c'];

$criticalSql = "SELECT COUNT(*) c FROM lab_tests WHERE result_flag = 'critical' AND test_date BETWEEN ? AND ?";
$criticalParams = [$dateFrom, $dateTo];
if ($role === 'doctor') {
    $criticalSql .= " AND doctor_id = ?";
    $criticalParams[] = $doctorId ?? 0;
}
$stmt = $pdo->prepare($criticalSql);
$stmt->execute($criticalParams);
$kpis['critical_lab_tests'] = $stmt->fetch()['c'];

include __DIR__ . '/../includes/header.php';
?>

<?php
$reportLinks = [
    ['Patient Report', 'patient_report.php', 'fa-user-injured', ['admin', 'doctor']],
    ['Doctor Report', 'doctor_report.php', 'fa-user-doctor', ['admin']],
    ['Appointment Report', 'appointment_report.php', 'fa-calendar-check', ['admin', 'doctor']],
    ['Medical Record Report', 'medical_record_report.php', 'fa-file-medical', ['admin', 'doctor']],
    ['Laboratory Test Report', 'laboratory_test_report.php', 'fa-flask-vial', ['admin', 'doctor']],
    ['Pharmacy Report', 'pharmacy_report.php', 'fa-pills', ['admin']],
    ['Billing Report', 'billing_report.php', 'fa-file-invoice-dollar', ['admin', 'accountant']],
    ['Payment Report', 'payment_report.php', 'fa-receipt', ['admin', 'accountant']],
    ['Revenue Report', 'revenue_report.php', 'fa-sack-dollar', ['admin', 'accountant']],
];
?>

<div class="row g-3 mb-4">
    <?php foreach ($reportLinks as $link): ?>
        <?php if (in_array($role, $link[3])): ?>
            <div class="col-md-4 col-sm-6">
                <a href="<?= APP_URL ?>/reports/<?= clean($link[1]) ?>" class="text-decoration-none">
                    <div class="stat-card h-100">
                        <div class="icon-box" style="background:#2563EB;"><i class="fa-solid <?= clean($link[2]) ?>"></i></div>
                        <div><p class="stat-value" style="font-size:18px;"><?= clean($link[0]) ?></p><p class="stat-label">Open report page</p></div>
                    </div>
                </a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<form class="card mb-3" method="GET">
    <div class="card-body d-flex gap-2 flex-wrap align-items-end">
        <div><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="<?= clean($dateFrom) ?>"></div>
        <div><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="<?= clean($dateTo) ?>"></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i> Apply Filter</button>
    </div>
</form>

<div class="row g-3 mb-4">
    <?php if (in_array($role, ['admin', 'accountant'])): ?>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#10B981;"><i class="fa-solid fa-sack-dollar"></i></div>
            <div><p class="stat-value"><?= formatMoney($kpis['revenue']) ?></p><p class="stat-label">Revenue (Selected Period)</p></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#EF4444;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div><p class="stat-value"><?= formatMoney($kpis['outstanding']) ?></p><p class="stat-label">Outstanding Balance</p></div></div>
    </div>
    <?php endif; ?>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#2563EB;"><i class="fa-solid fa-calendar-check"></i></div>
            <div><p class="stat-value"><?= $kpis['total_appointments'] ?></p><p class="stat-label">Appointments (Selected Period)</p></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#06B6D4;"><i class="fa-solid fa-flask-vial"></i></div>
            <div><p class="stat-value"><?= $kpis['total_lab_tests'] ?></p><p class="stat-label">Lab Tests (Selected Period)</p></div></div>
    </div>
    <div class="col-md-4">
        <div class="stat-card"><div class="icon-box" style="background:#EF4444;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div><p class="stat-value"><?= $kpis['critical_lab_tests'] ?></p><p class="stat-label">Critical Lab Results</p></div></div>
    </div>
</div>

<div class="row g-3">
    <?php if (!empty($revenueByMonth)): ?>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Revenue Trend (Last 6 Months)</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Month</th><th class="text-end">Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($revenueByMonth as $r): ?>
                        <tr><td><?= date('F Y', strtotime($r['ym'] . '-01')) ?></td><td class="text-end"><?= formatMoney($r['total']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Appointments by Status</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Status</th><th class="text-end">Count</th></tr></thead>
                    <tbody>
                    <?php foreach ($appointmentsByStatus as $s): ?>
                        <tr><td><?= statusBadge($s['status']) ?></td><td class="text-end"><?= $s['total'] ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointmentsByStatus)): ?><tr><td colspan="2" class="text-center text-muted py-3">No data for this period.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Top Diagnoses</div>
            <div class="card-body p-0">
                <table class="table-modern">
                    <thead><tr><th>Diagnosis</th><th class="text-end">Cases</th></tr></thead>
                    <tbody>
                    <?php foreach ($topDiagnoses as $d): ?>
                        <tr><td><?= clean(mb_strimwidth($d['diagnosis'], 0, 50, '...')) ?></td><td class="text-end"><?= $d['total'] ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($topDiagnoses)): ?><tr><td colspan="2" class="text-center text-muted py-3">No diagnosis data available.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
