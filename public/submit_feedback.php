<?php
session_start();
header('Content-Type: application/json');

// Database connection settings
$host = 'localhost';
$dbname = 'catshop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to submit feedback']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user has already submitted feedback
$checkStmt = $pdo->prepare("SELECT id FROM feedback WHERE user_id = ? LIMIT 1");
$checkStmt->execute([$user_id]);

if ($checkStmt->fetch()) {
    echo json_encode([
        'success' => false, 
        'already_submitted' => true,
        'message' => 'You have already submitted feedback. Thank you!'
    ]);
    exit();
}

// Validate input
if (!isset($_POST['rating']) || !isset($_POST['message'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT);
$message = trim($_POST['message']);

// Validate rating (1-5)
if ($rating === false || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit();
}

// Validate message length
if (empty($message) || strlen($message) > 500) {
    echo json_encode(['success' => false, 'message' => 'Message must be between 1 and 500 characters']);
    exit();
}

// Sanitize message
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

try {
    // Insert feedback
    $stmt = $pdo->prepare("
        INSERT INTO feedback (user_id, rating, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$user_id, $rating, $message]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thank you for your feedback! Your input helps us improve.'
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
}
?>