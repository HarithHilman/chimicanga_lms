<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $annId  = (int) ($_POST['announcement_id'] ?? 0);
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    $target = $_POST['target_role'] ?? 'all';
    $status = $_POST['status'] ?? 'draft';

    if ($title === '' || $body === '') {
        setFlash('error', 'Announcement title and content are required.');
        header('Location: ../admin/announcements.php');
        exit;
    }

    $pubAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

    if ($annId > 0) {
        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, body = ?, target_role = ?, status = ?, published_at = COALESCE(published_at, ?) WHERE announcement_id = ?");
        $stmt->execute([$title, $body, $target, $status, $pubAt, $annId]);
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Updated Announcement', "Updated announcement: $title");
        setFlash('success', 'Announcement updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (created_by, title, body, target_role, status, published_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$currentAdmin['user_id'], $title, $body, $target, $status, $pubAt]);
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Created Announcement', "Created announcement: $title ($status)");
        setFlash('success', 'Announcement saved.');
    }
    header('Location: ../admin/announcements.php');
    exit;
}
