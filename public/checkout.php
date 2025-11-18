<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get pet IDs from URL (comma-separated)
$pet_ids = isset($_GET['pet_ids']) ? $_GET['pet_ids'] : '';

if (empty($pet_ids)) {
    header("Location: cart.php");
    exit();
}

// Convert to array and sanitize
$pet_ids_array = array_map('intval', explode(',', $pet_ids));
$placeholders = implode(',', array_fill(0, count($pet_ids_array), '?'));

// Fetch selected pets with their images
$sql = "SELECT p.id, p.name, p.price, p.user_id as seller_id 
        FROM pets p 
        WHERE p.id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($pet_ids_array)), ...$pet_ids_array);
$stmt->execute();
$result = $stmt->get_result();

$checkout_items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    // Fetch first image for each pet
    $imgSql = "SELECT filename FROM pet_images WHERE pet_id = ? LIMIT 1";
    $imgStmt = $conn->prepare($imgSql);
    $imgStmt->bind_param("i", $row['id']);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    $imgRow = $imgResult->fetch_assoc();
    
    $row['image'] = $imgRow ? $imgRow['filename'] : 'default-pet.jpg';
    $checkout_items[] = $row;
    $total += $row['price'];
}

if (empty($checkout_items)) {
    header("Location: cart.php");
    exit();
}

// Fetch user information for pre-filling
$userSql = "SELECT username, email FROM users WHERE id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();

// Handle order submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    
    // Validate Philippine phone number
    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid Philippine number
    $valid_phone = false;
    if (preg_match('/^(09|639)\d{9}$/', $phone_clean)) {
        // Format: 09XXXXXXXXX or 639XXXXXXXXX
        $valid_phone = true;
        // Standardize to 09XXXXXXXXX format
        if (substr($phone_clean, 0, 3) === '639') {
            $phone_clean = '0' . substr($phone_clean, 2);
        }
    } elseif (preg_match('/^9\d{9}$/', $phone_clean)) {
        // Format: 9XXXXXXXXX (missing leading 0)
        $valid_phone = true;
        $phone_clean = '0' . $phone_clean;
    }
    
    // Validate inputs
    if (empty($full_name) || empty($email) || empty($phone) || empty($address) || empty($city)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!$valid_phone) {
        $error_message = "Please enter a valid Philippine mobile number (e.g., 09171234567).";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Create order for each pet (since pets can have different sellers)
            foreach ($checkout_items as $item) {
                // Check which date column exists
                $checkDateCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
                $dateColumn = $checkDateCol->num_rows > 0 ? 'order_date' : 'created_at';
                
                $orderSql = "INSERT INTO orders (buyer_id, seller_id, pet_id, total_amount, 
                            full_name, email, phone, address, city, postal_code, 
                            payment_method, notes, status, payment_status, $dateColumn) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())";
                $orderStmt = $conn->prepare($orderSql);
                
                if ($orderStmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $orderStmt->bind_param("iiidssssssss", 
                    $user_id, 
                    $item['seller_id'], 
                    $item['id'], 
                    $item['price'],
                    $full_name,
                    $email,
                    $phone_clean,
                    $address,
                    $city,
                    $postal_code,
                    $payment_method,
                    $notes
                );
                
                if (!$orderStmt->execute()) {
                    throw new Exception("Execute failed: " . $orderStmt->error);
                }
                
                $order_id = $conn->insert_id;
                
                // Create notification for seller
                $notifMessage = "New order received for " . $item['name'] . " from " . htmlspecialchars($full_name) . ". Order #" . $order_id;
                $notifSql = "INSERT INTO notifications (user_id, message, type, order_id, is_read, created_at) 
                            VALUES (?, ?, 'new_order', ?, 0, NOW())";
                $notifStmt = $conn->prepare($notifSql);
                
                if ($notifStmt) {
                    $notifStmt->bind_param("isi", $item['seller_id'], $notifMessage, $order_id);
                    $notifStmt->execute();
                }
                
                // Remove from cart (check which column exists)
                $checkCartCol = $conn->query("SHOW COLUMNS FROM cart LIKE 'user_id'");
                $cartUserCol = $checkCartCol->num_rows > 0 ? 'user_id' : 'buyer_id';
                
                $removeCartSql = "DELETE FROM cart WHERE $cartUserCol = ? AND pet_id = ?";
                $removeCartStmt = $conn->prepare($removeCartSql);
                $removeCartStmt->bind_param("ii", $user_id, $item['id']);
                $removeCartStmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to success page
            $_SESSION['order_success'] = "Your order has been placed successfully!";
            header("Location: order-success.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error_message = "Failed to place order: " . $e->getMessage();
        }
    }
}

// NOW include the header after all processing is done
include_once "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout - CatShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f9f9f9;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }
    .checkout-container {
      max-width: 1200px;
      margin: 40px auto;
      display: grid;
      grid-template-columns: 1fr 400px;
      gap: 30px;
    }
    .checkout-form {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .order-summary {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      height: fit-content;
      position: sticky;
      top: 20px;
    }
    .summary-item {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px 0;
      border-bottom: 1px solid #eee;
    }
    .summary-item img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
    }
    .summary-item-info {
      flex: 1;
    }
    .summary-item-name {
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 5px;
    }
    .summary-item-price {
      color: #666;
      font-size: 14px;
    }
    .summary-total {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 2px solid #dee2e6;
      font-size: 1.3em;
      font-weight: 700;
      display: flex;
      justify-content: space-between;
    }
    .form-section {
      margin-bottom: 30px;
    }
    .form-section h4 {
      margin-bottom: 20px;
      color: #333;
      font-weight: 600;
      border-bottom: 2px solid #28a745;
      padding-bottom: 10px;
    }
    .form-label {
      font-weight: 500;
      color: #555;
    }
    .required {
      color: #dc3545;
    }
    .payment-option {
      border: 2px solid #dee2e6;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .payment-option:hover {
      border-color: #28a745;
      background-color: #f8f9fa;
    }
    .payment-option input[type="radio"] {
      margin-right: 10px;
    }
    .payment-option.selected {
      border-color: #28a745;
      background-color: #e8f5e9;
    }
    .btn-place-order {
      width: 100%;
      padding: 15px;
      font-size: 18px;
      font-weight: 600;
      background: #28a745;
      border: none;
      border-radius: 8px;
      color: white;
      transition: background 0.3s ease;
    }
    .btn-place-order:hover {
      background: #218838;
    }
    .phone-hint {
      font-size: 12px;
      color: #6c757d;
      margin-top: 5px;
    }
    .is-invalid {
      border-color: #dc3545;
    }
    .invalid-feedback {
      display: block;
      color: #dc3545;
      font-size: 14px;
      margin-top: 5px;
    }
    @media (max-width: 992px) {
      .checkout-container {
        grid-template-columns: 1fr;
      }
      .order-summary {
        position: static;
      }
    }
  </style>
</head>
<body>

<div class="checkout-container">
  <!-- Checkout Form -->
  <div class="checkout-form">
    <h2 class="mb-4">🛍️ Checkout</h2>
    
    <?php if (isset($error_message)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm">
      <!-- Contact Information -->
      <div class="form-section">
        <h4>📞 Contact Information</h4>
        <div class="mb-3">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input type="text" name="full_name" class="form-control" 
                 value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Email <span class="required">*</span></label>
            <input type="email" name="email" class="form-control" 
                   value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number <span class="required">*</span></label>
            <input type="tel" 
                   name="phone" 
                   id="phone" 
                   class="form-control" 
                   placeholder="09171234567"
                   pattern="^(09|\+639|639)\d{9}$"
                   maxlength="13"
                   required>
            <div class="phone-hint">
              📱 Format: 09171234567 
            </div>
            <div id="phoneError" class="invalid-feedback" style="display: none;">
              Please enter a valid Philippine mobile number (e.g., 09171234567)
            </div>
          </div>
        </div>
      </div>

      <!-- Shipping Address -->
      <div class="form-section">
        <h4>📍 Shipping Address</h4>
        <div class="mb-3">
          <label class="form-label">Address <span class="required">*</span></label>
          <input type="text" name="address" class="form-control" 
                 placeholder="Street, Barangay" required>
        </div>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">City <span class="required">*</span></label>
            <input type="text" name="city" class="form-control" 
                   placeholder="City/Municipality" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Postal Code</label>
            <input type="text" name="postal_code" class="form-control" 
                   placeholder="1100" maxlength="4" pattern="\d{4}">
          </div>
        </div>
      </div>

      <!-- Payment Method -->
      <div class="form-section">
        <h4>💳 Payment Method</h4>
        <div class="payment-option" onclick="selectPayment(this, 'cod')">
          <input type="radio" name="payment_method" value="cod" id="cod" required>
          <label for="cod" style="cursor: pointer;">
            <strong>Cash on Delivery (COD)</strong>
            <p class="mb-0 text-muted small">Pay when you receive the pet</p>
          </label>
        </div>
        <div class="payment-option" onclick="selectPayment(this, 'gcash')">
          <input type="radio" name="payment_method" value="gcash" id="gcash">
          <label for="gcash" style="cursor: pointer;">
            <strong>GCash</strong>
            <p class="mb-0 text-muted small">Pay via GCash mobile wallet</p>
          </label>
        </div>
        <div class="payment-option" onclick="selectPayment(this, 'bank')">
          <input type="radio" name="payment_method" value="bank" id="bank">
          <label for="bank" style="cursor: pointer;">
            <strong>Bank Transfer</strong>
            <p class="mb-0 text-muted small">Direct bank deposit</p>
          </label>
        </div>
      </div>

      <!-- Order Notes -->
      <div class="form-section">
        <h4>📝 Order Notes (Optional)</h4>
        <textarea name="notes" class="form-control" rows="4" 
                  placeholder="Any special instructions or notes for the seller..."></textarea>
      </div>

      <button type="submit" name="place_order" class="btn-place-order">
        Place Order - ₱<?= number_format($total, 2) ?>
      </button>
    </form>
  </div>

  <!-- Order Summary -->
  <div class="order-summary">
    <h4 class="mb-4">📦 Order Summary</h4>
    
    <?php foreach ($checkout_items as $item): ?>
      <div class="summary-item">
        <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" 
             alt="<?= htmlspecialchars($item['name']) ?>">
        <div class="summary-item-info">
          <div class="summary-item-name"><?= htmlspecialchars($item['name']) ?></div>
          <div class="summary-item-price">₱<?= number_format($item['price'], 2) ?></div>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="summary-total">
      <span>Total:</span>
      <span>₱<?= number_format($total, 2) ?></span>
    </div>

    <div class="mt-3">
      <a href="cart.php" class="btn btn-outline-secondary w-100">← Back to Cart</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function selectPayment(element, method) {
  // Remove selected class from all options
  document.querySelectorAll('.payment-option').forEach(opt => {
    opt.classList.remove('selected');
  });
  
  // Add selected class to clicked option
  element.classList.add('selected');
  
  // Check the radio button
  document.getElementById(method).checked = true;
}

// Phone number validation and formatting
const phoneInput = document.getElementById('phone');
const phoneError = document.getElementById('phoneError');

phoneInput.addEventListener('input', function(e) {
  // Remove all non-numeric characters except +
  let value = e.target.value.replace(/[^\d+]/g, '');
  
  // Handle different formats
  if (value.startsWith('+63')) {
    // Keep +63 format
    value = '+63' + value.substring(3).replace(/\D/g, '').substring(0, 10);
  } else if (value.startsWith('63')) {
    // Convert 63 to +63
    value = '+63' + value.substring(2).replace(/\D/g, '').substring(0, 10);
  } else if (value.startsWith('09')) {
    // Keep 09 format
    value = '09' + value.substring(2).replace(/\D/g, '').substring(0, 9);
  } else if (value.startsWith('9')) {
    // Add 0 prefix
    value = '09' + value.substring(1).replace(/\D/g, '').substring(0, 9);
  } else {
    // Remove any leading zeros except 09
    value = value.replace(/^0+/, '');
    if (value.length > 0 && !value.startsWith('9')) {
      value = '';
    }
  }
  
  e.target.value = value;
  
  // Validate
  validatePhone();
});

phoneInput.addEventListener('blur', validatePhone);

function validatePhone() {
  const value = phoneInput.value;
  const patterns = [
    /^09\d{9}$/,           // 09171234567
    /^\+639\d{9}$/,        // +639171234567
    /^639\d{9}$/           // 639171234567
  ];
  
  const isValid = patterns.some(pattern => pattern.test(value));
  
  if (value && !isValid) {
    phoneInput.classList.add('is-invalid');
    phoneError.style.display = 'block';
  } else {
    phoneInput.classList.remove('is-invalid');
    phoneError.style.display = 'none';
  }
  
  return isValid;
}

// Form validation
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
  const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
  
  if (!paymentMethod) {
    e.preventDefault();
    alert('Please select a payment method.');
    return false;
  }
  
  // Validate phone number
  if (!validatePhone() && phoneInput.value) {
    e.preventDefault();
    phoneInput.focus();
    phoneInput.classList.add('is-invalid');
    phoneError.style.display = 'block';
    alert('Please enter a valid Philippine mobile number.');
    return false;
  }
});
</script>

</body>
</html>

<?php include_once "../includes/footer.php"; ?>