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
    
    // Verify that the user owns the cat
    $verifySql = "SELECT aa.*, ac.name as cat_name, ac.user_id as cat_owner_id
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
            
            // Send notification to applicant
            $notif_message = "Your adoption application for " . htmlspecialchars($application['cat_name']) . " has been " . strtolower($new_status);
            $notif_type = "adoption_" . strtolower($new_status);
            
            // Get applicant's user_id by email
            $applicantSql = "SELECT id FROM users WHERE email = ?";
            $applicantStmt = $conn->prepare($applicantSql);
            $applicantStmt->bind_param("s", $application['email']);
            $applicantStmt->execute();
            $applicantResult = $applicantStmt->get_result();
            
            if ($applicantResult->num_rows > 0) {
                $applicantData = $applicantResult->fetch_assoc();
                $applicant_id = $applicantData['id'];
                
                $notifSql = "INSERT INTO notifications (user_id, message, type, cat_id, is_read, created_at) 
                            VALUES (?, ?, ?, ?, 0, NOW())";
                $notifStmt = $conn->prepare($notifSql);
                $notifStmt->bind_param("issi", $applicant_id, $notif_message, $notif_type, $application['cat_id']);
                $notifStmt->execute();
            }
            
            $_SESSION['success_message'] = "Application has been " . strtolower($new_status) . " successfully!";
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