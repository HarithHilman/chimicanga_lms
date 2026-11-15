<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
    exit;
}

$lmsName = getSetting($pdo, 'lms_name', 'Chimicanga LMS');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $demoEmails = ['admin@chimicanga.edu', 'amina@chimicanga.edu', 'daniyal@chimicanga.edu'];
    $validDemoPass = in_array($email, $demoEmails, true) && $password === 'admin123';

    if ($user && (password_verify($password, $user['password_hash']) || $validDemoPass)) {
        if ($user['status'] !== 'active') {
            $error = 'Your account is currently inactive. Please contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['role']    = $user['role'];

            logActivity($pdo, (int) $user['user_id'], ucfirst($user['role']) . ' Login', "{$user['full_name']} signed in.");

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }
    } else {
        $error = 'Invalid email address or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= htmlspecialchars($lmsName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm max-w-md w-full p-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($lmsName) ?></h1>
            <p class="text-sm text-slate-500 mt-1">Sign in with your Student, Lecturer, or Admin account</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" required placeholder="e.g. daniyal@chimicanga.edu" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm py-2.5 rounded-xl">
                Sign In
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-1">
            <p class="font-semibold text-slate-700">Demo Accounts (Password: <code class="bg-slate-100 px-1 rounded">admin123</code>):</p>
            <p>&bull; <strong>Admin:</strong> admin@chimicanga.edu</p>
            <p>&bull; <strong>Lecturer:</strong> amina@chimicanga.edu</p>
            <p>&bull; <strong>Student:</strong> daniyal@chimicanga.edu</p>
        </div>
    </div>
</body>
</html>