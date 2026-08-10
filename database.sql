-- =====================================================================
-- HOSPITAL MANAGEMENT SYSTEM - DATABASE SCHEMA
-- Engine: MySQL 8+ | Charset: utf8mb4
-- =====================================================================

CREATE DATABASE IF NOT EXISTS hms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hms_db;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- TABLE: users  (core auth table for ALL roles)
-- =====================================================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','doctor','nurse','receptionist','accountant','patient') NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: patients
-- =====================================================================
DROP TABLE IF EXISTS patients;
CREATE TABLE patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    patient_code VARCHAR(20) NOT NULL UNIQUE,
    date_of_birth DATE,
    gender ENUM('male','female','other'),
    blood_group VARCHAR(5),
    address VARCHAR(255),
    city VARCHAR(100),
    emergency_contact_name VARCHAR(150),
    emergency_contact_phone VARCHAR(20),
    allergies TEXT,
    insurance_provider VARCHAR(150),
    insurance_number VARCHAR(100),
    registered_by INT DEFAULT NULL COMMENT 'receptionist user_id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_patients_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_patients_registeredby FOREIGN KEY (registered_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_patients_code (patient_code)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: doctors
-- =====================================================================
DROP TABLE IF EXISTS doctors;
CREATE TABLE doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doctor_code VARCHAR(20) NOT NULL UNIQUE,
    specialization VARCHAR(150) NOT NULL,
    qualification VARCHAR(255),
    license_number VARCHAR(100) UNIQUE,
    department VARCHAR(150),
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    available_days VARCHAR(100) COMMENT 'e.g. Mon,Tue,Wed',
    available_time_start TIME,
    available_time_end TIME,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_doctors_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_doctors_specialization (specialization)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: nurses
-- =====================================================================
DROP TABLE IF EXISTS nurses;
CREATE TABLE nurses (
    nurse_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nurse_code VARCHAR(20) NOT NULL UNIQUE,
    department VARCHAR(150),
    shift ENUM('morning','evening','night') DEFAULT 'morning',
    assigned_doctor_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_nurses_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_nurses_doctor FOREIGN KEY (assigned_doctor_id) REFERENCES doctors(doctor_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: receptionists
-- =====================================================================
DROP TABLE IF EXISTS receptionists;
CREATE TABLE receptionists (
    receptionist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    receptionist_code VARCHAR(20) NOT NULL UNIQUE,
    shift ENUM('morning','evening','night') DEFAULT 'morning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_receptionists_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: accountants
-- =====================================================================
DROP TABLE IF EXISTS accountants;
CREATE TABLE accountants (
    accountant_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    accountant_code VARCHAR(20) NOT NULL UNIQUE,
    department VARCHAR(150) DEFAULT 'Finance',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_accountants_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: appointments
-- =====================================================================
DROP TABLE IF EXISTS appointments;
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    scheduled_by INT DEFAULT NULL COMMENT 'receptionist/patient user_id',
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(255),
    status ENUM('pending','confirmed','completed','cancelled','no_show') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_scheduledby FOREIGN KEY (scheduled_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_appointments_date (appointment_date),
    INDEX idx_appointments_status (status)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: medical_records
-- =====================================================================
DROP TABLE IF EXISTS medical_records;
CREATE TABLE medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    record_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    visit_date DATE NOT NULL,
    diagnosis TEXT,
    prescription TEXT,
    symptoms TEXT,
    treatment_notes TEXT,
    lab_results TEXT,
    attachment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_records_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    CONSTRAINT fk_records_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    CONSTRAINT fk_records_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    INDEX idx_records_visitdate (visit_date)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: billing
-- =====================================================================
DROP TABLE IF EXISTS billing;
CREATE TABLE billing (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    bill_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    created_by INT DEFAULT NULL COMMENT 'accountant user_id',
    bill_date DATE NOT NULL,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    medicine_fee DECIMAL(10,2) DEFAULT 0.00,
    lab_fee DECIMAL(10,2) DEFAULT 0.00,
    other_fee DECIMAL(10,2) DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('unpaid','partially_paid','paid','cancelled') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_billing_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    CONSTRAINT fk_billing_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    CONSTRAINT fk_billing_createdby FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_billing_status (status)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: payments
-- =====================================================================
DROP TABLE IF EXISTS payments;
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_code VARCHAR(20) NOT NULL UNIQUE,
    bill_id INT NOT NULL,
    received_by INT DEFAULT NULL COMMENT 'accountant user_id',
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','card','bank_transfer','mobile_money','insurance') DEFAULT 'cash',
    payment_date DATE NOT NULL,
    reference_number VARCHAR(100),
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_bill FOREIGN KEY (bill_id) REFERENCES billing(bill_id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_receivedby FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_payments_date (payment_date)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: pharmacy  (medicine inventory)
-- =====================================================================
DROP TABLE IF EXISTS pharmacy;
CREATE TABLE pharmacy (
    medicine_id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_code VARCHAR(20) NOT NULL UNIQUE,
    medicine_name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    manufacturer VARCHAR(150),
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    reorder_level INT DEFAULT 10,
    expiry_date DATE NOT NULL,
    batch_number VARCHAR(100),
    managed_by INT DEFAULT NULL COMMENT 'pharmacy staff user_id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pharmacy_managedby FOREIGN KEY (managed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_pharmacy_expiry (expiry_date),
    INDEX idx_pharmacy_stock (stock_quantity)
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: activity_logs (audit trail - supports security requirement)
-- =====================================================================
DROP TABLE IF EXISTS activity_logs;
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    module VARCHAR(100),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- TABLE: lab_tests  (laboratory test orders and results)
-- =====================================================================
DROP TABLE IF EXISTS lab_tests;
CREATE TABLE lab_tests (
    test_id INT AUTO_INCREMENT PRIMARY KEY,
    test_code VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    test_name VARCHAR(150) NOT NULL,
    test_category ENUM('blood','urine','imaging','cardiology','microbiology','other') DEFAULT 'blood',
    test_date DATE NOT NULL,
    sample_collected_at DATETIME DEFAULT NULL,
    result_value TEXT COMMENT 'Main result findings/values',
    reference_range VARCHAR(150) COMMENT 'Normal range for comparison, e.g. 70-110 mg/dL',
    unit VARCHAR(30) COMMENT 'e.g. mg/dL, mmol/L, cells/uL',
    result_flag ENUM('normal','abnormal','critical','pending') DEFAULT 'pending',
    technician_notes TEXT,
    doctor_remarks TEXT,
    status ENUM('ordered','sample_collected','in_progress','completed','cancelled') DEFAULT 'ordered',
    attachment VARCHAR(255) DEFAULT NULL COMMENT 'Optional scanned report/image',
    requested_by INT DEFAULT NULL COMMENT 'doctor user_id who ordered the test',
    completed_by INT DEFAULT NULL COMMENT 'lab staff/admin user_id who entered results',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_labtests_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE,
    CONSTRAINT fk_labtests_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE,
    CONSTRAINT fk_labtests_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL,
    CONSTRAINT fk_labtests_requestedby FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_labtests_completedby FOREIGN KEY (completed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_labtests_status (status),
    INDEX idx_labtests_date (test_date)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SEED DATA: default admin account
-- =====================================================================
-- A row is created with a temporary, unusable password hash. After importing
-- this file, run the following ONCE from your project root to create/update
-- demo login accounts for all 6 roles:
--
--     php auth/generate_admin.php
--
-- You will then be able to log in with admin, doctor, nurse, receptionist,
-- accountant, and patient demo accounts.
-- Change these passwords immediately before real use.
INSERT INTO users (full_name, email, username, password_hash, role, status)
VALUES ('System Administrator', 'admin@hms.local', 'admin',
'NOT_SET_RUN_generate_admin.php', 'admin', 'active');
