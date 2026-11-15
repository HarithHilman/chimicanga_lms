<?php
require_once __DIR__ . '/../includes/auth_guard.php';

$uid         = (int) $currentUser['user_id'];
$role        = $currentUser['role'];
$redirectUrl = ($role === 'admin') ? '../admin/profile.php' : '../profile.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'info';

    try {
        if ($mode === 'info') {
            $name  = validateBoundedString($_POST['full_name'] ?? '', 'Full Name', 2, 120);
            $email = validateEmailSyntax($_POST['email'] ?? '');
            $phone = validatePhoneSyntax($_POST['phone'] ?? '');
            $dept  = validateBoundedString($_POST['department'] ?? '', 'Department', 0, 100);

            $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
            $dupStmt->execute([$email, $uid]);
            if ((int)$dupStmt->fetchColumn() > 0) {
                throw new InvalidArgumentException('Another account is already using that email address.');
            }

            $photoPath = $currentUser['profile_photo'];
            if (!empty($_FILES['profile_photo']['name'])) {
                if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException('Error uploading profile picture.');
                }

                $allowed = array_map('trim', explode(',', strtolower(getSetting($pdo, 'allowed_file_types', 'jpg,jpeg,png'))));
                $maxMb   = max(1, (int) getSetting($pdo, 'max_upload_mb', '5'));
                $ext     = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, ['jpg', 'jpeg', 'png'], true) || !in_array($ext, $allowed, true)) {
                    throw new InvalidArgumentException('Only allowed image formats (JPG, PNG) can be uploaded.');
                }
                if ($_FILES['profile_photo']['size'] > $maxMb * 1024 * 1024) {
                    throw new OverflowException("File exceeds maximum allowed size of {$maxMb}MB.");
                }

                $avatarDir = __DIR__ . '/../uploads/avatars/';
                if (!is_dir($avatarDir)) {
                    mkdir($avatarDir, 0775, true);
                }

                if (!empty($photoPath)) {
                    $oldAvatar = __DIR__ . '/../' . ltrim($photoPath, '/\\');
                    if (is_file($oldAvatar)) {
                        unlink($oldAvatar);
                    }
                }

                $fileName = $role . '_' . $uid . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $avatarDir . $fileName)) {
                    $photoPath = 'uploads/avatars/' . $fileName;
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, department = ?, profile_photo = ? WHERE user_id = ?");
            $stmt->execute([$name, $email, $phone, $dept, $photoPath, $uid]);
            logActivity($pdo, $uid, 'Updated Profile', 'Updated personal profile information.');
            setFlash('success', 'Profile updated successfully.');

        } elseif ($mode === 'password') {
            $current = validateBoundedString($_POST['current_password'] ?? '', 'Current Password', 1, 128);
            $minLen  = max(8, (int) getSetting($pdo, 'min_password_length', '8'));
            $newPass = validateBoundedString($_POST['new_password'] ?? '', 'New Password', $minLen, 128);
            $confirm = validateBoundedString($_POST['confirm_password'] ?? '', 'Confirm Password', $minLen, 128);

            if (!password_verify($current, $currentUser['password_hash'])) {
                throw new InvalidArgumentException('Current password is incorrect.');
            }
            if ($newPass !== $confirm) {
                throw new InvalidArgumentException('New passwords do not match.');
            }
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,128}$/', $newPass)) {
                throw new InvalidArgumentException('New password must be 8–128 characters and include at least one letter and one number.');
            }

            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?")->execute([$hash, $uid]);
            logActivity($pdo, $uid, 'Changed Password', 'User updated account password.');
            setFlash('success', 'Password changed successfully.');
        }
    } catch (InvalidArgumentException | OverflowException | RuntimeException $e) {
        setFlash('error', $e->getMessage());
    }

    header("Location: $redirectUrl");
    exit;
}

header("Location: $redirectUrl");
exit;