<?php
require_once __DIR__ . '/includes/header.php';

if ($currentUser['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}

$uid = (int) $currentUser['user_id'];
$role = $currentUser['role'];

// Fetch courses based on role
if ($role === 'lecturer') {
    $stmt = $pdo->prepare("
        SELECT c.*, cat.name AS category_name,
               (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.course_id AND e.status = 'enrolled') AS student_count
        FROM courses c
        JOIN course_lecturers cl ON c.course_id = cl.course_id
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        WHERE cl.lecturer_id = ?
        ORDER BY c.course_code ASC
    ");
    $stmt->execute([$uid]);
    $myCourses = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT c.*, cat.name AS category_name,
               (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ') FROM course_lecturers cl JOIN users u ON cl.lecturer_id = u.user_id WHERE cl.course_id = c.course_id) AS lecturers
        FROM courses c
        JOIN enrolments e ON c.course_id = e.course_id
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        WHERE e.student_id = ? AND e.status = 'enrolled' AND c.status = 'published'
        ORDER BY c.course_code ASC
    ");
    $stmt->execute([$uid]);
    $myCourses = $stmt->fetchAll();
}

// Fetch published announcements targeted to this role
$annStmt = $pdo->prepare("
    SELECT a.*, u.full_name AS author_name
    FROM announcements a
    JOIN users u ON a.created_by = u.user_id
    WHERE a.status = 'published' AND a.target_role IN ('all', ?)
    ORDER BY a.published_at DESC LIMIT 5
");
$annStmt->execute([$role]);
$announcements = $annStmt->fetchAll();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Welcome back, <?= htmlspecialchars($currentUser['full_name']) ?></h1>
    <p class="text-sm text-slate-500 mt-1"><?= $role === 'lecturer' ? 'Manage your assigned courses, lessons, and student submissions.' : 'Access your enrolled courses, lessons, and assignments.' ?></p>
</div>

<!-- Announcements Banner -->
<?php if (!empty($announcements)): ?>
    <div class="mb-8 space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Announcements</h2>
        <?php foreach ($announcements as $ann): ?>
            <div class="bg-white border border-indigo-100 rounded-2xl p-4 shadow-sm">
                <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($ann['title']) ?></div>
                <p class="text-xs text-slate-600 mt-1"><?= htmlspecialchars($ann['body']) ?></p>
                <p class="text-[11px] text-slate-400 mt-2">Posted by <?= htmlspecialchars($ann['author_name']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Courses Grid -->
<h2 class="text-base font-bold text-slate-900 mb-4"><?= $role === 'lecturer' ? 'Assigned Courses' : 'Enrolled Courses' ?></h2>
<?php if (empty($myCourses)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-sm text-slate-500">
        No courses assigned or enrolled yet.
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($myCourses as $c): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="font-mono font-bold text-indigo-600"><?= htmlspecialchars($c['course_code']) ?></span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"><?= htmlspecialchars($c['category_name'] ?? 'General') ?></span>
                    </div>
                    <h3 class="font-bold text-slate-900 text-base"><?= htmlspecialchars($c['title']) ?></h3>
                    <p class="text-xs text-slate-500 mt-2 line-clamp-2"><?= htmlspecialchars($c['description'] ?? '') ?></p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        <?= $role === 'lecturer' ? ((int)$c['student_count'] . ' Students') : ('Lecturer: ' . htmlspecialchars($c['lecturers'] ?? 'TBA')) ?>
                    </span>
                    <a href="course.php?id=<?= (int)$c['course_id'] ?>" class="text-xs font-semibold text-indigo-600 hover:underline">
                        Open Course &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>