<?php
require_once __DIR__ . '/../includes/auth.php';

// 🔒 Only allow admins
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - CatShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }
    .admin-card {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
    }
    .admin-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">⚙️ Admin Dashboard</a>
    <div class="d-flex">
      <a href="/catshop/public/logout.php" class="btn btn-outline-light">Logout</a>
    </div>
  </div>
</nav>

<!-- Dashboard Content -->
<div class="container my-4">
  <h2 class="mb-4 text-center">Welcome, Admin 👋</h2>
  <p class="text-center">Use the tools below to manage the CatShop system.</p>

  <div class="row g-4 mt-3">
    <div class="col-md-6 col-lg-3">
      <a href="/catshop/admin/products.php" class="text-decoration-none text-dark">
        <div class="admin-card">
          <h3>🐾 Manage Pets</h3>
          <p>Add, edit, or remove pets listed in the shop.</p>
        </div>
      </a>
    </div>

    <div class="col-md-6 col-lg-3">
      <a href="/catshop/admin/orders.php" class="text-decoration-none text-dark">
        <div class="admin-card">
          <h3>📦 View Orders</h3>
          <p>Check and manage customer orders.</p>
        </div>
      </a>
    </div>

    <div class="col-md-6 col-lg-3">
      <a href="/catshop/admin/users.php" class="text-decoration-none text-dark">
        <div class="admin-card">
          <h3>👤 Manage Users</h3>
          <p>View and manage user accounts.</p>
        </div>
      </a>
    </div>

    <div class="col-md-6 col-lg-3">
      <a href="/catshop/admin/reports.php" class="text-decoration-none text-dark">
        <div class="admin-card">
          <h3>📊 Reports</h3>
          <p>View sales and adoption reports.</p>
        </div>
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
