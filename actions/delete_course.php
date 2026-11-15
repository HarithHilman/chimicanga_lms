<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser['role'] === 'instructor') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ? AND instructor_id = ?");
    $stmt->execute([$courseId, $currentUser['user_id']]);
    setFlash('success', 'Course deleted successfully.');
}
header('Location: ../index.php');
exit;
