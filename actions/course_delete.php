<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int) ($_POST['course_id'] ?? 0);

    $enrolCheck = $pdo->prepare("SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND status = 'enrolled'");
    $enrolCheck->execute([$courseId]);
    if ($enrolCheck->fetchColumn() > 0) {
        setFlash('error', 'Cannot delete course with active enrolled students. Archive it or remove students first.');
        header('Location: ../admin/courses.php');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$courseId]);
    logActivity($pdo, (int)$currentAdmin['user_id'], 'Deleted Course', "Deleted course ID #$courseId");
    setFlash('success', 'Course deleted.');
    header('Location: ../admin/courses.php');
    exit;
}
