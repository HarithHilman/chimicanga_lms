<?php
require_once __DIR__ . '/../includes/header.php';

$categories = $pdo->query("
    SELECT cat.*, (SELECT COUNT(*) FROM courses c WHERE c.category_id = cat.category_id) AS course_count
    FROM categories cat
    ORDER BY cat.name ASC
")->fetchAll();

$editCat = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6">Course Category Management</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Create / Edit Category Form -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm h-fit">
        <h2 class="text-base font-bold text-slate-900 mb-4"><?= $editCat ? 'Edit Category' : 'Add New Category' ?></h2>
        <form action="../actions/category_save.php" method="POST" class="space-y-3">
            <input type="hidden" name="category_id" value="<?= (int)($editCat['category_id'] ?? 0) ?>">
            <div>
                <label class="block text-xs font-semibold mb-1">Category Name *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editCat['name'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border rounded-xl px-3 py-2 text-sm"><?= htmlspecialchars($editCat['description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Status</label>
                <select name="status" class="w-full border rounded-xl px-3 py-2 text-sm">
                    <option value="active" <?= ($editCat['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($editCat['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
                    <?= $editCat ? 'Update Category' : 'Create Category' ?>
                </button>
                <?php if ($editCat): ?>
                    <a href="categories.php" class="text-sm text-slate-500 px-3 py-2">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories List -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <th class="py-3 px-4">Category</th>
                    <th class="py-3 px-4">Courses</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="py-3 px-4">
                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($cat['name']) ?></div>
                            <div class="text-xs text-slate-500"><?= htmlspecialchars($cat['description'] ?? '') ?></div>
                        </td>
                        <td class="py-3 px-4 text-xs font-semibold"><?= (int)$cat['course_count'] ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $cat['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                                <?= ucfirst($cat['status']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right space-x-2">
                            <a href="?edit=<?= (int)$cat['category_id'] ?>" class="text-xs text-indigo-600 hover:underline">Edit</a>
                            <form action="../actions/category_delete.php" method="POST" class="inline" onsubmit="return confirm('Delete this category?');">
                                <input type="hidden" name="category_id" value="<?= (int)$cat['category_id'] ?>">
                                <button type="submit" class="text-xs text-rose-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
