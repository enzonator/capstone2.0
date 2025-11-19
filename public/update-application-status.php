<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $application_id = intval($_POST['application_id']);
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    // Verify that the user owns the cat and get application details
    $verifySql = "SELECT aa.*, ac.name as cat_name, ac.user_id as cat_owner_id, aa.user_id as applicant_user_id
                  FROM adoption_applications aa
                  JOIN adoption_cats ac ON aa.cat_id = ac.id
                  WHERE aa.id = ? AND ac.user_id = ?";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->bind_param("ii", $application_id, $user_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows > 0) {
        $application = $verifyResult->fetch_assoc();
        
        // Update the application status
        $updateSql = "UPDATE adoption_applications 
                     SET status = ?, admin_notes = ?, updated_at = NOW() 
                     WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ssi", $new_status, $admin_notes, $application_id);
        
        if ($updateStmt->execute()) {
            // Update cat status if approved
            if ($new_status === 'Approved') {
                $catUpdateSql = "UPDATE adoption_cats SET status = 'Approved' WHERE id = ?";
                $catUpdateStmt = $conn->prepare($catUpdateSql);
                $catUpdateStmt->bind_param("i", $application['cat_id']);
                $catUpdateStmt->execute();
            } elseif ($new_status === 'Rejected') {
                // Optionally set cat back to Available
                $catUpdateSql = "UPDATE adoption_cats SET status = 'Available' WHERE id = ?";
                $catUpdateStmt = $conn->prepare($catUpdateSql);
                $catUpdateStmt->bind_param("i", $application['cat_id']);
                $catUpdateStmt->execute();
            }
            
            // Determine applicant_user_id - try from aa.user_id first, fallback to email lookup
            $applicant_user_id = null;
            
            // If user_id exists in adoption_applications table
            if (!empty($application['applicant_user_id'])) {
                $applicant_user_id = $application['applicant_user_id'];
            } else {
                // Fallback: Get applicant's user_id by email
                $applicantSql = "SELECT id FROM users WHERE email = ?";
                $applicantStmt = $conn->prepare($applicantSql);
                $applicantStmt->bind_param("s", $application['email']);
                $applicantStmt->execute();
                $applicantResult = $applicantStmt->get_result();
                
                if ($applicantResult->num_rows > 0) {
                    $applicantData = $applicantResult->fetch_assoc();
                    $applicant_user_id = $applicantData['id'];
                }
            }
            
            // Send notification to applicant if we have their user_id
            if ($applicant_user_id) {
                $cat_name = $application['cat_name'];
                $cat_id = $application['cat_id'];
                
                // Create appropriate notification message and type
                if ($new_status === 'Approved') {
                    $notif_message = "Great news! Your adoption application for {$cat_name} has been approved. Click to start messaging with the pet owner.";
                    $notif_type = 'adoption_approved';
                } elseif ($new_status === 'Rejected') {
                    $notif_message = "Your adoption application for {$cat_name} has been reviewed. Click to view details.";
                    $notif_type = 'adoption_rejected';
                } else {
                    $notif_message = "Your adoption application for {$cat_name} has been updated to: {$new_status}";
                    $notif_type = 'adoption_status_update';
                }
                
                // Insert notification with application_id for proper routing
                $notifSql = "INSERT INTO notifications (user_id, message, type, cat_id, application_id, is_read, created_at) 
                            VALUES (?, ?, ?, ?, ?, 0, NOW())";
                $notifStmt = $conn->prepare($notifSql);
                $notifStmt->bind_param("issii", $applicant_user_id, $notif_message, $notif_type, $cat_id, $application_id);
                $notifStmt->execute();
            }
            
            $_SESSION['success_message'] = "Application has been " . strtolower($new_status) . " successfully! The applicant has been notified.";
        } else {
            $_SESSION['error_message'] = "Error updating application status.";
        }
    } else {
        $_SESSION['error_message'] = "You don't have permission to update this application.";
    }
}

header("Location: view-adoption-application.php?id=" . $application_id);
exit();
?>