<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/totp.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
    exit;
}

$lmsName = getSetting($pdo, 'lms_name', 'Chimicanga LMS');
$error   = '';
$step    = !empty($_SESSION['mfa_pending_user_id']) ? 'mfa' : 'credentials';

if (isset($_GET['reset_mfa'])) {
    unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_setup_secret'], $_SESSION['mfa_is_setup'], $_SESSION['mfa_attempts']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['auth_step'] ?? 'credentials';

    try {
        if ($action === 'credentials') {
            $email    = validateEmailSyntax($_POST['email'] ?? '');
            $password = validateBoundedString($_POST['password'] ?? '', 'Password', 1, 128);

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

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
                    session_regenerate_id(true);
                    $_SESSION['mfa_pending_user_id'] = (int) $user['user_id'];
                    $_SESSION['mfa_attempts']        = 0;

                    if (empty($user['totp_secret'])) {
                        // First-time TOTP enrollment
                        $_SESSION['mfa_is_setup']     = true;
                        $_SESSION['mfa_setup_secret'] = generateTotpSecret(16);
                    } else {
                        $_SESSION['mfa_is_setup']     = false;
                        $_SESSION['mfa_setup_secret'] = $user['totp_secret'];
                    }
                    $step = 'mfa';
                }
            } else {
                $error = 'Invalid email address or password.';
            }

        } elseif ($action === 'mfa' && $step === 'mfa') {
            $enteredCode = validateBoundedString($_POST['otp_code'] ?? '', 'Authenticator Code', 6, 6);
            if (!preg_match('/^\d{6}$/', $enteredCode)) {
                throw new InvalidArgumentException('Authenticator code must be a 6-digit number.');
            }

            $_SESSION['mfa_attempts'] = ($_SESSION['mfa_attempts'] ?? 0) + 1;

            if ($_SESSION['mfa_attempts'] > 5) {
                unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_setup_secret'], $_SESSION['mfa_is_setup'], $_SESSION['mfa_attempts']);
                $step = 'credentials';
                $error = 'Too many failed MFA attempts. Please sign in again.';
            } else {
                $pendingId = (int) $_SESSION['mfa_pending_user_id'];
                $secret    = (string) ($_SESSION['mfa_setup_secret'] ?? '');
                $isSetup   = !empty($_SESSION['mfa_is_setup']);

                if (verifyTotpCode($secret, $enteredCode, 1)) {
                    // Save newly enrolled TOTP secret permanently to user account
                    if ($isSetup) {
                        $pdo->prepare("UPDATE users SET totp_secret = ? WHERE user_id = ?")->execute([$secret, $pendingId]);
                    }

                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active' LIMIT 1");
                    $stmt->execute([$pendingId]);
                    $user = $stmt->fetch();

                    unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_setup_secret'], $_SESSION['mfa_is_setup'], $_SESSION['mfa_attempts']);

                    if ($user) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = (int) $user['user_id'];
                        $_SESSION['role']    = $user['role'];

                        logActivity($pdo, (int) $user['user_id'], ucfirst($user['role']) . ' TOTP Login', "{$user['full_name']} authenticated via Authenticator App (RFC 6238).");

                        header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
                        exit;
                    }
                    $step = 'credentials';
                    $error = 'Account unavailable.';
                } else {
                    $remaining = 5 - $_SESSION['mfa_attempts'];
                    $error = "Invalid Authenticator code. Ensure your phone's clock is set to automatic. $remaining attempt(s) remaining.";
                }
            }
        }
    } catch (InvalidArgumentException | OverflowException $e) {
        $error = $e->getMessage();
    }
}

$pendingUser = null;
if ($step === 'mfa' && !empty($_SESSION['mfa_pending_user_id'])) {
    $uStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $uStmt->execute([(int)$_SESSION['mfa_pending_user_id']]);
    $pendingUser = $uStmt->fetch();
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
                <?= $step === 'mfa' ? 'Step 2 of 2: Authenticator App Verification (TOTP)' : 'Step 1 of 2: Sign in with your credentials' ?>
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
        <?php else: ?>
            <?php if (!empty($_SESSION['mfa_is_setup']) && $pendingUser): ?>
                <div class="mb-4 p-4 rounded-xl bg-indigo-50 border border-indigo-200 text-center">
                    <p class="text-xs font-bold text-indigo-900">First-Time MFA Setup</p>
                    <p class="text-[11px] text-indigo-700 mt-1">Scan this QR code using <strong>Google Authenticator</strong> or <strong>Microsoft Authenticator</strong>:</p>
                    <img src="<?= htmlspecialchars(getTotpQrUrl($lmsName, $pendingUser['email'], $_SESSION['mfa_setup_secret'])) ?>" alt="TOTP QR Code" class="mx-auto my-3 rounded-lg border border-indigo-200 bg-white p-1.5 w-44 h-44">
                    <p class="text-[11px] text-slate-600">Manual Setup Key:</p>
                    <code class="text-xs font-mono font-bold tracking-wider bg-white px-2.5 py-1 rounded border border-indigo-200 text-indigo-900 inline-block mt-1">
                        <?= htmlspecialchars($_SESSION['mfa_setup_secret']) ?>
                    </code>
                </div>
            <?php else: ?>
                <div class="mb-4 px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-xs">
                    Open your <strong>Authenticator App</strong> on your mobile device and enter the live 6-digit verification code for <strong class="text-slate-900"><?= htmlspecialchars($pendingUser['email'] ?? '') ?></strong>.
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="auth_step" value="mfa">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">6-Digit Authenticator Code</label>
                    <input type="text" name="otp_code" maxlength="6" pattern="\d{6}" required autofocus autocomplete="one-time-code" placeholder="000000" class="w-full border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-mono tracking-widest text-center">
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