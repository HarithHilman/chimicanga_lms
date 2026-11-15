<?php
require_once __DIR__ . '/../includes/auth_guard.php';
requireRole(['student']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
    $courseId     = (int) ($_POST['course_id'] ?? 0);
    $notes        = trim($_POST['student_notes'] ?? '');
    $studentId    = (int) $currentUser['user_id'];

    // Verify student is enrolled in this course
    $enrolStmt = $pdo->prepare("SELECT COUNT(*) FROM enrolments WHERE course_id = ? AND student_id = ? AND status = 'enrolled'");
    $enrolStmt->execute([$courseId, $studentId]);
    if ((int)$enrolStmt->fetchColumn() === 0) {
        setFlash('error', 'Access denied: You are not enrolled in this course.');
        header('Location: ../index.php');
        exit;
    }

    if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] === UPLOAD_ERR_NO_FILE) {
        setFlash('error', 'Please select a file to upload.');
        header("Location: ../course.php?id=$courseId");
        exit;
    }

    if ($_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'Upload error code: ' . $_FILES['submission_file']['error'] . '. File may exceed server limit.');
        header("Location: ../course.php?id=$courseId");
        exit;
    }

    $allowedRaw = getSetting($pdo, 'allowed_file_types', 'pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png');
    $allowedExt = array_filter(array_map('trim', explode(',', strtolower($allowedRaw))));
    $maxMb      = max(1, (int) getSetting($pdo, 'max_upload_mb', '10'));

    $originalName = basename($_FILES['submission_file']['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        setFlash('error', "File type '.$ext' is not allowed. Allowed: " . implode(', ', $allowedExt));
        header("Location: ../course.php?id=$courseId");
        exit;
    }

    if ($_FILES['submission_file']['size'] > $maxMb * 1024 * 1024) {
        setFlash('error', "File exceeds maximum allowed size of {$maxMb}MB.");
        header("Location: ../course.php?id=$courseId");
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // Remove older uploaded file if student is re-submitting
    $oldStmt = $pdo->prepare("SELECT file_path FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
    $oldStmt->execute([$assignmentId, $studentId]);
    $oldFile = $oldStmt->fetchColumn();
    if ($oldFile) {
        $oldFullPath = __DIR__ . '/../' . ltrim($oldFile, '/\\');
        if (is_file($oldFullPath)) {
            unlink($oldFullPath);
        }
    }

    $safeName   = time() . '_' . $studentId . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
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
        $stmt->execute([$assignmentId, $studentId, $originalName, $dbPath, $notes]);

        logActivity($pdo, $studentId, 'Submitted Assignment', "Uploaded '$originalName' for assignment #$assignmentId");
        setFlash('success', "Submitted '$originalName' successfully.");
    } else {
        setFlash('error', 'Failed to move uploaded file on server. Check folder permissions on uploads/.');
    }

    header("Location: ../course.php?id=$courseId");
    exit;
}

header('Location: ../index.php');
exit;