<?php
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6">My Administrator Profile</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Personal Information & Profile Picture -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-4">Personal Information</h2>
        <form action="../actions/profile_update.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="mode" value="info">

            <div class="flex items-center gap-4">
                <?php if (!empty($currentAdmin['profile_photo']) && is_file(__DIR__ . '/../' . $currentAdmin['profile_photo'])): ?>
                    <img src="../<?= htmlspecialchars($currentAdmin['profile_photo']) ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover border">
                <?php else: ?>
                    <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-lg">
                        <?= strtoupper(substr($currentAdmin['full_name'], 0, 2)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Update Profile Picture</label>
                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png" class="text-xs">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Admin ID</label>
                <input type="text" disabled value="<?= htmlspecialchars($currentAdmin['staff_student_id']) ?>" class="w-full border bg-slate-50 rounded-xl px-3 py-2 text-sm text-slate-500">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Full Name *</label>
                <input type="text" name="full_name" required value="<?= htmlspecialchars($currentAdmin['full_name']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Email Address *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($currentAdmin['email']) ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1">Department</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($currentAdmin['department'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($currentAdmin['phone'] ?? '') ?>" class="w-full border rounded-xl px-3 py-2 text-sm">
                </div>
            </div>

            <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded-xl">Save Profile</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
        <h2 class="text-base font-bold text-slate-900 mb-4">Change Password</h2>
        <form action="../actions/profile_update.php" method="POST" class="space-y-4">
            <input type="hidden" name="mode" value="password">
            <div>
                <label class="block text-xs font-semibold mb-1">Current Password *</label>
                <input type="password" name="current_password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">New Password *</label>
                <input type="password" name="new_password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Confirm New Password *</label>
                <input type="password" name="confirm_password" required class="w-full border rounded-xl px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-slate-900 text-white text-sm font-medium px-4 py-2 rounded-xl">Update Password</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
