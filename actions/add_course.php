<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser['role'] === 'instructor') {
    $code = trim($_POST['course_code'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $credits = (int) ($_POST['credit_hours'] ?? 3);
    $category = trim($_POST['category'] ?? 'Software Engineering');

    if ($code !== '' && $title !== '') {
        $stmt = $pdo->prepare("INSERT INTO courses (course_code, title, description, instructor_id, credit_hours, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $title, $desc, $currentUser['user_id'], $credits, $category]);
        setFlash('success', "Course '$code - $title' created.");
    } else {
        setFlash('error', 'Course code and title are required.');
    }
}
header('Location: ../index.php');
exit;
