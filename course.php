<?php
require_once __DIR__ . '/includes/header.php';
requireRole(['student', 'lecturer']);

$courseId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    echo "<div class='p-6 bg-white rounded-xl'>Course not found. <a href='index.php' class='text-indigo-600 underline'>Go back</a></div></main></body></html>";
    exit;
}

// Fetch lessons + student completion status
$lessonStmt = $pdo->prepare("
    SELECT l.*, COALESCE(lp.is_completed, 0) AS is_completed
    FROM lessons l
    LEFT JOIN lesson_progress lp ON l.lesson_id = lp.lesson_id AND lp.user_id = ?
    WHERE l.course_id = ?
    ORDER BY l.module_order ASC
");
$lessonStmt->execute([$currentUser['user_id'], $courseId]);
$lessons = $lessonStmt->fetchAll();

$activeLessonId = (int) ($_GET['lesson'] ?? ($lessons[0]['lesson_id'] ?? 0));
$activeLesson = null;
foreach ($lessons as $l) {
    if ($l['lesson_id'] == $activeLessonId) {
        $activeLesson = $l;
        break;
    }
}

// Fetch Assignments
$assignStmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
$assignStmt->execute([$courseId]);
$assignments = $assignStmt->fetchAll();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="index.php" class="text-xs font-medium text-indigo-600 hover:underline">&larr; Back to Courses</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-1"><?= htmlspecialchars($course['course_code']) ?>: <?= htmlspecialchars($course['title']) ?></h1>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <?php if ($activeLesson): ?>
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                    <div>
                        <span class="text-xs font-semibold uppercase text-indigo-600"><?= htmlspecialchars($activeLesson['lesson_type']) ?> &bull; <?= (int)$activeLesson['duration_minutes'] ?> mins</span>
                        <h2 class="text-xl font-bold text-slate-900 mt-1"><?= htmlspecialchars($activeLesson['title']) ?></h2>
                    </div>

                    <?php if ($currentUser['role'] === 'student'): ?>
                        <form action="actions/toggle_progress.php" method="POST">
                            <input type="hidden" name="course_id" value="<?= $courseId ?>">
                            <input type="hidden" name="lesson_id" value="<?= $activeLesson['lesson_id'] ?>">
                            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold <?= $activeLesson['is_completed'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-900 text-white' ?>">
                                <?= $activeLesson['is_completed'] ? '✓ Completed' : 'Mark Complete' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="prose prose-slate max-w-none text-sm text-slate-700 whitespace-pre-line">
                    <?= htmlspecialchars($activeLesson['content_markdown']) ?>
                </div>

                <?php if (!empty($activeLesson['code_snippet'])): ?>
                    <pre class="mt-4 bg-slate-900 text-slate-100 p-4 rounded-xl text-xs overflow-x-auto"><code><?= htmlspecialchars($activeLesson['code_snippet']) ?></code></pre>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-sm text-slate-500">No lessons available in this course yet.</p>
            <?php endif; ?>
        </div>

        <!-- Assignments & Submissions Section -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Course Assignments</h3>
            <div class="space-y-4">
                <?php foreach ($assignments as $assign): ?>
                    <div class="border border-slate-200 rounded-xl p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($assign['title']) ?></h4>
                                <p class="text-xs text-slate-500 mt-0.5">Due: <?= htmlspecialchars($assign['due_date']) ?> &bull; Weight: <?= $assign['weight_percentage'] ?>%</p>
                                <p class="text-xs text-slate-600 mt-2"><?= htmlspecialchars($assign['instructions']) ?></p>
                            </div>
                        </div>

                        <?php if ($currentUser['role'] === 'student'): ?>
                            <?php
                                $subStmt = $pdo->prepare("SELECT * FROM assignment_submissions WHERE assignment_id = ? AND user_id = ?");
                                $subStmt->execute([$assign['assignment_id'], $currentUser['user_id']]);
                                $sub = $subStmt->fetch();
                            ?>
                            <div class="mt-4 pt-3 border-t border-slate-100">
                                <?php if ($sub): ?>
                                    <div class="text-xs bg-slate-50 p-3 rounded-lg mb-3">
                                        <span class="font-semibold text-indigo-700">Status: <?= strtoupper($sub['status']) ?></span> &bull;
                                        File: <a href="actions/download.php?id=<?= (int)$sub['submission_id'] ?>" target="_blank" class="text-indigo-600 underline"><?= htmlspecialchars($sub['file_name']) ?></a>
                                        <?php if ($sub['score'] !== null): ?>
                                            <div class="mt-1 font-bold text-emerald-700">Score: <?= $sub['score'] ?> / <?= $assign['max_score'] ?> — "<?= htmlspecialchars($sub['instructor_feedback']) ?>"</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <form action="actions/submit_assignment.php" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-2">
                                    <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                    <input type="hidden" name="assignment_id" value="<?= $assign['assignment_id'] ?>">
                                    <input type="file" name="submission_file" required class="text-xs border border-slate-300 rounded-lg p-1.5 flex-1">
                                    <input type="text" name="student_notes" placeholder="Optional notes..." class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5">
                                    <button type="submit" class="bg-indigo-600 text-white text-xs font-medium px-4 py-2 rounded-lg">Upload</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Instructor View: Grade Any Uploaded Student Submissions -->
                            <?php
                                $allSubs = $pdo->prepare("SELECT s.*, u.full_name FROM assignment_submissions s JOIN users u ON s.user_id = u.user_id WHERE s.assignment_id = ?");
                                $allSubs->execute([$assign['assignment_id']]);
                                $subs = $allSubs->fetchAll();
                            ?>
                            <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                                <p class="text-xs font-semibold text-slate-700">Student Submissions (<?= count($subs) ?>):</p>
                                <?php if (empty($subs)): ?>
                                    <p class="text-xs text-slate-400 italic">No student submissions uploaded yet.</p>
                                <?php endif; ?>
                                <?php foreach ($subs as $s): ?>
                                    <?php $fileExists = is_file(__DIR__ . '/' . ltrim($s['file_path'], '/')); ?>
                                    <form action="actions/grade_submission.php" method="POST" class="flex flex-wrap items-center gap-2 bg-slate-50 p-2.5 rounded-lg text-xs">
                                        <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                        <input type="hidden" name="submission_id" value="<?= (int)$s['submission_id'] ?>">
                                        <span class="font-semibold"><?= htmlspecialchars($s['full_name']) ?></span>
                                        <?php if ($fileExists): ?>
                                            <a href="actions/download.php?id=<?= (int)$s['submission_id'] ?>" target="_blank" class="text-indigo-600 underline"><?= htmlspecialchars($s['file_name']) ?></a>
                                        <?php else: ?>
                                            <span class="text-red-600"><?= htmlspecialchars($s['file_name']) ?> (missing on disk)</span>
                                        <?php endif; ?>
                                        <input type="number" step="0.5" name="score" value="<?= htmlspecialchars($s['score'] ?? '') ?>" placeholder="Score" required class="w-20 border rounded px-2 py-1">
                                        <input type="text" name="instructor_feedback" value="<?= htmlspecialchars($s['instructor_feedback'] ?? '') ?>" placeholder="Feedback" class="flex-1 border rounded px-2 py-1">
                                        <button type="submit" class="bg-emerald-600 text-white px-3 py-1 rounded font-medium">Save Grade</button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">Course Syllabus</h3>
            <div class="space-y-2">
                <?php foreach ($lessons as $idx => $lesson): ?>
                    <a href="course.php?id=<?= $courseId ?>&lesson=<?= $lesson['lesson_id'] ?>" class="flex items-center justify-between p-3 rounded-xl border text-sm <?= $lesson['lesson_id'] == $activeLessonId ? 'border-indigo-600 bg-indigo-50/60 font-semibold text-indigo-900' : 'border-slate-100 hover:bg-slate-50' ?>">
                        <span><?= ($idx + 1) ?>. <?= htmlspecialchars($lesson['title']) ?></span>
                        <?php if ($lesson['is_completed']): ?>
                            <span class="text-emerald-600 font-bold text-xs">✓</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($currentUser['role'] === 'lecturer'): ?>
                <form action="actions/add_lesson.php" method="POST" class="mt-6 pt-5 border-t border-slate-100 space-y-3">
                    <h4 class="text-xs font-bold uppercase text-slate-700">Add New Lesson</h4>
                    <input type="hidden" name="course_id" value="<?= $courseId ?>">
                    <input type="text" name="title" required placeholder="Lesson Title" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs">
                    <div class="flex gap-2">
                        <select name="lesson_type" class="border border-slate-300 rounded-lg px-2 py-1.5 text-xs flex-1">
                            <option value="video">Video</option>
                            <option value="reading">Reading</option>
                            <option value="quiz">Quiz</option>
                        </select>
                        <input type="number" name="duration_minutes" value="15" class="w-20 border border-slate-300 rounded-lg px-2 py-1.5 text-xs">
                    </div>
                    <textarea name="content_markdown" rows="3" placeholder="Lesson notes / markdown..." class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs"></textarea>
                    <button type="submit" class="w-full bg-slate-900 text-white text-xs font-medium py-2 rounded-lg">+ Add Lesson</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</main>
</body>
</html>
