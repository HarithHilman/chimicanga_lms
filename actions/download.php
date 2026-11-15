<?php
require_once __DIR__ . '/../config/db.php';

$submissionId = (int) ($_GET['id'] ?? 0);
if ($submissionId <= 0) {
    http_response_code(400);
    die('Invalid submission ID.');
}

$stmt = $pdo->prepare("SELECT file_name, file_path FROM assignment_submissions WHERE submission_id = ?");
$stmt->execute([$submissionId]);
$sub = $stmt->fetch();

if (!$sub) {
    http_response_code(404);
    die('Submission record not found.');
}

$fullPath = realpath(__DIR__ . '/../' . ltrim($sub['file_path'], '/'));
$uploadsRoot = realpath(__DIR__ . '/../uploads');

// Ensure file exists and resides safely inside uploads/
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
