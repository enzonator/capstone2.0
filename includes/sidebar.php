<?php
// Start session and include DB connection
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/db.php";

// Default unread message count
$unread_count = 0;

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    // Get unread messages count
    $sqlUnread = "SELECT COUNT(*) AS cnt 
                  FROM messages 
                  WHERE receiver_id = ? AND is_read = 0";
    $stmtUnread = $conn->prepare($sqlUnread);
    $stmtUnread->bind_param("i", $uid);
    $stmtUnread->execute();
    $resultUnread = $stmtUnread->get_result()->fetch_assoc();
    $unread_count = $resultUnread['cnt'] ?? 0;
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
.user-sidebar {
    width: 260px;
    min-height: 100vh;
    background: linear-gradient(135deg, #EADDCA 0%, #f5ebe0 100%);
    padding: 30px 20px;
    border-right: 2px solid rgba(139, 104, 71, 0.15);
    box-shadow: 2px 0 15px rgba(139, 104, 71, 0.08);
    position: relative;
}

.user-sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #8b6847, #c9a882, #8b6847);
}

.sidebar-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid rgba(139, 104, 71, 0.2);
}

.user-sidebar h2 {
    margin: 0;
    color: #5a4a3a;
    font-size: 22px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.user-sidebar a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    text-decoration: none;
    color: #5a4a3a;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
    background: rgba(255, 255, 255, 0.4);
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}

.user-sidebar a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: #8b6847;
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.user-sidebar a:hover {
    background: rgba(139, 104, 71, 0.15);
    border-color: rgba(139, 104, 71, 0.3);
    transform: translateX(4px);
    color: #3d2f23;
}

.user-sidebar a:hover::before {
    transform: scaleY(1);
}

.user-sidebar a.active {
    background: rgba(139, 104, 71, 0.25);
    border-color: rgba(139, 104, 71, 0.4);
    color: #3d2f23;
    font-weight: 600;
}

.user-sidebar a.active::before {
    transform: scaleY(1);
}

.link-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

.link-icon {
    font-size: 18px;
}

/* Badge */
.badge {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 9px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(231, 76, 60, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .user-sidebar {
        width: 100%;
        min-height: auto;
        border-right: none;
        border-bottom: 2px solid rgba(139, 104, 71, 0.15);
    }
    
    .user-sidebar::before {
        height: 3px;
    }
}
</style>

<div class="user-sidebar">
    <div class="sidebar-header">
        <h2>📋 User Menu</h2>
    </div>
    
    <nav class="sidebar-nav">
        <a href="profile.php" class="<?= $current_page === 'profile.php' ? 'active' : '' ?>">
            <span class="link-content">
                <span class="link-icon">👤</span>
                <span>My Profile</span>
            </span>
        </a>
        
        <a href="wishlisted-pets.php" class="<?= $current_page === 'wishlisted-pets.php' ? 'active' : '' ?>">
            <span class="link-content">
                <span class="link-icon">❤️</span>
                <span>Wishlisted Pets</span>
            </span>
        </a>
        
        <a href="my-messages.php" class="<?= $current_page === 'my-messages.php' ? 'active' : '' ?>">
            <span class="link-content">
                <span class="link-icon">💬</span>
                <span>My Messages</span>
            </span>
            <?php if ($unread_count > 0): ?>
                <span class="badge"><?= $unread_count; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="cart.php" class="<?= $current_page === 'cart.php' ? 'active' : '' ?>">
            <span class="link-content">
                <span class="link-icon">🛒</span>
                <span>Cart</span>
            </span>
        </a>

        <a href="my-listed-pets.php" class="<?= $current_page === 'my-listed-pets.php' ? 'active' : '' ?>">
            <span class="link-content">
                <span class="link-icon">📋</span>
                <span>My listed pets</span>
            </span>
        </a>

        <a href="orders.php" class="<?= $current_page === 'orders.php' ? 'active' : '' ?>">
            <span class="link-content">
                <span class="link-icon">🛒</span>
                <span>Orders</span>
            </span>
        </a>
    </nav>
</div>