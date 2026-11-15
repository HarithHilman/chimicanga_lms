<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser['role'] === 'instructor') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['lesson_type'] ?? 'reading';
    $duration = (int) ($_POST['duration_minutes'] ?? 15);
    $content = trim($_POST['content_markdown'] ?? '');

    if ($courseId > 0 && $title !== '') {
        $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(module_order), 0) + 1 FROM lessons WHERE course_id = ?");
        $orderStmt->execute([$courseId]);
        $nextOrder = (int) $orderStmt->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO lessons (course_id, module_order, title, lesson_type, duration_minutes, content_markdown) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$courseId, $nextOrder, $title, $type, $duration, $content]);
        setFlash('success', 'New lesson added to syllabus.');
    }
    header("Location: ../course.php?id=$courseId");
    exit;
}
header('Location: ../index.php');
exit;
