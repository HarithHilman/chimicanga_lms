<?php
require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['search'] ?? '');
$courseFilter = (int) ($_GET['course'] ?? 0);

$sql = "
    SELECT e.*, c.course_code, c.title AS course_title, u.staff_student_id, u.full_name AS student_name, u.email
    FROM enrolments e
    JOIN courses c ON e.course_id = c.course_id
    JOIN users u ON e.student_id = u.user_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.staff_student_id LIKE ? OR c.course_code LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($courseFilter > 0) {
    $sql .= " AND e.course_id = ?";
    $params[] = $courseFilter;
}
$sql .= " ORDER BY e.enrolled_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enrolments = $stmt->fetchAll();

$courses  = $pdo->query("SELECT course_id, course_code, title FROM courses WHERE status != 'archived' ORDER BY course_code")->fetchAll();
$students = $pdo->query("SELECT user_id, staff_student_id, full_name FROM users WHERE role = 'student' AND status = 'active' ORDER BY full_name")->fetchAll();
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6">Enrolment Management</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Enrol Students Form -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm h-fit">
        <h2 class="text-base font-bold text-slate-900 mb-4">Enrol Students into Course</h2>
        <form action="../actions/enrolment_manage.php" method="POST" class="space-y-4">
            <input type="hidden" name="mode" value="enrol">
            <div>
                <label class="block text-xs font-semibold mb-1">Select Course *</label>
                <select name="course_id" required class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="">-- Choose Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int)$c['course_id'] ?>" <?= $courseFilter === (int)$c['course_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Select Students (Hold Ctrl/Cmd for multiple) *</label>
                <select name="student_ids[]" multiple required size="7" class="w-full border rounded-xl p-2 text-sm">
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int)$s['user_id'] ?>">
                            <?= htmlspecialchars($s['staff_student_id']) ?> — <?= htmlspecialchars($s['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2.5 rounded-xl">
                Enrol Selected Students
            </button>
        </form>
    </div>

    <!-- Enrolment Records Table -->
    <div class="lg:col-span-2 space-y-4">
        <form method="GET" class="bg-white p-4 rounded-2xl border border-slate-200 flex flex-wrap gap-3">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search student or course..." class="flex-1 border rounded-xl px-3 py-2 text-sm">
            <select name="course" class="border rounded-xl px-3 py-2 text-sm">
                <option value="0">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int)$c['course_id'] ?>" <?= $courseFilter === (int)$c['course_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['course_code']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-slate-900 text-white text-sm px-4 py-2 rounded-xl">Filter</button>
        </form>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                        <th class="py-3 px-4">Student</th>
                        <th class="py-3 px-4">Course</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($enrolments as $e): ?>
                        <tr>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($e['student_name']) ?></div>
                                <div class="text-xs text-slate-400"><?= htmlspecialchars($e['staff_student_id']) ?></div>
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <span class="font-bold"><?= htmlspecialchars($e['course_code']) ?></span> — <?= htmlspecialchars($e['course_title']) ?>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize <?= $e['status'] === 'enrolled' ? 'bg-emerald-50 text-emerald-700' : ($e['status'] === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') ?>">
                                    <?= htmlspecialchars($e['status']) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <?php if ($e['status'] === 'pending'): ?>
                                    <form action="../actions/enrolment_manage.php" method="POST" class="inline">
                                        <input type="hidden" name="mode" value="status">
                                        <input type="hidden" name="enrolment_id" value="<?= (int)$e['enrolment_id'] ?>">
                                        <input type="hidden" name="status" value="enrolled">
                                        <button type="submit" class="text-xs text-emerald-600 font-semibold hover:underline">Approve</button>
                                    </form>
                                    <form action="../actions/enrolment_manage.php" method="POST" class="inline">
                                        <input type="hidden" name="mode" value="status">
                                        <input type="hidden" name="enrolment_id" value="<?= (int)$e['enrolment_id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="text-xs text-amber-600 hover:underline">Reject</button>
                                    </form>
                                <?php endif; ?>

                                <form action="../actions/enrolment_manage.php" method="POST" class="inline" onsubmit="return confirm('Remove student from this course?');">
                                    <input type="hidden" name="mode" value="remove">
                                    <input type="hidden" name="enrolment_id" value="<?= (int)$e['enrolment_id'] ?>">
                                    <button type="submit" class="text-xs text-rose-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
