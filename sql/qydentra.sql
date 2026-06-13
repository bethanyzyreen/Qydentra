-- ============================================================
-- Qydentra Database - phpMyAdmin Compatible Version
-- Fixed: Removed DEFINER clauses from triggers
-- Run this AFTER creating and selecting the database
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- Sequence Tables
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `_seq_appointments`;
CREATE TABLE `_seq_appointments` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `_seq_appointments` VALUES (4);

DROP TABLE IF EXISTS `_seq_pat_notif`;
CREATE TABLE `_seq_pat_notif` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `_seq_pat_notif` VALUES (18);

DROP TABLE IF EXISTS `_seq_patients`;
CREATE TABLE `_seq_patients` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `_seq_patients` VALUES (2);

DROP TABLE IF EXISTS `_seq_rec_notif`;
CREATE TABLE `_seq_rec_notif` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `_seq_rec_notif` VALUES (8);

DROP TABLE IF EXISTS `_seq_staff_ad`;
CREATE TABLE `_seq_staff_ad` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `_seq_staff_ad` VALUES (1);

DROP TABLE IF EXISTS `_seq_staff_de`;
CREATE TABLE `_seq_staff_de` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `_seq_staff_de` VALUES (1);

DROP TABLE IF EXISTS `_seq_staff_re`;
CREATE TABLE `_seq_staff_re` (
  `last_id` int(10) unsigned NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `_seq_staff_re` VALUES (1);

-- -----------------------------------------------------------
-- password_resets
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- staffs (no FK dependencies)
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `staffs`;
CREATE TABLE `staffs` (
  `staff_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `profile_photo` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `resigned_at` datetime DEFAULT NULL,
  `resignation_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `staffs` VALUES
('AD001','Clinic Admin','admin@qydentra.com','$2y$10$LeToVUwUqDZzNMn7HH3mme5z4oaZnid9dGR4JxbkABCu5eNLC0sym','admin',NULL,'active',NULL,NULL,'2026-06-08 04:33:25'),
('RE001','Clinic Receptionist','receptionist@qydentra.com','$2y$10$55aHBBqLFz35wzeS0JvaMexCGGWjEcG7laJTP4Igisizk0UT2wyiq','receptionist',NULL,'active',NULL,NULL,'2026-06-08 04:33:25');

DROP TRIGGER IF EXISTS `trg_staffs_bi`;
DELIMITER ;;
CREATE TRIGGER `trg_staffs_bi`
BEFORE INSERT ON `staffs`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE prefix  VARCHAR(2);

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
END;;
DELIMITER ;

-- -----------------------------------------------------------
-- dentists (no FK dependencies)
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `dentists`;
CREATE TABLE `dentists` (
  `dentist_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'dentist',
  `specialization` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_photo` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `resigned_at` datetime DEFAULT NULL,
  `resignation_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`dentist_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `dentists` VALUES
('DE001','Clinic Dentist','dentist@qydentra.com','$2y$10$42WaES.Ih13xFS1L8kkJreAnMIQQ7aJgPOMStCSpdJP9SiRNgbEdq','dentist',NULL,NULL,NULL,'active',NULL,NULL,'2026-06-08 04:56:32');

-- -----------------------------------------------------------
-- patients (no FK dependencies)
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `patient_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'patient',
  `profile_photo` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `medical_history` text DEFAULT NULL,
  `odontogram_data` text DEFAULT NULL,
  PRIMARY KEY (`patient_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `patients` VALUES
('PT001','Donna Mae Lat','donnamaelat@gmail.com','$2y$10$EOQmfooMdiJoXWQgIfHxEO6cR8zM9QIGpIxG6ptxteMEKMpWTC1ia','patient',NULL,'active',NULL,'2026-06-08 05:13:05','testing medical conditions ','{\"23\":\"Filled\"}'),
('PT002','user1','user@gmail.com','$2y$10$LJtSekupQqQThEGXP1YaLO6wGsF3rJlFpx1sWGaYkv5wUceYOudTi','patient',NULL,'active',NULL,'2026-06-08 07:15:39','','{\"27\":\"Missing\"}');

DROP TRIGGER IF EXISTS `trg_patients_bi`;
DELIMITER ;;
CREATE TRIGGER `trg_patients_bi`
BEFORE INSERT ON `patients`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_patients SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_patients LIMIT 1;
    SET NEW.patient_id = CONCAT('PT', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- -----------------------------------------------------------
-- appointments (FK -> patients)
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `appointment_id` varchar(10) NOT NULL,
  `patient_id` varchar(10) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `service_desc` varchar(150) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Approved','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `queue_number` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dentist_notes` text DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `odontogram_data` text DEFAULT NULL,
  PRIMARY KEY (`appointment_id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `appointments` VALUES
('AP001','PT001','Teeth Cleaning','Routine Dental Care','2026-06-08','15:20','Completed',1,'chuchuchu','2026-06-08 05:14:55',NULL,NULL,NULL,NULL),
('AP002','PT001','Dental Filling','Tooth Restoration','2026-06-08','14:00','Completed',2,'this is a test for patient notes','2026-06-08 05:44:05','for filling','biogesic ','testing medical conditions ','{\"23\":\"Filled\"}'),
('AP003','PT001','Teeth Cleaning','Routine Dental Care','2026-06-08','16:00','Completed',3,'3','2026-06-08 07:10:35','','','testing medical conditions ','{\"23\":\"Filled\"}'),
('AP004','PT002','Teeth Cleaning','Routine Dental Care','2026-06-08','16:11','Completed',4,'','2026-06-08 07:16:20','','','','{\"27\":\"Missing\"}');

DROP TRIGGER IF EXISTS `trg_appointments_bi`;
DELIMITER ;;
CREATE TRIGGER `trg_appointments_bi`
BEFORE INSERT ON `appointments`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_appointments SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_appointments LIMIT 1;
    SET NEW.appointment_id = CONCAT('AP', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- -----------------------------------------------------------
-- patient_notifications (FK -> patients, appointments)
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `patient_notifications`;
CREATE TABLE `patient_notifications` (
  `notification_id` varchar(10) NOT NULL,
  `patient_id` varchar(10) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `type` enum('Appointment','Queue','System') DEFAULT 'Appointment',
  `message` text NOT NULL,
  `appointment_id` varchar(10) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `patient_id` (`patient_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `patient_notifications_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  CONSTRAINT `patient_notifications_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `patient_notifications` VALUES
('PN001','PT001','Appointment Request Submitted','Appointment','Patient Donna Mae Lat submitted an appointment request successfully.','AP001',0,'2026-06-08 05:14:55'),
('PN002','PT001','Appointment Approved','Appointment','Clinic Receptionist approved the Teeth Cleaning appointment for Patient Donna Mae Lat on June 08, 2026 at 3:20 PM.','AP001',0,'2026-06-08 05:16:52'),
('PN003','PT001','Dentist Calling','Queue','The Dentist is ready to see you now. Please proceed to the clinic room.',NULL,0,'2026-06-08 05:17:55'),
('PN004','PT001','Consultation Finished','System','Your dental appointment has been completed. Thank you for visiting!',NULL,0,'2026-06-08 05:30:49'),
('PN005','PT001','Appointment Request Submitted','Appointment','Patient Donna Mae Lat submitted an appointment request successfully.','AP002',0,'2026-06-08 05:44:05'),
('PN006','PT001','Appointment Approved','Appointment','Clinic Receptionist approved the Dental Filling appointment for Patient Donna Mae Lat on June 08, 2026 at 2:00 PM.','AP002',0,'2026-06-08 05:44:24'),
('PN007','PT001','Queue Update','Queue','Your queue status has been updated to: Completed.',NULL,0,'2026-06-08 05:44:40'),
('PN008','PT001','Queue Update','Queue','Your queue status has been updated to: Approved.',NULL,0,'2026-06-08 05:44:46'),
('PN009','PT001','Dentist Calling','Queue','The Dentist is ready to see you now. Please proceed to the clinic room.',NULL,0,'2026-06-08 05:45:16'),
('PN010','PT001','Consultation Finished','System','Your dental appointment has been completed. Thank you for visiting!',NULL,0,'2026-06-08 06:36:38'),
('PN011','PT001','Appointment Request Submitted','Appointment','Patient Donna Mae Lat submitted an appointment request successfully.','AP003',0,'2026-06-08 07:10:35'),
('PN012','PT001','Appointment Approved','Appointment','Clinic Receptionist approved the Teeth Cleaning appointment for Patient Donna Mae Lat on June 08, 2026 at 4:00 PM.','AP003',0,'2026-06-08 07:11:32'),
('PN013','PT001','Dentist Calling','Queue','The Dentist is ready to see you now. Please proceed to the clinic room.',NULL,0,'2026-06-08 07:12:00'),
('PN014','PT002','Appointment Request Submitted','Appointment','Patient user1 submitted an appointment request successfully.','AP004',0,'2026-06-08 07:16:20'),
('PN015','PT002','Appointment Approved','Appointment','Clinic Receptionist approved the Teeth Cleaning appointment for Patient user1 on June 08, 2026 at 4:11 PM.','AP004',0,'2026-06-08 07:16:40'),
('PN016','PT001','Consultation Finished','System','Your dental appointment has been completed. Thank you for visiting!',NULL,0,'2026-06-08 07:17:14'),
('PN017','PT002','Dentist Calling','Queue','The Dentist is ready to see you now. Please proceed to the clinic room.',NULL,0,'2026-06-08 07:17:17'),
('PN018','PT002','Consultation Finished','System','Your dental appointment has been completed. Thank you for visiting!',NULL,0,'2026-06-08 07:17:31');

DROP TRIGGER IF EXISTS `trg_pat_notif_bi`;
DELIMITER ;;
CREATE TRIGGER `trg_pat_notif_bi`
BEFORE INSERT ON `patient_notifications`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_pat_notif SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_pat_notif LIMIT 1;
    SET NEW.notification_id = CONCAT('PN', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- -----------------------------------------------------------
-- receptionist_notifications (FK -> staffs, appointments)
-- -----------------------------------------------------------

DROP TABLE IF EXISTS `receptionist_notifications`;
CREATE TABLE `receptionist_notifications` (
  `receptionist_notification_id` varchar(10) NOT NULL,
  `receptionist_id` varchar(10) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('Appointment','Queue','System') DEFAULT 'Appointment',
  `status` enum('Unread','Read') DEFAULT 'Unread',
  `appointment_id` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`receptionist_notification_id`),
  KEY `receptionist_id` (`receptionist_id`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `receptionist_notifications_ibfk_1` FOREIGN KEY (`receptionist_id`) REFERENCES `staffs` (`staff_id`),
  CONSTRAINT `receptionist_notifications_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `receptionist_notifications` VALUES
('RN001','RE001','New Appointment Booked','Patient Donna Mae Lat booked a Teeth Cleaning appointment on June 08, 2026 at 3:20 PM.','Appointment','Unread','AP001','2026-06-08 05:14:55'),
('RN002','RE001','Appointment Approved','Clinic Receptionist approved the Teeth Cleaning appointment for Patient Donna Mae Lat on June 08, 2026 at 3:20 PM.','Appointment','Unread','AP001','2026-06-08 05:16:52'),
('RN003','RE001','New Appointment Booked','Patient Donna Mae Lat booked a Dental Filling appointment on June 08, 2026 at 2:00 PM.','Appointment','Unread','AP002','2026-06-08 05:44:05'),
('RN004','RE001','Appointment Approved','Clinic Receptionist approved the Dental Filling appointment for Patient Donna Mae Lat on June 08, 2026 at 2:00 PM.','Appointment','Unread','AP002','2026-06-08 05:44:24'),
('RN005','RE001','New Appointment Booked','Patient Donna Mae Lat booked a Teeth Cleaning appointment on June 08, 2026 at 4:00 PM.','Appointment','Unread','AP003','2026-06-08 07:10:35'),
('RN006','RE001','Appointment Approved','Clinic Receptionist approved the Teeth Cleaning appointment for Patient Donna Mae Lat on June 08, 2026 at 4:00 PM.','Appointment','Unread','AP003','2026-06-08 07:11:32'),
('RN007','RE001','New Appointment Booked','Patient user1 booked a Teeth Cleaning appointment on June 08, 2026 at 4:11 PM.','Appointment','Unread','AP004','2026-06-08 07:16:20'),
('RN008','RE001','Appointment Approved','Clinic Receptionist approved the Teeth Cleaning appointment for Patient user1 on June 08, 2026 at 4:11 PM.','Appointment','Unread','AP004','2026-06-08 07:16:40');

DROP TRIGGER IF EXISTS `trg_rec_notif_bi`;
DELIMITER ;;
CREATE TRIGGER `trg_rec_notif_bi`
BEFORE INSERT ON `receptionist_notifications`
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    UPDATE _seq_rec_notif SET last_id = last_id + 1;
    SELECT last_id INTO next_id FROM _seq_rec_notif LIMIT 1;
    SET NEW.receptionist_notification_id = CONCAT('RN', LPAD(next_id, 3, '0'));
END;;
DELIMITER ;

-- -----------------------------------------------------------
-- Re-enable FK checks
-- -----------------------------------------------------------

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- Import complete. Tables: staffs, dentists, patients,
-- appointments, patient_notifications,
-- receptionist_notifications + 7 sequence tables + 5 triggers
-- ============================================================
