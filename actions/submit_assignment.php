<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUser['role'] === 'student') {
    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $notes = trim($_POST['student_notes'] ?? '');

    if (!empty($_FILES['submission_file']['name'])) {
        $allowedExt = ['pdf', 'doc', 'docx', 'zip', 'rar', 'txt', 'png', 'jpg', 'jpeg'];
        $originalName = basename($_FILES['submission_file']['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            setFlash('error', "File type '.$ext' is not allowed. Allowed: " . implode(', ', $allowedExt));
            header("Location: ../course.php?id=$courseId");
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        // Remove older uploaded file if student is re-submitting
        $oldStmt = $pdo->prepare("SELECT file_path FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
        $oldStmt->execute([$assignmentId, $currentUser['user_id']]);
        $oldFile = $oldStmt->fetchColumn();
        if ($oldFile) {
            $oldFullPath = __DIR__ . '/../' . ltrim($oldFile, '/');
            if (is_file($oldFullPath)) {
                unlink($oldFullPath);
            }
        }

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $targetPath = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $targetPath)) {
            $dbPath = 'uploads/' . $safeName;
            $stmt = $pdo->prepare("
                INSERT INTO assignment_submissions (assignment_id, user_id, file_name, file_path, student_notes, status, submitted_at)
                VALUES (?, ?, ?, ?, ?, 'submitted', NOW())
                ON DUPLICATE KEY UPDATE
                    file_name = VALUES(file_name),
                    file_path = VALUES(file_path),
                    student_notes = VALUES(student_notes),
                    status = 'submitted',
                    submitted_at = NOW()
            ");
            $stmt->execute([$assignmentId, $currentUser['user_id'], $originalName, $dbPath, $notes]);
            setFlash('success', "Submitted '$originalName' successfully.");
        } else {
            setFlash('error', 'Failed to upload file.');
        }
    }
    header("Location: ../course.php?id=$courseId");
    exit;
}
header('Location: ../index.php');
exit;
