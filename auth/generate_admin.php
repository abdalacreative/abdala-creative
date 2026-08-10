<?php
/**
 * Run once after importing database.sql.
 *
 * This creates/updates demo login accounts for all 6 roles and adds the
 * linked role profile rows required by their dashboards.
 *
 * Usage:
 *   C:\xampp\php\php.exe auth\generate_admin.php
 */
require_once __DIR__ . '/../config/database.php';

$pdo = getDB();
$passwords = [
    'admin' => 'Admin@123',
    'doctor' => 'Doctor@123',
    'nurse' => 'Nurse@123',
    'receptionist' => 'Reception@123',
    'accountant' => 'Account@123',
    'patient' => 'Patient@123',
];

function upsertUser(PDO $pdo, array $user, string $password): int {

    $hash = $password;

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$user['username']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET full_name = ?, email = ?, password_hash = ?, role = ?, phone = ?, status = 'active'
            WHERE user_id = ?
        ");
        $stmt->execute([$user['full_name'], $user['email'], $hash, $user['role'], $user['phone'], $existing['user_id']]);
        return (int)$existing['user_id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, username, password_hash, role, phone, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$user['full_name'], $user['email'], $user['username'], $hash, $user['role'], $user['phone']]);
    return (int)$pdo->lastInsertId();
}

function ensureDoctor(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['doctor_id'];

    $stmt = $pdo->prepare("
        INSERT INTO doctors (user_id, doctor_code, specialization, qualification, license_number, department, consultation_fee, available_days, available_time_start, available_time_end, bio)
        VALUES (?, ?, 'General Medicine', 'MBBS', ?, 'Outpatient', 25.00, 'Mon,Tue,Wed,Thu,Fri', '08:00:00', '16:00:00', 'Demo doctor account')
    ");
    $stmt->execute([$userId, 'DOC-' . str_pad($userId, 4, '0', STR_PAD_LEFT), 'LIC-' . str_pad($userId, 4, '0', STR_PAD_LEFT)]);
    return (int)$pdo->lastInsertId();
}

function ensureNurse(PDO $pdo, int $userId, int $doctorId): int {
    $stmt = $pdo->prepare("SELECT nurse_id FROM nurses WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['nurse_id'];

    $stmt = $pdo->prepare("INSERT INTO nurses (user_id, nurse_code, department, shift, assigned_doctor_id) VALUES (?, ?, 'Outpatient', 'morning', ?)");
    $stmt->execute([$userId, 'NUR-' . str_pad($userId, 4, '0', STR_PAD_LEFT), $doctorId]);
    return (int)$pdo->lastInsertId();
}

function ensureReceptionist(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT receptionist_id FROM receptionists WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['receptionist_id'];

    $stmt = $pdo->prepare("INSERT INTO receptionists (user_id, receptionist_code, shift) VALUES (?, ?, 'morning')");
    $stmt->execute([$userId, 'REC-' . str_pad($userId, 4, '0', STR_PAD_LEFT)]);
    return (int)$pdo->lastInsertId();
}

function ensureAccountant(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT accountant_id FROM accountants WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['accountant_id'];

    $stmt = $pdo->prepare("INSERT INTO accountants (user_id, accountant_code, department) VALUES (?, ?, 'Finance')");
    $stmt->execute([$userId, 'ACC-' . str_pad($userId, 4, '0', STR_PAD_LEFT)]);
    return (int)$pdo->lastInsertId();
}

function ensurePatient(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['patient_id'];

    $stmt = $pdo->prepare("
        INSERT INTO patients (user_id, patient_code, date_of_birth, gender, blood_group, address, city, emergency_contact_name, emergency_contact_phone)
        VALUES (?, ?, '1995-05-15', 'male', 'O+', 'Demo Address', 'Mogadishu', 'Demo Contact', '+252610000006')
    ");
    $stmt->execute([$userId, 'PAT-' . str_pad($userId, 4, '0', STR_PAD_LEFT)]);
    return (int)$pdo->lastInsertId();
}

function ensureDemoData(PDO $pdo, int $patientId, int $doctorId, int $accountantUserId, int $adminUserId): void {
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("SELECT appointment_id FROM appointments WHERE appointment_code = 'APT-DEMO-001'");
    $stmt->execute();
    $appointmentId = $stmt->fetch()['appointment_id'] ?? null;
    if (!$appointmentId) {
        $stmt = $pdo->prepare("
            INSERT INTO appointments (appointment_code, patient_id, doctor_id, scheduled_by, appointment_date, appointment_time, reason, status, notes)
            VALUES ('APT-DEMO-001', ?, ?, ?, ?, '09:00:00', 'Demo consultation', 'confirmed', 'Seed appointment for dashboards')
        ");
        $stmt->execute([$patientId, $doctorId, $adminUserId, $today]);
        $appointmentId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("SELECT record_id FROM medical_records WHERE record_code = 'MR-DEMO-001'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO medical_records (record_code, patient_id, doctor_id, appointment_id, visit_date, diagnosis, prescription, symptoms, treatment_notes)
            VALUES ('MR-DEMO-001', ?, ?, ?, ?, 'General checkup', 'Rest and hydration', 'Routine symptoms', 'Demo medical record')
        ");
        $stmt->execute([$patientId, $doctorId, $appointmentId, $today]);
    }

    $stmt = $pdo->prepare("SELECT test_id FROM lab_tests WHERE test_code = 'LAB-DEMO-001'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO lab_tests (test_code, patient_id, doctor_id, appointment_id, test_name, test_category, test_date, result_value, reference_range, unit, result_flag, status, requested_by, completed_by)
            VALUES ('LAB-DEMO-001', ?, ?, ?, 'Complete Blood Count', 'blood', ?, 'Normal', 'Normal range', 'cells/uL', 'normal', 'completed', ?, ?)
        ");
        $stmt->execute([$patientId, $doctorId, $appointmentId, $today, $adminUserId, $adminUserId]);
    }

    $stmt = $pdo->prepare("SELECT bill_id FROM billing WHERE bill_code = 'BILL-DEMO-001'");
    $stmt->execute();
    $billId = $stmt->fetch()['bill_id'] ?? null;
    if (!$billId) {
        $stmt = $pdo->prepare("
            INSERT INTO billing (bill_code, patient_id, appointment_id, created_by, bill_date, consultation_fee, medicine_fee, lab_fee, other_fee, discount, tax, total_amount, status)
            VALUES ('BILL-DEMO-001', ?, ?, ?, ?, 25.00, 10.00, 15.00, 0.00, 0.00, 0.00, 50.00, 'paid')
        ");
        $stmt->execute([$patientId, $appointmentId, $accountantUserId, $today]);
        $billId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("SELECT payment_id FROM payments WHERE payment_code = 'PAY-DEMO-001'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO payments (payment_code, bill_id, received_by, amount_paid, payment_method, payment_date, reference_number, notes)
            VALUES ('PAY-DEMO-001', ?, ?, 50.00, 'cash', ?, 'DEMO-REF-001', 'Seed payment for dashboards')
        ");
        $stmt->execute([$billId, $accountantUserId, $today]);
    }

    $stmt = $pdo->prepare("SELECT medicine_id FROM pharmacy WHERE medicine_code = 'MED-DEMO-001'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("
            INSERT INTO pharmacy (medicine_code, medicine_name, category, manufacturer, unit_price, stock_quantity, reorder_level, expiry_date, batch_number, managed_by)
            VALUES ('MED-DEMO-001', 'Paracetamol 500mg', 'Pain Relief', 'Demo Pharma', 0.50, 100, 20, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'BATCH-DEMO-001', ?)
        ");
        $stmt->execute([$adminUserId]);
    }
}

$users = [
    'admin' => ['full_name' => 'System Administrator', 'email' => 'admin@hms.local', 'username' => 'admin', 'role' => 'admin', 'phone' => '+252610000001'],
    'doctor' => ['full_name' => 'Demo Doctor', 'email' => 'doctor@hms.local', 'username' => 'doctor', 'role' => 'doctor', 'phone' => '+252610000002'],
    'nurse' => ['full_name' => 'Demo Nurse', 'email' => 'nurse@hms.local', 'username' => 'nurse', 'role' => 'nurse', 'phone' => '+252610000003'],
    'receptionist' => ['full_name' => 'Demo Receptionist', 'email' => 'receptionist@hms.local', 'username' => 'receptionist', 'role' => 'receptionist', 'phone' => '+252610000004'],
    'accountant' => ['full_name' => 'Demo Accountant', 'email' => 'accountant@hms.local', 'username' => 'accountant', 'role' => 'accountant', 'phone' => '+252610000005'],
    'patient' => ['full_name' => 'Demo Patient', 'email' => 'patient@hms.local', 'username' => 'patient', 'role' => 'patient', 'phone' => '+252610000006'],
];

try {
    $pdo->beginTransaction();

    $adminUserId = upsertUser($pdo, $users['admin'], $passwords['admin']);
    $doctorUserId = upsertUser($pdo, $users['doctor'], $passwords['doctor']);
    $nurseUserId = upsertUser($pdo, $users['nurse'], $passwords['nurse']);
    $receptionistUserId = upsertUser($pdo, $users['receptionist'], $passwords['receptionist']);
    $accountantUserId = upsertUser($pdo, $users['accountant'], $passwords['accountant']);
    $patientUserId = upsertUser($pdo, $users['patient'], $passwords['patient']);

    $doctorId = ensureDoctor($pdo, $doctorUserId);
    ensureNurse($pdo, $nurseUserId, $doctorId);
    ensureReceptionist($pdo, $receptionistUserId);
    ensureAccountant($pdo, $accountantUserId);
    $patientId = ensurePatient($pdo, $patientUserId);
    ensureDemoData($pdo, $patientId, $doctorId, $accountantUserId, $adminUserId);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Failed to create demo users: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Demo users created/updated successfully.\n\n";
echo "Role          Username       Password\n";
echo "-------------------------------------\n";
foreach ($passwords as $username => $password) {
    echo str_pad(ucfirst($username), 14) . str_pad($username, 15) . $password . "\n";
}
echo "\nVisit http://localhost/hms/auth/login.php and log in with any account above.\n";
