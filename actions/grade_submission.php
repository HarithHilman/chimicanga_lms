<?php
require_once __DIR__ . '/../includes/auth_guard.php';
requireRole(['lecturer']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionId = (int) ($_POST['submission_id'] ?? 0);
    $courseId     = (int) ($_POST['course_id'] ?? 0);
    $score        = (float) ($_POST['score'] ?? 0);
    $feedback     = trim($_POST['instructor_feedback'] ?? '');
    $lecturerId   = (int) $currentUser['user_id'];

    // Verify lecturer is assigned to this course
    $authStmt = $pdo->prepare("SELECT COUNT(*) FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
    $authStmt->execute([$courseId, $lecturerId]);
    if ((int)$authStmt->fetchColumn() === 0) {
        setFlash('error', 'Access denied: You are not assigned to this course.');
        header('Location: ../index.php');
        exit;
    }

    // Fetch submission details for validation and student notification
    $subStmt = $pdo->prepare("
        SELECT s.user_id AS student_id, a.title AS assignment_title, a.max_score, c.course_code
        FROM assignment_submissions s
        JOIN assignments a ON s.assignment_id = a.assignment_id
        JOIN courses c ON a.course_id = c.course_id
        WHERE s.submission_id = ? AND a.course_id = ?
    ");
    $subStmt->execute([$submissionId, $courseId]);
    $sub = $subStmt->fetch();

    if ($sub) {
        $clampedScore = max(0, min((float)$sub['max_score'], $score));

        $stmt = $pdo->prepare("
            UPDATE assignment_submissions
            SET score = ?, instructor_feedback = ?, status = 'graded', graded_at = NOW()
            WHERE submission_id = ?
        ");
        $stmt->execute([$clampedScore, $feedback, $submissionId]);

        // Notify student if notifications are enabled
        if (getSetting($pdo, 'enable_notifications', '1') === '1') {
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $notifStmt->execute([
                (int)$sub['student_id'],
                "Grade Released: {$sub['course_code']}",
                "Your submission for '{$sub['assignment_title']}' was graded: {$clampedScore} / {$sub['max_score']}."
            ]);
        }

        logActivity($pdo, $lecturerId, 'Graded Submission', "Graded submission #$submissionId ({$clampedScore}/{$sub['max_score']}) in {$sub['course_code']}");
        setFlash('success', 'Submission graded and student notified.');
    } else {
        setFlash('error', 'Submission not found.');
    }

    header("Location: ../course.php?id=$courseId");
    exit;
}

header('Location: ../index.php');
exit;