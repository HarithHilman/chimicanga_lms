<?php
require_once __DIR__ . '/../config/db.php';
if (!empty($_SESSION['admin_id'])) {
    logActivity($pdo, (int)$_SESSION['admin_id'], 'Admin Logout', 'Administrator signed out.');
}
session_destroy();
header('Location: ../login.php');
exit;
