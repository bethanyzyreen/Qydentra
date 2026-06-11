# Qydentra

### ID Prefix Reference

| Table                         | Column                        | Prefix | Example |
|-------------------------------|-------------------------------|--------|---------|
| `patients`                    | `patient_id`                  | `PT`   | PT001   |
| `staffs` (receptionist)       | `staff_id`                    | `RE`   | RE001   |
| `staffs` (dentist)            | `staff_id`                    | `DE`   | DE001   |
| `staffs` (admin)              | `staff_id`                    | `AD`   | AD001   |
| `appointments`                | `appointment_id`              | `AP`   | AP001   |
| `patient_notifications`       | `notification_id`             | `PN`   | PN001   |
| `receptionist_notifications`  | `receptionist_notification_id`| `RN`   | RN001   |


## Default Accounts

| Role         | Email                          | Password                |
|--------------|--------------------------------|-------------------------|
| Receptionist | receptionist@qydentraa.com     | qydentra.receptionist   |
| Admin        | admin@qydentra.com             | qydentra.admin          |
| Dentist      | dentist@qydentra.com           | qydentra.dentist        |


-- ============================================================
-- Qydentra Database Schema (Unified v5)
-- ============================================================

CREATE DATABASE IF NOT EXISTS qydentra;
USE qydentra;

-- ============================================================
-- Sequence helper tables
-- ============================================================
CREATE TABLE IF NOT EXISTS _seq_patients (last_id INT UNSIGNED NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS _seq_staff_re (last_id INT UNSIGNED NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS _seq_staff_de (last_id INT UNSIGNED NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS _seq_staff_ad (last_id INT UNSIGNED NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS _seq_appointments (last_id INT UNSIGNED NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS _seq_pat_notif (last_id INT UNSIGNED NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS _seq_rec_notif (last_id INT UNSIGNED NOT NULL DEFAULT 0);

INSERT INTO _seq_patients VALUES (0);
INSERT INTO _seq_staff_re VALUES (0);
INSERT INTO _seq_staff_de VALUES (0);
INSERT INTO _seq_staff_ad VALUES (0);
INSERT INTO _seq_appointments VALUES (0);
INSERT INTO _seq_pat_notif VALUES (0);
INSERT INTO _seq_rec_notif VALUES (0);

-- ============================================================
-- patients (prefix PT)
-- ============================================================
CREATE TABLE IF NOT EXISTS patients (
    patient_id VARCHAR(10) NOT NULL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'patient',
    profile_photo TEXT,
    phone_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DELIMITER $$
CREATE TRIGGER trg_patients_bi
BEFORE INSERT ON patients
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_patients SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_patients LIMIT 1;
    SET NEW.patient_id = CONCAT('PT', LPAD(next_id, 3, '0'));
END$$
DELIMITER ;

-- ============================================================
-- staffs (prefix RE, DE, AD)
-- ============================================================
CREATE TABLE IF NOT EXISTS staffs (
    staff_id VARCHAR(10) NOT NULL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    profile_photo TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DELIMITER $$
CREATE TRIGGER trg_staffs_bi
BEFORE INSERT ON staffs
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE prefix VARCHAR(2);
    IF NEW.role = 'receptionist' THEN
        UPDATE _seq_staff_re SET last_id = last_id + 1;
        SELECT last_id INTO next_id FROM _seq_staff_re LIMIT 1;
        SET prefix = 'RE';
    ELSEIF NEW.role = 'dentist' THEN
        UPDATE _seq_staff_de SET last_id = last_id + 1;
        SELECT last_id INTO next_id FROM _seq_staff_de LIMIT 1;
        SET prefix = 'DE';
    ELSE
        UPDATE _seq_staff_ad SET last_id = last_id + 1;
        SELECT last_id INTO next_id FROM _seq_staff_ad LIMIT 1;
        SET prefix = 'AD';
    END IF;
    SET NEW.staff_id = CONCAT(prefix, LPAD(next_id, 3, '0'));
END$$
DELIMITER ;

-- ============================================================
-- appointments (prefix AP)
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id VARCHAR(10) NOT NULL PRIMARY KEY,
    patient_id VARCHAR(10),
    service_type VARCHAR(100),
    service_desc VARCHAR(150) DEFAULT NULL,
    appointment_date DATE,
    appointment_time VARCHAR(50),
    status ENUM('Pending','Approved','In Progress','Completed','Cancelled') DEFAULT 'Pending',
    queue_number INT DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
);

DELIMITER $$
CREATE TRIGGER trg_appointments_bi
BEFORE INSERT ON appointments
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_appointments SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_appointments LIMIT 1;
    SET NEW.appointment_id = CONCAT('AP', LPAD(next_id, 3, '0'));
END$$
DELIMITER ;

-- ============================================================
-- patient_notifications (prefix PN)
-- ============================================================
CREATE TABLE IF NOT EXISTS patient_notifications (
    notification_id VARCHAR(10) NOT NULL PRIMARY KEY,
    patient_id VARCHAR(10) NOT NULL,
    title VARCHAR(100) DEFAULT NULL,
    type ENUM('Appointment','Queue','System') DEFAULT 'Appointment',
    message TEXT NOT NULL,
    appointment_id VARCHAR(10) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);

DELIMITER $$
CREATE TRIGGER trg_pat_notif_bi
BEFORE INSERT ON patient_notifications
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_pat_notif SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_pat_notif LIMIT 1;
    SET NEW.notification_id = CONCAT('PN', LPAD(next_id, 3, '0'));
END$$
DELIMITER ;

-- ============================================================
-- receptionist_notifications (prefix RN)
-- ============================================================
CREATE TABLE IF NOT EXISTS receptionist_notifications (
    receptionist_notification_id VARCHAR(10) NOT NULL PRIMARY KEY,
    receptionist_id VARCHAR(10) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Appointment','Queue','System') DEFAULT 'Appointment',
    status ENUM('Unread','Read') DEFAULT 'Unread',
    appointment_id VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receptionist_id) REFERENCES staffs(staff_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);

DELIMITER $$
CREATE TRIGGER trg_rec_notif_bi
BEFORE INSERT ON receptionist_notifications
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_rec_notif SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_rec_notif LIMIT 1;
    SET NEW.receptionist_notification_id = CONCAT('RN', LPAD(next_id, 3, '0'));
END$$
DELIMITER ;

-- ============================================================
-- Seed Accounts (IDs auto-generated by trigger)
-- ============================================================
INSERT INTO staffs (full_name, email, password, role)
VALUES
('Clinic Receptionist', 'receptionist@qydentra.com', '$2y$10$55aHBBqLFz35wzeS0JvaMexCGGWjEcG7laJTP4Igisizk0UT2wyiq', 'receptionist'),
('Clinic Admin', 'admin@qydentra.com', '$2y$10$LeToVUwUqDZzNMn7HH3mme5z4oaZnid9dGR4JxbkABCu5eNLC0sym', 'admin'),
('Clinic Dentist', 'dentist@qydentra.com', '$2y$10$42WaES.Ih13xFS1L8kkJreAnMIQQ7aJgPOMStCSpdJP9SiRNgbEdq', 'dentist');
