CREATE DATABASE IF NOT EXISTS `chimicanga_lms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `chimicanga_lms`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `lesson_progress`;
DROP TABLE IF EXISTS `lessons`;
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

-- 4. COURSE LECTURERS PIVOT TABLE
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

-- 6. LESSONS & PROGRESS
CREATE TABLE `lessons` (
  `lesson_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `module_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `title` VARCHAR(200) NOT NULL,
  `lesson_type` ENUM('video', 'reading', 'quiz') NOT NULL DEFAULT 'video',
  `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
  `content_markdown` LONGTEXT NULL,
  `code_snippet` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `lesson_progress` (
  `progress_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `completed_at` TIMESTAMP NULL,
  UNIQUE KEY `uq_user_lesson` (`user_id`, `lesson_id`),
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. ASSIGNMENTS & SUBMISSIONS
CREATE TABLE `assignments` (
  `assignment_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `instructions` TEXT NOT NULL,
  `due_date` DATETIME NOT NULL,
  `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `weight_percentage` DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_assignments_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `assignment_submissions` (
  `submission_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `student_notes` TEXT NULL,
  `status` ENUM('pending', 'submitted', 'graded', 'late') NOT NULL DEFAULT 'submitted',
  `score` DECIMAL(5,2) NULL,
  `instructor_feedback` TEXT NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `graded_at` TIMESTAMP NULL,
  UNIQUE KEY `uq_assignment_student` (`assignment_id`, `user_id`),
  CONSTRAINT `fk_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`assignment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. ANNOUNCEMENTS, NOTIFICATIONS, LOGS, SETTINGS
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

CREATE TABLE `notifications` (
  `notification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `activity_logs` (
  `log_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(120) NOT NULL,
  `details` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `system_settings` (
  `setting_key` VARCHAR(60) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================================
-- DEFAULT USERS FOR ALL 3 ROLES (Password for all: admin123 / password)
-- ============================================================================
INSERT INTO `users` (`user_id`, `staff_student_id`, `full_name`, `email`, `password_hash`, `role`, `department`, `phone`, `status`) VALUES
(1, 'ADM001', 'System Administrator', 'admin@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT & Academic Operations', '012-3456789', 'active'),
(2, 'LEC101', 'Dr. Amina Rahman', 'amina@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer', 'Software Engineering', '013-2223344', 'active'),
(3, 'STU202601', 'Ahmad Daniyal', 'daniyal@chimicanga.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Software Engineering', '011-8889900', 'active');

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('lms_name', 'Chimicanga LMS'),
('lms_description', 'Standard University Learning Management System'),
('allowed_file_types', 'pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png'),
('max_upload_mb', '10'),
('min_password_length', '8'),
('enable_notifications', '1');