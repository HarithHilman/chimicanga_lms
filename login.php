<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
    exit;
}

$lmsName = getSetting($pdo, 'lms_name', 'Chimicanga LMS');
$error = '';
$step = (!empty($_SESSION['mfa_pending_user_id']) && !empty($_SESSION['mfa_otp_code'])) ? 'mfa' : 'credentials';

// Cancel MFA and return to password screen
if (isset($_GET['reset_mfa'])) {
    unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_otp_code'], $_SESSION['mfa_expires_at'], $_SESSION['mfa_attempts']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['auth_step'] ?? 'credentials';

    try {
        if ($action === 'credentials') {
            // Syntactical + Buffer Length Validation
            $email    = validateEmailSyntax($_POST['email'] ?? '');
            $password = validateBoundedString($_POST['password'] ?? '', 'Password', 1, 128);

            // Parameterized query (SQL Injection Prevention)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Pure Bcrypt Hash Verification (Also supports legacy demo 'admin123' ONLY if hash is default Laravel sample hash, then upgrades it to real bcrypt!)
            $isDefaultSampleHash = ($user && $user['password_hash'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' && $password === 'admin123');
            if ($isDefaultSampleHash) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$newHash, $user['user_id']]);
                $user['password_hash'] = $newHash;
            }

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'active') {
                    $error = 'Your account is currently inactive. Please contact the administrator.';
                } else {
                    // Generate Cryptographic 6-Digit MFA OTP
                    session_regenerate_id(true);
                    $_SESSION['mfa_pending_user_id'] = (int) $user['user_id'];
                    $_SESSION['mfa_otp_code']        = (string) random_int(100000, 999999);
                    $_SESSION['mfa_expires_at']      = time() + 300; // 5-minute expiry
                    $_SESSION['mfa_attempts']        = 0;
                    $step = 'mfa';
                }
            } else {
                $error = 'Invalid email address or password.';
            }

        } elseif ($action === 'mfa' && $step === 'mfa') {
            $enteredOtp = validateBoundedString($_POST['otp_code'] ?? '', 'OTP Code', 6, 6);
            if (!preg_match('/^\d{6}$/', $enteredOtp)) {
                throw new InvalidArgumentException('OTP must be a 6-digit numeric code.');
            }

            $_SESSION['mfa_attempts'] = ($_SESSION['mfa_attempts'] ?? 0) + 1;

            if (time() > ($_SESSION['mfa_expires_at'] ?? 0)) {
                unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_otp_code'], $_SESSION['mfa_expires_at'], $_SESSION['mfa_attempts']);
                $step = 'credentials';
                $error = 'MFA code expired. Please sign in again.';
            } elseif ($_SESSION['mfa_attempts'] > 3) {
                unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_otp_code'], $_SESSION['mfa_expires_at'], $_SESSION['mfa_attempts']);
                $step = 'credentials';
                $error = 'Too many failed MFA attempts. Please sign in again.';
            } elseif (hash_equals($_SESSION['mfa_otp_code'], $enteredOtp)) {
                $pendingId = (int) $_SESSION['mfa_pending_user_id'];
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active' LIMIT 1");
                $stmt->execute([$pendingId]);
                $user = $stmt->fetch();

                unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_otp_code'], $_SESSION['mfa_expires_at'], $_SESSION['mfa_attempts']);

                if ($user) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['user_id'];
                    $_SESSION['role']    = $user['role'];

                    logActivity($pdo, (int) $user['user_id'], ucfirst($user['role']) . ' MFA Login', "{$user['full_name']} completed 2-factor authentication.");

                    header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
                    exit;
                }
                $step = 'credentials';
                $error = 'Account unavailable.';
            } else {
                $remaining = 3 - $_SESSION['mfa_attempts'];
                $error = "Invalid verification code. $remaining attempt(s) remaining.";
            }
        }
    } catch (InvalidArgumentException | OverflowException $e) {
        $error = $e->getMessage();
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
            <p class="text-sm text-slate-500 mt-1">
                <?= $step === 'mfa' ? 'Step 2 of 2: Multi-Factor Authentication (MFA)' : 'Step 1 of 2: Sign in with your credentials' ?>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 'credentials'): ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="auth_step" value="credentials">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                    <input type="email" name="email" maxlength="150" required placeholder="e.g. daniyal@chimicanga.edu" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" maxlength="128" required placeholder="••••••••" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm">
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm py-2.5 rounded-xl">
                    Verify Password &rarr;
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                <p class="font-semibold text-slate-700">Demo Accounts (Password: <code class="bg-slate-100 px-1 rounded">admin123</code>):</p>
                <p>&bull; <strong>Admin:</strong> admin@chimicanga.edu</p>
                <p>&bull; <strong>Lecturer:</strong> amina@chimicanga.edu</p>
                <p>&bull; <strong>Student:</strong> daniyal@chimicanga.edu</p>
            </div>
        <?php else: ?>
            <div class="mb-4 px-4 py-3 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs">
                <p class="font-bold">Simulated Authenticator / Email OTP Code:</p>
                <p class="text-lg font-mono font-bold tracking-widest mt-1"><?= htmlspecialchars($_SESSION['mfa_otp_code']) ?></p>
                <p class="text-[11px] text-indigo-700 mt-1">Valid for 5 minutes (Max 3 attempts).</p>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="auth_step" value="mfa">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Enter 6-Digit Verification Code</label>
                    <input type="text" name="otp_code" maxlength="6" pattern="\d{6}" required autofocus placeholder="123456" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono tracking-widest text-center">
                </div>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm py-2.5 rounded-xl">
                    Verify & Complete Login
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="login.php?reset_mfa=1" class="text-xs text-slate-500 hover:underline">&larr; Cancel and use a different account</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>