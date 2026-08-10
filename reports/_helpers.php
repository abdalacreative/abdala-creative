<?php
function reportDateRange(): array {
    $from = $_GET['date_from'] ?? '2000-01-01';
    $to = $_GET['date_to'] ?? date('Y-m-d', strtotime('+1 year'));
    return [$from, $to];
}

function reportSearch(): string {
    return trim($_GET['search'] ?? '');
}

function reportDoctorId(PDO $pdo): int {
    if (($_SESSION['role'] ?? '') !== 'doctor') {
        return 0;
    }

    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    return (int)($stmt->fetch()['doctor_id'] ?? 0);
}

function reportPatientId(PDO $pdo): int {
    if (($_SESSION['role'] ?? '') !== 'patient') {
        return 0;
    }

    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    return (int)($stmt->fetch()['patient_id'] ?? 0);
}

function reportFilterForm(string $searchPlaceholder = 'Search reports...'): void {
    $dateFrom = $_GET['date_from'] ?? '2000-01-01';
    $dateTo = $_GET['date_to'] ?? date('Y-m-d', strtotime('+1 year'));
    $search = $_GET['search'] ?? '';
    ?>
    <form class="card mb-3" method="GET">
        <div class="card-body d-flex gap-2 flex-wrap align-items-end">
            <div><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="<?= clean($dateFrom) ?>"></div>
            <div><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="<?= clean($dateTo) ?>"></div>
            <div><label class="form-label">Search</label><input type="text" name="search" class="form-control" placeholder="<?= clean($searchPlaceholder) ?>" value="<?= clean($search) ?>" style="width:260px;"></div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i> Apply Filter</button>
            <a href="<?= clean(basename($_SERVER['PHP_SELF'])) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </form>
    <?php
}

function reportStatCard(string $label, $value, string $icon, string $color = '#2563EB'): void {
    ?>
    <div class="col-md-3 col-sm-6">
        <div class="stat-card">
            <div class="icon-box" style="background:<?= clean($color) ?>;"><i class="fa-solid <?= clean($icon) ?>"></i></div>
            <div><p class="stat-value"><?= clean((string)$value) ?></p><p class="stat-label"><?= clean($label) ?></p></div>
        </div>
    </div>
    <?php
}

function reportEmptyRow(int $columns, string $message = 'No data found for this period.'): void {
    echo '<tr><td colspan="' . (int)$columns . '" class="text-center text-muted py-4">' . clean($message) . '</td></tr>';
}

function reportPagination(array $result, array $extra = []): void {
    if (($result['totalPages'] ?? 0) <= 1) {
        return;
    }

    $query = array_merge($_GET, $extra);
    ?>
    <div class="card-body border-top">
        <nav><ul class="pagination justify-content-center mb-0">
            <?php for ($i = 1; $i <= $result['totalPages']; $i++): $query['page'] = $i; ?>
                <li class="page-item <?= $i === $result['currentPage'] ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= clean(http_build_query($query)) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php
}
