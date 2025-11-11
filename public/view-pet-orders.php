<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;

// Fetch pet details and verify ownership
$pet_sql = "SELECT p.*, 
            (SELECT filename FROM pet_images WHERE pet_id = p.id LIMIT 1) as first_image
            FROM pets p 
            WHERE p.id = ? AND p.user_id = ?";
$stmt = $conn->prepare($pet_sql);
$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pet = $result->fetch_assoc();

if (!$pet) {
    die("Pet not found or you don't have permission to view its orders.");
}

// Fetch all orders for this pet
$orders_sql = "SELECT o.*, u.username as buyer_username, u.email as buyer_email
               FROM orders o
               LEFT JOIN users u ON o.buyer_id = u.id
               WHERE o.pet_id = ? AND o.seller_id = ?
               ORDER BY o.order_date DESC";
$stmt = $conn->prepare($orders_sql);
$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once "../includes/header.php";
?>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5f0e8 0%, #e8dcc8 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 20px;
        transition: color 0.3s;
    }

    .back-btn:hover {
        color: #495057;
    }

    .page-header h1 {
        color: #2c3e50;
        font-size: 2.5em;
        margin-bottom: 10px;
    }

    .page-header p {
        color: #7f8c8d;
        font-size: 1.1em;
    }

    /* Pet Info Card */
    .pet-info-card {
        background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        gap: 25px;
        box-shadow: 0 5px 20px rgba(234, 221, 202, 0.4);
    }

    .pet-image {
        width: 150px;
        height: 150px;
        border-radius: 12px;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .pet-info-content {
        flex: 1;
        color: #3d3020;
    }

    .pet-info-content h2 {
        font-size: 2em;
        margin-bottom: 15px;
    }

    .pet-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .info-item {
        background: rgba(255, 255, 255, 0.3);
        padding: 10px 15px;
        border-radius: 8px;
    }

    .info-label {
        font-size: 0.85em;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 1.1em;
        font-weight: 700;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-icon {
        font-size: 3em;
        margin-bottom: 10px;
    }

    .stat-number {
        font-size: 2.5em;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 1em;
    }

    /* Filters */
    .filters {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-label {
        font-weight: 600;
        color: #2c3e50;
    }

    .filter-select {
        padding: 10px 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1em;
        cursor: pointer;
        transition: border-color 0.3s;
    }

    .filter-select:focus {
        outline: none;
        border-color: #c9b896;
    }

    /* Orders Table */
    .orders-container {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .orders-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .orders-header h2 {
        color: #2c3e50;
        font-size: 1.8em;
    }

    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }

    .orders-table thead {
        background: #f8f9fa;
    }

    .orders-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #2c3e50;
        border-bottom: 2px solid #dee2e6;
    }

    .orders-table td {
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
    }

    .orders-table tbody tr {
        transition: background-color 0.3s;
    }

    .orders-table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .order-id {
        font-weight: 700;
        color: #2c3e50;
    }

    .buyer-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .buyer-avatar {
        width: 40px;
        height: 40px;
        background: #c9b896;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
    }

    .buyer-details h4 {
        font-size: 1em;
        color: #2c3e50;
        margin-bottom: 3px;
    }

    .buyer-details p {
        font-size: 0.85em;
        color: #7f8c8d;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 600;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-paid {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-shipped {
        background: #cce5ff;
        color: #004085;
    }

    .status-completed {
        background: #d4edda;
        color: #155724;
    }

    .status-cancelled {
        background: #f8d7da;
        color: #721c24;
    }

    .amount {
        font-size: 1.1em;
        font-weight: 700;
        color: #28a745;
    }

    .action-btns {
        display: flex;
        gap: 8px;
    }

    .btn {
        padding: 8px 15px;
        border: none;
        border-radius: 6px;
        font-size: 0.9em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #c9b896;
        color: white;
    }

    .btn-primary:hover {
        background: #b8a785;
    }

    .btn-success {
        background: #28a745;
        color: white;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background: #138496;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state .icon {
        font-size: 5em;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #2c3e50;
        font-size: 1.8em;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #7f8c8d;
        font-size: 1.1em;
    }

    @media (max-width: 768px) {
        .pet-info-card {
            flex-direction: column;
        }

        .pet-image {
            width: 100%;
            height: 200px;
        }

        .orders-table {
            font-size: 0.9em;
        }

        .orders-table th,
        .orders-table td {
            padding: 10px 8px;
        }

        .action-btns {
            flex-direction: column;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Mobile: Stack table on small screens */
    @media (max-width: 992px) {
        .orders-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <a href="my-listed-pets.php" class="back-btn">
            ← Back to My Listed Pets
        </a>
        <h1>📦 Orders for <?php echo htmlspecialchars($pet['name']); ?></h1>
        <p>Manage all orders and track their status</p>
    </div>

    <!-- Pet Info Card -->
    <div class="pet-info-card">
        <img src="../uploads/<?php echo htmlspecialchars($pet['first_image'] ?: $pet['image'] ?: 'default-pet.jpg'); ?>" 
             alt="<?php echo htmlspecialchars($pet['name']); ?>" 
             class="pet-image"
             onerror="this.src='../uploads/default-pet.jpg'">
        <div class="pet-info-content">
            <h2><?php echo htmlspecialchars($pet['name']); ?></h2>
            <div class="pet-info-grid">
                <div class="info-item">
                    <div class="info-label">Breed</div>
                    <div class="info-value"><?php echo htmlspecialchars($pet['breed']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Age</div>
                    <div class="info-value"><?php echo htmlspecialchars($pet['age']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Price</div>
                    <div class="info-value">₱<?php echo number_format($pet['price'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value"><?php echo htmlspecialchars($pet['status']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number"><?php echo count($orders); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-number"><?php echo count(array_filter($orders, fn($o) => $o['status'] === 'Pending')); ?></div>
            <div class="stat-label">Pending Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?php echo count(array_filter($orders, fn($o) => $o['status'] === 'Completed')); ?></div>
            <div class="stat-label">Completed Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-number">₱<?php echo number_format(array_sum(array_column($orders, 'total_amount')), 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <span class="filter-label">Filter by Status:</span>
        <select id="statusFilter" class="filter-select">
            <option value="">All Orders</option>
            <option value="Pending">Pending</option>
            <option value="Paid">Paid</option>
            <option value="Shipped">Shipped</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>

    <!-- Orders Container -->
    <div class="orders-container">
        <div class="orders-header">
            <h2>Order History</h2>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>No Orders Yet</h3>
                <p>This pet hasn't received any orders yet. Orders will appear here once customers place them.</p>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Order Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <?php foreach ($orders as $order): ?>
                        <tr data-status="<?php echo htmlspecialchars($order['status']); ?>">
                            <td>
                                <span class="order-id">#<?php echo $order['id']; ?></span>
                            </td>
                            <td>
                                <div class="buyer-info">
                                    <div class="buyer-avatar">
                                        <?php echo strtoupper(substr($order['buyer_username'], 0, 1)); ?>
                                    </div>
                                    <div class="buyer-details">
                                        <h4><?php echo htmlspecialchars($order['buyer_username']); ?></h4>
                                        <p><?php echo htmlspecialchars($order['buyer_email']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($order['order_date'])); ?><br>
                                <small style="color: #7f8c8d;"><?php echo date('g:i A', strtotime($order['order_date'])); ?></small>
                            </td>
                            <td>
                                <span class="amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                            </td>
                            <td>
                                <?php echo strtoupper(htmlspecialchars($order['payment_method'])); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-primary">
                                        View Details
                                    </a>
                                    <?php if ($order['status'] === 'Pending'): ?>
                                        <button class="btn btn-success" 
                                                onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed')">
                                            Confirm
                                        </button>
                                    <?php elseif ($order['status'] === 'Paid' || $order['status'] === 'confirmed'): ?>
                                        <button class="btn btn-info" 
                                                onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')">
                                            Mark Shipped
                                        </button>
                                    <?php elseif ($order['status'] === 'Shipped'): ?>
                                        <button class="btn btn-success" 
                                                onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">
                                            Mark Delivered
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Filter orders by status
document.getElementById('statusFilter')?.addEventListener('change', function() {
    const status = this.value.toLowerCase();
    const rows = document.querySelectorAll('#ordersTableBody tr');
    
    rows.forEach(row => {
        const rowStatus = row.dataset.status.toLowerCase();
        if (status === '' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Update order status
function updateOrderStatus(orderId, newStatus) {
    let statusLabel = newStatus;
    if (newStatus === 'confirmed') statusLabel = 'Confirmed';
    if (newStatus === 'shipped') statusLabel = 'Shipped';
    if (newStatus === 'delivered') statusLabel = 'Delivered';
    
    Swal.fire({
        title: `Update Order Status`,
        text: `Are you sure you want to mark this order as ${statusLabel}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Yes, mark as ${statusLabel}`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update-order-status.php';
            
            const orderIdInput = document.createElement('input');
            orderIdInput.type = 'hidden';
            orderIdInput.name = 'order_id';
            orderIdInput.value = orderId;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'new_status';
            statusInput.value = newStatus;
            
            form.appendChild(orderIdInput);
            form.appendChild(statusInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php include_once "../includes/footer.php"; ?>