<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$uid         = (int) $currentUser['user_id'];
$role        = $currentUser['role'];
$redirectUrl = ($role === 'admin') ? '../admin/profile.php' : '../profile.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'info';

    if ($mode === 'info') {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $dept  = trim($_POST['department'] ?? '');

        if ($name === '' || $email === '') {
            setFlash('error', 'Name and email are required.');
            header("Location: $redirectUrl");
            exit;
        }

        // Prevent duplicate email collision with another user
        $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $dupStmt->execute([$email, $uid]);
        if ((int)$dupStmt->fetchColumn() > 0) {
            setFlash('error', 'Another account is already using that email address.');
            header("Location: $redirectUrl");
            exit;
        }

        $photoPath = $currentUser['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                setFlash('error', 'Error uploading profile picture.');
                header("Location: $redirectUrl");
                exit;
            }

            $allowed = array_map('trim', explode(',', strtolower(getSetting($pdo, 'allowed_file_types', 'jpg,jpeg,png'))));
            $maxMb   = max(1, (int) getSetting($pdo, 'max_upload_mb', '5'));
            $ext     = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['jpg', 'jpeg', 'png'], true) || !in_array($ext, $allowed, true)) {
                setFlash('error', 'Only allowed image formats (JPG, PNG) can be uploaded.');
                header("Location: $redirectUrl");
                exit;
            }
            if ($_FILES['profile_photo']['size'] > $maxMb * 1024 * 1024) {
                setFlash('error', "File exceeds maximum allowed size of {$maxMb}MB.");
                header("Location: $redirectUrl");
                exit;
            }

            $avatarDir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($avatarDir)) {
                mkdir($avatarDir, 0775, true);
            }

            // Delete previous avatar file if present
            if (!empty($photoPath)) {
                $oldAvatar = __DIR__ . '/../' . ltrim($photoPath, '/\\');
                if (is_file($oldAvatar)) {
                    unlink($oldAvatar);
                }
            }

            $fileName = $role . '_' . $uid . '_' . time() . '.' . $ext;
            $dest = $avatarDir . $fileName;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                $photoPath = 'uploads/avatars/' . $fileName;
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, department = ?, profile_photo = ? WHERE user_id = ?");
        $stmt->execute([$name, $email, $phone, $dept, $photoPath, $uid]);
        logActivity($pdo, $uid, 'Updated Profile', 'Updated personal profile information.');
        setFlash('success', 'Profile updated successfully.');

    } elseif ($mode === 'password') {
        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $minLen  = max(6, (int) getSetting($pdo, 'min_password_length', '8'));

        $demoEmails = ['admin@chimicanga.edu', 'amina@chimicanga.edu', 'daniyal@chimicanga.edu'];
        $validDemo  = in_array($currentUser['email'], $demoEmails, true) && $current === 'admin123';

        if (!password_verify($current, $currentUser['password_hash']) && !$validDemo) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($newPass) < $minLen) {
            setFlash('error', "New password must be at least $minLen characters.");
        } elseif ($newPass !== $confirm) {
            setFlash('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?")->execute([$hash, $uid]);
            logActivity($pdo, $uid, 'Changed Password', 'User updated account password.');
            setFlash('success', 'Password changed successfully.');
        }
    }

    header("Location: $redirectUrl");
    exit;
}

header("Location: $redirectUrl");
exit;