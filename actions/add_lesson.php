<?php
require_once __DIR__ . '/../includes/auth_guard.php';
requireRole(['lecturer']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId   = (int) ($_POST['course_id'] ?? 0);
    $actionType = $_POST['action_type'] ?? 'add_lesson';
    $lecturerId = (int) $currentUser['user_id'];

    // Verify this lecturer is actually assigned to this course
    $authStmt = $pdo->prepare("SELECT COUNT(*) FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
    $authStmt->execute([$courseId, $lecturerId]);
    if ((int)$authStmt->fetchColumn() === 0) {
        setFlash('error', 'Access denied: You are not assigned to this course.');
        header('Location: ../index.php');
        exit;
    }

    if ($actionType === 'add_lesson') {
        $title    = trim($_POST['title'] ?? '');
        $type     = in_array($_POST['lesson_type'] ?? '', ['video', 'reading', 'quiz'], true) ? $_POST['lesson_type'] : 'reading';
        $duration = max(1, (int) ($_POST['duration_minutes'] ?? 15));
        $content  = trim($_POST['content_markdown'] ?? '');
        $code     = trim($_POST['code_snippet'] ?? '');

        if ($title !== '') {
            $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(module_order), 0) + 1 FROM lessons WHERE course_id = ?");
            $orderStmt->execute([$courseId]);
            $nextOrder = (int) $orderStmt->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO lessons (course_id, module_order, title, lesson_type, duration_minutes, content_markdown, code_snippet) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$courseId, $nextOrder, $title, $type, $duration, $content, $code !== '' ? $code : null]);

            logActivity($pdo, $lecturerId, 'Added Lesson', "Added lesson '$title' to course #$courseId");
            setFlash('success', 'New lesson added to syllabus.');
        } else {
            setFlash('error', 'Lesson title is required.');
        }

    } elseif ($actionType === 'delete_lesson') {
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);
        $pdo->prepare("DELETE FROM lessons WHERE lesson_id = ? AND course_id = ?")->execute([$lessonId, $courseId]);
        logActivity($pdo, $lecturerId, 'Deleted Lesson', "Deleted lesson #$lessonId from course #$courseId");
        setFlash('success', 'Lesson removed from syllabus.');

    } elseif ($actionType === 'add_assignment') {
        $title        = trim($_POST['title'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $dueDateRaw   = trim($_POST['due_date'] ?? '');
        $maxScore     = max(1, (float) ($_POST['max_score'] ?? 100));
        $weight       = max(0, min(100, (float) ($_POST['weight_percentage'] ?? 15)));

        if ($title !== '' && $instructions !== '' && $dueDateRaw !== '') {
            $dueDate = date('Y-m-d H:i:s', strtotime($dueDateRaw));
            $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, instructions, due_date, max_score, weight_percentage) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$courseId, $title, $instructions, $dueDate, $maxScore, $weight]);

            logActivity($pdo, $lecturerId, 'Created Assignment', "Created assignment '$title' in course #$courseId");
            setFlash('success', "Assignment '$title' created.");
        } else {
            setFlash('error', 'Assignment title, instructions, and due date are required.');
        }

    } elseif ($actionType === 'delete_assignment') {
        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        $pdo->prepare("DELETE FROM assignments WHERE assignment_id = ? AND course_id = ?")->execute([$assignmentId, $courseId]);
        logActivity($pdo, $lecturerId, 'Deleted Assignment', "Deleted assignment #$assignmentId from course #$courseId");
        setFlash('success', 'Assignment deleted.');
    }

    header("Location: ../course.php?id=$courseId");
    exit;
}

header('Location: ../index.php');
exit;