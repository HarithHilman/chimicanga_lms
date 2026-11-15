<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser['role'] === 'student') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $mode = $_POST['mode'] ?? 'enroll';

    if ($mode === 'unenroll') {
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$currentUser['user_id'], $courseId]);
        setFlash('success', 'Unenrolled from course.');
    } else {
        $stmt = $pdo->prepare("INSERT IGNORE INTO enrollments (user_id, course_id) VALUES (?, ?)");
        $stmt->execute([$currentUser['user_id'], $courseId]);
        setFlash('success', 'Successfully enrolled in course!');
    }
}
header('Location: ../index.php');
exit;
