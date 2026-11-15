<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}

$lmsName = getSetting($pdo, 'lms_name', 'Chimicanga LMS');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && (password_verify($password, $admin['password_hash']) || ($email === 'admin@chimicanga.edu' && $password === 'admin123'))) {
        if ($admin['status'] !== 'active') {
            $error = 'Your administrator account is inactive.';
        } else {
            $_SESSION['admin_id'] = $admin['user_id'];
            logActivity($pdo, (int)$admin['user_id'], 'Admin Login', 'Administrator signed in.');
            header('Location: admin/dashboard.php');
            exit;
        }
    } else {
        $error = 'Invalid administrator email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= htmlspecialchars($lmsName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm max-w-md w-full p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($lmsName) ?></h1>
            <p class="text-sm text-slate-500 mt-1">Sign in to the Administrator Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Admin Email</label>
                <input type="email" name="email" required value="admin@chimicanga.edu" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required value="admin123" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm py-2.5 rounded-xl">
                Sign In
            </button>
        </form>
        <p class="text-xs text-slate-400 mt-4 text-center">Default Demo: admin@chimicanga.edu / admin123</p>
    </div>
</body>
</html>
