<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'enrol';
    $redirect = $_POST['redirect'] ?? '../admin/enrolments.php';

    if ($mode === 'enrol') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $studentIds = $_POST['student_ids'] ?? [];

        if ($courseId <= 0 || empty($studentIds)) {
            setFlash('error', 'Select a course and at least one student.');
            header("Location: $redirect");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO enrolments (course_id, student_id, status) VALUES (?, ?, 'enrolled') ON DUPLICATE KEY UPDATE status = 'enrolled'");
        $count = 0;
        foreach ($studentIds as $sid) {
            $stmt->execute([$courseId, (int)$sid]);
            $count++;
        }
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Enrolled Students', "Enrolled $count student(s) into course #$courseId");
        setFlash('success', "$count student(s) enrolled successfully.");
    } elseif ($mode === 'status') {
        $enrolmentId = (int) ($_POST['enrolment_id'] ?? 0);
        $status = $_POST['status'] ?? 'enrolled';
        if (in_array($status, ['enrolled', 'rejected'], true)) {
            $stmt = $pdo->prepare("UPDATE enrolments SET status = ? WHERE enrolment_id = ?");
            $stmt->execute([$status, $enrolmentId]);
            logActivity($pdo, (int)$currentAdmin['user_id'], 'Enrolment Status', "Set enrolment #$enrolmentId to $status");
            setFlash('success', "Enrolment marked as $status.");
        }
    } elseif ($mode === 'remove') {
        $enrolmentId = (int) ($_POST['enrolment_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM enrolments WHERE enrolment_id = ?");
        $stmt->execute([$enrolmentId]);
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Removed Enrolment', "Removed enrolment #$enrolmentId");
        setFlash('success', 'Student removed from course.');
    }

    header("Location: $redirect");
    exit;
}
