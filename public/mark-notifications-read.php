<?php
session_start();
require_once "../config/db.php";

// Return JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all notifications as read
$updateSql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("i", $user_id);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
}

$updateStmt->close();
$conn->close();
?>