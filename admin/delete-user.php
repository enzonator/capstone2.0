<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

// Check if id is passed
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php?error=invalid");
    exit;
}

$id = (int) $_GET['id'];

// Prevent deleting the main admin (id=1)
if ($id === 1) {
    header("Location: users.php?error=cannot_delete_admin");
    exit;
}

// Prepare and execute delete
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: users.php?success=deleted");
} else {
    header("Location: users.php?error=failed");
}
exit;
