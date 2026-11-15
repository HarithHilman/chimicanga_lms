<?php
require_once __DIR__ . '/auth_guard.php';
$currentPage = basename($_SERVER['PHP_SELF']);

$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unreadStmt->execute([$currentAdmin['user_id']]);
$unreadCount = (int) $unreadStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lmsName) ?> — Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex">

<!-- Sidebar -->
<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 min-h-screen">
    <div class="h-16 px-6 flex items-center border-b border-slate-800">
        <a href="dashboard.php" class="text-lg font-bold text-white tracking-tight">
            <?= htmlspecialchars($lmsName) ?> <span class="text-xs font-normal text-indigo-400 ml-1">Admin</span>
        </a>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1 text-sm overflow-y-auto">
        <a href="dashboard.php" class="flex items-center px-3 py-2.5 rounded-lg <?= $currentPage === 'dashboard.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Dashboard
        </a>

        <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">User Management</div>
        <a href="students.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'students.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Students
        </a>
        <a href="lecturers.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'lecturers.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Lecturers
        </a>
        <a href="admins.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'admins.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Admins
        </a>

        <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Course Management</div>
        <a href="courses.php" class="flex items-center px-3 py-2 rounded-lg <?= in_array($currentPage, ['courses.php', 'course_details.php']) ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Courses
        </a>
        <a href="categories.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'categories.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Categories
        </a>

        <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Operations</div>
        <a href="enrolments.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'enrolments.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Enrolment Management
        </a>
        <a href="announcements.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'announcements.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            Announcements
        </a>
        <a href="settings.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'settings.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            System Settings
        </a>

        <div class="pt-4 pb-1 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Account</div>
        <a href="profile.php" class="flex items-center px-3 py-2 rounded-lg <?= $currentPage === 'profile.php' ? 'bg-indigo-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?>">
            My Profile
        </a>
        <a href="../actions/logout.php" class="flex items-center px-3 py-2 rounded-lg text-rose-400 hover:bg-slate-800 hover:text-rose-300">
            Logout
        </a>
    </nav>
</aside>

<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col min-w-0">
    <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between">
        <div class="text-sm text-slate-500">
            <?= htmlspecialchars($lmsName) ?> &mdash; Admin Control Panel
        </div>
        <div class="flex items-center gap-4">
            <a href="dashboard.php#notifications" class="relative text-sm text-slate-600 hover:text-indigo-600 font-medium">
                Notifications
                <?php if ($unreadCount > 0): ?>
                    <span class="ml-1 px-2 py-0.5 text-xs font-bold bg-indigo-600 text-white rounded-full"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <div class="h-4 w-px bg-slate-200"></div>
            <a href="profile.php" class="flex items-center gap-2 text-sm font-medium text-slate-700 hover:text-indigo-600">
                <span><?= htmlspecialchars($currentAdmin['full_name']) ?></span>
            </a>
        </div>
    </header>

    <main class="p-6 max-w-7xl w-full mx-auto flex-1">
        <?php if (isset($_SESSION['flash'])): ?>
            <?php $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
            <div class="mb-6 px-4 py-3 rounded-xl border text-sm font-medium <?= $f['type'] === 'error' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' ?>">
                <?= htmlspecialchars($f['message']) ?>
            </div>
        <?php endif; ?>
