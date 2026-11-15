# Chimicanga LMS — Admin Module (Modular PHP + MySQL)

## Folder Structure
- `database.sql` — MySQL schema & seed data (`chimicanga_lms`)
- `config/db.php` — PDO connection, flash messages, activity logger, system settings helper
- `includes/` — RBAC auth guard (`auth_guard.php`) and shared layout (`header.php`, `footer.php`)
- `admin/` — Separated Admin module views (`dashboard.php`, `students.php`, `lecturers.php`, `admins.php`, `courses.php`, `course_details.php`, `categories.php`, `enrolments.php`, `announcements.php`, `settings.php`, `profile.php`)
- `actions/` — Separated backend form handlers (`user_create.php`, `user_update.php`, `user_delete.php`, `course_create.php`, `enrolment_manage.php`, etc.)

## Setup
1. Import `database.sql` in phpMyAdmin (`http://localhost/phpmyadmin`) to create `chimicanga_lms`.
2. Place `chimicanga_lms/` inside `htdocs/` (XAMPP) or `www/` (Laragon).
3. Visit `http://localhost/chimicanga_lms/login.php`.
4. Default login: `admin@chimicanga.edu` / `admin123`.
