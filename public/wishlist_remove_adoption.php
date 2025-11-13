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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cat_id'])) {
    $cat_id = intval($_POST['cat_id']);

    // Remove from wishlist
    $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND pet_id = ?");
    $delete->bind_param("ii", $user_id, $cat_id);
    $delete->execute();
}

// Redirect back to wishlisted-pets.php
header("Location: wishlisted-pets.php");
exit();
?>