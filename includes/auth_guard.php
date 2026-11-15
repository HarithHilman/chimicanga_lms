<?php
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin' AND status = 'active'");
$stmt->execute([$_SESSION['admin_id']]);
$currentAdmin = $stmt->fetch();

if (!$currentAdmin) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

$lmsName = getSetting($pdo, 'lms_name', 'Chimicanga LMS');
