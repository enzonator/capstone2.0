<?php
// Require authentication
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

// DB connection
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Delete the pet
    $stmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect back to products page
header("Location: /catshop/admin/products.php");
exit();
