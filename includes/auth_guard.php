<?php
require_once __DIR__ . '/../config/db.php';

$currentDir = basename(dirname($_SERVER['SCRIPT_FILENAME']));
$isInSubfolder = ($currentDir === 'admin' || $currentDir === 'actions');
$basePrefix = $isInSubfolder ? '../' : '';

if (empty($_SESSION['user_id'])) {
    header("Location: {$basePrefix}login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    session_destroy();
    header("Location: {$basePrefix}login.php");
    exit;
}

$currentAdmin = ($currentUser['role'] === 'admin') ? $currentUser : null;
$lmsName = getSetting($pdo, 'lms_name', 'Chimicanga LMS');

function getRoleHome(string $role, string $prefix = ''): string {
    return match ($role) {
        'admin' => "{$prefix}admin/dashboard.php",
        default => "{$prefix}index.php",
    };
}

function requireRole(array $allowedRoles): void {
    global $currentUser, $basePrefix;
    if (!in_array($currentUser['role'], $allowedRoles, true)) {
        setFlash('error', 'Access denied: You do not have permission to view that page.');
        header('Location: ' . getRoleHome($currentUser['role'], $basePrefix));
        exit;
    }
}

// Protect /admin/ directory
if ($currentDir === 'admin') {
    requireRole(['admin']);
}

// Enforce temporary password change if reset by admin
$scriptName = basename($_SERVER['PHP_SELF']);
$allowedDuringReset = ['profile.php', 'profile_update.php', 'logout.php'];
if ((int)$currentUser['must_change_password'] === 1 && !in_array($scriptName, $allowedDuringReset, true)) {
    $targetProfile = ($currentUser['role'] === 'admin') ? "{$basePrefix}admin/profile.php" : "{$basePrefix}profile.php";
    header("Location: $targetProfile");
    exit;
}