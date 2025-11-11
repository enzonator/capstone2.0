<?php
session_start();
require_once "../config/db.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['pet_id'])) {
    $pet_id = intval($_POST['pet_id']);

    // Check if already in wishlist
    $check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND pet_id = ?");
    $check->bind_param("ii", $user_id, $pet_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // If exists, remove from wishlist
        $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND pet_id = ?");
        $delete->bind_param("ii", $user_id, $pet_id);
        $delete->execute();
    } else {
        // If not, add to wishlist
        $insert = $conn->prepare("INSERT INTO wishlist (user_id, pet_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $pet_id);
        $insert->execute();
    }
}

// Redirect back to products.php
header("Location: products.php");
exit();
