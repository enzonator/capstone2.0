<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id === 0) {
    header("Location: orders.php");
    exit();
}

// Check which column name is used
$checkSql = "SHOW COLUMNS FROM orders LIKE 'buyer_id'";
$checkResult = $conn->query($checkSql);
$userColumn = $checkResult->num_rows > 0 ? 'buyer_id' : 'user_id';

// Check which date column exists
$checkDateSql = "SHOW COLUMNS FROM orders LIKE 'order_date'";
$checkDateResult = $conn->query($checkDateSql);
$dateColumn = $checkDateResult->num_rows > 0 ? 'order_date' : 'created_at';

// Fetch order details - allow both buyer and seller to view
$sql = "SELECT o.*, 
        p.name as pet_name, p.breed, p.type, p.age,
        buyer.username as buyer_name, buyer.email as buyer_email,
        seller.username as seller_name, seller.email as seller_email, seller.id as seller_user_id
        FROM orders o
        JOIN pets p ON o.pet_id = p.id
        JOIN users buyer ON o.$userColumn = buyer.id
        JOIN users seller ON o.seller_id = seller.id
        WHERE o.id = ? AND (o.$userColumn = ? OR o.seller_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $order_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found or you don't have permission to view it.");
}

// Fetch pet image
$imgSql = "SELECT filename FROM pet_images WHERE pet_id = ? LIMIT 1";
$imgStmt = $conn->prepare($imgSql);
$imgStmt->bind_param("i", $order['pet_id']);
$imgStmt->execute();
$imgResult = $imgStmt->get_result();
$imgRow = $imgResult->fetch_assoc();
$order['image'] = $imgRow ? $imgRow['filename'] : 'default-pet.jpg';

// Determine if current user is buyer or seller
$isBuyer = ($order[$userColumn] == $user_id);
$isSeller = ($order['seller_id'] == $user_id);

// Handle status update (only seller can update) - BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $isSeller) {
    $new_status = $_POST['status'];
    $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses) && $new_status !== $order['status']) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Determine payment status based on order status
            $payment_status = 'pending';
            if ($new_status === 'delivered') {
                $payment_status = 'completed';
            } elseif ($new_status === 'cancelled') {
                $payment_status = 'cancelled';
            }
            
            // Update order status AND payment status
            $updateSql = "UPDATE orders SET status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssi", $new_status, $payment_status, $order_id);
            $updateStmt->execute();
            
            // If status is delivered, mark pet as sold
            if ($new_status === 'delivered') {
                $petUpdateSql = "UPDATE pets SET status = 'sold' WHERE id = ?";
                $petUpdateStmt = $conn->prepare($petUpdateSql);
                $petUpdateStmt->bind_param("i", $order['pet_id']);
                $petUpdateStmt->execute();
            }
            
            // If status is cancelled, mark pet as available again
            if ($new_status === 'cancelled') {
                $petUpdateSql = "UPDATE pets SET status = 'available' WHERE id = ?";
                $petUpdateStmt = $conn->prepare($petUpdateSql);
                $petUpdateStmt->bind_param("i", $order['pet_id']);
                $petUpdateStmt->execute();
            }
            
            // Log status change in order_status_history table
            $historySql = "INSERT INTO order_status_history (order_id, status, changed_by, changed_at) 
                          VALUES (?, ?, ?, NOW())";
            $historyStmt = $conn->prepare($historySql);
            $historyStmt->bind_param("isi", $order_id, $new_status, $user_id);
            $historyStmt->execute();
            
            // Create notification for buyer
            $statusMessages = [
                'confirmed' => 'Your order #' . $order_id . ' for ' . $order['pet_name'] . ' has been confirmed by the seller.',
                'shipped' => 'Great news! Your order #' . $order_id . ' for ' . $order['pet_name'] . ' has been shipped!',
                'delivered' => 'Your order #' . $order_id . ' for ' . $order['pet_name'] . ' has been delivered successfully! Payment completed. Enjoy your new pet! 🎉',
                'cancelled' => 'Your order #' . $order_id . ' for ' . $order['pet_name'] . ' has been cancelled by the seller. Payment has been cancelled.'
            ];
            
            if (isset($statusMessages[$new_status])) {
                $notifMessage = $statusMessages[$new_status];
                $notifSql = "INSERT INTO notifications (user_id, message, type, order_id, is_read, created_at) 
                            VALUES (?, ?, ?, ?, 0, NOW())";
                $notifStmt = $conn->prepare($notifSql);
                
                if ($notifStmt) {
                    $statusType = 'order_' . $new_status;
                    $notifStmt->bind_param("issi", $order[$userColumn], $notifMessage, $statusType, $order_id);
                    $notifStmt->execute();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $successMsg = "Order status updated to '" . ucfirst($new_status) . "' successfully!";
            if ($new_status === 'delivered') {
                $successMsg .= " Pet has been marked as sold. Payment completed.";
            } elseif ($new_status === 'cancelled') {
                $successMsg .= " Pet is now available again. Payment cancelled.";
            }
            $_SESSION['success_message'] = $successMsg . " Buyer has been notified.";
            header("Location: order-details.php?id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error_message'] = "Failed to update order status: " . $e->getMessage();
        }
    } else {
        if ($new_status === $order['status']) {
            $_SESSION['error_message'] = "Order is already in '" . ucfirst($new_status) . "' status.";
        } else {
            $_SESSION['error_message'] = "Invalid status selected.";
        }
    }
}

// NOW include the header after all processing
include_once "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Details - CatShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f9f9f9;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .order-details-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .back-btn {
      display: inline-block;
      margin-bottom: 20px;
      color: #667eea;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s;
    }
    .back-btn:hover {
      color: #5568d3;
    }
    .order-header {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    .order-title {
      font-size: 28px;
      font-weight: 700;
      color: #333;
      margin-bottom: 10px;
    }
    .order-meta {
      color: #666;
      font-size: 14px;
    }
    .content-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }
    .main-content, .sidebar {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      padding: 25px;
    }
    .section-title {
      font-size: 20px;
      font-weight: 600;
      color: #333;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f0f0f0;
    }
    .pet-preview {
      display: flex;
      gap: 20px;
      margin-bottom: 30px;
    }
    .pet-image {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border-radius: 10px;
      cursor: pointer;
      transition: transform 0.3s;
    }
    .pet-image:hover {
      transform: scale(1.05);
    }
    .pet-info h3 {
      font-size: 22px;
      color: #333;
      margin-bottom: 10px;
    }
    .pet-detail {
      color: #666;
      font-size: 14px;
      margin-bottom: 5px;
    }
    .info-section {
      margin-bottom: 25px;
    }
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label {
      color: #666;
      font-weight: 500;
    }
    .info-value {
      color: #333;
      font-weight: 500;
      text-align: right;
    }
    .status-badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
    }
    .status-pending {
      background: #fff3cd;
      color: #856404;
    }
    .status-confirmed {
      background: #d1ecf1;
      color: #0c5460;
    }
    .status-shipped {
      background: #cce5ff;
      color: #004085;
    }
    .status-delivered {
      background: #d4edda;
      color: #155724;
    }
    .status-cancelled {
      background: #f8d7da;
      color: #721c24;
    }
    .price-total {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
    }
    .price-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }
    .price-label {
      font-size: 16px;
      color: #666;
    }
    .price-amount {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }
    .total-row {
      border-top: 2px solid #dee2e6;
      padding-top: 15px;
      margin-top: 15px;
    }
    .total-row .price-label {
      font-size: 18px;
      font-weight: 700;
    }
    .total-row .price-amount {
      font-size: 24px;
      color: #667eea;
    }
    .status-update-form {
      margin-top: 20px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 8px;
    }
    .status-select {
      width: 100%;
      padding: 10px;
      border: 2px solid #dee2e6;
      border-radius: 6px;
      margin-bottom: 10px;
      font-size: 14px;
    }
    .update-btn {
      width: 100%;
      padding: 12px;
      background: #667eea;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s;
    }
    .update-btn:hover {
      background: #5568d3;
    }
    .contact-btn {
      width: 100%;
      padding: 12px;
      background: #28a745;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      text-decoration: none;
      display: block;
      text-align: center;
      margin-top: 15px;
      transition: background 0.3s;
    }
    .contact-btn:hover {
      background: #218838;
      color: white;
    }
    .timeline {
      position: relative;
      padding-left: 30px;
    }
    .timeline-item {
      position: relative;
      padding-bottom: 20px;
    }
    .timeline-item::before {
      content: '';
      position: absolute;
      left: -22px;
      top: 5px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #667eea;
      border: 3px solid #f0f0f0;
    }
    .timeline-item::after {
      content: '';
      position: absolute;
      left: -17px;
      top: 17px;
      width: 2px;
      height: calc(100% - 10px);
      background: #e0e0e0;
    }
    .timeline-item:last-child::after {
      display: none;
    }
    .timeline-status {
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
    }
    .timeline-date {
      font-size: 13px;
      color: #666;
    }
    .text-success {
      color: #28a745 !important;
    }
    .text-warning {
      color: #ffc107 !important;
    }
    .text-danger {
      color: #dc3545 !important;
    }
    @media (max-width: 992px) {
      .content-grid {
        grid-template-columns: 1fr;
      }
      .pet-preview {
        flex-direction: column;
      }
      .pet-image {
        width: 100%;
        height: 250px;
      }
    }
  </style>
</head>
<body>

<div class="order-details-container">
  <a href="orders.php" class="back-btn">← Back to Orders</a>

  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($_SESSION['success_message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($_SESSION['error_message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>

  <!-- Order Header -->
  <div class="order-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
      <div>
        <h1 class="order-title">Order #<?= $order['id'] ?></h1>
        <div class="order-meta">
          Placed on <?php 
            $orderDate = isset($order['order_date']) ? $order['order_date'] : $order['created_at'];
            echo date('F d, Y - h:i A', strtotime($orderDate));
          ?>
        </div>
      </div>
      <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
        <?= ucfirst(htmlspecialchars($order['status'])) ?>
      </span>
    </div>
  </div>

  <!-- Content Grid -->
  <div class="content-grid">
    <!-- Main Content -->
    <div class="main-content">
      <!-- Pet Details -->
      <h2 class="section-title">Pet Information</h2>
      <div class="pet-preview">
        <img src="../uploads/<?= htmlspecialchars($order['image']) ?>" 
             alt="<?= htmlspecialchars($order['pet_name']) ?>"
             class="pet-image"
             onclick="window.location.href='pet-details.php?id=<?= $order['pet_id'] ?>'">
        <div class="pet-info">
          <h3><?= htmlspecialchars($order['pet_name']) ?></h3>
          <div class="pet-detail"><strong>Type:</strong> <?= htmlspecialchars($order['type']) ?></div>
          <div class="pet-detail"><strong>Breed:</strong> <?= htmlspecialchars($order['breed']) ?></div>
          <div class="pet-detail"><strong>Age:</strong> <?= htmlspecialchars($order['age']) ?> year(s)</div>
          <div class="pet-detail" style="margin-top: 10px; font-size: 20px; color: #667eea;">
            <strong>₱<?= number_format($order['total_amount'], 2) ?></strong>
          </div>
        </div>
      </div>

      <!-- Delivery Information -->
      <h2 class="section-title">Delivery Information</h2>
      <div class="info-section">
        <div class="info-row">
          <span class="info-label">Full Name</span>
          <span class="info-value"><?= htmlspecialchars($order['full_name']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Email</span>
          <span class="info-value"><?= htmlspecialchars($order['email']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Phone</span>
          <span class="info-value"><?= htmlspecialchars($order['phone']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Address</span>
          <span class="info-value">
            <?= htmlspecialchars($order['address']) ?>, 
            <?= htmlspecialchars($order['city']) ?>
            <?= !empty($order['postal_code']) ? ', ' . htmlspecialchars($order['postal_code']) : '' ?>
          </span>
        </div>
      </div>

      <!-- Payment Information -->
      <h2 class="section-title">Payment Information</h2>
      <div class="info-section">
        <div class="info-row">
          <span class="info-label">Payment Method</span>
          <span class="info-value"><?= strtoupper(htmlspecialchars($order['payment_method'])) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Payment Status</span>
          <span class="info-value">
            <?php 
              // Get payment status from database or determine from order status
              $paymentStatus = 'Pending';
              $paymentClass = 'text-warning';
              
              // First check if payment_status column exists and use it
              if (isset($order['payment_status'])) {
                switch($order['payment_status']) {
                  case 'completed':
                    $paymentStatus = 'Completed';
                    $paymentClass = 'text-success';
                    break;
                  case 'cancelled':
                    $paymentStatus = 'Cancelled';
                    $paymentClass = 'text-danger';
                    break;
                  default:
                    $paymentStatus = 'Pending';
                    $paymentClass = 'text-warning';
                }
              } else {
                // Fallback: determine from order status if column doesn't exist
                if ($order['status'] === 'delivered') {
                  $paymentStatus = 'Completed';
                  $paymentClass = 'text-success';
                } elseif ($order['status'] === 'cancelled') {
                  $paymentStatus = 'Cancelled';
                  $paymentClass = 'text-danger';
                }
              }
            ?>
            <span class="<?= $paymentClass ?>" style="font-weight: 600;">
              <?= $paymentStatus ?>
            </span>
          </span>
        </div>
        <?php if ($paymentStatus === 'Completed'): ?>
          <div class="info-row">
            <span class="info-label">Payment Completed On</span>
            <span class="info-value">
              <?= isset($order['updated_at']) ? date('M d, Y - h:i A', strtotime($order['updated_at'])) : 'N/A' ?>
            </span>
          </div>
        <?php elseif ($paymentStatus === 'Cancelled'): ?>
          <div class="info-row">
            <span class="info-label">Cancelled On</span>
            <span class="info-value">
              <?= isset($order['updated_at']) ? date('M d, Y - h:i A', strtotime($order['updated_at'])) : 'N/A' ?>
            </span>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($order['notes'])): ?>
        <!-- Order Notes -->
        <h2 class="section-title">Order Notes</h2>
        <div class="info-section">
          <p style="color: #666; line-height: 1.6;"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Order Summary -->
      <h2 class="section-title">Order Summary</h2>
      <div class="price-total">
        <div class="price-row">
          <span class="price-label">Subtotal</span>
          <span class="price-amount">₱<?= number_format($order['total_amount'], 2) ?></span>
        </div>
        <div class="price-row">
          <span class="price-label">Delivery Fee</span>
          <span class="price-amount">₱0.00</span>
        </div>
        <div class="price-row total-row">
          <span class="price-label">Total</span>
          <span class="price-amount">₱<?= number_format($order['total_amount'], 2) ?></span>
        </div>
      </div>

      <!-- Contact Information -->
      <h2 class="section-title" style="margin-top: 30px;">
        <?= $isBuyer ? 'Seller' : 'Buyer' ?> Information
      </h2>
      <div class="info-section">
        <div class="info-row">
          <span class="info-label">Name</span>
          <span class="info-value">
            <?= $isBuyer ? htmlspecialchars($order['seller_name']) : htmlspecialchars($order['buyer_name']) ?>
          </span>
        </div>
        <div class="info-row">
          <span class="info-label">Email</span>
          <span class="info-value">
            <?= $isBuyer ? htmlspecialchars($order['seller_email']) : htmlspecialchars($order['buyer_email']) ?>
          </span>
        </div>
      </div>

      <?php if ($isBuyer): ?>
        <a href="message-seller.php?seller_id=<?= $order['seller_user_id'] ?>&pet_id=<?= $order['pet_id'] ?>" 
           class="contact-btn">
          📩 Contact Seller
        </a>
      <?php else: ?>
        <a href="message-seller.php?seller_id=<?= $order[$userColumn] ?>&pet_id=<?= $order['pet_id'] ?>" 
           class="contact-btn">
          📩 Contact Buyer
        </a>
      <?php endif; ?>

      <!-- Status Update (Seller Only) -->
      <?php if ($isSeller): ?>
        <div class="status-update-form">
          <h3 style="font-size: 16px; margin-bottom: 15px; font-weight: 600;">Update Order Status</h3>
          <form method="POST">
            <select name="status" class="status-select" required>
              <option value="">Select Status</option>
              <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
              <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
              <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
              <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button type="submit" name="update_status" class="update-btn">
              Update Status
            </button>
          </form>
        </div>
      <?php endif; ?>

      <!-- Order Timeline -->
      <h2 class="section-title" style="margin-top: 30px;">Order Timeline</h2>
      <div class="timeline">
        <?php
        // Fetch all status changes from history
        $historySql = "SELECT status, changed_at FROM order_status_history 
                       WHERE order_id = ? 
                       ORDER BY changed_at ASC";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bind_param("i", $order_id);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();
        $statusHistory = $historyResult->fetch_all(MYSQLI_ASSOC);
        ?>
        
        <!-- Order Placed (Always shown) -->
        <div class="timeline-item">
          <div class="timeline-status">✓ Order Placed</div>
          <div class="timeline-date">
            <?php 
              $orderDate = isset($order['order_date']) ? $order['order_date'] : $order['created_at'];
              echo date('M d, Y - h:i A', strtotime($orderDate));
            ?>
          </div>
        </div>
        
        <?php if (!empty($statusHistory)): ?>
          <?php foreach ($statusHistory as $history): ?>
            <div class="timeline-item">
              <div class="timeline-status">
                <?php if ($history['status'] === 'cancelled'): ?>
                  ✗ <?= ucfirst($history['status']) ?>
                <?php else: ?>
                  ✓ <?= ucfirst($history['status']) ?>
                <?php endif; ?>
              </div>
              <div class="timeline-date">
                <?= date('M d, Y - h:i A', strtotime($history['changed_at'])) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include_once "../includes/footer.php"; ?>