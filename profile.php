<?php
require_once __DIR__ . '/includes/header.php';
requireRole(['student', 'lecturer']);

$uid = (int) $currentUser['user_id'];

if (isset($_GET['read_notif'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?")
        ->execute([(int)$_GET['read_notif'], $uid]);
    header('Location: profile.php#notifications');
    exit;
}

if (isset($_GET['read_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);
    header('Location: profile.php#notifications');
    exit;
}

$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notifStmt->execute([$uid]);
$notifications = $notifStmt->fetchAll();
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6">My Profile & Account</h1>

<?php if ((int)$currentUser['must_change_password'] === 1): ?>
    <div class="mb-6 px-4 py-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-800 text-sm font-medium">
        Your password was reset by an administrator. Please update your password below for security.
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Personal Information & Profile Picture -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-4">Personal Information</h2>
        <form action="actions/profile_update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="mode" value="info">

            <div class="flex items-center gap-4">
                <?php if (!empty($currentUser['profile_photo']) && is_file(__DIR__ . '/' . ltrim($currentUser['profile_photo'], '/\\'))): ?>
                    <img src="<?= htmlspecialchars($currentUser['profile_photo']) ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover border">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-lg">
                        <?= strtoupper(substr($currentUser['full_name'], 0, 2)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Update Profile Picture</label>
                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png" class="text-xs">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1"><?= $currentUser['role'] === 'lecturer' ? 'Staff ID' : 'Student ID' ?></label>
                    <input type="text" disabled value="<?= htmlspecialchars($currentUser['staff_student_id']) ?>" class="w-full border bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Role</label>
                    <input type="text" disabled value="<?= ucfirst($currentUser['role']) ?>" class="w-full border bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($currentUser['full_name']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Email Address *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($currentUser['email']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Department</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($currentUser['department'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">Save Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
        <h2 class="text-base font-bold text-slate-900 mb-4">Change Password</h2>
        <form action="actions/profile_update.php" method="POST" class="space-y-4">
            <input type="hidden" name="mode" value="password">
            <div>
                <label class="block text-xs font-semibold mb-1">Current Password *</label>
                <input type="password" name="current_password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">New Password *</label>
                <input type="password" name="new_password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Confirm New Password *</label>
                <input type="password" name="confirm_password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded-xl">Update Password</button>
        </form>
    </div>
</div>

<!-- Notifications Inbox -->
<div id="notifications" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-slate-900">Notifications</h2>
        <?php if ($unreadCount > 0): ?>
            <a href="?read_all=1" class="text-xs font-semibold text-indigo-600 hover:underline">Mark all as read</a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <p class="text-xs text-slate-400">No notifications yet.</p>
    <?php else: ?>
        <div class="space-y-2.5">
            <?php foreach ($notifications as $n): ?>
                <div class="p-3.5 rounded-xl border text-xs flex justify-between items-start gap-3 <?= $n['is_read'] ? 'bg-slate-50 border-slate-100' : 'bg-indigo-50/50 border-indigo-200' ?>">
                    <div>
                        <div class="font-bold text-slate-900"><?= htmlspecialchars($n['title']) ?> <span class="font-normal text-slate-400 ml-1">&bull; <?= htmlspecialchars($n['created_at']) ?></span></div>
                        <div class="text-slate-600 mt-0.5"><?= htmlspecialchars($n['message']) ?></div>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <a href="?read_notif=<?= (int)$n['notification_id'] ?>" class="text-indigo-600 font-semibold hover:underline shrink-0">Mark read</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>