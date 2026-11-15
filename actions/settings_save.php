<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'lms_name'             => trim($_POST['lms_name'] ?? 'Chimicanga LMS'),
        'lms_description'      => trim($_POST['lms_description'] ?? ''),
        'allowed_file_types'   => strtolower(trim($_POST['allowed_file_types'] ?? 'pdf,doc,docx,zip,jpg,png')),
        'max_upload_mb'        => (string) max(1, (int)($_POST['max_upload_mb'] ?? 10)),
        'min_password_length'  => (string) max(6, (int)($_POST['min_password_length'] ?? 8)),
        'enable_notifications' => isset($_POST['enable_notifications']) ? '1' : '0',
    ];

    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    logActivity($pdo, (int)$currentAdmin['user_id'], 'Updated Settings', 'Modified Chimicanga LMS configuration.');
    setFlash('success', 'System settings saved.');
    header('Location: ../admin/settings.php');
    exit;
}
