<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check which column name is used for user
$checkUserCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'buyer_id'");
$userColumn = $checkUserCol->num_rows > 0 ? 'buyer_id' : 'user_id';

// Check which date column exists
$checkDateCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
$dateColumn = $checkDateCol->num_rows > 0 ? 'order_date' : 'created_at';

// Fetch all orders for the current user
$sql = "SELECT o.*, 
        p.name as pet_name, p.breed, p.type, 
        u.username as seller_name, u.email as seller_email,
        o.$dateColumn as order_timestamp
        FROM orders o
        JOIN pets p ON o.pet_id = p.id
        JOIN users u ON o.seller_id = u.id
        WHERE o.$userColumn = ?
        ORDER BY o.$dateColumn DESC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Fetch pet images for orders
foreach ($orders as &$order) {
    $imgSql = "SELECT filename FROM pet_images WHERE pet_id = ? LIMIT 1";
    $imgStmt = $conn->prepare($imgSql);
    $imgStmt->bind_param("i", $order['pet_id']);
    $imgStmt->execute();
    $imgResult = $imgStmt->get_result();
    $imgRow = $imgResult->fetch_assoc();
    $order['image'] = $imgRow ? $imgRow['filename'] : 'default-pet.jpg';
}

// Count orders by status
$statusCounts = [
    'all' => count($orders),
    'pending' => 0,
    'confirmed' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    if (isset($statusCounts[$order['status']])) {
        $statusCounts[$order['status']]++;
    }
}

include_once "../includes/header.php";
?>

<style>
/* Remove default container margin from header */
body {
    margin: 0 !important;
    padding: 0 !important;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%) !important;
}

.container.mt-4 {
    margin: 0 !important;
    padding: 0 !important;
    max-width: 100% !important;
}

.dashboard {
    display: flex;
    min-height: 100vh;
    margin: 0;
    width: 100%;
}

.orders-container {
    flex-grow: 1;
    padding: 30px;
    max-width: 100%;
    width: 100%;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%);
}

/* Header Section */
.orders-header {
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    padding: 35px 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 8px 24px rgba(139, 111, 71, 0.25);
    position: relative;
    overflow: hidden;
}

.orders-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
}

.orders-header-content {
    position: relative;
    z-index: 1;
}

.orders-header h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.orders-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.05rem;
    font-weight: 400;
}

.orders-count {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-left: 10px;
}

/* Orders Content */
.orders-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(139, 111, 71, 0.12);
    border: 1px solid rgba(212, 196, 176, 0.3);
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 12px 20px;
    border: 2px solid rgba(139, 111, 71, 0.2);
    background: rgba(139, 111, 71, 0.05);
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    color: #5D4E37;
    display: flex;
    align-items: center;
    gap: 6px;
}

.filter-btn:hover {
    border-color: #8B6F47;
    background: rgba(139, 111, 71, 0.1);
    transform: translateY(-2px);
}

.filter-btn.active {
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(139, 111, 71, 0.4);
}

.filter-badge {
    background: rgba(255, 255, 255, 0.3);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
}

.filter-btn.active .filter-badge {
    background: rgba(255, 255, 255, 0.3);
}

/* Order Card */
.order-card {
    background: #fafafa;
    border-radius: 16px;
    border: 2px solid rgba(139, 111, 71, 0.15);
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(139, 111, 71, 0.15);
    border-color: rgba(139, 111, 71, 0.3);
}

.order-header {
    background: rgba(139, 111, 71, 0.08);
    padding: 20px;
    border-bottom: 2px solid rgba(139, 111, 71, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.order-id {
    font-weight: 700;
    color: #5D4E37;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.order-date {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 4px;
}

.order-body {
    padding: 20px;
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.order-image {
    width: 130px;
    height: 130px;
    object-fit: cover;
    border-radius: 12px;
    cursor: pointer;
    border: 2px solid rgba(139, 111, 71, 0.2);
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.order-image:hover {
    border-color: #8B6F47;
    transform: scale(1.05);
}

.order-details {
    flex: 1;
}

.pet-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: #5D4E37;
    margin-bottom: 10px;
}

.pet-info {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.seller-info {
    color: #8B6F47;
    font-size: 0.9rem;
    font-weight: 600;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.payment-method {
    display: inline-block;
    padding: 6px 12px;
    background: rgba(139, 111, 71, 0.1);
    border: 1px solid rgba(139, 111, 71, 0.2);
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #5D4E37;
    margin-top: 5px;
}

.order-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 12px;
}

.info-item {
    font-size: 0.85rem;
}

.info-label {
    color: #6c757d;
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
}

.info-value {
    color: #5D4E37;
    font-weight: 500;
}

.order-price {
    text-align: right;
    padding-left: 20px;
}

.price-label {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 5px;
}

.price-amount {
    font-size: 1.6rem;
    font-weight: 700;
    color: #8B6F47;
}

.order-footer {
    background: rgba(139, 111, 71, 0.08);
    padding: 18px 20px;
    border-top: 2px solid rgba(139, 111, 71, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.status-confirmed {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
}

.status-shipped {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
}

.status-delivered {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.status-cancelled {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.order-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-view {
    background: linear-gradient(135deg, #6B5540 0%, #8B6F47 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(107, 85, 64, 0.4);
}

.btn-view:hover {
    background: linear-gradient(135deg, #5D4E37 0%, #6B5540 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(93, 78, 55, 0.5);
    color: white;
}

.btn-contact {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.btn-contact:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    color: white;
}

.order-note {
    color: #6c757d;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Empty State */
.empty-orders {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #A0826D;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #5D4E37;
    margin-bottom: 10px;
}

.empty-text {
    color: #6c757d;
    font-size: 1.05rem;
    margin-bottom: 30px;
}

.btn-browse {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 30px;
    background: linear-gradient(135deg, #6B5540 0%, #8B6F47 100%);
    color: white;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(107, 85, 64, 0.4);
}

.btn-browse:hover {
    background: linear-gradient(135deg, #5D4E37 0%, #6B5540 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(93, 78, 55, 0.5);
    color: white;
}

/* Responsive */
@media (max-width: 991px) {
    .dashboard {
        flex-direction: column;
    }
    
    .orders-container {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .orders-content {
        padding: 20px;
    }

    .orders-header h2 {
        font-size: 1.8rem;
    }

    .order-body {
        flex-direction: column;
    }

    .order-image {
        width: 100%;
        height: 200px;
    }

    .order-price {
        text-align: left;
        padding-left: 0;
        margin-top: 15px;
    }

    .order-header,
    .order-footer {
        flex-direction: column;
        align-items: flex-start;
    }

    .order-info-grid {
        grid-template-columns: 1fr;
    }

    .order-actions {
        width: 100%;
        flex-direction: column;
    }

    .btn-action {
        width: 100%;
        justify-content: center;
    }

    .filter-tabs {
        gap: 8px;
    }

    .filter-btn {
        font-size: 0.85rem;
        padding: 10px 16px;
    }
}
</style>

<div class="dashboard">
    <!-- Sidebar -->
    <?php include_once "../includes/sidebar.php"; ?>

    <!-- Orders Section -->
    <div class="orders-container">
        <!-- Header -->
        <div class="orders-header">
            <div class="orders-header-content">
                <h2>
                    <i class="bi bi-box-seam-fill"></i>
                    My Orders
                    <span class="orders-count"><?= count($orders); ?> <?= count($orders) === 1 ? 'Order' : 'Orders' ?></span>
                </h2>
                <p>Track and manage all your pet orders</p>
            </div>
        </div>

        <!-- Orders Content -->
        <div class="orders-content">
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-btn active" onclick="filterOrders('all')">
                    All Orders
                    <span class="filter-badge"><?= $statusCounts['all'] ?></span>
                </button>
                <button class="filter-btn" onclick="filterOrders('pending')">
                    Pending
                    <span class="filter-badge"><?= $statusCounts['pending'] ?></span>
                </button>
                <button class="filter-btn" onclick="filterOrders('confirmed')">
                    Confirmed
                    <span class="filter-badge"><?= $statusCounts['confirmed'] ?></span>
                </button>
                <button class="filter-btn" onclick="filterOrders('shipped')">
                    Shipped
                    <span class="filter-badge"><?= $statusCounts['shipped'] ?></span>
                </button>
                <button class="filter-btn" onclick="filterOrders('delivered')">
                    Delivered
                    <span class="filter-badge"><?= $statusCounts['delivered'] ?></span>
                </button>
                <button class="filter-btn" onclick="filterOrders('cancelled')">
                    Cancelled
                    <span class="filter-badge"><?= $statusCounts['cancelled'] ?></span>
                </button>
            </div>

            <!-- Orders List -->
            <?php if (count($orders) > 0): ?>
                <div id="ordersList">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card" data-status="<?= htmlspecialchars($order['status']) ?>">
                            <!-- Order Header -->
                            <div class="order-header">
                                <div>
                                    <div class="order-id">
                                        <i class="bi bi-receipt"></i>
                                        Order #<?= $order['id'] ?>
                                    </div>
                                    <div class="order-date">
                                        <i class="bi bi-calendar3"></i>
                                        <?= date('F d, Y - h:i A', strtotime($order['order_timestamp'])) ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                </span>
                            </div>

                            <!-- Order Body -->
                            <div class="order-body">
                                <img src="../uploads/<?= htmlspecialchars($order['image']) ?>" 
                                     alt="<?= htmlspecialchars($order['pet_name']) ?>"
                                     class="order-image"
                                     onclick="window.location.href='pet-details.php?id=<?= $order['pet_id'] ?>'">
                                
                                <div class="order-details">
                                    <div class="pet-name"><?= htmlspecialchars($order['pet_name']) ?></div>
                                    <div class="pet-info">
                                        <i class="bi bi-paw"></i>
                                        <?= htmlspecialchars($order['type']) ?> • <?= htmlspecialchars($order['breed']) ?>
                                    </div>
                                    <div class="seller-info">
                                        <i class="bi bi-person-circle"></i>
                                        Seller: <?= htmlspecialchars($order['seller_name']) ?>
                                    </div>
                                    <div class="payment-method">
                                        <i class="bi bi-credit-card"></i>
                                        <?= strtoupper(htmlspecialchars($order['payment_method'])) ?>
                                    </div>
                                    
                                    <div class="order-info-grid">
                                        <div class="info-item">
                                            <span class="info-label"><i class="bi bi-geo-alt-fill"></i> Delivery to:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['city']) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label"><i class="bi bi-telephone-fill"></i> Contact:</span>
                                            <span class="info-value"><?= htmlspecialchars($order['phone']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="order-price">
                                    <div class="price-label">Total Amount</div>
                                    <div class="price-amount">₱<?= number_format($order['total_amount'], 2) ?></div>
                                </div>
                            </div>

                            <!-- Order Footer -->
                            <div class="order-footer">
                                <div>
                                    <?php if (!empty($order['notes'])): ?>
                                        <div class="order-note">
                                            <i class="bi bi-chat-left-text"></i>
                                            Note: <?= htmlspecialchars($order['notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="order-actions">
                                    <a href="order-details.php?id=<?= $order['id'] ?>" class="btn-action btn-view">
                                        <i class="bi bi-eye"></i> View Details
                                    </a>
                                    <a href="message-seller.php?seller_id=<?= $order['seller_id'] ?>&pet_id=<?= $order['pet_id'] ?>" 
                                       class="btn-action btn-contact">
                                        <i class="bi bi-chat-dots"></i> Contact Seller
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-orders">
                    <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
                    <h2 class="empty-title">No Orders Yet</h2>
                    <p class="empty-text">You haven't placed any orders yet. Start shopping for your perfect pet!</p>
                    <a href="products.php" class="btn-browse">
                        <i class="bi bi-search"></i> Browse Pets
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function filterOrders(status) {
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Filter orders
    const orderCards = document.querySelectorAll('.order-card');
    
    orderCards.forEach(card => {
        if (status === 'all') {
            card.style.display = 'block';
        } else {
            if (card.dataset.status === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        }
    });
}
</script>

<?php include_once "../includes/footer.php"; ?>