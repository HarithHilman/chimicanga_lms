<?php
require_once __DIR__ . '/includes/header.php';
requireRole(['student']);

$stmt = $pdo->prepare("
    SELECT c.course_code, c.title AS course_title, a.title AS assignment_title, a.max_score, a.weight_percentage,
           s.submission_id, s.file_name, s.status, s.score, s.instructor_feedback, s.submitted_at
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN enrolments e ON c.course_id = e.course_id AND e.student_id = :uid1 AND e.status = 'enrolled'
    LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id AND s.user_id = :uid2
    ORDER BY c.course_code, a.due_date
");
$stmt->execute(['uid1' => $currentUser['user_id'], 'uid2' => $currentUser['user_id']]);
$records = $stmt->fetchAll();
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6">Academic Gradebook</h1>

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
    <table class="w-full text-left border-collapse text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-600">
                <th class="py-3.5 px-4">Course</th>
                <th class="py-3.5 px-4">Assessment</th>
                <th class="py-3.5 px-4">Uploaded File</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4">Score</th>
                <th class="py-3.5 px-4">Instructor Feedback</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="6" class="py-6 px-4 text-center text-slate-500">No assignments or grades found yet.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($records as $row): ?>
                <tr>
                    <td class="py-3.5 px-4 font-medium text-slate-900"><?= htmlspecialchars($row['course_code']) ?></td>
                    <td class="py-3.5 px-4"><?= htmlspecialchars($row['assignment_title']) ?></td>
                    <td class="py-3.5 px-4">
                        <?php if (!empty($row['submission_id'])): ?>
                            <a href="actions/download.php?id=<?= (int)$row['submission_id'] ?>" target="_blank" class="text-indigo-600 underline text-xs">
                                <?= htmlspecialchars($row['file_name']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-xs text-slate-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $row['status'] === 'graded' ? 'bg-emerald-100 text-emerald-800' : ($row['status'] === 'submitted' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600') ?>">
                            <?= strtoupper($row['status'] ?? 'pending') ?>
                        </span>
                    </td>
                    <td class="py-3.5 px-4 font-semibold">
                        <?= $row['score'] !== null ? htmlspecialchars($row['score']) . ' / ' . htmlspecialchars($row['max_score']) : '—' ?>
                    </td>
                    <td class="py-3.5 px-4 text-slate-500"><?= htmlspecialchars($row['instructor_feedback'] ?? 'No feedback yet') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>