<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "You must be logged in."]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $cat_id = intval($_POST['cat_id'] ?? 0);

    if ($cat_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid cat ID."]);
        exit;
    }

    // Check if already in wishlist
    $check = $conn->prepare("SELECT id FROM wishlist WHERE user_id=? AND pet_id=?");
    if (!$check) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $check->bind_param("ii", $user_id, $cat_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // Remove from wishlist
        $del = $conn->prepare("DELETE FROM wishlist WHERE user_id=? AND pet_id=?");
        if (!$del) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        $del->bind_param("ii", $user_id, $cat_id);
        
        if ($del->execute()) {
            echo json_encode(["success" => true, "action" => "removed"]);
        } else {
            throw new Exception("Failed to remove from wishlist: " . $del->error);
        }
    } else {
        // Add to wishlist
        $add = $conn->prepare("INSERT INTO wishlist (user_id, pet_id) VALUES (?, ?)");
        if (!$add) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        $add->bind_param("ii", $user_id, $cat_id);
        
        if ($add->execute()) {
            echo json_encode(["success" => true, "action" => "added"]);
        } else {
            throw new Exception("Failed to add to wishlist: " . $add->error);
        }
    }
} catch (Exception $e) {
    // Log error to PHP error log
    error_log("Wishlist toggle error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>