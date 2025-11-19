<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id  = $_SESSION['user_id'];
$cat_id   = intval($_GET['cat_id'] ?? 0);
$owner_id = intval($_GET['owner_id'] ?? 0);

if (!$cat_id || !$owner_id) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT m.*, u.username 
        FROM adoption_messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.cat_id = ? AND 
              ((m.sender_id = ? AND m.receiver_id = ?) OR 
               (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([]);
    exit();
}

$stmt->bind_param("iiiii", $cat_id, $user_id, $owner_id, $owner_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($messages);
?>