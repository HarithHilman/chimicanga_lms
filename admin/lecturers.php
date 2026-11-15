<?php
require_once __DIR__ . '/../includes/header.php';

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT * FROM users WHERE role = 'lecturer'";
$params = [];

if ($search !== '') {
    $sql .= " AND (full_name LIKE ? OR staff_student_id LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if (in_array($statusFilter, ['active', 'inactive'], true)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usersList = $stmt->fetchAll();

$editUser = null;
if (isset($_GET['edit'])) {
    $eStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'lecturer'");
    $eStmt->execute([(int)$_GET['edit']]);
    $editUser = $eStmt->fetch();
}

$viewUser = null;
if (isset($_GET['view'])) {
    $vStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'lecturer'");
    $vStmt->execute([(int)$_GET['view']]);
    $viewUser = $vStmt->fetch();
}
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Lecturer Management</h1>
    <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">
        + Add Lecturer
    </button>
</div>

<!-- Search & Filter Bar -->
<form method="GET" class="bg-white p-4 rounded-2xl border border-slate-200 mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, Staff ID, or email..." class="flex-1 min-w-[200px] border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
    <select name="status" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
        <option value="">All Statuses</option>
        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button type="submit" class="bg-slate-900 text-white text-sm font-medium px-4 py-2 rounded-xl">Filter</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
        <a href="lecturers.php" class="text-sm text-slate-500 px-3 py-2">Reset</a>
    <?php endif; ?>
</form>

<?php if ($viewUser): ?>
    <div class="bg-white rounded-2xl border border-indigo-200 p-5 mb-6 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($viewUser['full_name']) ?> (<?= htmlspecialchars($viewUser['staff_student_id']) ?>)</h2>
                <p class="text-xs text-slate-500 mt-1">Email: <?= htmlspecialchars($viewUser['email']) ?> &bull; Department: <?= htmlspecialchars($viewUser['department'] ?? 'N/A') ?> &bull; Phone: <?= htmlspecialchars($viewUser['phone'] ?? 'N/A') ?></p>
                <p class="text-xs text-slate-400 mt-1">Registered: <?= htmlspecialchars($viewUser['created_at']) ?> &bull; Status: <?= strtoupper($viewUser['status']) ?></p>
            </div>
            <a href="lecturers.php" class="text-xs text-slate-500 hover:underline">Close Details</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($editUser): ?>
    <div class="bg-white rounded-2xl border border-amber-200 p-5 mb-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-3">Edit Lecturer: <?= htmlspecialchars($editUser['full_name']) ?></h2>
        <form action="../actions/user_update.php" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <input type="hidden" name="user_id" value="<?= (int)$editUser['user_id'] ?>">
            <input type="hidden" name="redirect" value="../admin/lecturers.php">
            <div>
                <label class="block text-xs font-semibold mb-1">Staff ID</label>
                <input type="text" name="staff_student_id" required value="<?= htmlspecialchars($editUser['staff_student_id']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Full Name</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($editUser['full_name']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Email</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Department</label>
                <input type="text" name="department" value="<?= htmlspecialchars($editUser['department'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-xl">Save Changes</button>
                <a href="lecturers.php" class="text-sm text-slate-500 px-3 py-2">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Users Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                <th class="py-3 px-4">Staff ID</th>
                <th class="py-3 px-4">Full Name</th>
                <th class="py-3 px-4">Email</th>
                <th class="py-3 px-4">Department</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($usersList as $u): ?>
                <tr>
                    <td class="py-3 px-4 font-mono text-xs"><?= htmlspecialchars($u['staff_student_id']) ?></td>
                    <td class="py-3 px-4 font-medium text-slate-900"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="py-3 px-4 text-slate-500"><?= htmlspecialchars($u['department'] ?? '—') ?></td>
                    <td class="py-3 px-4">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $u['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                            <?= ucfirst($u['status']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                        <a href="?view=<?= (int)$u['user_id'] ?>" class="text-xs text-slate-600 hover:underline">View</a>
                        <a href="?edit=<?= (int)$u['user_id'] ?>" class="text-xs text-indigo-600 hover:underline">Edit</a>

                        <form action="../actions/user_toggle_status.php" method="POST" class="inline">
                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                            <input type="hidden" name="redirect" value="../admin/lecturers.php">
                            <button type="submit" class="text-xs text-amber-600 hover:underline">
                                <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>

                        <button onclick="openResetModal(<?= (int)$u['user_id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>')" class="text-xs text-slate-700 hover:underline">Reset PW</button>

                        <form action="../actions/user_delete.php" method="POST" class="inline" onsubmit="return confirm('Delete this account permanently?');">
                            <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                            <input type="hidden" name="redirect" value="../admin/lecturers.php">
                            <button type="submit" class="text-xs text-rose-600 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-bold text-slate-900 mb-4">Create Lecturer Account</h3>
        <form action="../actions/user_create.php" method="POST" class="space-y-3">
            <input type="hidden" name="role" value="lecturer">
            <input type="hidden" name="redirect" value="../admin/lecturers.php">
            <div>
                <label class="block text-xs font-semibold mb-1">Staff ID *</label>
                <input type="text" name="staff_student_id" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Full Name *</label>
                <input type="text" name="full_name" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Email *</label>
                <input type="email" name="email" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Department</label>
                <input type="text" name="department" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Phone</label>
                <input type="text" name="phone" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Initial Password *</label>
                <input type="password" name="password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-xl">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" class="hidden fixed inset-0 bg-slate-900/50 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-xl">
        <h3 class="text-base font-bold text-slate-900 mb-2">Reset Password</h3>
        <p id="resetUserName" class="text-xs text-slate-500 mb-4"></p>
        <form action="../actions/user_reset_password.php" method="POST" class="space-y-3">
            <input type="hidden" name="user_id" id="resetUserId">
            <input type="hidden" name="redirect" value="../admin/lecturers.php">
            <div>
                <label class="block text-xs font-semibold mb-1">New Temporary Password *</label>
                <input type="password" name="new_password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('resetModal').classList.add('hidden')" class="px-4 py-2 text-sm text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium bg-slate-900 text-white rounded-xl">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResetModal(id, name) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUserName').textContent = 'Setting new password for: ' + name;
    document.getElementById('resetModal').classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
