<<<<<<< HEAD
# MediCare HMS — Hospital Management System

A full-featured Hospital Management System built with **PHP (vanilla, PDO)**, **MySQL**, and **Bootstrap 5**, designed to run on **XAMPP**.

## Features

- 6 roles: Admin, Doctor, Nurse, Receptionist, Accountant, Patient (each with a tailored dashboard and sidebar)
- Secure authentication: bcrypt password hashing, CSRF protection, session regeneration, forgot/reset password flow
- Patients, Doctors, Nurses, Receptionists management (full CRUD)
- Appointment booking with double-booking prevention
- Medical records (diagnosis, prescriptions, lab results)
- Billing & invoicing with itemized charges, auto-calculated totals
- Payments with partial-payment support and automatic bill status updates
- Pharmacy inventory with low-stock and expiry alerts
- Reports & analytics (revenue trends, appointment stats, top diagnoses)
- Activity audit log
- Responsive UI (mobile-friendly sidebar)

## Requirements

- XAMPP (Apache + MySQL + PHP 8.0+)
- A modern browser

## Setup Instructions

1. **Copy the project folder** into your XAMPP `htdocs` directory, e.g.:
   ```
   C:\xampp\htdocs\hms      (Windows)
   /Applications/XAMPP/htdocs/hms   (Mac)
   ```

2. **Start Apache and MySQL** from the XAMPP Control Panel.

3. **Create the database:**
   - Open `http://localhost/phpmyadmin`
   - Click "Import" → select `database.sql` from the project folder → Go.
   - This creates the `hms_db` database with all tables and a placeholder admin row.

4. **Generate the demo login accounts** (required once — the SQL file ships with a non-functional placeholder hash for security reasons):
   - Open a terminal in the project folder and run:
     ```
     php auth/generate_admin.php
     ```
   - This creates/updates demo users for all 6 roles and adds the linked doctor, nurse, receptionist, accountant, and patient rows their dashboards need.

5. **Configure database credentials** (if different from defaults) in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'hms_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

6. **Set the app URL** in `config/config.php` if your folder name differs from `hms`:
   ```php
   define('APP_URL', 'http://localhost/hms');
   ```

7. **Visit the app:** `http://localhost/hms`
   - Log in with any demo account below.
   - Change these passwords immediately via Settings before real use.

## Demo Logins

| Role         | Username       | Password        |
|--------------|----------------|-----------------|
| Admin        | `admin`        | `Admin@123`     |
| Doctor       | `doctor`       | `Doctor@123`    |
| Nurse        | `nurse`        | `Nurse@123`     |
| Receptionist | `receptionist` | `Reception@123` |
| Accountant   | `accountant`   | `Account@123`   |
| Patient      | `patient`      | `Patient@123`   |

## Folder Structure

```
hms/
├── auth/              Login, register, logout, password reset
├── dashboard/         Role-aware dashboard, profile, settings
├── patients/           Patient CRUD
├── doctors/            Doctor CRUD
├── nurses/              Nurse CRUD
├── receptionists/      Receptionist CRUD
├── appointments/       Appointment booking & management
├── medical_records/    Medical records (doctor-authored)
├── billing/             Invoicing
├── payments/           Payment recording
├── pharmacy/            Medicine inventory
├── reports/             Analytics & reports
├── includes/            Shared header/footer/sidebar, helper functions, auth guards
├── config/              Database connection & app configuration
├── assets/               CSS, JS, uploaded files
└── database.sql          Full database schema + seed data
```

## Roles & Default Access

| Role          | Can access                                                              |
|---------------|--------------------------------------------------------------------------|
| Admin         | Everything                                                               |
| Doctor        | Their own appointments, patients, medical records, reports              |
| Nurse         | Patients, appointments, medical records (read/limited write)            |
| Receptionist  | Patient registration, appointment scheduling                            |
| Accountant    | Billing, payments, financial reports                                    |
| Patient       | Their own appointments, medical history, bills (self-service portal)    |

## Security Notes

- All passwords are hashed with bcrypt (`password_hash` / `password_verify`).
- All forms are protected against CSRF via per-session tokens.
- All database queries use PDO prepared statements (no raw SQL concatenation).
- All output is escaped with `htmlspecialchars()` to prevent XSS.
- Role-based access control is enforced server-side on every protected page (`requireRole()`), not just hidden in the UI.

## Customization

- Branding/colors: edit CSS variables at the top of `assets/css/style.css`.
- App name: change `APP_NAME` in `config/config.php`.
- Add more roles or modules by following the existing pattern in any module folder (`index.php`, `create.php`, `edit.php`, `view.php`, `delete.php`).
=======
# To-Do List App

This is a simple to-do list app built using HTML, CSS, and JavaScript.

## Features
- Add tasks
- Delete tasks
- Mark tasks as completed

## How to use
1. Open the app
2. Add your tasks
3. Manage your daily activities

## Author
Abdalacreative
>>>>>>> 961a2e63821304503f0b3fadc0e8b7404235cbf3
