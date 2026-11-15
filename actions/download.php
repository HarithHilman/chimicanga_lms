<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$submissionId = (int) ($_GET['id'] ?? 0);
if ($submissionId <= 0) {
    http_response_code(400);
    die('Invalid submission ID.');
}

$stmt = $pdo->prepare("
    SELECT s.file_name, s.file_path, s.user_id AS student_id, a.course_id
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.assignment_id
    WHERE s.submission_id = ?
");
$stmt->execute([$submissionId]);
$sub = $stmt->fetch();

if (!$sub) {
    http_response_code(404);
    die('Submission record not found.');
}

// Role-based download authorization
$uid  = (int) $currentUser['user_id'];
$role = $currentUser['role'];
$isAuthorized = false;

if ($role === 'admin') {
    $isAuthorized = true;
} elseif ($role === 'student' && (int)$sub['student_id'] === $uid) {
    $isAuthorized = true;
} elseif ($role === 'lecturer') {
    $lecCheck = $pdo->prepare("SELECT COUNT(*) FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
    $lecCheck->execute([(int)$sub['course_id'], $uid]);
    $isAuthorized = ((int)$lecCheck->fetchColumn() > 0);
}

if (!$isAuthorized) {
    http_response_code(403);
    die('Access denied: You do not have permission to download this file.');
}

$fullPath = realpath(__DIR__ . '/../' . ltrim($sub['file_path'], '/'));
$uploadsRoot = realpath(__DIR__ . '/../uploads');

if (!$fullPath || !$uploadsRoot || strpos($fullPath, $uploadsRoot) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    die('File not found on server.');
}

$mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($sub['file_name']) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;