<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// ============================================================================
// 1. GOOD EXCEPTION HANDLING (Global Handler — Prevents Stack Trace Leakage)
// ============================================================================
set_exception_handler(function (Throwable $e): void {
    error_log('[Chimicanga LMS Exception] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>System Error</title><script src="https://cdn.tailwindcss.com"></script></head>'
       . '<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">'
       . '<div class="bg-white border border-rose-200 rounded-2xl p-6 max-w-md w-full shadow-sm">'
       . '<h1 class="text-lg font-bold text-rose-700">Unexpected System Error</h1>'
       . '<p class="text-sm text-slate-600 mt-2">A handled exception occurred while processing your request. The incident has been logged safely without exposing internal details.</p>'
       . '<a href="javascript:history.back()" class="inline-block mt-4 text-xs font-semibold text-indigo-600 hover:underline">&larr; Return to Previous Page</a>'
       . '</div></body></html>';
    exit;
});

$host = 'localhost';
$db   = 'chimicanga_lms';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new RuntimeException('Database service unavailable.');
}

// ============================================================================
// 2. SYNTACTICAL & SEMANTIC VALIDATION + BUFFER OVERFLOW BOUNDS HELPERS
// ============================================================================
function validateBoundedString(string $value, string $fieldName, int $minLen, int $maxLen): string {
    $trimmed = trim($value);
    $len = mb_strlen($trimmed, 'UTF-8');
    if ($len < $minLen) {
        throw new InvalidArgumentException("$fieldName must be at least $minLen character(s).");
    }
    if ($len > $maxLen) {
        throw new OverflowException("$fieldName exceeds maximum allowed buffer length of $maxLen characters.");
    }
    return $trimmed;
}

function validateEmailSyntax(string $email): string {
    $email = validateBoundedString($email, 'Email address', 5, 150);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email syntax format.');
    }
    return strtolower($email);
}

function validateStaffStudentIdSyntax(string $id): string {
    $id = validateBoundedString($id, 'Staff/Student ID', 3, 30);
    if (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $id)) {
        throw new InvalidArgumentException('ID must contain only alphanumeric characters, hyphens, or underscores (3–30 chars).');
    }
    return strtoupper($id);
}

function validatePhoneSyntax(string $phone): string {
    $phone = trim($phone);
    if ($phone === '') {
        return '';
    }
    if (mb_strlen($phone, 'UTF-8') > 30 || !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        throw new InvalidArgumentException('Phone number must be 7–20 digits and valid symbols (+, -, spaces).');
    }
    return $phone;
}

// ============================================================================
// 3. SYSTEM HELPERS
// ============================================================================
function getSetting(PDO $pdo, string $key, string $default = ''): string {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (string)$val : $default;
}

function logActivity(PDO $pdo, ?int $userId, string $action, string $details): void {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$userId, mb_substr($action, 0, 120), mb_substr($details, 0, 255)]);
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}