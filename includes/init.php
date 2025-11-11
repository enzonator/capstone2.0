<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../config/db.php';

function isLoggedIn() {
  return isset($_SESSION['user']);
}

function isAdmin() {
  return isLoggedIn() && $_SESSION['user']['role'] === 'admin';
}

function requireLogin() {
  if (!isLoggedIn()) {
    header('Location: /catshop/public/login.php');
    exit;
  }
}

function csrf_token() {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function verify_csrf($token) {
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
