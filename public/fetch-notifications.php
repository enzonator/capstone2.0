<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

require_once "../config/db.php";

$user_id = $_SESSION['user_id'];

// Get the last 5 notifications
$notifSql = "SELECT id, message, is_read, created_at, type 
             FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT 5";

$notifStmt = $conn->prepare($notifSql);
if (!$notifStmt) {
    echo json_encode(['error' => 'Database error']);
    exit();
}

$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$result = $notifStmt->get_result();

$notifications = [];
$unreadCount = 0;

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    if (!$row['is_read']) {
        $unreadCount++;
    }
}

$notifStmt->close();

// Return JSON response
echo json_encode([
    'notifications' => $notifications,
    'unreadCount' => $unreadCount,
    'success' => true
]);
?>