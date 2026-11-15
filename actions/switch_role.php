<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $_SESSION['user_id'] = (int) $_POST['user_id'];
    setFlash('success', 'Switched active user session.');
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index.php'));
exit;
