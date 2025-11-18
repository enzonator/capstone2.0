<?php
session_start();
include '../config/db.php';

// Set header for JSON response
header('Content-Type: application/json');

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if required parameters are present
if (!isset($_POST['feedback_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$feedback_id = (int)$_POST['feedback_id'];
$status = $_POST['status'];

// Validate status
$valid_statuses = ['pending', 'reviewed', 'archived'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Validate feedback_id
if ($feedback_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
    exit;
}

// Update the feedback status
$stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $feedback_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Feedback status updated successfully',
            'new_status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Feedback not found or status unchanged']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>