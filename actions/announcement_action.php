<?php
require_once __DIR__ . '/../includes/auth_guard.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $annId = (int) ($_POST['announcement_id'] ?? 0);
    $act   = $_POST['action'] ?? '';

    if ($act === 'publish') {
        $stmt = $pdo->prepare("SELECT title, target_role, status FROM announcements WHERE announcement_id = ?");
        $stmt->execute([$annId]);
        $ann = $stmt->fetch();

        $pdo->prepare("UPDATE announcements SET status = 'published', published_at = NOW() WHERE announcement_id = ?")->execute([$annId]);

        if ($ann && $ann['status'] !== 'published' && getSetting($pdo, 'enable_notifications', '1') === '1') {
            if ($ann['target_role'] === 'all') {
                $users = $pdo->query("SELECT user_id FROM users WHERE status = 'active' AND role IN ('student', 'lecturer')")->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $uStmt = $pdo->prepare("SELECT user_id FROM users WHERE status = 'active' AND role = ?");
                $uStmt->execute([$ann['target_role']]);
                $users = $uStmt->fetchAll(PDO::FETCH_COLUMN);
            }
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            foreach ($users as $uid) {
                $notifStmt->execute([(int)$uid, 'New Announcement', $ann['title']]);
            }
        }
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