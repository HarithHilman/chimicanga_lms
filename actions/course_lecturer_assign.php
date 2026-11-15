<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId   = (int) ($_POST['course_id'] ?? 0);
    $lecturerId = (int) ($_POST['lecturer_id'] ?? 0);
    $action     = $_POST['action'] ?? 'assign';

    if ($courseId > 0 && $lecturerId > 0) {
        if ($action === 'remove') {
            $stmt = $pdo->prepare("DELETE FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
            $stmt->execute([$courseId, $lecturerId]);
            logActivity($pdo, (int)$currentAdmin['user_id'], 'Removed Lecturer', "Removed lecturer #$lecturerId from course #$courseId");
            setFlash('success', 'Lecturer removed from course.');
        } else {
            $stmt = $pdo->prepare("INSERT IGNORE INTO course_lecturers (course_id, lecturer_id) VALUES (?, ?)");
            $stmt->execute([$courseId, $lecturerId]);
            logActivity($pdo, (int)$currentAdmin['user_id'], 'Assigned Lecturer', "Assigned lecturer #$lecturerId to course #$courseId");
            setFlash('success', 'Lecturer assigned to course.');
        }
    }
    header("Location: ../admin/course_details.php?id=$courseId");
    exit;
}
