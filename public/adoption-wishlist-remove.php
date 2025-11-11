<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pet_id'])) {
    $user_id = $_SESSION['user_id'];
    $pet_id = intval($_POST['pet_id']);

    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $user_id, $pet_id);
    $stmt->execute();
}

header("Location: wishlist.php");
exit();
