<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $redirect = $_POST['redirect'] ?? '../admin/students.php';

    if (!in_array($role, ['student', 'lecturer', 'admin'], true)) {
        setFlash('error', 'Invalid user role.');
        header("Location: $redirect");
        exit;
    }

    $idCode = trim($_POST['staff_student_id'] ?? '');
    $name   = trim($_POST['full_name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $dept   = trim($_POST['department'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $minLen = (int) getSetting($pdo, 'min_password_length', '8');

    if ($idCode === '' || $name === '' || $email === '' || strlen($pass) < $minLen) {
        setFlash('error', "All required fields must be filled and password must be at least $minLen characters.");
        header("Location: $redirect");
        exit;
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR staff_student_id = ?");
    $check->execute([$email, $idCode]);
    if ($check->fetchColumn() > 0) {
        setFlash('error', 'Email or ID number already exists.');
        header("Location: $redirect");
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (staff_student_id, full_name, email, password_hash, role, department, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$idCode, $name, $email, $hash, $role, $dept, $phone]);

    logActivity($pdo, (int)$currentAdmin['user_id'], 'Created ' . ucfirst($role), "Created $role account: $name ($idCode)");
    setFlash('success', ucfirst($role) . " account created successfully.");
    header("Location: $redirect");
    exit;
}
