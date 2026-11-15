<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser['role'] === 'instructor') {
    $submissionId = (int) ($_POST['submission_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $score = (float) ($_POST['score'] ?? 0);
    $feedback = trim($_POST['instructor_feedback'] ?? '');

    $stmt = $pdo->prepare("UPDATE assignment_submissions SET score = ?, instructor_feedback = ?, status = 'graded', graded_at = NOW() WHERE submission_id = ?");
    $stmt->execute([$score, $feedback, $submissionId]);
    setFlash('success', 'Submission graded and student notified.');
    header("Location: ../course.php?id=$courseId");
    exit;
}
header('Location: ../index.php');
exit;
