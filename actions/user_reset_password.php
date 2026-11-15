<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId   = (int) ($_POST['user_id'] ?? 0);
    $newPass  = $_POST['new_password'] ?? '';
    $redirect = $_POST['redirect'] ?? '../admin/students.php';
    $minLen   = (int) getSetting($pdo, 'min_password_length', '8');

    if (strlen($newPass) < $minLen) {
        setFlash('error', "New password must be at least $minLen characters.");
        header("Location: $redirect");
        exit;
    }

    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE user_id = ?");
        $upd->execute([$hash, $userId]);

        logActivity($pdo, (int)$currentAdmin['user_id'], 'Password Reset', "Reset password for {$user['full_name']}");
        setFlash('success', "Password reset for {$user['full_name']}.");
    }
    header("Location: $redirect");
    exit;
}
