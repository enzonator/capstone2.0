<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Catshop Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .navbar-brand {
      font-weight: bold;
      font-size: 1.3rem;
    }
    .nav-link.active {
      font-weight: 600;
      border-bottom: 2px solid #fff;
    }
    .sidebar {
      min-height: 100vh;
      background: #343a40;
    }
    .sidebar .nav-link {
      color: #adb5bd;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      color: #fff;
      background-color: #495057;
      border-radius: 6px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand" href="/catshop/admin/index.php">⚙️ Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? ' active' : '' ?>" href="/catshop/admin/index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'products.php' ? ' active' : '' ?>" href="/catshop/admin/products.php">Manage Pets</a></li>
        <li class="nav-item"><a class="nav-link<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? ' active' : '' ?>" href="/catshop/admin/users.php">Users</a></li>
      </ul>
      <div class="d-flex">
        <span class="navbar-text me-3 text-light">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
        <a href="/catshop/public/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid mt-4">
