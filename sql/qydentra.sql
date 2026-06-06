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

CREATE TABLE staff (
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

CREATE TABLE patient_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id) REFERENCES patients(patient_id)
);

CREATE TABLE receptionist_notifications (
    receptionist_notification_id INT AUTO_INCREMENT PRIMARY KEY,
    receptionist_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Appointment','Queue','System') DEFAULT 'Appointment',
    status ENUM('Unread','Read') DEFAULT 'Unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (receptionist_id) REFERENCES staff(staff_id)
);

/* =============== RECEPTIONIST ACCOUNT =============== */
-- Email:    receptionist@qydentra.com
-- Password: qydentra.recep
INSERT INTO staff (full_name, email, password, role)
VALUES (
    'Clinic Receptionist',
    'receptionist@qydentra.com',
    '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36Z8Y5qz9sQnFZpFhZfQG9e',
    'receptionist'
);
