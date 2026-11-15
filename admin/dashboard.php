<?php
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['read_notif'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?")->execute([(int)$_GET['read_notif'], $currentAdmin['user_id']]);
    header('Location: dashboard.php');
    exit;
}

$totalStudents  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalLecturers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'lecturer'")->fetchColumn();
$totalCourses   = (int) $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$activeUsers    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$inactiveUsers  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();

$recentUsers   = $pdo->query("SELECT staff_student_id, full_name, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentCourses = $pdo->query("SELECT course_code, title, status, created_at FROM courses ORDER BY created_at DESC LIMIT 5")->fetchAll();
$activities    = $pdo->query("SELECT a.*, u.full_name FROM activity_logs a LEFT JOIN users u ON a.user_id = u.user_id ORDER BY a.created_at DESC LIMIT 6")->fetchAll();

$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifStmt->execute([$currentAdmin['user_id']]);
$notifications = $notifStmt->fetchAll();
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6"><?= htmlspecialchars($lmsName) ?> Dashboard</h1>

<!-- Key Metrics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Registered Students</div>
        <div class="text-3xl font-bold text-slate-900 mt-1"><?= $totalStudents ?></div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Registered Lecturers</div>
        <div class="text-3xl font-bold text-slate-900 mt-1"><?= $totalLecturers ?></div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Total Courses</div>
        <div class="text-3xl font-bold text-slate-900 mt-1"><?= $totalCourses ?></div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-xs font-semibold uppercase text-slate-400">Active vs Inactive Users</div>
        <div class="text-2xl font-bold mt-1">
            <span class="text-emerald-600"><?= $activeUsers ?></span>
            <span class="text-slate-300 mx-1">/</span>
            <span class="text-rose-500"><?= $inactiveUsers ?></span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Recently Registered Users -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-bold text-slate-900">Recently Registered Users</h2>
            <a href="students.php" class="text-xs text-indigo-600 hover:underline">Manage Users</a>
        </div>
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs text-slate-400">
                    <th class="pb-2">ID</th>
                    <th class="pb-2">Name</th>
                    <th class="pb-2">Role</th>
                    <th class="pb-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td class="py-2.5 font-mono text-xs"><?= htmlspecialchars($u['staff_student_id']) ?></td>
                        <td class="py-2.5 font-medium"><?= htmlspecialchars($u['full_name']) ?></td>
                        <td class="py-2.5 capitalize text-xs"><?= htmlspecialchars($u['role']) ?></td>
                        <td class="py-2.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $u['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                                <?= htmlspecialchars($u['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Recently Created Courses -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-base font-bold text-slate-900">Recently Created Courses</h2>
            <a href="courses.php" class="text-xs text-indigo-600 hover:underline">All Courses</a>
        </div>
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs text-slate-400">
                    <th class="pb-2">Code</th>
                    <th class="pb-2">Title</th>
                    <th class="pb-2">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($recentCourses as $c): ?>
                    <tr>
                        <td class="py-2.5 font-mono text-xs font-semibold"><?= htmlspecialchars($c['course_code']) ?></td>
                        <td class="py-2.5"><?= htmlspecialchars($c['title']) ?></td>
                        <td class="py-2.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium capitalize <?= $c['status'] === 'published' ? 'bg-emerald-50 text-emerald-700' : ($c['status'] === 'archived' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') ?>">
                                <?= htmlspecialchars($c['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="notifications">
    <!-- Recent System Activities -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-4">Recent System Activities</h2>
        <div class="space-y-3">
            <?php foreach ($activities as $act): ?>
                <div class="text-xs border-l-2 border-indigo-500 pl-3 py-1">
                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($act['action']) ?> <span class="font-normal text-slate-400">&bull; <?= htmlspecialchars($act['created_at']) ?></span></div>
                    <div class="text-slate-600 mt-0.5"><?= htmlspecialchars($act['details']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- System Notifications -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-4">Notifications</h2>
        <div class="space-y-3">
            <?php foreach ($notifications as $n): ?>
                <div class="p-3 rounded-xl border text-xs flex justify-between items-start <?= $n['is_read'] ? 'bg-slate-50 border-slate-100' : 'bg-indigo-50/50 border-indigo-200' ?>">
                    <div>
                        <div class="font-bold text-slate-900"><?= htmlspecialchars($n['title']) ?></div>
                        <div class="text-slate-600 mt-0.5"><?= htmlspecialchars($n['message']) ?></div>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <a href="?read_notif=<?= (int)$n['notification_id'] ?>" class="text-indigo-600 font-semibold hover:underline shrink-0 ml-2">Mark read</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
