<?php
require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['search'] ?? '');
$catFilter = (int) ($_GET['category'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$sql = "
    SELECT c.*, cat.name AS category_name,
           (SELECT COUNT(*) FROM enrolments e WHERE e.course_id = c.course_id AND e.status = 'enrolled') AS student_count,
           (SELECT GROUP_CONCAT(u.full_name SEPARATOR ', ') FROM course_lecturers cl JOIN users u ON cl.lecturer_id = u.user_id WHERE cl.course_id = c.course_id) AS lecturers
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (c.course_code LIKE ? OR c.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catFilter > 0) {
    $sql .= " AND c.category_id = ?";
    $params[] = $catFilter;
}
if (in_array($statusFilter, ['draft', 'published', 'archived'], true)) {
    $sql .= " AND c.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$editCourse = null;
if (isset($_GET['edit'])) {
    $eStmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
    $eStmt->execute([(int)$_GET['edit']]);
    $editCourse = $eStmt->fetch();
}
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Course Management</h1>
    <button onclick="document.getElementById('createCourseModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">
        + Create Course
    </button>
</div>

<!-- Search & Filter -->
<form method="GET" class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by course code or title..." class="flex-1 min-w-[200px] border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
    <select name="category" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['category_id'] ?>" <?= $catFilter === (int)$cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
        <option value="">All Statuses</option>
        <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft (Unpublished)</option>
        <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="archived" <?= $statusFilter === 'archived' ? 'selected' : '' ?>>Archived</option>
    </select>
    <button type="submit" class="bg-slate-900 text-white text-sm font-medium px-4 py-2 rounded-xl">Filter</button>
</form>

<?php if ($editCourse): ?>
    <div class="bg-white rounded-2xl border border-amber-200 p-5 mb-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-3">Edit Course: <?= htmlspecialchars($editCourse['course_code']) ?></h2>
        <form action="../actions/course_update.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="course_id" value="<?= (int)$editCourse['course_id'] ?>">
            <div>
                <label class="block text-xs font-semibold mb-1">Course Code *</label>
                <input type="text" name="course_code" required value="<?= htmlspecialchars($editCourse['course_code']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Title *</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($editCourse['title']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Category</label>
                <select name="category_id" class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="">-- Uncategorized --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['category_id'] ?>" <?= (int)$editCourse['category_id'] === (int)$cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold mb-1">Credits</label>
                    <input type="number" name="credit_hours" value="<?= (int)$editCourse['credit_hours'] ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Status</label>
                    <select name="status" class="w-full border rounded-xl px-3 py-2 text-sm">
                        <option value="draft" <?= $editCourse['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $editCourse['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="archived" <?= $editCourse['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm"><?= htmlspecialchars($editCourse['description'] ?? '') ?></textarea>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-xl">Update Course</button>
                <a href="courses.php" class="text-sm text-slate-500 px-3 py-2">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                <th class="py-3 px-4">Code</th>
                <th class="py-3 px-4">Course Title</th>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4">Assigned Lecturers</th>
                <th class="py-3 px-4">Students</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($courses as $c): ?>
                <tr>
                    <td class="py-3 px-4 font-mono text-xs font-bold"><?= htmlspecialchars($c['course_code']) ?></td>
                    <td class="py-3 px-4 font-medium text-slate-900"><?= htmlspecialchars($c['title']) ?></td>
                    <td class="py-3 px-4 text-xs text-slate-600"><?= htmlspecialchars($c['category_name'] ?? 'Uncategorized') ?></td>
                    <td class="py-3 px-4 text-xs text-slate-600"><?= htmlspecialchars($c['lecturers'] ?? 'Unassigned') ?></td>
                    <td class="py-3 px-4 text-xs font-semibold"><?= (int)$c['student_count'] ?></td>
                    <td class="py-3 px-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize <?= $c['status'] === 'published' ? 'bg-emerald-50 text-emerald-700' : ($c['status'] === 'archived' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') ?>">
                            <?= htmlspecialchars($c['status']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                        <a href="course_details.php?id=<?= (int)$c['course_id'] ?>" class="text-xs font-semibold text-indigo-600 hover:underline">Manage</a>
                        <a href="?edit=<?= (int)$c['course_id'] ?>" class="text-xs text-slate-600 hover:underline">Edit</a>
                        <form action="../actions/course_delete.php" method="POST" class="inline" onsubmit="return confirm('Delete this course?');">
                            <input type="hidden" name="course_id" value="<?= (int)$c['course_id'] ?>">
                            <button type="submit" class="text-xs text-rose-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create Course Modal -->
<div id="createCourseModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Create New Course</h3>
        <form action="../actions/course_create.php" method="POST" class="space-y-3">
            <div>
                <label class="block text-xs font-semibold mb-1">Course Code *</label>
                <input type="text" name="course_code" required placeholder="e.g. CSEB4113" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Course Title *</label>
                <input type="text" name="title" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Category</label>
                <select name="category_id" class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['status'] === 'active'): ?>
                            <option value="<?= (int)$cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold mb-1">Credit Hours</label>
                    <input type="number" name="credit_hours" value="3" min="1" max="6" class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Initial Status</label>
                    <select name="status" class="w-full border rounded-xl px-3 py-2 text-sm">
                        <option value="draft">Draft (Unpublished)</option>
                        <option value="published">Published</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border rounded-xl px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('createCourseModal').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl">Create Course</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
