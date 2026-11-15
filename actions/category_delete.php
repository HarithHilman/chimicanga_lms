<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId = (int) ($_POST['category_id'] ?? 0);

    $check = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE category_id = ?");
    $check->execute([$catId]);
    if ($check->fetchColumn() > 0) {
        setFlash('error', 'Cannot delete category while courses are assigned to it.');
        header('Location: ../admin/categories.php');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->execute([$catId]);
    logActivity($pdo, (int)$currentAdmin['user_id'], 'Deleted Category', "Deleted category ID #$catId");
    setFlash('success', 'Category deleted.');
    header('Location: ../admin/categories.php');
    exit;
}
