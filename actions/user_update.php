<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId   = (int) ($_POST['user_id'] ?? 0);
    $idCode   = trim($_POST['staff_student_id'] ?? '');
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $dept     = trim($_POST['department'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $redirect = $_POST['redirect'] ?? '../admin/students.php';

    if ($userId <= 0 || $idCode === '' || $name === '' || $email === '') {
        setFlash('error', 'Please fill in all required fields.');
        header("Location: $redirect");
        exit;
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR staff_student_id = ?) AND user_id != ?");
    $check->execute([$email, $idCode, $userId]);
    if ($check->fetchColumn() > 0) {
        setFlash('error', 'Another user already uses that email or ID.');
        header("Location: $redirect");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET staff_student_id = ?, full_name = ?, email = ?, department = ?, phone = ? WHERE user_id = ?");
    $stmt->execute([$idCode, $name, $email, $dept, $phone, $userId]);

    logActivity($pdo, (int)$currentAdmin['user_id'], 'Updated User', "Updated details for $name ($idCode)");
    setFlash('success', 'User information updated.');
    header("Location: $redirect");
    exit;
}
