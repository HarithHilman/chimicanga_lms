<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $code     = strtoupper(trim($_POST['course_code'] ?? ''));
    $title    = trim($_POST['title'] ?? '');
    $catId    = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $credits  = (int) ($_POST['credit_hours'] ?? 3);
    $status   = $_POST['status'] ?? 'draft';
    $desc     = trim($_POST['description'] ?? '');

    if ($courseId <= 0 || $code === '' || $title === '') {
        setFlash('error', 'Course code and title are required.');
        header('Location: ../admin/courses.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE courses SET category_id = ?, course_code = ?, title = ?, description = ?, credit_hours = ?, status = ? WHERE course_id = ?");
    $stmt->execute([$catId, $code, $title, $desc, $credits, $status, $courseId]);

    logActivity($pdo, (int)$currentAdmin['user_id'], 'Updated Course', "Updated course $code ($status)");
    setFlash('success', 'Course updated successfully.');
    header('Location: ../admin/courses.php');
    exit;
}
