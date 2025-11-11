<?php
require_once __DIR__ . '/../includes/init.php';
$username = 'admin';
$email = 'admin@catshop.local';
$hash = password_hash('admin123', PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT IGNORE INTO users (username,email,password,role) VALUES (?,?,?,'admin')");
$stmt->bind_param('sss', $username, $email, $hash);
$stmt->execute();
echo $stmt->affected_rows ? "Admin created. Email: $email / Pass: admin123" : "Admin already exists.";
