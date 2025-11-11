<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "You must be logged in."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id = intval($_POST['pet_id'] ?? 0);

if ($pet_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid pet ID."]);
    exit;
}

// Check if already in wishlist
$check = $conn->prepare("SELECT id FROM wishlist WHERE user_id=? AND pet_id=?");
$check->bind_param("ii", $user_id, $pet_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // Remove from wishlist
    $del = $conn->prepare("DELETE FROM wishlist WHERE user_id=? AND pet_id=?");
    $del->bind_param("ii", $user_id, $pet_id);
    $del->execute();
    echo json_encode(["success" => true, "action" => "removed"]);
} else {
    // Add to wishlist
    $add = $conn->prepare("INSERT INTO wishlist (user_id, pet_id) VALUES (?, ?)");
    $add->bind_param("ii", $user_id, $pet_id);
    $add->execute();
    echo json_encode(["success" => true, "action" => "added"]);
}
