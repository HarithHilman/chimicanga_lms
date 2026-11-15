<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $annId = (int) ($_POST['announcement_id'] ?? 0);
    $act   = $_POST['action'] ?? '';

    if ($act === 'publish') {
        $pdo->prepare("UPDATE announcements SET status = 'published', published_at = NOW() WHERE announcement_id = ?")->execute([$annId]);
        setFlash('success', 'Announcement published.');
    } elseif ($act === 'unpublish') {
        $pdo->prepare("UPDATE announcements SET status = 'draft' WHERE announcement_id = ?")->execute([$annId]);
        setFlash('success', 'Announcement unpublished.');
    } elseif ($act === 'delete') {
        $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?")->execute([$annId]);
        setFlash('success', 'Announcement deleted.');
    }
    header('Location: ../admin/announcements.php');
    exit;
}
