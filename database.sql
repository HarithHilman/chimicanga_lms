-- ============================================================================
-- CHIMICANGA LMS - ADMIN MODULE DATABASE SCHEMA (MySQL / MariaDB)
-- Single-tier Admin RBAC | Clean Relational Structure
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `chimicanga_lms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `chimicanga_lms`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `enrolments`;
DROP TABLE IF EXISTS `course_lecturers`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. UNIFIED USERS TABLE (admin, lecturer, student)
CREATE TABLE `users` (
  `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `staff_student_id` VARCHAR(30) NOT NULL UNIQUE,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'lecturer', 'student') NOT NULL DEFAULT 'student',
  `department` VARCHAR(100) NULL,
  `phone` VARCHAR(30) NULL,
  `profile_photo` VARCHAR(255) NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. COURSE CATEGORIES TABLE
CREATE TABLE `categories` (
  `category_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. COURSES TABLE
CREATE TABLE `courses` (
  `course_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NULL,
  `course_code` VARCHAR(25) NOT NULL UNIQUE,
  `title` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `credit_hours` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_courses_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. COURSE LECTURERS PIVOT TABLE (Assign/Remove lecturers)
CREATE TABLE `course_lecturers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `lecturer_id` INT UNSIGNED NOT NULL,
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_course_lecturer` (`course_id`, `lecturer_id`),
  CONSTRAINT `fk_cl_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cl_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. STUDENT ENROLMENTS TABLE
CREATE TABLE `enrolments` (
  `enrolment_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `status` ENUM('enrolled', 'pending', 'rejected') NOT NULL DEFAULT 'enrolled',
  `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_course_student` (`course_id`, `student_id`),
  CONSTRAINT `fk_enrol_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrol_student` FOREIGN KEY (`student_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. ANNOUNCEMENTS TABLE
CREATE TABLE `announcements` (
  `announcement_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `created_by` INT UNSIGNED NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `body` TEXT NOT NULL,
  `target_role` ENUM('all', 'student', 'lecturer') NOT NULL DEFAULT 'all',
  `status` ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  `published_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ann_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. NOTIFICATIONS TABLE
CREATE TABLE `notifications` (
  `notification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. RECENT SYSTEM ACTIVITIES LOG
CREATE TABLE `activity_logs` (
  `log_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(120) NOT NULL,
  `details` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 9. SYSTEM SETTINGS TABLE
CREATE TABLE `system_settings` (
  `setting_key` VARCHAR(60) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================================
-- INITIAL SEED DATA (Default Admin Login: admin@chimicanga.edu / admin123)
-- ============================================================================

INSERT INTO `users` (`user_id`, `staff_student_id`, `full_name`, `email`, `password_hash`, `role`, `department`, `phone`, `status`) VALUES
(1, 'ADM001', 'System Administrator', 'admin@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT & Academic Operations', '012-3456789', 'active'),
(2, 'LEC101', 'Dr. Amina Rahman', 'amina@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer', 'Software Engineering', '013-2223344', 'active'),
(3, 'LEC102', 'Prof. Lim Wei Jie', 'weijie@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer', 'Computer Science', '014-5556677', 'active'),
(4, 'STU202601', 'Ahmad Daniyal', 'daniyal@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Software Engineering', '011-8889900', 'active'),
(5, 'STU202602', 'Nurul Izzah', 'izzah@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Information Systems', '017-4445566', 'active'),
(6, 'STU202603', 'Kevin Tan', 'kevin@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Computer Science', '019-7778899', 'inactive');

INSERT INTO `categories` (`category_id`, `name`, `description`, `status`) VALUES
(1, 'Software Engineering', 'Core software design, architecture, testing, and development courses.', 'active'),
(2, 'Database & Data Systems', 'Relational databases, SQL optimization, and data management.', 'active'),
(3, 'Web & Mobile Development', 'Frontend, backend, and full-stack application engineering.', 'active');

INSERT INTO `courses` (`course_id`, `category_id`, `course_code`, `title`, `description`, `credit_hours`, `status`) VALUES
(1, 1, 'CSEB3213', 'Software Architecture & Design Patterns', 'Principles of clean architecture, SOLID, and reusable design patterns.', 3, 'published'),
(2, 2, 'CSNB3123', 'Database Systems & Administration', 'Normalization, transactions, indexing, and relational database design.', 3, 'published'),
(3, 3, 'CSEB3413', 'Web Application Engineering', 'Building structured web systems with PHP, MySQL, and modern UI.', 4, 'draft');

INSERT INTO `course_lecturers` (`course_id`, `lecturer_id`) VALUES
(1, 2),
(2, 3);

INSERT INTO `enrolments` (`course_id`, `student_id`, `status`) VALUES
(1, 4, 'enrolled'),
(1, 5, 'enrolled'),
(2, 4, 'enrolled');

INSERT INTO `announcements` (`created_by`, `title`, `body`, `target_role`, `status`, `published_at`) VALUES
(1, 'Welcome to Chimicanga LMS', 'All courses are now open for student enrolment. Please check your assigned subjects.', 'all', 'published', NOW()),
(1, 'Lecturer Course Outline Submission', 'All lecturers are reminded to finalize their course descriptions before Friday.', 'lecturer', 'published', NOW());

INSERT INTO `notifications` (`user_id`, `title`, `message`, `is_read`) VALUES
(1, 'System Ready', 'Chimicanga LMS Admin module initialized successfully.', 0),
(1, 'New Student Registered', 'Student Kevin Tan (STU202603) account is pending activation.', 0);

INSERT INTO `activity_logs` (`user_id`, `action`, `details`) VALUES
(1, 'System Initialized', 'Chimicanga LMS database schema and default settings configured.'),
(1, 'Course Published', 'Published CSEB3213 - Software Architecture & Design Patterns.');

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('lms_name', 'Chimicanga LMS'),
('lms_description', 'Standard University Learning Management System'),
('allowed_file_types', 'pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png'),
('max_upload_mb', '10'),
('min_password_length', '8'),
('enable_notifications', '1');
