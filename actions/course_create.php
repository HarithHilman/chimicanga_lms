<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code     = strtoupper(trim($_POST['course_code'] ?? ''));
    $title    = trim($_POST['title'] ?? '');
    $catId    = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $credits  = (int) ($_POST['credit_hours'] ?? 3);
    $status   = $_POST['status'] ?? 'draft';
    $desc     = trim($_POST['description'] ?? '');

    if ($code === '' || $title === '') {
        setFlash('error', 'Course code and title are required.');
        header('Location: ../admin/courses.php');
        exit;
    }

    $check = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_code = ?");
    $check->execute([$code]);
    if ($check->fetchColumn() > 0) {
        setFlash('error', "Course code '$code' already exists.");
        header('Location: ../admin/courses.php');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO courses (category_id, course_code, title, description, credit_hours, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$catId, $code, $title, $desc, $credits, $status]);

    logActivity($pdo, (int)$currentAdmin['user_id'], 'Created Course', "Created course $code - $title");
    setFlash('success', "Course '$code' created.");
    header('Location: ../admin/courses.php');
    exit;
}
