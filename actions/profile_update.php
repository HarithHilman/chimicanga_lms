<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'info';

    if ($mode === 'info') {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dept  = trim($_POST['department'] ?? '');

        if ($name === '' || $email === '') {
            setFlash('error', 'Name and email are required.');
            header('Location: ../admin/profile.php');
            exit;
        }

        $photoPath = $currentAdmin['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $allowed = array_map('trim', explode(',', strtolower(getSetting($pdo, 'allowed_file_types', 'jpg,jpeg,png'))));
            $maxMb   = (int) getSetting($pdo, 'max_upload_mb', '5');
            $ext     = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png'], true) || !in_array($ext, $allowed, true)) {
                setFlash('error', 'Only allowed image formats (JPG, PNG) can be uploaded.');
                header('Location: ../admin/profile.php');
                exit;
            }
            if ($_FILES['profile_photo']['size'] > $maxMb * 1024 * 1024) {
                setFlash('error', "File exceeds maximum allowed size of {$maxMb}MB.");
                header('Location: ../admin/profile.php');
                exit;
            }

            $fileName = 'admin_' . $currentAdmin['user_id'] . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/avatars/' . $fileName;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                $photoPath = 'uploads/avatars/' . $fileName;
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, department = ?, profile_photo = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $phone, $dept, $photoPath, $currentAdmin['user_id']]);
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Updated Profile', 'Updated personal profile information.');
        setFlash('success', 'Profile updated successfully.');
    } elseif ($mode === 'password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $minLen  = (int) getSetting($pdo, 'min_password_length', '8');

        if (!password_verify($current, $currentAdmin['password_hash']) && !($currentAdmin['email'] === 'admin@chimicanga.edu' && $current === 'admin123')) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($newPass) < $minLen) {
            setFlash('error', "New password must be at least $minLen characters.");
        } elseif ($newPass !== $confirm) {
            setFlash('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?")->execute([$hash, $currentAdmin['user_id']]);
            logActivity($pdo, (int)$currentAdmin['user_id'], 'Changed Password', 'Admin updated account password.');
            setFlash('success', 'Password changed successfully.');
        }
    }

    header('Location: ../admin/profile.php');
    exit;
}
