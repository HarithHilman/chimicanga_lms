-- ============================================================================
-- CHIMICANGA LMS - RELATIONAL DATABASE SCHEMA & SEED DATA (MySQL / MariaDB)
-- Compatible with phpMyAdmin, XAMPP, Laragon, WAMP, and MySQL 8.0+
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `chimicanga_lms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `chimicanga_lms`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `calendar_events`;
DROP TABLE IF EXISTS `forum_replies`;
DROP TABLE IF EXISTS `forum_threads`;
DROP TABLE IF EXISTS `lesson_notes`;
DROP TABLE IF EXISTS `quiz_attempts`;
DROP TABLE IF EXISTS `quiz_questions`;
DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `lesson_progress`;
DROP TABLE IF EXISTS `lessons`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS TABLE
CREATE TABLE `users` (
  `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student',
  `student_matrix_id` VARCHAR(30) NULL UNIQUE,
  `department` VARCHAR(100) NULL,
  `avatar_initials` VARCHAR(5) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. COURSES TABLE
CREATE TABLE `courses` (
  `course_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_code` VARCHAR(20) NOT NULL UNIQUE,
  `title` VARCHAR(180) NOT NULL,
  `description` TEXT NULL,
  `instructor_id` INT UNSIGNED NOT NULL,
  `credit_hours` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `category` VARCHAR(80) NOT NULL DEFAULT 'Software Engineering',
  `banner_gradient` VARCHAR(100) DEFAULT 'from-indigo-600 to-violet-600',
  `status` ENUM('active', 'archived', 'draft') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_courses_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. ENROLLMENTS TABLE
CREATE TABLE `enrollments` (
  `enrollment_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `final_letter_grade` VARCHAR(5) NULL,
  UNIQUE KEY `uq_user_course` (`user_id`, `course_id`),
  CONSTRAINT `fk_enrollments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. LESSONS TABLE
CREATE TABLE `lessons` (
  `lesson_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `module_order` INT UNSIGNED NOT NULL DEFAULT 1,
  `title` VARCHAR(200) NOT NULL,
  `lesson_type` ENUM('video', 'reading', 'quiz') NOT NULL DEFAULT 'video',
  `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
  `video_url` VARCHAR(255) NULL,
  `content_markdown` LONGTEXT NULL,
  `code_snippet` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. LESSON PROGRESS TABLE
CREATE TABLE `lesson_progress` (
  `progress_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `watched_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
  `completed_at` TIMESTAMP NULL,
  UNIQUE KEY `uq_user_lesson` (`user_id`, `lesson_id`),
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. ASSIGNMENTS TABLE
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

-- 7. ASSIGNMENT SUBMISSIONS TABLE (Populated dynamically via file uploads)
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

-- 8. QUIZ QUESTIONS & ATTEMPTS
CREATE TABLE `quiz_questions` (
  `question_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `lesson_id` INT UNSIGNED NOT NULL,
  `question_text` TEXT NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_option` ENUM('A', 'B', 'C', 'D') NOT NULL,
  `explanation` TEXT NULL,
  CONSTRAINT `fk_quiz_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `quiz_attempts` (
  `attempt_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `score_percentage` DECIMAL(5,2) NOT NULL,
  `correct_count` INT UNSIGNED NOT NULL,
  `total_questions` INT UNSIGNED NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempts_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. LESSON NOTES
CREATE TABLE `lesson_notes` (
  `note_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `note_content` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_lesson_note` (`user_id`, `lesson_id`),
  CONSTRAINT `fk_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notes_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`lesson_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. FORUM THREADS & REPLIES
CREATE TABLE `forum_threads` (
  `thread_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_threads_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_threads_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `forum_replies` (
  `reply_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `thread_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `reply_body` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_replies_thread` FOREIGN KEY (`thread_id`) REFERENCES `forum_threads`(`thread_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. CALENDAR EVENTS & NOTIFICATIONS
CREATE TABLE `calendar_events` (
  `event_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NULL,
  `title` VARCHAR(180) NOT NULL,
  `event_date` DATE NOT NULL,
  `start_time` TIME NULL,
  `event_type` ENUM('lecture', 'deadline', 'exam', 'study_block') NOT NULL DEFAULT 'study_block',
  CONSTRAINT `fk_events_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `notifications` (
  `notification_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. SAMPLE SEED DATA (No hardcoded dummy file paths)
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `student_matrix_id`, `department`, `avatar_initials`) VALUES
(1, 'Harith Hilman', 'harithhilman49@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'SW0108492', 'Software Engineering', 'HH'),
(2, 'Dr. Amina Rahman', 'amina.rahman@university.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', NULL, 'Department of Computing', 'AR'),
(3, 'Prof. Lim Wei Jie', 'weijie.lim@university.edu.my', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', NULL, 'Department of Software Systems', 'LW');

INSERT INTO `courses` (`course_id`, `course_code`, `title`, `description`, `instructor_id`, `credit_hours`, `category`) VALUES
(1, 'CSEB3213', 'Software Architecture & Design Patterns', 'Master SOLID principles, Gang of Four design patterns, microservices, and clean architecture.', 2, 3, 'Software Engineering'),
(2, 'CSEB3413', 'Full-Stack Web Engineering & REST APIs', 'Build scalable web applications using relational databases, RESTful APIs, authentication, and modern frontends.', 3, 4, 'Web Development'),
(3, 'CSNB3123', 'Database Systems & Query Optimization', 'Deep dive into relational normalization, ACID transactions, indexing strategies, and SQL performance tuning.', 2, 3, 'Data Engineering');

INSERT INTO `enrollments` (`user_id`, `course_id`, `final_letter_grade`) VALUES
(1, 1, 'A'),
(1, 2, 'A-'),
(1, 3, NULL);

INSERT INTO `lessons` (`lesson_id`, `course_id`, `module_order`, `title`, `lesson_type`, `duration_minutes`, `video_url`, `content_markdown`, `code_snippet`) VALUES
(1, 1, 1, 'SOLID Principles in Modern Software Engineering', 'video', 24, 'https://example.com/videos/solid-principles.mp4', '### Key Takeaways
- **Single Responsibility Principle**: A class should have one reason to change.
- **Open/Closed Principle**: Open for extension, closed for modification.', 'interface PaymentProcessor {
  process(amount: number): boolean;
}'),
(2, 1, 2, 'Factory & Strategy Design Patterns', 'reading', 18, NULL, '### Strategy Pattern
Defines a family of algorithms, encapsulates each one, and makes them interchangeable at runtime.', 'class Context {
  constructor(private strategy: Strategy) {}
  execute() { return this.strategy.doAlgorithm(); }
}'),
(3, 1, 3, 'Module 1 Architecture Checkpoint Quiz', 'quiz', 15, NULL, 'Complete this 3-question checkpoint quiz to test your understanding of SOLID and Design Patterns.', NULL),
(4, 2, 1, 'RESTful API Design & HTTP Status Semantics', 'video', 28, 'https://example.com/videos/rest-api.mp4', '### REST Best Practices
Use plural nouns for resources (`/api/v1/courses`) and proper HTTP status codes (`200`, `201`, `400`, `401`, `404`).', 'GET /api/v1/courses/1/lessons HTTP/1.1
Authorization: Bearer <jwt_token>'),
(5, 3, 1, 'B-Tree Indexing & Query Execution Plans', 'reading', 20, NULL, '### EXPLAIN ANALYZE
Always index foreign keys and columns frequently used in `WHERE`, `JOIN`, and `ORDER BY` clauses.', 'CREATE INDEX idx_submissions_status ON assignment_submissions(status);');

INSERT INTO `lesson_progress` (`user_id`, `lesson_id`, `is_completed`, `watched_seconds`, `completed_at`) VALUES
(1, 1, 1, 1440, NOW()),
(1, 2, 1, 1080, NOW()),
(1, 4, 1, 1680, NOW());

INSERT INTO `quiz_questions` (`lesson_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `explanation`) VALUES
(3, 'Which SOLID principle states that subtypes must be substitutable for their base types?', 'Single Responsibility Principle', 'Open/Closed Principle', 'Liskov Substitution Principle', 'Dependency Inversion Principle', 'C', 'Liskov Substitution Principle (LSP) ensures derived classes extend base classes without changing expected behavior.'),
(3, 'Which design pattern encapsulates interchangeable algorithms at runtime?', 'Singleton Pattern', 'Strategy Pattern', 'Adapter Pattern', 'Observer Pattern', 'B', 'The Strategy Pattern lets you select an algorithm implementation dynamically at runtime.'),
(3, 'What HTTP status code should be returned when a new resource is successfully created via POST?', '200 OK', '201 Created', '204 No Content', '400 Bad Request', 'B', '201 Created indicates the request succeeded and led to the creation of a resource.');

INSERT INTO `assignments` (`assignment_id`, `course_id`, `title`, `instructions`, `due_date`, `max_score`, `weight_percentage`) VALUES
(1, 1, 'Lab 1: Refactoring Legacy Code with Strategy Pattern', 'Refactor the provided monolithic billing class into clean Strategy pattern interfaces and submit a ZIP or PDF report.', '2026-11-22 23:59:00', 100.00, 20.00),
(2, 2, 'Project Milestone 1: REST API & MySQL Schema', 'Design a normalized MySQL database schema and implement CRUD endpoints with JWT authentication.', '2026-11-28 23:59:00', 100.00, 25.00),
(3, 3, 'Assignment 1: Query Optimization Benchmark', 'Optimize 5 slow SQL queries using composite indexes and attach EXPLAIN execution screenshots.', '2026-11-12 23:59:00', 100.00, 15.00);

INSERT INTO `forum_threads` (`thread_id`, `course_id`, `lesson_id`, `user_id`, `title`, `body`) VALUES
(1, 1, 2, 1, 'When should we prefer Composition over Inheritance?', 'In Lab 1, is it better to inject the TaxCalculator interface into the Invoice class rather than extending a base Invoice class?');

INSERT INTO `forum_replies` (`thread_id`, `user_id`, `reply_body`) VALUES
(1, 2, 'Great question Harith! Prefer composition (injecting TaxCalculator) whenever behaviors vary independently—it avoids rigid class hierarchies.');

INSERT INTO `calendar_events` (`user_id`, `course_id`, `title`, `event_date`, `start_time`, `event_type`) VALUES
(1, 1, 'Lab 1 Submission Deadline', '2026-11-22', '23:59:00', 'deadline'),
(1, 2, 'Project Milestone 1 Due', '2026-11-28', '23:59:00', 'deadline'),
(1, 1, 'Architecture Revision Session', '2026-11-18', '20:00:00', 'study_block');

INSERT INTO `notifications` (`user_id`, `title`, `message`, `is_read`) VALUES
(1, 'Upcoming Deadline', 'Lab 1: Refactoring Legacy Code is due on Nov 22 at 11:59 PM.', 0);
