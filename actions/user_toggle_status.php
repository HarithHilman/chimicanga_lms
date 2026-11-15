<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId   = (int) ($_POST['user_id'] ?? 0);
    $redirect = $_POST['redirect'] ?? '../admin/students.php';

    if ($userId === (int)$currentAdmin['user_id']) {
        setFlash('error', 'You cannot deactivate your own admin account.');
        header("Location: $redirect");
        exit;
    }

    $stmt = $pdo->prepare("SELECT full_name, status FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();

    if ($target) {
        $newStatus = $target['status'] === 'active' ? 'inactive' : 'active';
        $upd = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $upd->execute([$newStatus, $userId]);

        logActivity($pdo, (int)$currentAdmin['user_id'], 'Changed User Status', "Set {$target['full_name']} to $newStatus");
        setFlash('success', "Account status changed to $newStatus.");
    }
    header("Location: $redirect");
    exit;
}
