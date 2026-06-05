USE qydentra;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('patient','admin','dentist','receptionist') DEFAULT 'patient',
    profile_photo VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    service_type VARCHAR(100),
    service_desc VARCHAR(150) DEFAULT NULL,
    appointment_date DATE,
    appointment_time VARCHAR(50),
    status ENUM('Pending','Approved','In Progress','Completed','Cancelled') DEFAULT 'Pending',
    queue_number INT DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id)
);

/* =============== RECEPTIONIST ACCOUNT =============== */
-- Email:    receptionist@qydentra.com
-- Password: qydentra.recep
INSERT INTO users (full_name, email, password, role)
VALUES (
    'Clinic Receptionist',
    'receptionist@qydentra.com',
    '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36Z8Y5qz9sQnFZpFhZfQG9e',
    'receptionist'
);

/* =============== QUEUE NUMBER UNIQUENESS =============== */
-- Queue numbers are AUTO-ASSIGNED per appointment_date
-- The system ensures no two appointments on the same day share the same queue number
-- by querying: SELECT COALESCE(MAX(queue_number),0)+1 FROM appointments WHERE appointment_date='2026-06-04'

/* =============== PROFILE PICTURES =============== */
-- Profile photos are stored in: /uploads/profile/
-- Default placeholder: /assets/img/profile_placeholder.svg
-- Supports: JPG, JPEG, PNG, WebP formats

/* =============== PHONE FIELD =============== */
-- Optional phone field for patient contact information
-- Can be NULL if not provided during registration

