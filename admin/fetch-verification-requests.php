<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Fetch all verification requests
$sql = "SELECT v.id, v.user_id, v.id_image, v.status, v.created_at, 
               u.username, u.email, u.verification_status
        FROM verifications v
        JOIN users u ON v.user_id = u.id
        ORDER BY v.created_at DESC";

$result = $conn->query($sql);

$requests = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'requests' => $requests
]);
?>