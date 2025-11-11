<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if there's a success message
if (!isset($_SESSION['order_success'])) {
    header("Location: cart.php");
    exit();
}

$success_message = $_SESSION['order_success'];
unset($_SESSION['order_success']);

$user_id = $_SESSION['user_id'];

// Check which column name is used
$checkSql = "SHOW COLUMNS FROM orders LIKE 'buyer_id'";
$checkResult = $conn->query($checkSql);
$userColumn = $checkResult->num_rows > 0 ? 'buyer_id' : 'user_id';

// Check which date column exists
$checkDateSql = "SHOW COLUMNS FROM orders LIKE 'order_date'";
$checkDateResult = $conn->query($checkDateSql);
$dateColumn = $checkDateResult->num_rows > 0 ? 'order_date' : 'created_at';

// Fetch recent orders (last 5 orders from this session)
$sql = "SELECT o.*, p.name as pet_name, u.username as seller_name 
        FROM orders o
        JOIN pets p ON o.pet_id = p.id
        JOIN users u ON o.seller_id = u.id
        WHERE o.$userColumn = ?
        ORDER BY o.$dateColumn DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_orders = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Success - CatShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .success-container {
      max-width: 700px;
      margin: 60px auto;
      padding: 40px;
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      animation: slideUp 0.6s ease;
    }
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .success-icon {
      width: 120px;
      height: 120px;
      margin: 0 auto 30px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 60px;
      animation: checkmark 0.8s ease;
    }
    @keyframes checkmark {
      0% {
        transform: scale(0) rotate(-45deg);
      }
      50% {
        transform: scale(1.2) rotate(10deg);
      }
      100% {
        transform: scale(1) rotate(0deg);
      }
    }
    .success-title {
      text-align: center;
      font-size: 32px;
      font-weight: 700;
      color: #333;
      margin-bottom: 15px;
    }
    .success-message {
      text-align: center;
      font-size: 18px;
      color: #666;
      margin-bottom: 40px;
      line-height: 1.6;
    }
    .order-details {
      background: #f8f9fa;
      padding: 25px;
      border-radius: 12px;
      margin-bottom: 30px;
    }
    .order-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 0;
      border-bottom: 1px solid #dee2e6;
    }
    .order-item:last-child {
      border-bottom: none;
    }
    .order-label {
      font-weight: 600;
      color: #555;
    }
    .order-value {
      color: #333;
      font-weight: 500;
    }
    .action-buttons {
      display: flex;
      gap: 15px;
      margin-top: 30px;
    }
    .btn-custom {
      flex: 1;
      padding: 15px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 10px;
      border: none;
      transition: all 0.3s ease;
      text-decoration: none;
      text-align: center;
      display: inline-block;
    }
    .btn-primary-custom {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    .btn-primary-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
      color: white;
    }
    .btn-secondary-custom {
      background: white;
      color: #667eea;
      border: 2px solid #667eea;
    }
    .btn-secondary-custom:hover {
      background: #667eea;
      color: white;
      transform: translateY(-2px);
    }
    .info-box {
      background: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
    }
    .info-box p {
      margin: 0;
      color: #856404;
      font-size: 14px;
    }
    .order-summary-title {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 20px;
      color: #333;
    }
    .pet-item {
      display: flex;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #dee2e6;
    }
    .pet-item:last-child {
      border-bottom: none;
    }
    .pet-number {
      width: 30px;
      height: 30px;
      background: #667eea;
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      margin-right: 15px;
      font-size: 14px;
    }
    .pet-info {
      flex: 1;
    }
    .pet-name {
      font-weight: 600;
      color: #333;
      margin-bottom: 3px;
    }
    .pet-seller {
      font-size: 13px;
      color: #666;
    }
    .pet-price {
      font-weight: 600;
      color: #667eea;
    }
  </style>
</head>
<body>

<div class="success-container">
  <!-- Success Icon -->
  <div class="success-icon">
    ✓
  </div>

  <!-- Success Title -->
  <h1 class="success-title">Order Placed Successfully! 🎉</h1>
  
  <!-- Success Message -->
  <p class="success-message">
    <?= htmlspecialchars($success_message) ?>
    <br>
    Your order has been received and is being processed. We'll notify you once the seller confirms your order.
  </p>

  <!-- Order Summary -->
  <?php if (!empty($recent_orders)): ?>
    <div class="order-details">
      <h3 class="order-summary-title">📦 Your Recent Orders</h3>
      <?php 
      $order_total = 0;
      foreach ($recent_orders as $index => $order): 
        $order_total += $order['total_amount'];
      ?>
        <div class="pet-item">
          <div class="pet-number"><?= $index + 1 ?></div>
          <div class="pet-info">
            <div class="pet-name"><?= htmlspecialchars($order['pet_name']) ?></div>
            <div class="pet-seller">Seller: <?= htmlspecialchars($order['seller_name']) ?></div>
          </div>
          <div class="pet-price">₱<?= number_format($order['total_amount'], 2) ?></div>
        </div>
      <?php endforeach; ?>
      
      <div class="order-item" style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #667eea;">
        <span class="order-label" style="font-size: 18px;">Total Amount:</span>
        <span class="order-value" style="font-size: 20px; color: #667eea;">₱<?= number_format($order_total, 2) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <!-- Order Details -->
  <div class="order-details">
    <h3 class="order-summary-title">📋 Order Information</h3>
    <?php if (!empty($recent_orders)): ?>
      <div class="order-item">
        <span class="order-label">Order Date:</span>
        <span class="order-value"><?= date('F d, Y - h:i A', strtotime($recent_orders[0]['order_date'])) ?></span>
      </div>
      <div class="order-item">
        <span class="order-label">Payment Method:</span>
        <span class="order-value"><?= strtoupper(htmlspecialchars($recent_orders[0]['payment_method'])) ?></span>
      </div>
      <div class="order-item">
        <span class="order-label">Delivery Address:</span>
        <span class="order-value">
          <?= htmlspecialchars($recent_orders[0]['address']) ?>, 
          <?= htmlspecialchars($recent_orders[0]['city']) ?>
          <?= !empty($recent_orders[0]['postal_code']) ? ', ' . htmlspecialchars($recent_orders[0]['postal_code']) : '' ?>
        </span>
      </div>
      <div class="order-item">
        <span class="order-label">Status:</span>
        <span class="badge bg-warning text-dark" style="font-size: 14px; padding: 8px 15px;">
          <?= ucfirst(htmlspecialchars($recent_orders[0]['status'])) ?>
        </span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Info Box -->
  <div class="info-box">
    <p>
      <strong>📌 What's Next?</strong><br>
      • The seller will review and confirm your order<br>
      • You'll receive an email notification with order updates<br>
      • Track your order status in "My Orders" section
    </p>
  </div>

  <!-- Action Buttons -->
  <div class="action-buttons">
    <a href="orders.php" class="btn-custom btn-primary-custom">
      📋 View My Orders
    </a>
    <a href="products.php" class="btn-custom btn-secondary-custom">
      🛍️ Continue Shopping
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>