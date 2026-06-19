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
-- QYDENTRA DENTAL CLINIC MANAGEMENT SYSTEM
-- Complete Database and Table Structure
-- ============================================================

-- Create the database
CREATE DATABASE IF NOT EXISTS `qydentra` 
  DEFAULT CHARACTER SET utf8mb4 
  DEFAULT COLLATE utf8mb4_general_ci;

USE `qydentra`;

-- ============================================================
-- SEQUENCE TABLES (for ID generation with custom prefixes)
-- ============================================================

-- Sequence table for appointments (AP prefix)
DROP TABLE IF EXISTS `_seq_appointments`;
CREATE TABLE `_seq_appointments` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequence table for patients (PT prefix)
DROP TABLE IF EXISTS `_seq_patients`;
CREATE TABLE `_seq_patients` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequence table for dentists (DE prefix)
DROP TABLE IF EXISTS `_seq_staff_de`;
CREATE TABLE `_seq_staff_de` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequence table for receptionists (RE prefix)
DROP TABLE IF EXISTS `_seq_staff_re`;
CREATE TABLE `_seq_staff_re` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequence table for admins (AD prefix)
DROP TABLE IF EXISTS `_seq_staff_ad`;
CREATE TABLE `_seq_staff_ad` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequence table for patient notifications (PN prefix)
DROP TABLE IF EXISTS `_seq_pat_notif`;
CREATE TABLE `_seq_pat_notif` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequence table for receptionist notifications (RN prefix)
DROP TABLE IF EXISTS `_seq_rec_notif`;
CREATE TABLE `_seq_rec_notif` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sequence table for admin notifications (AN prefix)
DROP TABLE IF EXISTS `_seq_admin_notif`;
CREATE TABLE `_seq_admin_notif` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- MAIN ENTITIES
-- ============================================================

-- Patients table (PT prefix IDs)
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `patient_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `profile_photo` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dentists table (DE prefix IDs)
DROP TABLE IF EXISTS `dentists`;
CREATE TABLE `dentists` (
  `dentist_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `availability_status` varchar(50) DEFAULT 'available',
  `profile_photo` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`dentist_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Staff table (RE prefix for receptionist, AD prefix for admin)
DROP TABLE IF EXISTS `staffs`;
CREATE TABLE `staffs` (
  `staff_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `profile_photo` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- CLINICAL & OPERATIONAL
-- ============================================================

-- Appointments table (AP prefix IDs)
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `appointment_id` varchar(10) NOT NULL,
  `patient_id` varchar(10) NOT NULL,
  `dentist_id` varchar(10) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `service` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `queue_number` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  KEY `dentist_id` (`dentist_id`),
  KEY `appointment_date` (`appointment_date`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`dentist_id`) REFERENCES `dentists` (`dentist_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================

-- Patient notifications table (PN prefix IDs)
DROP TABLE IF EXISTS `patient_notifications`;
CREATE TABLE `patient_notifications` (
  `patient_notification_id` varchar(10) NOT NULL,
  `patient_id` varchar(10) NOT NULL,
  `notification_type` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `related_appointment_id` varchar(10) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`patient_notification_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `patient_notifications_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Receptionist notifications table (RN prefix IDs)
DROP TABLE IF EXISTS `receptionist_notifications`;
CREATE TABLE `receptionist_notifications` (
  `receptionist_notification_id` varchar(10) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `notification_type` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `related_appointment_id` varchar(10) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`receptionist_notification_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `receptionist_notifications_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staffs` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admin notifications table (AN prefix IDs)
DROP TABLE IF EXISTS `admin_notifications`;
CREATE TABLE `admin_notifications` (
  `notification_id` varchar(10) NOT NULL,
  `admin_id` varchar(10) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('System','Report','Alert','Staff') DEFAULT 'System',
  `status` enum('Unread','Read') DEFAULT 'Unread',
  `related_id` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `admin_id` (`admin_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `staffs` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admin audit logs table (tracks admin actions)
DROP TABLE IF EXISTS `admin_audit_logs`;
CREATE TABLE `admin_audit_logs` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` varchar(10) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`audit_id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `admin_audit_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `staffs` (`staff_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TRIGGERS FOR AUTO ID GENERATION
-- ============================================================

-- Trigger for appointments auto-increment with AP prefix
DELIMITER ;;
CREATE TRIGGER `trg_appointments_bi` BEFORE INSERT ON `appointments`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE `_seq_appointments` SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM `_seq_appointments` LIMIT 1;
    SET NEW.appointment_id = CONCAT('AP', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- Trigger for patients auto-increment with PT prefix
DELIMITER ;;
CREATE TRIGGER `trg_patients_bi` BEFORE INSERT ON `patients`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE `_seq_patients` SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM `_seq_patients` LIMIT 1;
    SET NEW.patient_id = CONCAT('PT', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- Trigger for dentists auto-increment with DE prefix
DELIMITER ;;
CREATE TRIGGER `trg_dentists_bi` BEFORE INSERT ON `dentists`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE `_seq_staff_de` SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM `_seq_staff_de` LIMIT 1;
    SET NEW.dentist_id = CONCAT('DE', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- Trigger for staffs (receptionists and admins) auto-increment
DELIMITER ;;
CREATE TRIGGER `trg_staffs_bi` BEFORE INSERT ON `staffs`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE prefix VARCHAR(2);
    
    IF NEW.role = 'receptionist' THEN
        UPDATE `_seq_staff_re` SET last_id = last_id + 1;
        SELECT last_id INTO next_id FROM `_seq_staff_re` LIMIT 1;
        SET prefix = 'RE';
    ELSEIF NEW.role = 'dentist' THEN
        UPDATE `_seq_staff_de` SET last_id = last_id + 1;
        SELECT last_id INTO next_id FROM `_seq_staff_de` LIMIT 1;
        SET prefix = 'DE';
    ELSE
        -- admin (and any future role falls here as AD)
        UPDATE `_seq_staff_ad` SET last_id = last_id + 1;
        SELECT last_id INTO next_id FROM `_seq_staff_ad` LIMIT 1;
        SET prefix = 'AD';
    END IF;
    
    SET NEW.staff_id = CONCAT(prefix, LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- Trigger for patient notifications auto-increment with PN prefix
DELIMITER ;;
CREATE TRIGGER `trg_patient_notifications_bi` BEFORE INSERT ON `patient_notifications`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE `_seq_pat_notif` SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM `_seq_pat_notif` LIMIT 1;
    SET NEW.patient_notification_id = CONCAT('PN', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- Trigger for receptionist notifications auto-increment with RN prefix
DELIMITER ;;
CREATE TRIGGER `trg_receptionist_notifications_bi` BEFORE INSERT ON `receptionist_notifications`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE `_seq_rec_notif` SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM `_seq_rec_notif` LIMIT 1;
    SET NEW.receptionist_notification_id = CONCAT('RN', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- Trigger for admin notifications auto-increment with AN prefix
DELIMITER ;;
CREATE TRIGGER `trg_admin_notif_bi` BEFORE INSERT ON `admin_notifications`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE `_seq_admin_notif` SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM `_seq_admin_notif` LIMIT 1;
    SET NEW.notification_id = CONCAT('AN', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;


-- Create the dentist_schedules table
CREATE TABLE dentist_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    dentist_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ============================================================
-- Seed Accounts (IDs auto-generated by trigger)
-- ============================================================
INSERT INTO staffs (full_name, email, password, role)
VALUES
('Clinic Receptionist', 'receptionist@qydentra.com', '$2y$10$55aHBBqLFz35wzeS0JvaMexCGGWjEcG7laJTP4Igisizk0UT2wyiq', 'receptionist'),
('Clinic Admin', 'admin@qydentra.com', '$2y$10$LeToVUwUqDZzNMn7HH3mme5z4oaZnid9dGR4JxbkABCu5eNLC0sym', 'admin'),
('Clinic Dentist', 'dentist@qydentra.com', '$2y$10$42WaES.Ih13xFS1L8kkJreAnMIQQ7aJgPOMStCSpdJP9SiRNgbEdq', 'dentist');
