<?php
/**
 * Core helper functions used across the application
 */

/** Sanitize a string input */
function clean($value) {
    if ($value === null) return '';
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/** Sanitize an array of inputs (e.g. $_POST) recursively */
function cleanArray($data) {
    $out = [];
    foreach ($data as $key => $value) {
        $out[$key] = is_array($value) ? cleanArray($value) : clean($value);
    }
    return $out;
}

/** Redirect helper */
function redirect($url) {
    header("Location: $url");
    exit();
}

/** Generate and store a CSRF token */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Output a hidden CSRF input field */
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** Verify a submitted CSRF token, terminating the request on failure */
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Request blocked.');
    }
}

/** Set a one-time flash message */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Render and clear the flash message */
function renderFlash() {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'] === 'error' ? 'danger' : $_SESSION['flash']['type'];
        $message = clean($_SESSION['flash']['message']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['flash']);
    }
}

/** Generate a unique code with a prefix, e.g. PAT-0001 */
function generateCode($prefix, $table, $column) {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM {$table}");
    $count = (int)$stmt->fetch()['total'] + 1;
    return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/** Log an activity to the audit trail */
function logActivity($action, $module = null) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $action,
        $module,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}

/** Paginate a result set: returns [data, totalPages, currentPage] */
function paginate($sql, $params, $page, $pageSize = PAGE_SIZE) {
    $pdo = getDB();
    $page = max(1, (int)$page);
    $offset = ($page - 1) * $pageSize;

    $countSql = "SELECT COUNT(*) AS total FROM ({$sql}) AS count_table";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    $dataSql = $sql . " LIMIT {$pageSize} OFFSET {$offset}";
    $stmt = $pdo->prepare($dataSql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    return [
        'data' => $data,
        'totalPages' => (int)ceil($total / $pageSize),
        'currentPage' => $page,
        'totalRecords' => $total
    ];
}

/** Format currency for display */
function formatMoney($amount) {
    return '$' . number_format((float)$amount, 2);
}

/** Format date for display */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return '—';
    return date($format, strtotime($date));
}

/** Badge HTML helper for status columns */
function statusBadge($status) {
    $map = [
        'active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger',
        'pending' => 'warning', 'confirmed' => 'info', 'completed' => 'success',
        'cancelled' => 'danger', 'no_show' => 'secondary',
        'unpaid' => 'danger', 'partially_paid' => 'warning', 'paid' => 'success'
    ];
    $color = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class='badge bg-{$color}'>{$label}</span>";
}

/** Badge for lab test workflow status (ordered -> sample_collected -> in_progress -> completed) */
function labStatusBadge($status) {
    $map = [
        'ordered' => 'secondary', 'sample_collected' => 'info', 'in_progress' => 'warning',
        'completed' => 'success', 'cancelled' => 'danger'
    ];
    $color = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class='badge bg-{$color}'>{$label}</span>";
}

/** Badge for lab test result flag (normal/abnormal/critical/pending) */
function labResultBadge($flag) {
    $map = [
        'normal' => 'success', 'abnormal' => 'warning', 'critical' => 'danger', 'pending' => 'secondary'
    ];
    $color = $map[$flag] ?? 'secondary';
    $label = ucwords($flag);
    return "<span class='badge bg-{$color}'>{$label}</span>";
}
