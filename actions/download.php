<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$type = $_GET['type'] ?? 'submission';
$id   = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    die('Invalid file ID.');
}

$uid  = (int) $currentUser['user_id'];
$role = $currentUser['role'];

$fileName     = null;
$filePath     = null;
$courseId     = 0;
$isAuthorized = false;

if ($type === 'lesson') {
    $stmt = $pdo->prepare("SELECT course_id, file_name, file_path FROM lessons WHERE lesson_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || empty($row['file_path'])) {
        http_response_code(404);
        die('Lesson material not found.');
    }
    $courseId = (int) $row['course_id'];
    $fileName = $row['file_name'];
    $filePath = $row['file_path'];

    if ($role === 'admin') {
        $isAuthorized = true;
    } elseif ($role === 'lecturer') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
        $chk->execute([$courseId, $uid]);
        $isAuthorized = ((int)$chk->fetchColumn() > 0);
    } elseif ($role === 'student') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND student_id = ? AND status = 'enrolled'");
        $chk->execute([$courseId, $uid]);
        $isAuthorized = ((int)$chk->fetchColumn() > 0);
    }

} elseif ($type === 'assignment') {
    $stmt = $pdo->prepare("SELECT course_id, file_name, file_path FROM assignments WHERE assignment_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row || empty($row['file_path'])) {
        http_response_code(404);
        die('Assignment attachment not found.');
    }
    $courseId = (int) $row['course_id'];
    $fileName = $row['file_name'];
    $filePath = $row['file_path'];

    if ($role === 'admin') {
        $isAuthorized = true;
    } elseif ($role === 'lecturer') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
        $chk->execute([$courseId, $uid]);
        $isAuthorized = ((int)$chk->fetchColumn() > 0);
    } elseif ($role === 'student') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND student_id = ? AND status = 'enrolled'");
        $chk->execute([$courseId, $uid]);
        $isAuthorized = ((int)$chk->fetchColumn() > 0);
    }

} else {
    // Default: student assignment submission
    $stmt = $pdo->prepare("
        SELECT s.file_name, s.file_path, s.user_id AS student_id, a.course_id
        FROM assignment_submissions s
        JOIN assignments a ON s.assignment_id = a.assignment_id
        WHERE s.submission_id = ?
    ");
    $stmt->execute([$id]);
    $sub = $stmt->fetch();

    if (!$sub) {
        http_response_code(404);
        die('Submission record not found.');
    }
    $fileName = $sub['file_name'];
    $filePath = $sub['file_path'];

    if ($role === 'admin') {
        $isAuthorized = true;
    } elseif ($role === 'student' && (int)$sub['student_id'] === $uid) {
        $isAuthorized = true;
    } elseif ($role === 'lecturer') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
        $chk->execute([(int)$sub['course_id'], $uid]);
        $isAuthorized = ((int)$chk->fetchColumn() > 0);
    }
}

if (!$isAuthorized) {
    http_response_code(403);
    die('Access denied: You do not have permission to download this file.');
}

$uploadsRoot = realpath(__DIR__ . '/../uploads');
$fullPath    = realpath(__DIR__ . '/../' . ltrim($filePath, '/\\'));

if (!$fullPath || !$uploadsRoot || strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    die('File not found on server.');
}

$mimeType = function_exists('mime_content_type') ? (mime_content_type($fullPath) ?: 'application/octet-stream') : 'application/octet-stream';

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: no-cache, must-revalidate');
readfile($fullPath);
exit;