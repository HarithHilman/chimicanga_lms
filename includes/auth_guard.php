<?php
require_once __DIR__ . '/../config/db.php';

// Determine base URL prefix depending on whether the current script is inside /admin/ or root
$isInSubfolder = (basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'admin' || basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'actions');
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

// Keep backward compatibility with existing admin/* and actions/* files
$currentAdmin = ($currentUser['role'] === 'admin') ? $currentUser : null;
$lmsName = getSetting($pdo, 'lms_name', 'Chimicanga LMS');

function getRoleHome(string $role, string $prefix = ''): string {
    return match ($role) {
        'admin'    => "{$prefix}admin/dashboard.php",
        'lecturer' => "{$prefix}index.php",
        default    => "{$prefix}index.php",
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

// Automatically protect everything inside /admin/ so only 'admin' can access it
if (basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'admin') {
    requireRole(['admin']);
}