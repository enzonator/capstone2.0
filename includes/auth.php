<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /catshop/public/login.php");
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: /catshop/public/index.php");
        exit();
    }
}

/*if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ✅ Check if admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// ✅ Require login (redirect if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /catshop/public/login.php");
        exit();
    }
}

// ✅ Require admin (redirect if not admin)
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: /catshop/public/index.php");
        exit();
    }
}*/
