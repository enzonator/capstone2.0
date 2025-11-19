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
        // Handle adoption application approval - redirect to messaging
        if ($notification['type'] === 'adoption_approved' && !empty($notification['application_id'])) {
            // Get the cat owner's ID from the application
            $appSql = "SELECT ac.user_id as cat_owner_id, aa.cat_id 
                      FROM adoption_applications aa
                      JOIN adoption_cats ac ON aa.cat_id = ac.id
                      WHERE aa.id = ?";
            $appStmt = $conn->prepare($appSql);
            $appStmt->bind_param("i", $notification['application_id']);
            $appStmt->execute();
            $appResult = $appStmt->get_result();
            
            if ($appResult->num_rows > 0) {
                $appData = $appResult->fetch_assoc();
                // Redirect to message-adopter page with cat owner info
                header("Location: message-adopter.php?cat_id=" . $appData['cat_id'] . "&owner_id=" . $appData['cat_owner_id']);
                exit();
            }
        }
        
        // Handle adoption application rejection or other status updates
        if (($notification['type'] === 'adoption_rejected' || $notification['type'] === 'adoption_status_update') 
            && !empty($notification['application_id'])) {
            // Redirect to view the application details
            header("Location: view-my-application.php?id=" . $notification['application_id']);
            exit();
        }
        
        // Handle original adoption application notification (for cat owners)
        if ($notification['type'] === 'adoption_application') {
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
                    header("Location: manage-adoption-applications.php");
                    exit();
                }
            }
        }
        
        // Handle order notifications
        if ($notification['type'] === 'new_order' && !empty($notification['order_id'])) {
            header("Location: order-details.php?id=" . $notification['order_id']);
            exit();
        }
        
        // Handle adoption message notifications
        if ($notification['type'] === 'adoption_message') {
            if (!empty($notification['application_id'])) {
                // Get the application details to determine who should see what
                $appSql = "SELECT aa.user_id as applicant_user_id, ac.user_id as cat_owner_id, aa.cat_id
                          FROM adoption_applications aa
                          JOIN adoption_cats ac ON aa.cat_id = ac.id
                          WHERE aa.id = ?";
                $appStmt = $conn->prepare($appSql);
                $appStmt->bind_param("i", $notification['application_id']);
                $appStmt->execute();
                $appResult = $appStmt->get_result();
                
                if ($appResult->num_rows > 0) {
                    $appData = $appResult->fetch_assoc();
                    
                    // If current user is the cat owner, redirect to message-cat-owner
                    if ($user_id == $appData['cat_owner_id']) {
                        header("Location: message-cat-owner.php?cat_id=" . $appData['cat_id'] . "&adopter_id=" . $appData['applicant_user_id']);
                        exit();
                    }
                    // If current user is the adopter, redirect to message-adopter
                    elseif ($user_id == $appData['applicant_user_id']) {
                        header("Location: message-adopter.php?cat_id=" . $appData['cat_id'] . "&owner_id=" . $appData['cat_owner_id']);
                        exit();
                    }
                }
            } 
            // Fallback: Try to use cat_id if application_id is missing
            elseif (!empty($notification['cat_id'])) {
                // Find the approved application for this cat and user
                $appSql = "SELECT aa.id, aa.user_id as applicant_user_id, ac.user_id as cat_owner_id, aa.cat_id
                          FROM adoption_applications aa
                          JOIN adoption_cats ac ON aa.cat_id = ac.id
                          WHERE aa.cat_id = ? AND (aa.user_id = ? OR ac.user_id = ?) AND aa.status = 'Approved'
                          ORDER BY aa.updated_at DESC LIMIT 1";
                $appStmt = $conn->prepare($appSql);
                $appStmt->bind_param("iii", $notification['cat_id'], $user_id, $user_id);
                $appStmt->execute();
                $appResult = $appStmt->get_result();
                
                if ($appResult->num_rows > 0) {
                    $appData = $appResult->fetch_assoc();
                    
                    // If current user is the cat owner, redirect to message-cat-owner
                    if ($user_id == $appData['cat_owner_id']) {
                        header("Location: message-cat-owner.php?cat_id=" . $appData['cat_id'] . "&adopter_id=" . $appData['applicant_user_id']);
                        exit();
                    }
                    // If current user is the adopter, redirect to message-adopter
                    elseif ($user_id == $appData['applicant_user_id']) {
                        header("Location: message-adopter.php?cat_id=" . $appData['cat_id'] . "&owner_id=" . $appData['cat_owner_id']);
                        exit();
                    }
                }
            }
        }
        
        // Handle all order-related notifications
        if (strpos($notification['type'], 'order_') === 0 && !empty($notification['order_id'])) {
            header("Location: order-details.php?id=" . $notification['order_id']);
            exit();
        }
    }
}

// If no specific action, redirect to orders page
header("Location: verify.php");
exit();
?>