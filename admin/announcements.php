<?php
require_once __DIR__ . '/../includes/header.php';

$announcements = $pdo->query("
    SELECT a.*, u.full_name AS author_name
    FROM announcements a
    JOIN users u ON a.created_by = u.user_id
    ORDER BY a.created_at DESC
")->fetchAll();

$editAnn = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE announcement_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editAnn = $stmt->fetch();
}
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6">Announcement Management</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Create / Edit Announcement -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm h-fit">
        <h2 class="text-base font-bold text-slate-900 mb-4"><?= $editAnn ? 'Edit Announcement' : 'Create Announcement' ?></h2>
        <form action="../actions/announcement_save.php" method="POST" class="space-y-3">
            <input type="hidden" name="announcement_id" value="<?= (int)($editAnn['announcement_id'] ?? 0) ?>">
            <div>
                <label class="block text-xs font-semibold mb-1">Title *</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($editAnn['title'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Target Audience</label>
                <select name="target_role" class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="all" <?= ($editAnn['target_role'] ?? 'all') === 'all' ? 'selected' : '' ?>>All Users (Students & Lecturers)</option>
                    <option value="student" <?= ($editAnn['target_role'] ?? '') === 'student' ? 'selected' : '' ?>>Students Only</option>
                    <option value="lecturer" <?= ($editAnn['target_role'] ?? '') === 'lecturer' ? 'selected' : '' ?>>Lecturers Only</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Visibility Status</label>
                <select name="status" class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="draft" <?= ($editAnn['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft (Unpublished)</option>
                    <option value="published" <?= ($editAnn['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Message *</label>
                <textarea name="body" rows="4" required class="w-full border rounded-xl px-3 py-2 text-sm"><?= htmlspecialchars($editAnn['body'] ?? '') ?></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-xl">Save Announcement</button>
                <?php if ($editAnn): ?>
                    <a href="announcements.php" class="text-sm text-slate-500 px-3 py-2">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Announcements Feed -->
    <div class="lg:col-span-2 space-y-4">
        <?php foreach ($announcements as $ann): ?>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $ann['status'] === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                <?= ucfirst($ann['status']) ?>
                            </span>
                            <span class="text-xs font-medium bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-full">
                                Audience: <?= $ann['target_role'] === 'all' ? 'All Users' : ucfirst($ann['target_role']) . 's' ?>
                            </span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mt-2"><?= htmlspecialchars($ann['title']) ?></h3>
                        <p class="text-sm text-slate-600 mt-1 whitespace-pre-line"><?= htmlspecialchars($ann['body']) ?></p>
                        <p class="text-xs text-slate-400 mt-3">By <?= htmlspecialchars($ann['author_name']) ?> &bull; <?= htmlspecialchars($ann['created_at']) ?></p>
                    </div>

                    <div class="flex flex-col gap-1.5 text-right shrink-0">
                        <a href="?edit=<?= (int)$ann['announcement_id'] ?>" class="text-xs text-indigo-600 hover:underline">Edit</a>

                        <form action="../actions/announcement_action.php" method="POST">
                            <input type="hidden" name="announcement_id" value="<?= (int)$ann['announcement_id'] ?>">
                            <input type="hidden" name="action" value="<?= $ann['status'] === 'published' ? 'unpublish' : 'publish' ?>">
                            <button type="submit" class="text-xs text-amber-600 hover:underline">
                                <?= $ann['status'] === 'published' ? 'Unpublish' : 'Publish' ?>
                            </button>
                        </form>

                        <form action="../actions/announcement_action.php" method="POST" onsubmit="return confirm('Delete this announcement?');">
                            <input type="hidden" name="announcement_id" value="<?= (int)$ann['announcement_id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="text-xs text-rose-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
