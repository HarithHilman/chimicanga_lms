<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $status   = $_POST['status'] ?? 'draft';

    if (in_array($status, ['draft', 'published', 'archived'], true)) {
        $stmt = $pdo->prepare("UPDATE courses SET status = ? WHERE course_id = ?");
        $stmt->execute([$status, $courseId]);
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Course Status Changed', "Course ID #$courseId set to $status");
        setFlash('success', "Course status updated to " . ucfirst($status) . ".");
    }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../admin/courses.php'));
    exit;
}
