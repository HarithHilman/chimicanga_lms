<?php
require_once __DIR__ . '/../includes/header.php';

$courseId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, cat.name AS category_name FROM courses c LEFT JOIN categories cat ON c.category_id = cat.category_id WHERE c.course_id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    echo "<div class='bg-white p-6 rounded-xl'>Course not found.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$assignedStmt = $pdo->prepare("SELECT u.* FROM course_lecturers cl JOIN users u ON cl.lecturer_id = u.user_id WHERE cl.course_id = ?");
$assignedStmt->execute([$courseId]);
$assignedLecturers = $assignedStmt->fetchAll();

$allLecturers = $pdo->query("SELECT * FROM users WHERE role = 'lecturer' AND status = 'active' ORDER BY full_name")->fetchAll();

$enrolledStmt = $pdo->prepare("
    SELECT e.enrolment_id, e.status, e.enrolled_at, u.staff_student_id, u.full_name, u.email
    FROM enrolments e
    JOIN users u ON e.student_id = u.user_id
    WHERE e.course_id = ?
    ORDER BY u.full_name
");
$enrolledStmt->execute([$courseId]);
$enrolledStudents = $enrolledStmt->fetchAll();
?>

<div class="mb-6">
    <a href="courses.php" class="text-xs text-indigo-600 hover:underline">&larr; Back to Courses</a>
    <div class="flex flex-wrap items-center justify-between gap-4 mt-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($course['course_code']) ?>: <?= htmlspecialchars($course['title']) ?></h1>
            <p class="text-xs text-slate-500 mt-1">Category: <?= htmlspecialchars($course['category_name'] ?? 'Uncategorized') ?> &bull; Credits: <?= (int)$course['credit_hours'] ?></p>
        </div>

        <form action="../actions/course_status.php" method="POST" class="flex items-center gap-2">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <select name="status" class="border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold">
                <option value="draft" <?= $course['status'] === 'draft' ? 'selected' : '' ?>>Draft (Unpublished)</option>
                <option value="published" <?= $course['status'] === 'published' ? 'selected' : '' ?>>Published (Available)</option>
                <option value="archived" <?= $course['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
            <button type="submit" class="bg-slate-900 text-white text-xs font-medium px-3.5 py-2 rounded-xl">Update Availability</button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Assign / Remove Lecturers -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-3">Assigned Lecturers</h2>
        <form action="../actions/course_lecturer_assign.php" method="POST" class="flex gap-2 mb-4">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <input type="hidden" name="action" value="assign">
            <select name="lecturer_id" required class="flex-1 border border-slate-300 rounded-xl px-3 py-2 text-sm">
                <option value="">Select lecturer to assign...</option>
                <?php foreach ($allLecturers as $lec): ?>
                    <option value="<?= (int)$lec['user_id'] ?>"><?= htmlspecialchars($lec['full_name']) ?> (<?= htmlspecialchars($lec['staff_student_id']) ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-xl">Assign</button>
        </form>

        <div class="divide-y divide-slate-100">
            <?php if (empty($assignedLecturers)): ?>
                <p class="text-xs text-slate-400 py-2">No lecturers assigned to this course yet.</p>
            <?php endif; ?>
            <?php foreach ($assignedLecturers as $lec): ?>
                <div class="py-2.5 flex justify-between items-center text-sm">
                    <div>
                        <span class="font-medium text-slate-900"><?= htmlspecialchars($lec['full_name']) ?></span>
                        <span class="text-xs text-slate-400 ml-1">(<?= htmlspecialchars($lec['staff_student_id']) ?>)</span>
                    </div>
                    <form action="../actions/course_lecturer_assign.php" method="POST">
                        <input type="hidden" name="course_id" value="<?= $courseId ?>">
                        <input type="hidden" name="lecturer_id" value="<?= (int)$lec['user_id'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="text-xs text-rose-600 hover:underline">Remove</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Enrolled Students in Course -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-base font-bold text-slate-900">Enrolled Students (<?= count($enrolledStudents) ?>)</h2>
            <a href="enrolments.php?course=<?= $courseId ?>" class="text-xs text-indigo-600 hover:underline">+ Enrol Students</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($enrolledStudents)): ?>
                <p class="text-xs text-slate-400 py-2">No students enrolled in this course.</p>
            <?php endif; ?>
            <?php foreach ($enrolledStudents as $st): ?>
                <div class="py-2.5 flex justify-between items-center text-sm">
                    <div>
                        <span class="font-mono text-xs text-slate-500 mr-1"><?= htmlspecialchars($st['staff_student_id']) ?></span>
                        <span class="font-medium text-slate-900"><?= htmlspecialchars($st['full_name']) ?></span>
                    </div>
                    <form action="../actions/enrolment_manage.php" method="POST">
                        <input type="hidden" name="mode" value="remove">
                        <input type="hidden" name="enrolment_id" value="<?= (int)$st['enrolment_id'] ?>">
                        <input type="hidden" name="redirect" value="../admin/course_details.php?id=<?= $courseId ?>">
                        <button type="submit" class="text-xs text-rose-600 hover:underline">Remove</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
