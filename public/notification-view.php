<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$notif_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($notif_id > 0) {
    // Mark ALL notifications as read for this user
    $updateAllSql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $updateAllStmt = $conn->prepare($updateAllSql);
    $updateAllStmt->bind_param("i", $user_id);
    $updateAllStmt->execute();
    $updateAllStmt->close();
    
    // Get notification details
    $notifSql = "SELECT * FROM notifications WHERE id = ? AND user_id = ?";
    $notifStmt = $conn->prepare($notifSql);
    $notifStmt->bind_param("ii", $notif_id, $user_id);
    $notifStmt->execute();
    $result = $notifStmt->get_result();
    $notification = $result->fetch_assoc();
    
    if ($notification) {
        // Redirect based on notification type
        if ($notification['type'] === 'adoption_application') {
            // Get the most recent application for this cat that the user owns
            if (!empty($notification['cat_id'])) {
                $appSql = "SELECT aa.id 
                          FROM adoption_applications aa
                          JOIN adoption_cats ac ON aa.cat_id = ac.id
                          WHERE aa.cat_id = ? AND ac.user_id = ?
                          ORDER BY aa.submitted_at DESC LIMIT 1";
                $appStmt = $conn->prepare($appSql);
                $appStmt->bind_param("ii", $notification['cat_id'], $user_id);
                $appStmt->execute();
                $appResult = $appStmt->get_result();
                
                if ($appResult->num_rows > 0) {
                    $appData = $appResult->fetch_assoc();
                    header("Location: view-adoption-application.php?id=" . $appData['id']);
                    exit();
                } else {
                    // Debug: Log the issue
                    error_log("No application found for cat_id: " . $notification['cat_id'] . " and user_id: " . $user_id);
                    // Fallback to applications list
                    header("Location: manage-adoption-applications.php");
                    exit();
                }
            } else {
                // No cat_id in notification
                error_log("No cat_id in notification: " . $notif_id);
                header("Location: manage-adoption-applications.php");
                exit();
            }
        } elseif ($notification['type'] === 'new_order' && !empty($notification['order_id'])) {
            header("Location: order-details.php?id=" . $notification['order_id']);
            exit();
        } elseif ($notification['type'] === 'new_message' && !empty($notification['pet_id'])) {
            // Redirect to messages page for this specific pet
            header("Location: my-messages.php");
            exit();
        } elseif (strpos($notification['type'], 'order_') === 0 && !empty($notification['order_id'])) {
            // Handle all order-related notifications
            header("Location: order-details.php?id=" . $notification['order_id']);
            exit();
        }
    }
}

// If no specific action, redirect to orders page
header("Location: cart.php");
exit();
?>