<?php
require_once __DIR__ . '/../includes/auth_guard.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role        = in_array($_POST['role'] ?? '', ['student', 'lecturer', 'admin'], true) ? $_POST['role'] : 'student';
    $redirectMap = ['student' => '../admin/students.php', 'lecturer' => '../admin/lecturers.php', 'admin' => '../admin/admins.php'];
    $redirect    = $redirectMap[$role];

    try {
        // Syntactical & Buffer Overflow Length Bounds Validation
        $staffId = validateStaffStudentIdSyntax($_POST['staff_student_id'] ?? '');
        $name    = validateBoundedString($_POST['full_name'] ?? '', 'Full Name', 2, 120);
        if (!preg_match("/^[a-zA-Z\s.'@-]{2,120}$/", $name)) {
            throw new InvalidArgumentException('Full name contains invalid characters.');
        }
        $email   = validateEmailSyntax($_POST['email'] ?? '');
        $dept    = validateBoundedString($_POST['department'] ?? '', 'Department', 0, 100);
        $phone   = validatePhoneSyntax($_POST['phone'] ?? '');
        $minLen  = max(8, (int) getSetting($pdo, 'min_password_length', '8'));
        $rawPass = validateBoundedString($_POST['password'] ?? '', 'Password', $minLen, 128);

        // Semantic Validation: Check duplicate Staff/Student ID or Email
        $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR staff_student_id = ?");
        $dupStmt->execute([$email, $staffId]);
        if ((int)$dupStmt->fetchColumn() > 0) {
            throw new InvalidArgumentException('A user with this Email or Staff/Student ID already exists.');
        }

        $hash = password_hash($rawPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (staff_student_id, full_name, email, password_hash, role, department, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$staffId, $name, $email, $hash, $role, $dept, $phone]);

        logActivity($pdo, (int)$currentAdmin['user_id'], 'Created ' . ucfirst($role), "Created $role account: $name ($staffId)");
        setFlash('success', ucfirst($role) . " account created successfully.");

    } catch (InvalidArgumentException | OverflowException $e) {
        setFlash('error', $e->getMessage());
    }

    header("Location: $redirect");
    exit;
}