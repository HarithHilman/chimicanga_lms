<?php
require_once __DIR__ . '/../config/db.php';
if (!empty($_SESSION['user_id'])) {
    logActivity($pdo, (int)$_SESSION['user_id'], 'User Logout', 'User signed out.');
}
session_destroy();
header('Location: ../login.php');
exit;