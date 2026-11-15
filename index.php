<?php
session_start();
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: login.php');
}
exit;
