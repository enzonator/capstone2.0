<?php
session_start();
require_once "../config/db.php";

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

// Get verification ID and action
$verification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($verification_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    header("Location: verify-requests.php?status=error");
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get user_id from verification request
    $stmt = $conn->prepare("SELECT user_id FROM verifications WHERE id = ? AND status = 'Pending'");
    $stmt->bind_param("i", $verification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Verification request not found or already processed");
    }
    
    $verification = $result->fetch_assoc();
    $user_id = $verification['user_id'];
    $stmt->close();

    if ($action === 'approve') {
        // Update verification request status to Approved
        $stmt = $conn->prepare("UPDATE verifications SET status = 'Approved' WHERE id = ?");
        $stmt->bind_param("i", $verification_id);
        $stmt->execute();
        $stmt->close();

        // Update user's verification_status to 'verified'
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'verified' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Send notification to user
        $notif_message = "🎉 Congratulations! Your verification request has been approved. You are now a verified user!";
        $notif_type = "verification_approved";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmt->bind_param("iss", $user_id, $notif_message, $notif_type);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: verify-requests.php?status=approved");
        exit;

    } elseif ($action === 'reject') {
        // Update verification request status to Rejected
        $stmt = $conn->prepare("UPDATE verifications SET status = 'Rejected' WHERE id = ?");
        $stmt->bind_param("i", $verification_id);
        $stmt->execute();
        $stmt->close();

        // Update user's verification_status back to 'not verified'
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'not verified' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Send notification to user about rejection
        $notif_message = "❌ Your verification request has been declined. Please ensure all requirements are met and try again.";
        $notif_type = "verification_rejected";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmt->bind_param("iss", $user_id, $notif_message, $notif_type);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: verify-requests.php?status=rejected");
        exit;
    }

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("Verification action error: " . $e->getMessage());
    header("Location: verify-requests.php?status=error");
    exit;
}
?>