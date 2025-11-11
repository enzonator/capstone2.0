<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']); // detect current page

// Get cart count for logged-in users
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . "/../config/db.php";
    $user_id = $_SESSION['user_id'];
    $cartSql = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $cartStmt = $conn->prepare($cartSql);
    if ($cartStmt) {
        $cartStmt->bind_param("i", $user_id);
        $cartStmt->execute();
        $cartResult = $cartStmt->get_result();
        $cartRow = $cartResult->fetch_assoc();
        $cartCount = $cartRow['count'];
    }

    // Get notification count and sample notifications
    $notifSql = "SELECT id, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $notifStmt = $conn->prepare($notifSql);
    $notifications = [];
    $unreadCount = 0;
    if ($notifStmt) {
        $notifStmt->bind_param("i", $user_id);
        $notifStmt->execute();
        $notifResult = $notifStmt->get_result();
        while ($row = $notifResult->fetch_assoc()) {
            $notifications[] = $row;
            if (!$row['is_read']) $unreadCount++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CatShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  
  <style>
    /* Reset and base styles to prevent inheritance issues */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      margin: 0 !important;
      padding: 0 !important;
    }

    /* CSS Variables for consistent theming */
    :root {
      --header-bg: #EADDCA;
      --header-text: #5D4E37;
      --accent-color: #8B6F47;
      --hover-color: #A0826D;
      --badge-color: #D2691E;
      --white: #ffffff;
      --light-accent: rgba(139, 111, 71, 0.1);
    }

    /* Navbar base styles with high specificity */
    nav.navbar.catshop-header {
      background-color: var(--header-bg) !important;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
      padding: 1rem 0 !important;
      margin: 0 !important;
      border: none !important;
      position: sticky !important;
      top: 0 !important;
      z-index: 1030 !important;
      width: 100% !important;
    }

    nav.navbar.catshop-header * {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    }

    /* Container adjustments */
    nav.navbar.catshop-header .container {
      max-width: 1320px !important;
      padding-left: 1rem !important;
      padding-right: 1rem !important;
    }

    /* Brand/Logo styles */
    nav.navbar.catshop-header .navbar-brand {
      display: flex !important;
      align-items: center !important;
      font-weight: 700 !important;
      font-size: 1.5rem !important;
      color: var(--header-text) !important;
      transition: transform 0.3s ease !important;
      text-decoration: none !important;
      padding: 0 !important;
      margin: 0 !important;
    }

    nav.navbar.catshop-header .navbar-brand:hover {
      transform: scale(1.05) !important;
      color: var(--header-text) !important;
    }

    nav.navbar.catshop-header .navbar-brand img {
      height: 60px !important;
      width: auto !important;
      margin-right: 12px !important;
      object-fit: contain !important;
    }

    /* Navigation links base */
    nav.navbar.catshop-header .nav-link {
      color: var(--header-text) !important;
      font-weight: 500 !important;
      font-size: 1rem !important;
      padding: 0.5rem 1rem !important;
      transition: all 0.3s ease !important;
      border-radius: 8px !important;
      margin: 0 0.25rem !important;
      text-decoration: none !important;
      background: transparent !important;
      border: none !important;
      cursor: pointer !important;
    }

    nav.navbar.catshop-header .nav-link:hover {
      background-color: var(--light-accent) !important;
      color: var(--accent-color) !important;
      transform: translateY(-2px) !important;
    }

    nav.navbar.catshop-header .nav-link.active {
      background-color: var(--accent-color) !important;
      color: var(--white) !important;
      font-weight: 600 !important;
    }

    /* Icon wrappers for cart and notifications */
    nav.navbar.catshop-header .cart-icon-wrapper,
    nav.navbar.catshop-header .notif-icon-wrapper {
      position: relative !important;
      display: inline-block !important;
    }

    nav.navbar.catshop-header .cart-badge,
    nav.navbar.catshop-header .notif-badge {
      position: absolute !important;
      top: -8px !important;
      right: -10px !important;
      background: var(--badge-color) !important;
      color: var(--white) !important;
      border-radius: 50% !important;
      padding: 3px 7px !important;
      font-size: 11px !important;
      font-weight: bold !important;
      min-width: 22px !important;
      text-align: center !important;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
      animation: pulse 2s infinite !important;
      line-height: 1.2 !important;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    nav.navbar.catshop-header .cart-link,
    nav.navbar.catshop-header .notif-link {
      font-size: 24px !important;
      padding: 8px 12px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      border-radius: 8px !important;
      transition: all 0.3s ease !important;
      background: transparent !important;
      color: var(--header-text) !important;
      border: none !important;
    }

    nav.navbar.catshop-header .cart-link:hover,
    nav.navbar.catshop-header .notif-link:hover {
      background-color: rgba(139, 111, 71, 0.15) !important;
      transform: scale(1.1) !important;
      color: var(--accent-color) !important;
    }

    /* Dropdown container */
    nav.navbar.catshop-header .dropdown {
      position: relative !important;
    }

    /* Dropdown toggle */
    nav.navbar.catshop-header .dropdown-toggle {
      cursor: pointer !important;
    }

    nav.navbar.catshop-header .dropdown-toggle::after {
      margin-left: 0.5rem !important;
      vertical-align: middle !important;
    }

    /* Dropdown menu - hidden by default */
    nav.navbar.catshop-header .dropdown-menu {
      display: none !important;
      position: absolute !important;
      top: 100% !important;
      left: auto !important;
      right: 0 !important;
      border-radius: 12px !important;
      border: 2px solid var(--accent-color) !important;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
      margin-top: 0.5rem !important;
      background-color: var(--white) !important;
      padding: 0.5rem 0 !important;
      z-index: 1050 !important;
      min-width: 10rem !important;
    }

    /* Show dropdown when active */
    nav.navbar.catshop-header .dropdown-menu.show {
      display: block !important;
      animation: slideDown 0.3s ease !important;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    nav.navbar.catshop-header .dropdown-item {
      padding: 0.7rem 1.2rem !important;
      font-weight: 500 !important;
      color: var(--header-text) !important;
      transition: all 0.2s ease !important;
      background: transparent !important;
      text-decoration: none !important;
      display: block !important;
      clear: both !important;
      width: 100% !important;
      border: none !important;
      cursor: pointer !important;
    }

    nav.navbar.catshop-header .dropdown-item:hover {
      background-color: var(--light-accent) !important;
      color: var(--accent-color) !important;
      padding-left: 1.5rem !important;
    }

    nav.navbar.catshop-header .dropdown-item.text-danger {
      color: #dc3545 !important;
    }

    nav.navbar.catshop-header .dropdown-item.text-danger:hover {
      background-color: rgba(220, 53, 69, 0.1) !important;
      color: #dc3545 !important;
    }

    nav.navbar.catshop-header .dropdown-divider {
      border-color: var(--accent-color) !important;
      opacity: 0.3 !important;
      margin: 0.5rem 0 !important;
    }

    /* Notification specific styles */
    nav.navbar.catshop-header .notif-item {
      white-space: normal !important;
      font-size: 14px !important;
      border-bottom: 1px solid rgba(139, 111, 71, 0.1) !important;
      display: block !important;
    }

    nav.navbar.catshop-header .notif-item:last-child {
      border-bottom: none !important;
    }

    nav.navbar.catshop-header .notif-item.unread {
      background-color: #FFF8F0 !important;
      font-weight: 600 !important;
      border-left: 4px solid var(--badge-color) !important;
    }

    nav.navbar.catshop-header .notif-empty {
      text-align: center !important;
      color: #999 !important;
      font-size: 14px !important;
      padding: 20px !important;
      font-style: italic !important;
    }

    /* Mobile toggle button */
    nav.navbar.catshop-header .navbar-toggler {
      border: 2px solid var(--accent-color) !important;
      padding: 0.5rem 0.75rem !important;
      background: transparent !important;
    }

    nav.navbar.catshop-header .navbar-toggler:focus {
      box-shadow: 0 0 0 0.2rem rgba(139, 111, 71, 0.25) !important;
      outline: none !important;
    }

    nav.navbar.catshop-header .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%235D4E37' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
    }

    /* Navbar collapse */
    nav.navbar.catshop-header .navbar-collapse {
      background: transparent !important;
    }

    /* Navbar navigation lists */
    nav.navbar.catshop-header .navbar-nav {
      align-items: center !important;
    }

    /* Visual separator between main nav and user actions */
    nav.navbar.catshop-header .nav-separator {
      display: inline-block !important;
      width: 2px !important;
      height: 30px !important;
      background: var(--accent-color) !important;
      opacity: 0.3 !important;
      margin: 0 0.5rem !important;
    }

    /* Responsive adjustments */
    @media (max-width: 991px) {
      nav.navbar.catshop-header .navbar-brand img {
        height: 50px !important;
      }

      nav.navbar.catshop-header .nav-link {
        margin: 0.25rem 0 !important;
      }

      nav.navbar.catshop-header .navbar-nav {
        align-items: flex-start !important;
      }

      nav.navbar.catshop-header .navbar-collapse {
        background-color: var(--header-bg) !important;
        padding: 1rem !important;
        margin-top: 1rem !important;
        border-radius: 8px !important;
      }
      
      nav.navbar.catshop-header .nav-separator {
        display: none !important;
      }
    }

    @media (max-width: 576px) {
      nav.navbar.catshop-header .navbar-brand img {
        height: 45px !important;
      }

      nav.navbar.catshop-header .navbar-brand {
        font-size: 1.25rem !important;
      }
    }
  </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light catshop-header">
  <div class="container">
    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="../assets/images/catshoplogo.png" alt="CatShop Logo">
      <span>CatShop</span>
    </a>

    <!-- Mobile toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar content -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <!-- All links on the right side -->
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-house-door me-1"></i>Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage == 'products.php' ? 'active' : '' ?>" href="products.php">
            <i class="bi bi-grid me-1"></i>Buy a cat
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage == 'sell.php' ? 'active' : '' ?>" href="sell.php">
            <i class="bi bi-grid me-1"></i>Sell your cat
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $currentPage == 'adoption.php' ? 'active' : '' ?>" href="adoption.php">
            <i class="bi bi-heart me-1"></i>Adopt a cat
          </a>
        </li>
        
        <!-- Visual separator -->
        <li class="nav-item d-none d-lg-block">
          <span class="nav-separator"></span>
        </li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <!-- Wishlist - Only show when logged in -->
          <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'wishlisted-pets.php' ? 'active' : '' ?>" href="wishlisted-pets.php">
              <i class="bi bi-heart-fill me-1"></i>Wishlist
            </a>
          </li>

          <!-- Notifications -->
          <li class="nav-item dropdown">
            <a class="nav-link notif-link" href="#" id="notifDropdown" onclick="toggleDropdown(event, 'notifDropdown')">
              <span class="notif-icon-wrapper">
                <i class="bi bi-bell-fill"></i>
                <?php if (!empty($unreadCount)): ?>
                  <span class="notif-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
              </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" id="notifDropdownMenu" style="width: 320px; max-height: 400px; overflow-y: auto;">
              <li class="px-3 py-2">
                <h6 class="mb-0" style="color: var(--accent-color);">
                  <i class="bi bi-bell me-2"></i>Notifications
                </h6>
              </li>
              <li><hr class="dropdown-divider"></li>
              <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notif): ?>
                  <li>
                    <a class="dropdown-item notif-item <?= !$notif['is_read'] ? 'unread' : '' ?>" href="notification-view.php?id=<?= $notif['id'] ?>">
                      <div><?= htmlspecialchars($notif['message']) ?></div>
                      <small class="text-muted">
                        <i class="bi bi-clock me-1"></i><?= date("M d, Y h:i A", strtotime($notif['created_at'])) ?>
                      </small>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li><div class="notif-empty">
                  <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                  <div>No notifications yet</div>
                </div></li>
              <?php endif; ?>
            </ul>
          </li>

          <!-- Cart Icon -->
          <li class="nav-item">
            <a class="nav-link cart-link <?= $currentPage == 'cart.php' ? 'active' : '' ?>" href="cart.php" title="Shopping Cart">
              <span class="cart-icon-wrapper">
                <i class="bi bi-cart-fill"></i>
                <?php if ($cartCount > 0): ?>
                  <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
              </span>
            </a>
          </li>

          <!-- User Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" onclick="toggleDropdown(event, 'userDropdown')">
              <i class="bi bi-person-circle me-1"></i>
              <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" id="userDropdownMenu">
              <li>
                <a class="dropdown-item" href="profile.php">
                  <i class="bi bi-person me-2"></i>My Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="orders.php">
                  <i class="bi bi-box-seam me-2"></i>My Orders
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'login.php' ? 'active' : '' ?>" href="login.php">
              <i class="bi bi-box-arrow-in-right me-1"></i>Login
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'register.php' ? 'active' : '' ?>" href="register.php">
              <i class="bi bi-person-plus me-1"></i>Register
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">

<!-- Custom Dropdown Script - Works without Bootstrap JS -->
<script>
// Custom dropdown toggle function that works on all pages
function toggleDropdown(event, dropdownId) {
  event.preventDefault();
  event.stopPropagation();
  
  const menuId = dropdownId + 'Menu';
  const menu = document.getElementById(menuId);
  
  if (!menu) return;
  
  // Close all other dropdowns first
  document.querySelectorAll('.dropdown-menu').forEach(function(otherMenu) {
    if (otherMenu.id !== menuId) {
      otherMenu.classList.remove('show');
    }
  });
  
  // Check if we're opening or closing the dropdown
  const isOpening = !menu.classList.contains('show');
  
  // Toggle current dropdown
  menu.classList.toggle('show');
  
  // If opening notification dropdown, mark all as read
  if (isOpening && dropdownId === 'notifDropdown') {
    markNotificationsAsRead();
  }
}

// Function to mark all notifications as read
function markNotificationsAsRead() {
  fetch('mark-notifications-read.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove unread styling from all notifications
        document.querySelectorAll('.notif-item.unread').forEach(function(item) {
          item.classList.remove('unread');
        });
        
        // Remove or hide the notification badge
        const notifBadge = document.querySelector('.notif-badge');
        if (notifBadge) {
          notifBadge.style.display = 'none';
        }
        
        console.log('All notifications marked as read');
      }
    })
    .catch(error => {
      console.error('Error marking notifications as read:', error);
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
  const isDropdownClick = event.target.closest('.dropdown');
  
  if (!isDropdownClick) {
    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
      menu.classList.remove('show');
    });
  }
});

// Prevent dropdown from closing when clicking inside (except on links)
document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
  menu.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-item')) {
      e.stopPropagation();
    }
  });
});

// Close dropdown on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
      menu.classList.remove('show');
    });
  }
});

console.log('Custom dropdown system loaded');
</script>

<!-- Load Bootstrap JS at the very end for other Bootstrap features -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>