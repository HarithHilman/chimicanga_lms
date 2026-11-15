<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser['role'] === 'student') {
    $lessonId = (int) ($_POST['lesson_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);

    $stmt = $pdo->prepare("
        INSERT INTO lesson_progress (user_id, lesson_id, is_completed, completed_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            completed_at = IF(is_completed = 0, NOW(), NULL),
            is_completed = IF(is_completed = 1, 0, 1)
    ");
    $stmt->execute([$currentUser['user_id'], $lessonId]);
    setFlash('success', 'Lesson progress updated.');
    header("Location: ../course.php?id=$courseId&lesson=$lessonId");
    exit;
}
header('Location: ../index.php');
exit;
