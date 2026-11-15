<?php
require_once __DIR__ . '/../includes/header.php';

$name    = getSetting($pdo, 'lms_name', 'Chimicanga LMS');
$desc    = getSetting($pdo, 'lms_description', '');
$formats = getSetting($pdo, 'allowed_file_types', 'pdf,doc,docx,zip,jpg,png');
$maxMb   = getSetting($pdo, 'max_upload_mb', '10');
$minPass = getSetting($pdo, 'min_password_length', '8');
$notifs  = getSetting($pdo, 'enable_notifications', '1');
?>

<h1 class="text-2xl font-bold text-slate-900 mb-6">System Settings</h1>

<div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm max-w-2xl">
    <form action="../actions/settings_save.php" method="POST" class="space-y-5">
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">LMS Name</label>
            <input type="text" name="lms_name" required value="<?= htmlspecialchars($name) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">LMS Description</label>
            <textarea name="lms_description" rows="2" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm"><?= htmlspecialchars($desc) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Allowed File Formats (comma-separated)</label>
                <input type="text" name="allowed_file_types" required value="<?= htmlspecialchars($formats) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Maximum File Size (MB)</label>
                <input type="number" name="max_upload_mb" min="1" max="100" required value="<?= htmlspecialchars($maxMb) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Minimum Password Length</label>
                <input type="number" name="min_password_length" min="6" max="32" required value="<?= htmlspecialchars($minPass) ?>" class="w-full border border-slate-300 rounded-xl px-3.5 py-2 text-sm">
            </div>
            <div class="pt-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="enable_notifications" value="1" <?= $notifs === '1' ? 'checked' : '' ?> class="rounded border-slate-300 text-indigo-600">
                    <span>Enable System Notifications</span>
                </label>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2.5 rounded-xl">
                Save Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
