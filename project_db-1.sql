-- =====================================================
-- PROJECT_DB - FULLY UPDATED WITH CCJE + CTE + CBAA + CAS + ENGINEERING
-- Compatible: MySQL 5.7+, 8.0+, XAMPP, phpMyAdmin
-- Collation: utf8mb4_unicode_ci (NO 0900 ERROR)
-- ADDED: CCJE (Criminology) + CTE (Teacher Education) Department Data
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `project_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `project_db`;

-- =====================================================
-- CORRECT DROP ORDER: CHILD TABLES FIRST!
-- =====================================================
DROP TABLE IF EXISTS `board_exam_dates`;
DROP TABLE IF EXISTS `subject_exam_types`;
DROP TABLE IF EXISTS `board_passer_subjects`;
DROP TABLE IF EXISTS `board_passers`;
DROP TABLE IF EXISTS `board_exam_types`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `board_passers_backup`;
DROP TABLE IF EXISTS `users`;

-- =====================================================
-- 1. board_exam_types (ENGINEERING + CAS + CBAA + CCJE + CTE)
-- =====================================================
CREATE TABLE `board_exam_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_type_name` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `board_exam_types` (`id`, `exam_type_name`, `department`) VALUES
-- Engineering Board Exams
(1, 'Registered Electrical Engineer Licensure Exam (REELE)', 'Engineering'),
(2, 'Registered Master Electrician (RME)', 'Engineering'),
(3, 'Electronics Engineer Licensure Exam (EELE)', 'Engineering'),
(7, 'Electrical Engineer Licensure Exam (EELE)', 'Engineering'),

-- CAS Board Exams
(8, 'Licensure Examination for Teachers (LET)', 'Arts and Sciences'),
(9, 'Psychometrician Licensure Examination', 'Arts and Sciences'),
(10, 'Guidance Counselor Licensure Examination', 'Arts and Sciences'),
(11, 'Social Worker Licensure Examination', 'Arts and Sciences'),
(12, 'Chemist Licensure Examination', 'Arts and Sciences'),
(13, 'Biologist Licensure Examination', 'Arts and Sciences'),

-- CBAA Board Exams
(14, 'CPA Licensure Examination', 'Business Administration and Accountancy'),
(15, 'Real Estate Broker Licensure Examination', 'Business Administration and Accountancy'),
(16, 'Certified Management Accountant (CMA) Exam', 'Business Administration and Accountancy'),

-- CCJE Board Exams (NEW!)
(17, 'Criminologist Licensure Examination (CLE)', 'Criminal Justice Education'),

-- CTE Board Exams (NEW! - LET is shared but department-specific)
(18, 'Licensure Examination for Teachers (LET) - Elementary', 'Teacher Education'),
(19, 'Licensure Examination for Teachers (LET) - Secondary', 'Teacher Education');

-- =====================================================
-- 2. board_exam_dates (ENGINEERING + CAS + CBAA + CCJE + CTE)
-- =====================================================
CREATE TABLE `board_exam_dates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_date` date NOT NULL,
  `exam_description` varchar(255) DEFAULT NULL,
  `exam_type_id` int DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_exam_date_type_dept` (`exam_date`,`exam_type_id`,`department`),
  KEY `fk_exam_type` (`exam_type_id`),
  CONSTRAINT `fk_exam_type` FOREIGN KEY (`exam_type_id`)
    REFERENCES `board_exam_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `board_exam_dates` (`id`, `exam_date`, `exam_description`, `exam_type_id`, `department`, `created_at`) VALUES
-- Engineering Dates (unchanged)
(2, '2025-10-01', 'Electronics Board Exam', 3, 'Engineering', '2025-10-12 10:42:38'),
(3, '2024-08-01', 'General Engineering Exam', NULL, 'Engineering', '2025-10-12 10:47:01'),
(4, '2024-12-06', 'December Board Exam', NULL, 'Engineering', '2025-10-12 13:05:31'),
(5, '2024-12-16', 'End of Year Exam', NULL, 'Engineering', '2025-10-12 13:54:29'),
(6, '2024-12-01', 'December 1 Exam', NULL, 'Engineering', '2025-10-22 01:47:33'),
(11, '2024-12-05', 'December 5 Exam', NULL, 'Engineering', '2025-10-22 01:50:06'),
(16, '2024-12-04', 'Electrical Exam', 7, 'Engineering', '2025-10-27 12:36:54'),
(17, '2024-03-30', 'March Electrical Exam', 7, 'Engineering', '2025-10-27 16:52:08'),
(18, '2025-07-09', 'Master Electrician Exam', 2, 'Engineering', '2025-10-30 18:45:23'),
(19, '2024-07-17', 'REELE July Exam', 1, 'Engineering', '2025-10-30 18:45:41'),
(20, '2023-01-01', 'January Electrical Exam', 7, 'Engineering', '2025-11-05 03:05:50'),

-- CAS Dates (unchanged)
(21, '2024-09-29', 'LET September 2024', 8, 'Arts and Sciences', NOW()),
(22, '2024-03-24', 'LET March 2024', 8, 'Arts and Sciences', NOW()),
(23, '2024-11-17', 'Psychometrician November 2024', 9, 'Arts and Sciences', NOW()),
(24, '2024-08-11', 'Guidance Counselor August 2024', 10, 'Arts and Sciences', NOW()),
(25, '2024-06-23', 'Social Worker June 2024', 11, 'Arts and Sciences', NOW()),
(26, '2024-10-27', 'Chemist October 2024', 12, 'Arts and Sciences', NOW()),
(27, '2024-05-12', 'Biologist May 2024', 13, 'Arts and Sciences', NOW()),
(28, '2025-03-23', 'LET March 2025', 8, 'Arts and Sciences', NOW()),
(29, '2025-09-28', 'LET September 2025', 8, 'Arts and Sciences', NOW()),

-- CBAA Dates (unchanged)
(30, '2024-10-20', 'CPA October 2024', 14, 'Business Administration and Accountancy', NOW()),
(31, '2024-05-19', 'CPA May 2024', 14, 'Business Administration and Accountancy', NOW()),
(32, '2023-10-22', 'CPA October 2023', 14, 'Business Administration and Accountancy', NOW()),
(33, '2025-05-18', 'CPA May 2025', 14, 'Business Administration and Accountancy', NOW()),
(34, '2025-10-19', 'CPA October 2025', 14, 'Business Administration and Accountancy', NOW()),
(35, '2024-07-28', 'Real Estate Broker July 2024', 15, 'Business Administration and Accountancy', NOW()),

-- CCJE Dates (NEW!)
(50, '2024-10-15', 'CLE October 2024', 17, 'Criminal Justice Education', NOW()),
(51, '2024-04-20', 'CLE April 2024', 17, 'Criminal Justice Education', NOW()),
(52, '2025-04-19', 'CLE April 2025', 17, 'Criminal Justice Education', NOW()),
(53, '2025-10-14', 'CLE October 2025', 17, 'Criminal Justice Education', NOW()),

-- CTE Dates (NEW! - LET dates shared but tagged to CTE)
(60, '2024-09-29', 'LET Elementary September 2024', 18, 'Teacher Education', NOW()),
(61, '2024-03-24', 'LET Elementary March 2024', 18, 'Teacher Education', NOW()),
(62, '2024-09-29', 'LET Secondary September 2024', 19, 'Teacher Education', NOW()),
(63, '2024-03-24', 'LET Secondary March 2024', 19, 'Teacher Education', NOW()),
(64, '2025-03-23', 'LET Elementary March 2025', 18, 'Teacher Education', NOW()),
(65, '2025-09-28', 'LET Secondary September 2025', 19, 'Teacher Education', NOW());

-- =====================================================
-- 3. courses (ENGINEERING + CAS + CBAA + CCJE + CTE)
-- =====================================================
CREATE TABLE `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_name` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `gdate` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `courses` (`id`, `course_name`, `department`, `gdate`) VALUES
-- Engineering Courses (unchanged)
(4, 'Bachelor of Science in Electrical Engineering (BSEE)', 'Engineering', NULL),
(10, 'Bachelor of Science in Computer Engineering (BSCpE)', 'Engineering', NULL),
(22, 'Bachelor of Science in Civil Engineering (BSCE)', 'Engineering', NULL),
(23, 'Bachelor of Science in Mechanical Engineering (BSME)', 'Engineering', NULL),
(24, 'Bachelor of Science in Electronics Engineering (BSEcE)', 'Engineering', NULL),

-- CAS Courses (unchanged)
(25, 'Bachelor of Arts in Communication (BAC)', 'Arts and Sciences', NULL),
(26, 'Bachelor of Arts in English Language (BAEL)', 'Arts and Sciences', NULL),
(27, 'Bachelor of Arts in Filipino (BAF)', 'Arts and Sciences', NULL),
(28, 'Bachelor of Science in Psychology (BSPsych)', 'Arts and Sciences', NULL),
(29, 'Bachelor of Science in Biology (BSBio)', 'Arts and Sciences', NULL),
(30, 'Bachelor of Science in Mathematics (BSMath)', 'Arts and Sciences', NULL),
(31, 'Bachelor of Science in Chemistry (BSChem)', 'Arts and Sciences', NULL),
(32, 'Bachelor of Science in Social Work (BSSW)', 'Arts and Sciences', NULL),
(33, 'Bachelor of Elementary Education (BEEd)', 'Arts and Sciences', NULL),
(34, 'Bachelor of Secondary Education (BSEd)', 'Arts and Sciences', NULL),

-- CBAA Courses (unchanged)
(35, 'Bachelor of Science in Accountancy (BSA)', 'Business Administration and Accountancy', NULL),
(36, 'Bachelor of Science in Business Administration - Financial Management (BSBA-FM)', 'Business Administration and Accountancy', NULL),
(37, 'Bachelor of Science in Business Administration - Marketing Management (BSBA-MM)', 'Business Administration and Accountancy', NULL),
(38, 'Bachelor of Science in Business Administration - Human Resource Management (BSBA-HRM)', 'Business Administration and Accountancy', NULL),
(39, 'Bachelor of Science in Entrepreneurship (BSEntrep)', 'Business Administration and Accountancy', NULL),
(40, 'Bachelor of Science in Real Estate Management (BSREM)', 'Business Administration and Accountancy', NULL),

-- CCJE Courses (NEW!)
(70, 'Bachelor of Science in Criminology (BSCrim)', 'Criminal Justice Education', NULL),

-- CTE Courses (NEW!)
(80, 'Bachelor of Elementary Education (BEEd)', 'Teacher Education', NULL),
(81, 'Bachelor of Secondary Education major in English (BSEd English)', 'Teacher Education', NULL),
(82, 'Bachelor of Secondary Education major in Mathematics (BSEd Math)', 'Teacher Education', NULL),
(83, 'Bachelor of Secondary Education major in Science (BSEd Science)', 'Teacher Education', NULL),
(84, 'Bachelor of Secondary Education major in Social Studies (BSEd Social Studies)', 'Teacher Education', NULL),
(85, 'Bachelor of Physical Education (BPEd)', 'Teacher Education', NULL);

-- =====================================================
-- 4. subjects (ENGINEERING + CAS + CBAA + CCJE + CTE)
-- =====================================================
CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(255) NOT NULL,
  `total_items` int NOT NULL DEFAULT 50,
  `exam_type_id` int DEFAULT NULL,
  `department` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subjects` (`id`, `subject_name`, `total_items`, `exam_type_id`, `department`, `created_at`) VALUES
-- Engineering Subjects (unchanged)
(13, 'Electric Circuits', 100, 7, 'Engineering', '2025-10-27 12:36:28'),
(14, 'Electronics Engineering', 100, 3, 'Engineering', '2025-10-27 15:57:33'),
(15, 'Power Systems', 100, 2, 'Engineering', '2025-10-27 16:15:44'),
(16, 'Mathematics for Engineers', 100, 7, 'Engineering', '2025-10-30 18:44:07'),
(17, 'Engineering Economics', 100, 7, 'Engineering', '2025-10-30 18:44:14'),
(18, 'Control Systems', 100, 7, 'Engineering', '2025-10-30 18:44:25'),

-- CAS Subjects (unchanged)
(30, 'Professional Education', 150, 8, 'Arts and Sciences', NOW()),
(31, 'General Education', 150, 8, 'Arts and Sciences', NOW()),
(32, 'Major in Elementary Education', 100, 8, 'Arts and Sciences', NOW()),
(33, 'Major in Secondary Education', 100, 8, 'Arts and Sciences', NOW()),
(34, 'Psychological Assessment', 100, 9, 'Arts and Sciences', NOW()),
(35, 'Abnormal Psychology', 100, 9, 'Arts and Sciences', NOW()),
(36, 'Guidance and Counseling Theories', 100, 10, 'Arts and Sciences', NOW()),
(37, 'Career Counseling', 100, 10, 'Arts and Sciences', NOW()),
(38, 'Social Work Practice', 100, 11, 'Arts and Sciences', NOW()),
(39, 'Community Organization', 100, 11, 'Arts and Sciences', NOW()),
(40, 'Analytical Chemistry', 100, 12, 'Arts and Sciences', NOW()),
(41, 'Organic Chemistry', 100, 12, 'Arts and Sciences', NOW()),
(42, 'Cell Biology', 100, 13, 'Arts and Sciences', NOW()),
(43, 'Genetics', 100, 13, 'Arts and Sciences', NOW()),
(44, 'Ecology', 100, 13, 'Arts and Sciences', NOW()),

-- CBAA Subjects (unchanged)
(45, 'Financial Accounting and Reporting', 150, 14, 'Business Administration and Accountancy', NOW()),
(46, 'Advanced Financial Accounting', 100, 14, 'Business Administration and Accountancy', NOW()),
(47, 'Management Advisory Services', 100, 14, 'Business Administration and Accountancy', NOW()),
(48, 'Auditing', 150, 14, 'Business Administration and Accountancy', NOW()),
(49, 'Taxation', 100, 14, 'Business Administration and Accountancy', NOW()),
(50, 'Regulatory Framework for Business Transactions', 100, 14, 'Business Administration and Accountancy', NOW()),
(51, 'Real Estate Laws', 100, 15, 'Business Administration and Accountancy', NOW()),
(52, 'Property Valuation', 100, 15, 'Business Administration and Accountancy', NOW()),

-- CCJE Subjects (NEW!)
(90, 'Criminalistics', 150, 17, 'Criminal Justice Education', NOW()),
(91, 'Law Enforcement Administration', 150, 17, 'Criminal Justice Education', NOW()),
(92, 'Criminal Law and Jurisprudence', 150, 17, 'Criminal Justice Education', NOW()),
(93, 'Correctional Administration', 100, 17, 'Criminal Justice Education', NOW()),
(94, 'Criminal Sociology', 100, 17, 'Criminal Justice Education', NOW()),
(95, 'Ethics and Values in Criminology', 100, 17, 'Criminal Justice Education', NOW()),

-- CTE Subjects (NEW!)
(100, 'General Education (LET)', 150, 18, 'Teacher Education', NOW()),
(101, 'Professional Education (LET Elementary)', 150, 18, 'Teacher Education', NOW()),
(102, 'Pre-School Education', 100, 18, 'Teacher Education', NOW()),
(103, 'General Education (LET)', 150, 19, 'Teacher Education', NOW()),
(104, 'Professional Education (LET Secondary)', 150, 19, 'Teacher Education', NOW()),
(105, 'English Specialization', 100, 19, 'Teacher Education', NOW()),
(106, 'Mathematics Specialization', 100, 19, 'Teacher Education', NOW()),
(107, 'Science Specialization', 100, 19, 'Teacher Education', NOW()),
(108, 'Social Studies Specialization', 100, 19, 'Teacher Education', NOW());

-- =====================================================
-- 5. subject_exam_types (ENGINEERING + CAS + CBAA + CCJE + CTE)
-- =====================================================
CREATE TABLE `subject_exam_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `exam_type_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_exam_unique` (`subject_id`,`exam_type_id`),
  KEY `exam_type_id` (`exam_type_id`),
  CONSTRAINT `subject_exam_types_ibfk_1` FOREIGN KEY (`subject_id`)
    REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_exam_types_ibfk_2` FOREIGN KEY (`exam_type_id`)
    REFERENCES `board_exam_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subject_exam_types` (`id`, `subject_id`, `exam_type_id`, `created_at`) VALUES
-- Engineering Links (unchanged)
(5, 13, 7, '2025-10-27 12:36:29'),
(6, 14, 3, '2025-10-27 15:57:33'),
(7, 15, 2, '2025-10-27 16:15:44'),
(8, 16, 7, '2025-10-30 18:44:07'),
(9, 17, 7, '2025-10-30 18:44:14'),
(10, 18, 7, '2025-10-30 18:44:25'),

-- CAS Links (unchanged)
(20, 30, 8, NOW()),
(21, 31, 8, NOW()),
(22, 32, 8, NOW()),
(23, 33, 8, NOW()),
(24, 34, 9, NOW()),
(25, 35, 9, NOW()),
(26, 36, 10, NOW()),
(27, 37, 10, NOW()),
(28, 38, 11, NOW()),
(29, 39, 11, NOW()),
(30, 40, 12, NOW()),
(31, 41, 12, NOW()),
(32, 42, 13, NOW()),
(33, 43, 13, NOW()),
(34, 44, 13, NOW()),

-- CBAA Links (unchanged)
(35, 45, 14, NOW()),
(36, 46, 14, NOW()),
(37, 47, 14, NOW()),
(38, 48, 14, NOW()),
(39, 49, 14, NOW()),
(40, 50, 14, NOW()),
(41, 51, 15, NOW()),
(42, 52, 15, NOW()),

-- CCJE Links (NEW!)
(70, 90, 17, NOW()),
(71, 91, 17, NOW()),
(72, 92, 17, NOW()),
(73, 93, 17, NOW()),
(74, 94, 17, NOW()),
(75, 95, 17, NOW()),

-- CTE Links (NEW!)
(80, 100, 18, NOW()),
(81, 101, 18, NOW()),
(82, 102, 18, NOW()),
(83, 103, 19, NOW()),
(84, 104, 19, NOW()),
(85, 105, 19, NOW()),
(86, 106, 19, NOW()),
(87, 107, 19, NOW()),
(88, 108, 19, NOW());

-- =====================================================
-- 6. board_passers (ENGINEERING + CAS + CBAA + CCJE + CTE)
-- =====================================================
CREATE TABLE `board_passers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `sex` varchar(10) NOT NULL,
  `course` varchar(255) NOT NULL,
  `year_graduated` int NOT NULL,
  `board_exam_date` date NOT NULL,
  `result` varchar(20) NOT NULL DEFAULT 'PASSED',
  `department` varchar(100) NOT NULL,
  `exam_type` varchar(255) DEFAULT NULL,
  `board_exam_type` varchar(255) DEFAULT NULL,
  `rating` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `board_passers` (`id`, `name`, `first_name`, `last_name`, `middle_name`, `suffix`, `sex`, `course`, `year_graduated`, `board_exam_date`, `result`, `department`, `exam_type`, `board_exam_type`, `rating`, `created_at`, `updated_at`) VALUES
-- Engineering Records (unchanged)
(29, 'Desacula, Angel Anne', 'Angel Anne', 'Desacula', '', NULL, 'Female', 'Bachelor of Science in Computer Engineering (BSCpE)', 2024, '2024-12-04', 'Passed', 'Engineering', 'First Timer', '7', 85.50, '2025-11-02 07:24:56', '2025-11-06 12:38:56'),
(30, 'Camiso, Jaina Marie', 'Jaina Marie', 'Camiso', '', NULL, 'Female', 'Bachelor of Science in Electrical Engineering (BSEE)', 2023, '2024-03-30', 'Passed', 'Engineering', 'First Timer', 'Electrical Engineer Licensure Exam (EELE)', 88.75, '2025-11-02 07:27:38', '2025-11-02 07:27:38'),
(31, 'Dela Cruz, Juan', 'Juan', 'Dela Cruz', '', NULL, 'Male', 'Bachelor of Science in Electrical Engineering (BSEE)', 2020, '2024-07-17', 'Passed', 'Engineering', 'Repeater', 'Registered Electrical Engineer Licensure Exam (REELE)', 78.25, '2025-11-02 07:29:00', '2025-11-02 07:29:00'),
(32, 'Evora, Rose Anne', 'Rose Anne', 'Evora', '', NULL, 'Female', 'Bachelor of Science in Computer Engineering (BSCpE)', 2019, '2024-12-04', 'Failed', 'Engineering', 'First Timer', '7', 68.50, '2025-11-02 14:04:47', '2025-11-06 16:04:46'),
(33, 'Endrenal, Dannielle Anne', 'Dannielle Anne', 'Endrenal', '', NULL, 'Female', 'Bachelor of Science in Computer Engineering (BSCpE)', 2018, '2023-01-01', 'Failed', 'Engineering', 'Repeater', 'Electrical Engineer Licensure Exam (EELE)', 72.00, '2025-11-05 03:16:41', '2025-11-05 03:16:41'),

-- CAS Records (unchanged)
(34, 'Santos, Maria Clara', 'Maria Clara', 'Santos', 'Reyes', NULL, 'Female', 'Bachelor of Elementary Education (BEEd)', 2024, '2024-09-29', 'Passed', 'Arts and Sciences', 'First Timer', '8', 82.50, NOW(), NOW()),
(35, 'Garcia, Juan Pedro', 'Juan Pedro', 'Garcia', 'Lopez', NULL, 'Male', 'Bachelor of Secondary Education (BSEd)', 2024, '2024-09-29', 'Passed', 'Arts and Sciences', 'First Timer', '8', 85.75, NOW(), NOW()),
(36, 'Reyes, Ana Marie', 'Ana Marie', 'Reyes', 'Cruz', NULL, 'Female', 'Bachelor of Science in Psychology (BSPsych)', 2023, '2024-11-17', 'Passed', 'Arts and Sciences', 'First Timer', '9', 88.25, NOW(), NOW()),
(37, 'Torres, Carlos Miguel', 'Carlos Miguel', 'Torres', 'Diaz', NULL, 'Male', 'Bachelor of Science in Psychology (BSPsych)', 2023, '2024-11-17', 'Passed', 'Arts and Sciences', 'Repeater', '9', 76.50, NOW(), NOW()),
(38, 'Mendoza, Sofia Isabel', 'Sofia Isabel', 'Mendoza', 'Fernandez', NULL, 'Female', 'Bachelor of Science in Social Work (BSSW)', 2024, '2024-06-23', 'Passed', 'Arts and Sciences', 'First Timer', '11', 84.00, NOW(), NOW()),
(39, 'Villanueva, Mark Anthony', 'Mark Anthony', 'Villanueva', 'Ramos', NULL, 'Male', 'Bachelor of Science in Chemistry (BSChem)', 2023, '2024-10-27', 'Passed', 'Arts and Sciences', 'First Timer', '12', 90.25, NOW(), NOW()),
(40, 'Cruz, Patricia Ann', 'Patricia Ann', 'Cruz', 'Martinez', NULL, 'Female', 'Bachelor of Science in Biology (BSBio)', 2024, '2024-05-12', 'Passed', 'Arts and Sciences', 'First Timer', '13', 87.50, NOW(), NOW()),
(41, 'Aquino, Joshua Daniel', 'Joshua Daniel', 'Aquino', 'Gonzales', NULL, 'Male', 'Bachelor of Elementary Education (BEEd)', 2023, '2024-03-24', 'Failed', 'Arts and Sciences', 'First Timer', '8', 72.25, NOW(), NOW()),
(42, 'Bautista, Catherine Joy', 'Catherine Joy', 'Bautista', 'Santos', NULL, 'Female', 'Bachelor of Science in Psychology (BSPsych)', 2022, '2024-11-17', 'Conditional', 'Arts and Sciences', 'Repeater', '9', 74.75, NOW(), NOW()),
(43, 'Flores, Miguel Angelo', 'Miguel Angelo', 'Flores', 'Rivera', NULL, 'Male', 'Bachelor of Arts in Communication (BAC)', 2024, '2024-09-29', 'Passed', 'Arts and Sciences', 'First Timer', '8', 83.50, NOW(), NOW()),
(44, 'Navarro, Christine Mae', 'Christine Mae', 'Navarro', 'Perez', NULL, 'Female', 'Bachelor of Science in Biology (BSBio)', 2023, '2024-05-12', 'Passed', 'Arts and Sciences', 'Repeater', '13', 79.00, NOW(), NOW()),

-- CBAA Records (unchanged)
(45, 'Lim, John Paul', 'John Paul', 'Lim', 'Tan', NULL, 'Male', 'Bachelor of Science in Accountancy (BSA)', 2024, '2024-10-20', 'Passed', 'Business Administration and Accountancy', 'First Timer', '14', 87.50, NOW(), NOW()),
(46, 'Tan, Maria Sofia', 'Maria Sofia', 'Tan', 'Go', NULL, 'Female', 'Bachelor of Science in Accountancy (BSA)', 2023, '2024-05-19', 'Passed', 'Business Administration and Accountancy', 'Repeater', '14', 76.25, NOW(), NOW()),
(47, 'Go, Michael Angelo', 'Michael Angelo', 'Go', 'Sy', NULL, 'Male', 'Bachelor of Science in Real Estate Management (BSREM)', 2024, '2024-07-28', 'Passed', 'Business Administration and Accountancy', 'First Timer', '15', 82.00, NOW(), NOW()),
(48, 'Sy, Christine Joy', 'Christine Joy', 'Sy', 'Ong', NULL, 'Female', 'Bachelor of Science in Accountancy (BSA)', 2023, '2024-10-20', 'Conditional', 'Business Administration and Accountancy', 'First Timer', '14', 74.80, NOW(), NOW()),

-- CCJE Records (NEW!)
(90, 'Reyes, Mark Joseph', 'Mark Joseph', 'Reyes', 'Santos', NULL, 'Male', 'Bachelor of Science in Criminology (BSCrim)', 2024, '2024-10-15', 'Passed', 'Criminal Justice Education', 'First Timer', '17', 88.40, NOW(), NOW()),
(91, 'Cruz, Angela Marie', 'Angela Marie', 'Cruz', 'Dela Cruz', NULL, 'Female', 'Bachelor of Science in Criminology (BSCrim)', 2023, '2024-04-20', 'Passed', 'Criminal Justice Education', 'First Timer', '17', 86.75, NOW(), NOW()),
(92, 'Santos, Carlo Miguel', 'Carlo Miguel', 'Santos', 'Reyes', NULL, 'Male', 'Bachelor of Science in Criminology (BSCrim)', 2023, '2024-10-15', 'Passed', 'Criminal Justice Education', 'Repeater', '17', 75.50, NOW(), NOW()),
(93, 'Mendoza, Joanna Rose', 'Joanna Rose', 'Mendoza', 'Garcia', NULL, 'Female', 'Bachelor of Science in Criminology (BSCrim)', 2022, '2024-04-20', 'Conditional', 'Criminal Justice Education', 'Repeater', '17', 74.90, NOW(), NOW()),

-- CTE Records (NEW!)
(100, 'Villanueva, Maria Theresa', 'Maria Theresa', 'Villanueva', 'Lopez', NULL, 'Female', 'Bachelor of Elementary Education (BEEd)', 2024, '2024-09-29', 'Passed', 'Teacher Education', 'First Timer', '18', 84.20, NOW(), NOW()),
(101, 'Garcia, John Michael', 'John Michael', 'Garcia', 'Reyes', NULL, 'Male', 'Bachelor of Secondary Education major in Mathematics (BSEd Math)', 2024, '2024-09-29', 'Passed', 'Teacher Education', 'First Timer', '19', 87.60, NOW(), NOW()),
(102, 'Torres, Ana Lourdes', 'Ana Lourdes', 'Torres', 'Cruz', NULL, 'Female', 'Bachelor of Secondary Education major in English (BSEd English)', 2023, '2024-03-24', 'Passed', 'Teacher Education', 'Repeater', '19', 78.30, NOW(), NOW()),
(103, 'Reyes, Paul Vincent', 'Paul Vincent', 'Reyes', 'Santos', NULL, 'Male', 'Bachelor of Physical Education (BPEd)', 2024, '2024-09-29', 'Passed', 'Teacher Education', 'First Timer', '19', 85.10, NOW(), NOW()),
(104, 'Dela Cruz, Catherine Anne', 'Catherine Anne', 'Dela Cruz', 'Mendoza', NULL, 'Female', 'Bachelor of Elementary Education (BEEd)', 2023, '2024-03-24', 'Failed', 'Teacher Education', 'First Timer', '18', 71.50, NOW(), NOW());

-- =====================================================
-- 7. board_passer_subjects
-- =====================================================
CREATE TABLE `board_passer_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `board_passer_id` int NOT NULL,
  `subject_id` int DEFAULT NULL,
  `subject_name` varchar(255) DEFAULT '',
  `grade` decimal(5,2) DEFAULT NULL,
  `passed` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `board_passer_id` (`board_passer_id`),
  CONSTRAINT `board_passer_subjects_ibfk_1` FOREIGN KEY (`board_passer_id`)
    REFERENCES `board_passers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `board_passer_subjects` (`id`, `board_passer_id`, `subject_id`, `subject_name`, `grade`, `passed`) VALUES
-- Engineering Grades (unchanged)
(22, 30, 13, '', 85.00, 1),
(23, 30, 16, '', 88.00, 1),
(24, 30, 17, '', 90.00, 1),
(25, 30, 18, '', 87.00, 1),
(26, 31, 13, '', 78.00, 1),
(27, 31, 16, '', 76.00, 1),
(28, 31, 17, '', 80.00, 1),
(33, 33, 13, '', 68.00, 0),
(34, 33, 16, '', 72.00, 0),
(35, 33, 17, '', 70.00, 0),
(36, 33, 18, '', 71.00, 0),

-- CAS Grades (unchanged)
(37, 34, 30, '', 120.00, 1),
(38, 34, 31, '', 125.00, 1),
(39, 34, 32, '', 82.00, 1),
(40, 35, 30, '', 125.00, 1),
(41, 35, 31, '', 130.00, 1),
(42, 35, 33, '', 85.00, 1),
(43, 36, 34, '', 88.00, 1),
(44, 36, 35, '', 89.00, 1),
(45, 37, 34, '', 76.00, 1),
(46, 37, 35, '', 77.00, 1),
(47, 38, 38, '', 84.00, 1),
(48, 38, 39, '', 84.00, 1),
(49, 39, 40, '', 92.00, 1),
(50, 39, 41, '', 89.00, 1),
(51, 40, 42, '', 87.00, 1),
(52, 40, 43, '', 88.00, 1),
(53, 40, 44, '', 88.00, 1),
(54, 41, 30, '', 70.00, 0),
(55, 41, 31, '', 73.00, 0),
(56, 42, 34, '', 74.00, 0),
(57, 42, 35, '', 75.00, 51),
(58, 43, 30, '', 83.00, 1),
(59, 44, 42, '', 79.00, 1),

-- CBAA Grades (unchanged)
(60, 45, 45, '', 130.00, 1),
(61, 45, 46, '', 88.00, 1),
(62, 45, 47, '', 85.00, 1),
(63, 45, 48, '', 135.00, 1),
(64, 45, 49, '', 82.00, 1),
(65, 45, 50, '', 80.00, 1),
(66, 46, 45, '', 110.00, 0),
(67, 46, 48, '', 112.00, 0),
(68, 47, 51, '', 85.00, 1),
(69, 47, 52, '', 79.00, 1),
(70, 48, 45, '', 108.00, 0),
(71, 48, 48, '', 110.00, 0),

-- CCJE Grades (NEW!)
(150, 90, 90, '', 132.00, 1),
(151, 90, 91, '', 130.00, 1),
(152, 90, 92, '', 128.00, 1),
(153, 90, 93, '', 88.00, 1),
(154, 90, 94, '', 86.00, 1),
(155, 90, 95, '', 90.00, 1),
(156, 91, 90, '', 130.00, 1),
(157, 91, 91, '', 128.00, 1),
(158, 91, 92, '', 125.00, 1),
(159, 91, 93, '', 85.00, 1),
(160, 92, 90, '', 112.00, 1),
(161, 92, 91, '', 110.00, 1),
(162, 93, 90, '', 108.00, 0),
(163, 93, 91, '', 107.00, 0),

-- CTE Grades (NEW!)
(170, 100, 100, '', 125.00, 1),
(171, 100, 101, '', 130.00, 1),
(172, 100, 102, '', 85.00, 1),
(173, 101, 103, '', 130.00, 1),
(174, 101, 104, '', 132.00, 1),
(175, 101, 106, '', 88.00, 1),
(176, 102, 103, '', 115.00, 0),
(177, 102, 104, '', 118.00, 0),
(178, 102, 105, '', 76.00, 1),
(179, 103, 103, '', 128.00, 1),
(180, 103, 104, '', 130.00, 1),
(181, 104, 100, '', 108.00, 0),
(182, 104, 101, '', 110.00, 0);

-- =====================================================
-- 8. board_passers_backup
-- =====================================================
CREATE TABLE `board_passers_backup` (
  `id` int NOT NULL DEFAULT 0,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `year_graduated` int NOT NULL,
  `board_exam_date` date NOT NULL,
  `result` varchar(50) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `exam_type` varchar(50) DEFAULT NULL,
  `board_exam_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. users (NO DUPLICATES + CCJE + CTE)
-- =====================================================
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `email`, `password`) VALUES
(1, 'yourgmail@gmail.com', 'adminpass'),
(3, 'angel@gmail.com', '12345'),
(4, 'eng_admin@lspu.edu.ph', 'engpass'),
(5, 'cas_admin@lspu.edu.ph', 'caspass'),
(6, 'cbaa_admin@lspu.edu.ph', 'cbaapass'),
(7, 'ccje_admin@lspu.edu.ph', 'ccjepass'),
(8, 'cte_admin@lspu.edu.ph', 'ctepass'),
(9, 'icts_admin@lspu.edu.ph', 'ictspass'),
(10, 'president@lspu.edu.ph', 'prespass');

-- =====================================================
-- ALL DONE! Database ready with CCJE, CTE, CBAA, CAS, and Engineering data!
-- =====================================================