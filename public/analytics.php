<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ============= ADOPTION ANALYTICS =============

// Total adoption listings
$adoption_count_sql = "SELECT COUNT(*) as count FROM adoption_cats WHERE user_id = ?";
$stmt = $conn->prepare($adoption_count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$adoption_total = $stmt->get_result()->fetch_assoc()['count'];

// Adoption applications stats
$adoption_apps_sql = "SELECT 
    COUNT(DISTINCT aa.id) as total_applications,
    COUNT(DISTINCT CASE WHEN aa.status = 'Pending' THEN aa.id END) as pending_apps,
    COUNT(DISTINCT CASE WHEN aa.status = 'Approved' THEN aa.id END) as approved_apps,
    COUNT(DISTINCT CASE WHEN aa.status = 'Rejected' THEN aa.id END) as rejected_apps
    FROM adoption_cats ac
    LEFT JOIN adoption_applications aa ON ac.id = aa.cat_id
    WHERE ac.user_id = ?";
$stmt = $conn->prepare($adoption_apps_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$adoption_apps = $stmt->get_result()->fetch_assoc();

// Adoption by status
$adoption_status_sql = "SELECT status, COUNT(*) as count 
    FROM adoption_cats 
    WHERE user_id = ? 
    GROUP BY status";
$stmt = $conn->prepare($adoption_status_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$adoption_by_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top adoption breeds
$adoption_breeds_sql = "SELECT breed, COUNT(*) as count,
    COUNT(DISTINCT aa.id) as application_count
    FROM adoption_cats ac
    LEFT JOIN adoption_applications aa ON ac.id = aa.cat_id
    WHERE ac.user_id = ?
    GROUP BY breed
    ORDER BY application_count DESC
    LIMIT 5";
$stmt = $conn->prepare($adoption_breeds_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$adoption_breeds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Average adoption fee
$avg_adoption_fee_sql = "SELECT AVG(adoption_fee) as avg_fee, SUM(adoption_fee) as total_fees 
    FROM adoption_cats WHERE user_id = ?";
$stmt = $conn->prepare($avg_adoption_fee_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$adoption_fees = $stmt->get_result()->fetch_assoc();

// Recent adoption applications
$recent_adoption_sql = "SELECT aa.*, ac.name as cat_name, ac.breed
    FROM adoption_applications aa
    JOIN adoption_cats ac ON aa.cat_id = ac.id
    WHERE ac.user_id = ?
    ORDER BY aa.submitted_at DESC
    LIMIT 5";
$stmt = $conn->prepare($recent_adoption_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_adoptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ============= SELLING ANALYTICS =============

// Total selling listings
$selling_count_sql = "SELECT COUNT(*) as count FROM pets WHERE user_id = ?";
$stmt = $conn->prepare($selling_count_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$selling_total = $stmt->get_result()->fetch_assoc()['count'];

// Selling orders stats
$selling_orders_sql = "SELECT 
    COUNT(DISTINCT o.id) as total_orders,
    COUNT(DISTINCT CASE WHEN o.status = 'Pending' THEN o.id END) as pending_orders,
    COUNT(DISTINCT CASE WHEN o.status = 'Shipped' THEN o.id END) as shipped_orders,
    COUNT(DISTINCT CASE WHEN o.status = 'Completed' THEN o.id END) as completed_orders,
    SUM(o.total_amount) as total_revenue
    FROM pets p
    LEFT JOIN orders o ON p.id = o.pet_id
    WHERE p.user_id = ?";
$stmt = $conn->prepare($selling_orders_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$selling_orders = $stmt->get_result()->fetch_assoc();

// Pets by status
$pets_status_sql = "SELECT status, COUNT(*) as count 
    FROM pets 
    WHERE user_id = ? 
    GROUP BY status";
$stmt = $conn->prepare($pets_status_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pets_by_status = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Top selling breeds
$selling_breeds_sql = "SELECT p.breed, COUNT(DISTINCT p.id) as count,
    COUNT(DISTINCT o.id) as order_count,
    SUM(o.total_amount) as revenue
    FROM pets p
    LEFT JOIN orders o ON p.id = o.pet_id
    WHERE p.user_id = ?
    GROUP BY p.breed
    ORDER BY order_count DESC
    LIMIT 5";
$stmt = $conn->prepare($selling_breeds_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$selling_breeds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Average pet price
$avg_price_sql = "SELECT AVG(price) as avg_price, MIN(price) as min_price, MAX(price) as max_price 
    FROM pets WHERE user_id = ?";
$stmt = $conn->prepare($avg_price_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$price_stats = $stmt->get_result()->fetch_assoc();

// Recent orders
$recent_orders_sql = "SELECT o.*, p.name as pet_name, p.breed
    FROM orders o
    JOIN pets p ON o.pet_id = p.id
    WHERE p.user_id = ?
    ORDER BY o.order_date DESC
    LIMIT 5";
$stmt = $conn->prepare($recent_orders_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include_once "../includes/header.php";
?>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%) !important;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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

    .main-content {
        flex: 1;
        padding: 30px;
        max-width: 100%;
        background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%);
    }

    .page-header {
        background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
        color: white;
        padding: 40px 35px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(139, 111, 71, 0.3);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .page-header-content {
        position: relative;
        z-index: 1;
    }

    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    }

    .page-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 1.1rem;
        font-weight: 400;
    }

    .tabs {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        background: white;
        padding: 12px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(139, 111, 71, 0.15);
    }

    .tab-btn {
        flex: 1;
        padding: 16px 30px;
        border: none;
        background: transparent;
        color: #6c757d;
        font-size: 1.05rem;
        font-weight: 600;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .tab-btn:hover:not(.active) {
        background: rgba(139, 111, 71, 0.08);
        color: #5D4E37;
    }

    .tab-btn.active {
        background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(139, 111, 71, 0.35);
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.4s ease;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 28px 24px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(139, 111, 71, 0.12);
        border: 1px solid rgba(212, 196, 176, 0.25);
        text-align: center;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 30px rgba(139, 111, 71, 0.2);
        border-color: rgba(139, 111, 71, 0.3);
    }

    .stat-card .icon {
        font-size: 3rem;
        margin-bottom: 12px;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    .stat-card .number {
        font-size: 2.8rem;
        font-weight: 700;
        color: #8B6F47;
        margin-bottom: 6px;
        line-height: 1;
    }

    .stat-card .label {
        color: #6c757d;
        font-size: 1rem;
        font-weight: 500;
    }

    .chart-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(139, 111, 71, 0.12);
        border: 1px solid rgba(212, 196, 176, 0.25);
        margin-bottom: 30px;
    }

    .chart-section h3 {
        color: #2c3e50;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 20px 0;
    }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .breed-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%);
        border-radius: 12px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }

    .breed-item:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(139, 111, 71, 0.15);
    }

    .breed-name {
        font-weight: 600;
        color: #2c3e50;
        font-size: 1rem;
    }

    .breed-count {
        font-weight: 700;
        color: #8B6F47;
        font-size: 1.2rem;
    }

    .progress-bar {
        height: 8px;
        background: #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
        border-radius: 4px;
        transition: width 0.5s ease;
    }

    .table-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(139, 111, 71, 0.12);
        border: 1px solid rgba(212, 196, 176, 0.25);
        margin-bottom: 30px;
        overflow-x: auto;
    }

    .table-section h3 {
        color: #2c3e50;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 20px 0;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .data-table th:first-child {
        border-radius: 8px 0 0 0;
    }

    .data-table th:last-child {
        border-radius: 0 8px 0 0;
    }

    .data-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        color: #2c3e50;
        font-size: 0.95rem;
    }

    .data-table tr:hover td {
        background: rgba(139, 111, 71, 0.05);
    }

    .status-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
        display: inline-block;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .status-available {
        background: #d4edda;
        color: #155724;
    }

    .status-sold {
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

    .empty-state {
        text-align: center;
        padding: 80px 30px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(139, 111, 71, 0.12);
    }

    .empty-state .icon {
        font-size: 5rem;
        margin-bottom: 20px;
        opacity: 0.6;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    }

    .empty-state h3 {
        color: #2c3e50;
        font-size: 1.8rem;
        margin-bottom: 12px;
        font-weight: 700;
    }

    .empty-state p {
        color: #6c757d;
        font-size: 1.1rem;
        margin-bottom: 25px;
    }

    /* Responsive Design */
    @media (max-width: 991px) {
        .dashboard {
            flex-direction: column;
        }
        
        .main-content {
            padding: 20px;
        }

        .page-header {
            padding: 30px 25px;
        }

        .page-header h1 {
            font-size: 2rem;
        }
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .chart-grid {
            grid-template-columns: 1fr;
        }

        .tabs {
            flex-direction: column;
            gap: 8px;
        }

        .tab-btn {
            padding: 14px 20px;
            font-size: 1rem;
        }

        .page-header h1 {
            font-size: 1.75rem;
        }

        .table-section {
            padding: 20px 15px;
        }
    }

    @media (max-width: 480px) {
        .main-content {
            padding: 15px;
        }

        .page-header {
            padding: 25px 20px;
        }

        .stat-card {
            padding: 20px 16px;
        }

        .stat-card .number {
            font-size: 2.2rem;
        }
    }
</style>

<div class="dashboard">
    <?php include_once "../includes/sidebar.php"; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="page-header-content">
                <h1>📊 Analytics Dashboard</h1>
                <p>Track your pet listings performance and insights</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('adoption')">
                🏠 Adoption Analytics
            </button>
            <button class="tab-btn" onclick="switchTab('selling')">
                💰 Selling Analytics
            </button>
        </div>

        <!-- Adoption Analytics Tab -->
        <div id="adoption-tab" class="tab-content active">
            <?php if ($adoption_total == 0): ?>
                <div class="empty-state">
                    <div class="icon">📊</div>
                    <h3>No Adoption Data Yet</h3>
                    <p>Start listing cats for adoption to see your analytics here!</p>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">🐱</div>
                        <div class="number"><?php echo $adoption_total; ?></div>
                        <div class="label">Total Listings</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">📋</div>
                        <div class="number"><?php echo $adoption_apps['total_applications'] ?: 0; ?></div>
                        <div class="label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">⏳</div>
                        <div class="number"><?php echo $adoption_apps['pending_apps'] ?: 0; ?></div>
                        <div class="label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">✅</div>
                        <div class="number"><?php echo $adoption_apps['approved_apps'] ?: 0; ?></div>
                        <div class="label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">💰</div>
                        <div class="number">₱<?php echo number_format($adoption_fees['avg_fee'] ?: 0, 0); ?></div>
                        <div class="label">Avg. Adoption Fee</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">💵</div>
                        <div class="number">₱<?php echo number_format($adoption_fees['total_fees'] ?: 0, 0); ?></div>
                        <div class="label">Total Fees Value</div>
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="chart-section">
                        <h3>📈 Listings by Status</h3>
                        <?php foreach ($adoption_by_status as $status): ?>
                            <div class="breed-item">
                                <span class="breed-name"><?php echo htmlspecialchars($status['status']); ?></span>
                                <span class="breed-count"><?php echo $status['count']; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($status['count'] / $adoption_total * 100); ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="chart-section">
                        <h3>🏆 Top Breeds by Applications</h3>
                        <?php if (empty($adoption_breeds)): ?>
                            <p style="color: #6c757d; text-align: center;">No data available</p>
                        <?php else: ?>
                            <?php 
                            $max_apps = max(array_column($adoption_breeds, 'application_count'));
                            foreach ($adoption_breeds as $breed): 
                            ?>
                                <div class="breed-item">
                                    <span class="breed-name"><?php echo htmlspecialchars($breed['breed']); ?></span>
                                    <span class="breed-count"><?php echo $breed['application_count']; ?> apps</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $max_apps > 0 ? ($breed['application_count'] / $max_apps * 100) : 0; ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($recent_adoptions)): ?>
                <div class="table-section">
                    <h3>📋 Recent Applications</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Cat</th>
                                <th>Breed</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_adoptions as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['cat_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['breed']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                        <?php echo $app['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($app['submitted_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Selling Analytics Tab -->
        <div id="selling-tab" class="tab-content">
            <?php if ($selling_total == 0): ?>
                <div class="empty-state">
                    <div class="icon">📊</div>
                    <h3>No Selling Data Yet</h3>
                    <p>Start listing pets for sale to see your analytics here!</p>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">🐾</div>
                        <div class="number"><?php echo $selling_total; ?></div>
                        <div class="label">Total Listings</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">🛒</div>
                        <div class="number"><?php echo $selling_orders['total_orders'] ?: 0; ?></div>
                        <div class="label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">⏳</div>
                        <div class="number"><?php echo $selling_orders['pending_orders'] ?: 0; ?></div>
                        <div class="label">Pending Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">✅</div>
                        <div class="number"><?php echo $selling_orders['completed_orders'] ?: 0; ?></div>
                        <div class="label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">💰</div>
                        <div class="number">₱<?php echo number_format($price_stats['avg_price'] ?: 0, 0); ?></div>
                        <div class="label">Avg. Price</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">💵</div>
                        <div class="number">₱<?php echo number_format($selling_orders['total_revenue'] ?: 0, 0); ?></div>
                        <div class="label">Total Revenue</div>
                    </div>
                </div>

                <div class="chart-grid">
                    <div class="chart-section">
                        <h3>📈 Pets by Status</h3>
                        <?php foreach ($pets_by_status as $status): ?>
                            <div class="breed-item">
                                <span class="breed-name"><?php echo htmlspecialchars(ucfirst($status['status'])); ?></span>
                                <span class="breed-count"><?php echo $status['count']; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($status['count'] / $selling_total * 100); ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="chart-section">
                        <h3>🏆 Top Selling Breeds</h3>
                        <?php if (empty($selling_breeds)): ?>
                            <p style="color: #6c757d; text-align: center;">No data available</p>
                        <?php else: ?>
                            <?php 
                            $max_orders = max(array_column($selling_breeds, 'order_count'));
                            foreach ($selling_breeds as $breed): 
                            ?>
                                <div class="breed-item">
                                    <div>
                                        <div class="breed-name"><?php echo htmlspecialchars($breed['breed']); ?></div>
                                        <small style="color: #6c757d;">₱<?php echo number_format($breed['revenue'] ?: 0, 0); ?> revenue</small>
                                    </div>
                                    <span class="breed-count"><?php echo $breed['order_count']; ?> orders</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $max_orders > 0 ? ($breed['order_count'] / $max_orders * 100) : 0; ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($recent_orders)): ?>
                <div class="table-section">
                    <h3>🛒 Recent Orders</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Pet</th>
                                <th>Breed</th>
                                <th>Buyer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['pet_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['breed']); ?></td>
                                <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div class="chart-section">
                    <h3>💰 Price Range Analysis</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                        <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%); border-radius: 12px;">
                            <div style="color: #6c757d; font-size: 0.9rem; margin-bottom: 8px;">Minimum Price</div>
                            <div style="font-size: 1.8rem; font-weight: 700; color: #8B6F47;">₱<?php echo number_format($price_stats['min_price'] ?: 0, 0); ?></div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%); border-radius: 12px;">
                            <div style="color: #6c757d; font-size: 0.9rem; margin-bottom: 8px;">Average Price</div>
                            <div style="font-size: 1.8rem; font-weight: 700; color: #8B6F47;">₱<?php echo number_format($price_stats['avg_price'] ?: 0, 0); ?></div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%); border-radius: 12px;">
                            <div style="color: #6c757d; font-size: 0.9rem; margin-bottom: 8px;">Maximum Price</div>
                            <div style="font-size: 1.8rem; font-weight: 700; color: #8B6F47;">₱<?php echo number_format($price_stats['max_price'] ?: 0, 0); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    if (tab === 'adoption') {
        document.getElementById('adoption-tab').classList.add('active');
        document.querySelectorAll('.tab-btn')[0].classList.add('active');
    } else {
        document.getElementById('selling-tab').classList.add('active');
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
    }
}
</script>

<?php include_once "../includes/footer.php"; ?>