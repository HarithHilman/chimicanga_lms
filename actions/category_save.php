<?php
require_once __DIR__ . '/../includes/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catId  = (int) ($_POST['category_id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($name === '') {
        setFlash('error', 'Category name is required.');
        header('Location: ../admin/categories.php');
        exit;
    }

    if ($catId > 0) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE category_id = ?");
        $stmt->execute([$name, $desc, $status, $catId]);
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Updated Category', "Updated category: $name");
        setFlash('success', 'Category updated.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, status) VALUES (?, ?, ?)");
        $stmt->execute([$name, $desc, $status]);
        logActivity($pdo, (int)$currentAdmin['user_id'], 'Created Category', "Created category: $name");
        setFlash('success', 'Category created.');
    }
    header('Location: ../admin/categories.php');
    exit;
}
