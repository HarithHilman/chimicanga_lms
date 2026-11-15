<?php
require_once __DIR__ . '/../includes/auth_guard.php';
requireRole(['lecturer']);

function handleMaterialUpload(PDO $pdo, string $inputName, int $courseId): array {
    if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['file_name' => null, 'file_path' => null, 'error' => null];
    }

    if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return ['file_name' => null, 'file_path' => null, 'error' => 'File upload error code: ' . $_FILES[$inputName]['error']];
    }

    $allowedRaw = getSetting($pdo, 'allowed_file_types', 'pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png');
    $allowedExt = array_filter(array_map('trim', explode(',', strtolower($allowedRaw))));
    $maxMb      = max(1, (int) getSetting($pdo, 'max_upload_mb', '10'));

    $originalName = basename($_FILES[$inputName]['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        return ['file_name' => null, 'file_path' => null, 'error' => "File type '.$ext' is not allowed. Allowed: " . implode(', ', $allowedExt)];
    }

    if ($_FILES[$inputName]['size'] > $maxMb * 1024 * 1024) {
        return ['file_name' => null, 'file_path' => null, 'error' => "File exceeds maximum allowed size of {$maxMb}MB."];
    }

    $uploadDir = __DIR__ . '/../uploads/materials/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $safeName   = time() . '_c' . $courseId . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    $targetPath = $uploadDir . $safeName;

    if (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetPath)) {
        return ['file_name' => null, 'file_path' => null, 'error' => 'Failed to save uploaded material on server.'];
    }

    return ['file_name' => $originalName, 'file_path' => 'uploads/materials/' . $safeName, 'error' => null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId   = (int) ($_POST['course_id'] ?? 0);
    $actionType = $_POST['action_type'] ?? 'add_lesson';
    $lecturerId = (int) $currentUser['user_id'];

    // Verify lecturer is assigned to this course
    $authStmt = $pdo->prepare("SELECT COUNT(*) FROM course_lecturers WHERE course_id = ? AND lecturer_id = ?");
    $authStmt->execute([$courseId, $lecturerId]);
    if ((int)$authStmt->fetchColumn() === 0) {
        setFlash('error', 'Access denied: You are not assigned to this course.');
        header('Location: ../index.php');
        exit;
    }

    if ($actionType === 'add_lesson') {
        $title    = trim($_POST['title'] ?? '');
        $type     = in_array($_POST['lesson_type'] ?? '', ['video', 'reading', 'quiz'], true) ? $_POST['lesson_type'] : 'reading';
        $duration = max(1, (int) ($_POST['duration_minutes'] ?? 15));
        $content  = trim($_POST['content_markdown'] ?? '');
        $code     = trim($_POST['code_snippet'] ?? '');

        if ($title === '') {
            setFlash('error', 'Lesson title is required.');
            header("Location: ../course.php?id=$courseId");
            exit;
        }

        $upload = handleMaterialUpload($pdo, 'lesson_file', $courseId);
        if ($upload['error'] !== null) {
            setFlash('error', $upload['error']);
            header("Location: ../course.php?id=$courseId");
            exit;
        }

        $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(module_order), 0) + 1 FROM lessons WHERE course_id = ?");
        $orderStmt->execute([$courseId]);
        $nextOrder = (int) $orderStmt->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO lessons (course_id, module_order, title, lesson_type, duration_minutes, content_markdown, code_snippet, file_name, file_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $courseId,
            $nextOrder,
            $title,
            $type,
            $duration,
            $content,
            $code !== '' ? $code : null,
            $upload['file_name'],
            $upload['file_path']
        ]);

        logActivity($pdo, $lecturerId, 'Added Lesson', "Added lesson '$title' to course #$courseId");
        setFlash('success', 'New lesson added to syllabus.');

    } elseif ($actionType === 'delete_lesson') {
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);
        $fileStmt = $pdo->prepare("SELECT file_path FROM lessons WHERE lesson_id = ? AND course_id = ?");
        $fileStmt->execute([$lessonId, $courseId]);
        $filePath = $fileStmt->fetchColumn();
        if ($filePath) {
            $full = __DIR__ . '/../' . ltrim($filePath, '/\\');
            if (is_file($full)) {
                unlink($full);
            }
        }

        $pdo->prepare("DELETE FROM lessons WHERE lesson_id = ? AND course_id = ?")->execute([$lessonId, $courseId]);
        logActivity($pdo, $lecturerId, 'Deleted Lesson', "Deleted lesson #$lessonId from course #$courseId");
        setFlash('success', 'Lesson removed from syllabus.');

    } elseif ($actionType === 'add_assignment') {
        $title        = trim($_POST['title'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $dueDateRaw   = trim($_POST['due_date'] ?? '');
        $maxScore     = max(1, (float) ($_POST['max_score'] ?? 100));
        $weight       = max(0, min(100, (float) ($_POST['weight_percentage'] ?? 15)));

        if ($title === '' || $instructions === '' || $dueDateRaw === '') {
            setFlash('error', 'Assignment title, instructions, and due date are required.');
            header("Location: ../course.php?id=$courseId");
            exit;
        }

        $upload = handleMaterialUpload($pdo, 'assignment_file', $courseId);
        if ($upload['error'] !== null) {
            setFlash('error', $upload['error']);
            header("Location: ../course.php?id=$courseId");
            exit;
        }

        $dueDate = date('Y-m-d H:i:s', strtotime($dueDateRaw));
        $stmt = $pdo->prepare("
            INSERT INTO assignments (course_id, title, instructions, due_date, max_score, weight_percentage, file_name, file_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $courseId,
            $title,
            $instructions,
            $dueDate,
            $maxScore,
            $weight,
            $upload['file_name'],
            $upload['file_path']
        ]);

        logActivity($pdo, $lecturerId, 'Created Assignment', "Created assignment '$title' in course #$courseId");
        setFlash('success', "Assignment '$title' created.");

    } elseif ($actionType === 'delete_assignment') {
        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        $fileStmt = $pdo->prepare("SELECT file_path FROM assignments WHERE assignment_id = ? AND course_id = ?");
        $fileStmt->execute([$assignmentId, $courseId]);
        $filePath = $fileStmt->fetchColumn();
        if ($filePath) {
            $full = __DIR__ . '/../' . ltrim($filePath, '/\\');
            if (is_file($full)) {
                unlink($full);
            }
        }

        $pdo->prepare("DELETE FROM assignments WHERE assignment_id = ? AND course_id = ?")->execute([$assignmentId, $courseId]);
        logActivity($pdo, $lecturerId, 'Deleted Assignment', "Deleted assignment #$assignmentId from course #$courseId");
        setFlash('success', 'Assignment deleted.');
    }

    header("Location: ../course.php?id=$courseId");
    exit;
}

header('Location: ../index.php');
exit;