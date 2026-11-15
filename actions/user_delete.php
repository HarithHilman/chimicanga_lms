<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId   = (int) ($_POST['user_id'] ?? 0);
    $redirect = $_POST['redirect'] ?? '../admin/students.php';

    if ($userId === (int)$currentAdmin['user_id']) {
        setFlash('error', 'You cannot delete your own active admin account.');
        header("Location: $redirect");
        exit;
    }

    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();

    if ($target) {
        if ($target['role'] === 'admin') {
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($adminCount <= 1) {
                setFlash('error', 'Cannot delete the last remaining administrator account.');
                header("Location: $redirect");
                exit;
            }
        }

        $del = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $del->execute([$userId]);

        logActivity($pdo, (int)$currentAdmin['user_id'], 'Deleted User', "Deleted {$target['role']}: {$target['full_name']}");
        setFlash('success', 'Account deleted permanently.');
    }
    header("Location: $redirect");
    exit;
}
