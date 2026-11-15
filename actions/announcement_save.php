<?php
require_once __DIR__ . '/../includes/auth_guard.php';
requireRole(['admin']);

function broadcastAnnouncementNotification(PDO $pdo, string $title, string $targetRole): void {
    if (getSetting($pdo, 'enable_notifications', '1') !== '1') {
        return;
    }
    if ($targetRole === 'all') {
        $users = $pdo->query("SELECT user_id FROM users WHERE status = 'active' AND role IN ('student', 'lecturer')")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE status = 'active' AND role = ?");
        $stmt->execute([$targetRole]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    foreach ($users as $uid) {
        $notifStmt->execute([(int)$uid, 'New Announcement', $title]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $annId  = (int) ($_POST['announcement_id'] ?? 0);
    $title  = trim($_POST['title'] ?? '');
    $body   = trim($_POST['body'] ?? '');
    $target = in_array($_POST['target_role'] ?? '', ['all', 'student', 'lecturer'], true) ? $_POST['target_role'] : 'all';
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';

    if ($title === '' || $body === '') {
        setFlash('error', 'Announcement title and content are required.');
        header('Location: ../admin/announcements.php');
        exit;
    }

    $pubAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

    if ($annId > 0) {
        $oldStmt = $pdo->prepare("SELECT status FROM announcements WHERE announcement_id = ?");
        $oldStmt->execute([$annId]);
        $prevStatus = $oldStmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, body = ?, target_role = ?, status = ?, published_at = COALESCE(published_at, ?) WHERE announcement_id = ?");
        $stmt->execute([$title, $body, $target, $status, $pubAt, $annId]);

        if ($status === 'published' && $prevStatus !== 'published') {
            broadcastAnnouncementNotification($pdo, $title, $target);
        }

        logActivity($pdo, (int)$currentAdmin['user_id'], 'Updated Announcement', "Updated announcement: $title");
        setFlash('success', 'Announcement updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO announcements (created_by, title, body, target_role, status, published_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$currentAdmin['user_id'], $title, $body, $target, $status, $pubAt]);

        if ($status === 'published') {
            broadcastAnnouncementNotification($pdo, $title, $target);
        }

        logActivity($pdo, (int)$currentAdmin['user_id'], 'Created Announcement', "Created announcement: $title ($status)");
        setFlash('success', 'Announcement saved.');
    }

    header('Location: ../admin/announcements.php');
    exit;
}