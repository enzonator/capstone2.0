<!-- sidebar.php -->
<?php
// Count pending verification requests
$pending_count = 0;
if (isset($conn)) {
    $count_sql = "SELECT COUNT(*) as count FROM verifications WHERE status = 'Pending'";
    $count_result = $conn->query($count_sql);
    if ($count_result) {
        $count_row = $count_result->fetch_assoc();
        $pending_count = $count_row['count'];
    }
}
?>

<div class="sidebar">
  <div class="sidebar-header">
    <h2>Dashboard</h2>
    <div class="header-accent"></div>
  </div>
  
  <nav class="sidebar-nav">
    <a href="products.php" class="nav-item">
      <span class="nav-icon">🐾</span>
      <span class="nav-text">Manage Pets</span>
    </a>
    <a href="users.php" class="nav-item">
      <span class="nav-icon">👥</span>
      <span class="nav-text">Manage Users</span>
    </a>
    <a href="verify-requests.php" class="nav-item">
      <span class="nav-icon">✓</span>
      <span class="nav-text">Verify Requests</span>
      <?php if ($pending_count > 0): ?>
        <span class="notification-badge"><?= $pending_count ?></span>
      <?php endif; ?>
    </a>
  </nav>

  <!-- Logout Button at the Bottom -->
  <div class="logout-container">
    <a href="/catshop/public/logout.php" class="logout-btn">
      <span class="logout-icon">🚪</span>
      <span>Logout</span>
    </a>
  </div>
</div>

<style>
  body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    background: #faf8f5;
  }
  
  .sidebar {
    width: 260px;
    background: linear-gradient(180deg, #F5E6D3 0%, #E8D5BA 100%);
    color: #5C4A3A;
    height: 100vh;
    position: fixed;
    box-shadow: 4px 0 12px rgba(92, 74, 58, 0.08);
    display: flex;
    flex-direction: column;
    overflow: hidden; /* Prevent scrollbar on sidebar itself */
  }
  
  .sidebar-header {
    padding: 32px 24px 24px;
    border-bottom: 2px solid rgba(92, 74, 58, 0.1);
    flex-shrink: 0;
  }
  
  .sidebar-header h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 600;
    color: #5C4A3A;
    letter-spacing: -0.5px;
  }
  
  .header-accent {
    width: 50px;
    height: 3px;
    background: linear-gradient(90deg, #D4A574, #C19A6B);
    margin-top: 12px;
    border-radius: 2px;
  }
  
  .sidebar-nav {
    padding: 20px 0 20px 0; /* Added bottom padding */
    flex-grow: 1;
    overflow-y: auto; /* Allow scrolling if needed */
    min-height: 0; /* Important for flex child scrolling */
  }
  
  .nav-item {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    text-decoration: none;
    color: #5C4A3A;
    transition: all 0.3s ease;
    margin: 4px 12px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 15px;
    position: relative;
  }
  
  .nav-icon {
    font-size: 20px;
    margin-right: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
  }
  
  .nav-text {
    flex: 1;
  }
  
  .nav-item:hover {
    background: rgba(212, 165, 116, 0.2);
    transform: translateX(4px);
    color: #4A3829;
  }
  
  .nav-item:active {
    transform: translateX(2px);
  }

  /* Notification Badge */
  .notification-badge {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 12px;
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    animation: pulse-badge 2s ease-in-out infinite;
    margin-left: auto;
  }

  @keyframes pulse-badge {
    0%, 100% {
      transform: scale(1);
      box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    }
    50% {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.6);
    }
  }

  .content {
    margin-left: 260px;
    padding: 20px;
    flex-grow: 1;
  }

  /* Logout button styling */
  .logout-container {
    padding: 16px 20px 20px 20px; /* Adjusted padding */
    border-top: 2px solid rgba(92, 74, 58, 0.1);
    flex-shrink: 0;
    margin-top: auto;
  }
  
  .logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px 16px; /* Adjusted padding */
    background: linear-gradient(135deg, #C19A6B, #A67C52);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(161, 124, 82, 0.3);
    box-sizing: border-box; /* Ensure padding is included in width */
  }
  
  .logout-icon {
    font-size: 18px;
  }
  
  .logout-btn:hover {
    background: linear-gradient(135deg, #A67C52, #8B6840);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(161, 124, 82, 0.4);
  }
  
  .logout-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(161, 124, 82, 0.3);
  }

  /* Ensure proper scrollbar styling */
  .sidebar-nav::-webkit-scrollbar {
    width: 6px;
  }

  .sidebar-nav::-webkit-scrollbar-track {
    background: rgba(92, 74, 58, 0.05);
    border-radius: 3px;
  }

  .sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(92, 74, 58, 0.2);
    border-radius: 3px;
  }

  .sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(92, 74, 58, 0.3);
  }
</style>