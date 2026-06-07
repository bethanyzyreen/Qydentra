-- ============================================================
-- Qydentra Database Schema  (v4 — VARCHAR prefixed PKs)
-- ============================================================
-- Primary key format:  prefix + zero-padded 3-digit number
--   PT → patients.patient_id              PT001, PT002, …
--   ST → staffs.staff_id                  ST001, ST002, …  (dentist / admin)
--   RE → staffs.staff_id (receptionists)  RE001, RE002, …
--   AP → appointments.appointment_id      AP001, AP002, …
--   PN → patient_notifications.notification_id       PN001, PN002, …
--   RN → receptionist_notifications.receptionist_notification_id  RN001, RN002, …
--
-- Auto-increment behaviour is preserved via helper tables + BEFORE INSERT triggers.
-- The application layer never constructs IDs — it calls INSERT without supplying a PK
-- and reads it back with LAST_INSERT_ID_STR() or mysqli_insert_id() (see note below).
-- IDs are stored/queried as VARCHAR; they are NEVER displayed in the application UI.
-- ============================================================

CREATE DATABASE IF NOT EXISTS qydentra;
USE qydentra;

-- ============================================================
-- Sequence helper tables  (one row each, holds last used int)
-- ============================================================
CREATE TABLE IF NOT EXISTS _seq_patients      (last_id INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS _seq_staffs        (last_id INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS _seq_appointments  (last_id INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS _seq_pat_notif     (last_id INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS _seq_rec_notif     (last_id INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB;

INSERT INTO _seq_patients     VALUES (0);
INSERT INTO _seq_staffs       VALUES (0);
INSERT INTO _seq_appointments VALUES (0);
INSERT INTO _seq_pat_notif    VALUES (0);
INSERT INTO _seq_rec_notif    VALUES (0);

-- ============================================================
-- patients  (prefix PT)
-- ============================================================
CREATE TABLE IF NOT EXISTS patients (
    patient_id    VARCHAR(10)  NOT NULL PRIMARY KEY,   -- PT001, PT002, …
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) UNIQUE NOT NULL,
    password      VARCHAR(255) NOT NULL,
    role          VARCHAR(50)  DEFAULT 'patient',
    profile_photo TEXT,
    phone_number  VARCHAR(20),
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
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
-- staffs  (prefix ST for dentist/admin, RE for receptionists)
-- ============================================================
CREATE TABLE IF NOT EXISTS staffs (
    staff_id      VARCHAR(10)  NOT NULL PRIMARY KEY,   -- RE001 / ST001
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) UNIQUE NOT NULL,
    password      VARCHAR(255) NOT NULL,
    role          VARCHAR(50)  NOT NULL,   -- 'receptionist' | 'dentist' | 'admin'
    profile_photo TEXT,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

DELIMITER $$
CREATE TRIGGER trg_staffs_bi
BEFORE INSERT ON staffs
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE prefix  VARCHAR(2);
    UPDATE _seq_staffs SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_staffs LIMIT 1;
    -- Receptionists get prefix RE; everyone else gets ST
    IF NEW.role = 'receptionist' THEN
        SET prefix = 'RE';
    ELSE
        SET prefix = 'ST';
    END IF;
    SET NEW.staff_id = CONCAT(prefix, LPAD(next_id, 3, '0'));
END$$
DELIMITER ;

-- ============================================================
-- appointments  (prefix AP)
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id   VARCHAR(10)  NOT NULL PRIMARY KEY,   -- AP001, AP002, …
    patient_id       VARCHAR(10),
    service_type     VARCHAR(100),
    service_desc     VARCHAR(150) DEFAULT NULL,
    appointment_date DATE,
    appointment_time VARCHAR(50),
    status           ENUM('Pending','Approved','In Progress','Completed','Cancelled') DEFAULT 'Pending',
    queue_number     INT          DEFAULT NULL,
    notes            TEXT,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

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
-- patient_notifications  (prefix PN)
-- ============================================================
CREATE TABLE IF NOT EXISTS patient_notifications (
    notification_id  VARCHAR(10)  NOT NULL PRIMARY KEY,   -- PN001, PN002, …
    patient_id       VARCHAR(10)  NOT NULL,
    title            VARCHAR(100) DEFAULT NULL,
    type             ENUM('Appointment','Queue','System') DEFAULT 'Appointment',
    message          TEXT NOT NULL,
    appointment_id   VARCHAR(10)  DEFAULT NULL,
    is_read          TINYINT(1)   DEFAULT 0,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id)     REFERENCES patients(patient_id),
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
-- receptionist_notifications  (prefix RN)
-- ============================================================
CREATE TABLE IF NOT EXISTS receptionist_notifications (
    receptionist_notification_id VARCHAR(10)  NOT NULL PRIMARY KEY,   -- RN001, RN002, …
    receptionist_id  VARCHAR(10)  NOT NULL,
    title            VARCHAR(100) NOT NULL,
    message          TEXT NOT NULL,
    type             ENUM('Appointment','Queue','System') DEFAULT 'Appointment',
    status           ENUM('Unread','Read')               DEFAULT 'Unread',
    appointment_id   VARCHAR(10)  DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (receptionist_id) REFERENCES staffs(staff_id),
    FOREIGN KEY (appointment_id)  REFERENCES appointments(appointment_id) ON DELETE SET NULL
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
-- Seed accounts 
-- ============================================================

/* Receptionist — email: receptionist@qydentra.com  pass: qydentra.receptionist */
INSERT INTO staffs (full_name, email, password, role) VALUES (
    'Clinic Receptionist',
    'receptionist@qydentra.com',
    '$2y$10$55aHBBqLFz35wzeS0JvaMexCGGWjEcG7laJTP4Igisizk0UT2wyiq',
    'receptionist'
);

/* Admin — email: admin@qydentra.com  pass: qydentra.admin */
INSERT INTO staffs (full_name, email, password, role) VALUES (
    'Clinic Admin',
    'admin@qydentra.com',
    '$2y$10$LeToVUwUqDZzNMn7HH3mme5z4oaZnid9dGR4JxbkABCu5eNLC0sym',
    'admin'
);

/* Dentist — email: dentist@qydentra.com  pass: qydentra.dentist */
INSERT INTO staffs (full_name, email, password, role) VALUES (
    'Clinic Dentist',
    'dentist@qydentra.com',
    '$2y$10$42WaES.Ih13xFS1L8kkJreAnMIQQ7aJgPOMStCSpdJP9SiRNgbEdq',
    'dentist'
);
