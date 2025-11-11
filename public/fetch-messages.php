<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit();
}

$user_id   = $_SESSION['user_id'];
$pet_id    = intval($_GET['pet_id'] ?? 0);
$seller_id = intval($_GET['seller_id'] ?? 0);

if (!$pet_id || !$seller_id) {
    http_response_code(400);
    exit("Invalid request.");
}

// ✅ Mark messages as read (from other user → current user)
$markSql = "UPDATE messages 
            SET is_read = 1 
            WHERE pet_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0";
$markStmt = $conn->prepare($markSql);
$markStmt->bind_param("iii", $pet_id, $seller_id, $user_id);
$markStmt->execute();
$markStmt->close();

// Fetch conversation
$msgSql = "SELECT m.*, u.username 
           FROM messages m
           JOIN users u ON m.sender_id = u.id
           WHERE m.pet_id = ? AND 
                 ((m.sender_id = ? AND m.receiver_id = ?) OR 
                  (m.sender_id = ? AND m.receiver_id = ?))
           ORDER BY m.created_at ASC";
$stmt = $conn->prepare($msgSql);
$stmt->bind_param("iiiii", $pet_id, $user_id, $seller_id, $seller_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header("Content-Type: application/json");
echo json_encode($messages);
