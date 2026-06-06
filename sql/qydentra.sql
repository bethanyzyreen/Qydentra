CREATE DATABASE qydentra;
USE qydentra;

CREATE TABLE patients (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'patient',
    profile_photo TEXT,
    phone_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staffs (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL, -- receptionist, dentist, admin
    profile_photo TEXT,
    phone_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
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

-- ============================================================
-- patient_notifications
--   title        : short heading (e.g. "Appointment Approved")
--   type         : matches receptionist_notifications for parity
--   appointment_id: links back to the triggering appointment
-- ============================================================
CREATE TABLE patient_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    title VARCHAR(100) DEFAULT NULL,
    type ENUM('Appointment','Queue','System') DEFAULT 'Appointment',
    message TEXT NOT NULL,
    appointment_id INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);

-- ============================================================
-- receptionist_notifications
--   appointment_id: links back to the triggering appointment
-- ============================================================
CREATE TABLE receptionist_notifications (
    receptionist_notification_id INT AUTO_INCREMENT PRIMARY KEY,
    receptionist_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Appointment','Queue','System') DEFAULT 'Appointment',
    status ENUM('Unread','Read') DEFAULT 'Unread',
    appointment_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (receptionist_id) REFERENCES staffs(staff_id),
    FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL
);

/* =============== RECEPTIONIST ACCOUNT =============== */
-- Email:    receptionist@qydentra.com
-- Password: qydentra.recep
-- Email:    receptionist@qydentraa.com
-- Password: qydentra.receptionist
INSERT INTO staffs (full_name, email, password, role)
VALUES 
    ('Clinic Receptionist',
    'receptionist@qydentra.com',
    '$2y$10$c.Fw4tw5BnrUUShQlIIVn.QPJKhXdyxzziWgHFpcMH3MthWzGgHba',
    'receptionist'),
    ('Clinic Receptionist',
    'receptionist@qydentraa.com',
    '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36Z8Y5qz9sQnFZpFhZfQG9e',
    'receptionist'
);

-- ============================================
-- ADMIN ACCOUNT
-- Email:    admin@qydentra.com
-- Password: qydentra.admin
-- ============================================
INSERT INTO staffs (full_name, email, password, role)
VALUES (
    'Clinic Admin',
    'admin@qydentra.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- ============================================
-- DENTIST ACCOUNT
-- Email:    dentist@qydentra.com
-- Password: qydentra.dentist
-- ============================================
INSERT INTO staffs (full_name, email, password, role)
VALUES (
    'Clinic Dentist',
    'dentist@qydentra.com',
    '$2y$10$TKh8H1.PFetR5450ygyxGO5RPyvAZQFrRDaxOCi7FBtX3A/6MdI2.',
    'dentist'
);

-- ============================================================
-- MIGRATION: run these ALTER statements on an existing database
-- instead of dropping and recreating it.
-- ============================================================
-- ALTER TABLE patient_notifications
--     ADD COLUMN title VARCHAR(100) DEFAULT NULL AFTER patient_id,
--     ADD COLUMN type ENUM('Appointment','Queue','System') DEFAULT 'Appointment' AFTER title,
--     ADD COLUMN appointment_id INT DEFAULT NULL AFTER message,
--     ADD CONSTRAINT fk_pn_appointment
--         FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL;
--
-- ALTER TABLE receptionist_notifications
--     ADD COLUMN appointment_id INT DEFAULT NULL AFTER status,
--     ADD CONSTRAINT fk_rn_appointment
--         FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL;
