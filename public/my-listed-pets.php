<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch adoption cats
$adoption_sql = "SELECT ac.*, 
                COUNT(DISTINCT aa.id) as application_count,
                COUNT(DISTINCT CASE WHEN aa.status = 'Pending' THEN aa.id END) as pending_count,
                COUNT(DISTINCT CASE WHEN aa.status = 'Approved' THEN aa.id END) as approved_count
                FROM adoption_cats ac
                LEFT JOIN adoption_applications aa ON ac.id = aa.cat_id
                WHERE ac.user_id = ?
                GROUP BY ac.id
                ORDER BY ac.created_at DESC";

$stmt = $conn->prepare($adoption_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$adoption_cats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch selling pets
$selling_sql = "SELECT p.*, 
                COUNT(DISTINCT o.id) as order_count,
                COUNT(DISTINCT CASE WHEN o.status = 'Pending' THEN o.id END) as pending_orders
                FROM pets p
                LEFT JOIN orders o ON p.id = o.pet_id
                WHERE p.user_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC";

$stmt = $conn->prepare($selling_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$selling_pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

    .pets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 24px;
    }

    .pet-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(139, 111, 71, 0.12);
        border: 1px solid rgba(212, 196, 176, 0.25);
        transition: all 0.3s ease;
    }

    .pet-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 35px rgba(139, 111, 71, 0.22);
        border-color: rgba(139, 111, 71, 0.35);
    }

    .pet-image {
        width: 100%;
        height: 260px;
        object-fit: cover;
        background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
    }

    .pet-content {
        padding: 22px;
    }

    .pet-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        gap: 12px;
    }

    .pet-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        line-height: 1.3;
        flex: 1;
    }

    .status-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
        white-space: nowrap;
    }

    .status-available {
        background: #d4edda;
        color: #155724;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-adopted,
    .status-sold {
        background: #d1ecf1;
        color: #0c5460;
    }

    .pet-details {
        margin-bottom: 18px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #6c757d;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .detail-value {
        color: #2c3e50;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .pet-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 18px;
        padding: 18px;
        background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%);
        border-radius: 12px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: #8B6F47;
        line-height: 1;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
    }

    .pet-actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        font-size: 0.95rem;
        display: inline-block;
    }

    .btn-primary {
        background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(139, 111, 71, 0.3);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #6B5540 0%, #8B6F47 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(107, 85, 64, 0.4);
        color: white;
    }

    .btn-secondary {
        background: white;
        color: #8B6F47;
        border: 2px solid #8B6F47;
    }

    .btn-secondary:hover {
        background: #8B6F47;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 111, 71, 0.3);
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
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    .empty-state .btn {
        display: inline-block;
        flex: none;
        padding: 14px 32px;
    }

    .price {
        font-size: 1.3rem;
        font-weight: 700;
        color: #28a745;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .pets-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }

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
        .pets-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
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

        .page-header p {
            font-size: 1rem;
        }

        .pet-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
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

        .pet-content {
            padding: 18px;
        }

        .pet-name {
            font-size: 1.3rem;
        }

        .empty-state {
            padding: 60px 20px;
        }
    }
</style>

<div class="dashboard">
    <?php include_once "../includes/sidebar.php"; ?>
    
    <div class="main-content">
        <div class="page-header">
            <div class="page-header-content">
                <h1>🐾 My Listed Pets</h1>
                <p>Manage your pets available for adoption and sale</p>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('adoption')">
                🏠 Adoption Listings (<?php echo count($adoption_cats); ?>)
            </button>
            <button class="tab-btn" onclick="switchTab('selling')">
                💰 Selling Listings (<?php echo count($selling_pets); ?>)
            </button>
        </div>

        <!-- Adoption Tab -->
        <div id="adoption-tab" class="tab-content active">
            <?php if (empty($adoption_cats)): ?>
                <div class="empty-state">
                    <div class="icon">🐱</div>
                    <h3>No Adoption Listings Yet</h3>
                    <p>You haven't listed any cats for adoption. Start helping cats find their forever homes!</p>
                    <a href="add-adoption-cat.php" class="btn btn-primary">Post Adoption Cat</a>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">📋</div>
                        <div class="number"><?php echo array_sum(array_column($adoption_cats, 'application_count')); ?></div>
                        <div class="label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">⏳</div>
                        <div class="number"><?php echo array_sum(array_column($adoption_cats, 'pending_count')); ?></div>
                        <div class="label">Pending Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">✅</div>
                        <div class="number"><?php echo array_sum(array_column($adoption_cats, 'approved_count')); ?></div>
                        <div class="label">Approved Applications</div>
                    </div>
                </div>

                <div class="pets-grid">
                    <?php foreach ($adoption_cats as $cat): ?>
                        <div class="pet-card">
                            <img src="../uploads/<?php echo htmlspecialchars($cat['image_url'] ?: 'default-cat.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($cat['name']); ?>" 
                                 class="pet-image"
                                 onerror="this.src='../uploads/default-cat.jpg'">
                            <div class="pet-content">
                                <div class="pet-header">
                                    <h3 class="pet-name"><?php echo htmlspecialchars($cat['name']); ?></h3>
                                    <span class="status-badge status-<?php echo strtolower($cat['status']); ?>">
                                        <?php echo htmlspecialchars($cat['status']); ?>
                                    </span>
                                </div>

                                <div class="pet-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Breed:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($cat['breed']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Age:</span>
                                        <span class="detail-value"><?php echo $cat['age']; ?> year(s)</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Gender:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($cat['gender']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Adoption Fee:</span>
                                        <span class="price">₱<?php echo number_format($cat['adoption_fee'], 2); ?></span>
                                    </div>
                                </div>

                                <div class="pet-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $cat['application_count']; ?></div>
                                        <div class="stat-label">Applications</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $cat['pending_count']; ?></div>
                                        <div class="stat-label">Pending</div>
                                    </div>
                                </div>

                                <div class="pet-actions">
                                    <a href="manage-adoption-applications.php?cat_id=<?php echo $cat['id']; ?>" 
                                       class="btn btn-primary">
                                        View Applications
                                    </a>
                                    <a href="edit-adoption-cat.php?id=<?php echo $cat['id']; ?>" 
                                       class="btn btn-secondary">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Selling Tab -->
        <div id="selling-tab" class="tab-content">
            <?php if (empty($selling_pets)): ?>
                <div class="empty-state">
                    <div class="icon">💰</div>
                    <h3>No Selling Listings Yet</h3>
                    <p>You haven't listed any pets for sale. Start selling today!</p>
                    <a href="sell.php" class="btn btn-primary">Post Pet for Sale</a>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon">🛒</div>
                        <div class="number"><?php echo array_sum(array_column($selling_pets, 'order_count')); ?></div>
                        <div class="label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">⏳</div>
                        <div class="number"><?php echo array_sum(array_column($selling_pets, 'pending_orders')); ?></div>
                        <div class="label">Pending Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">💵</div>
                        <div class="number">₱<?php echo number_format(array_sum(array_column($selling_pets, 'price')), 2); ?></div>
                        <div class="label">Total Value</div>
                    </div>
                </div>

                <div class="pets-grid">
                    <?php foreach ($selling_pets as $pet): ?>
                        <?php
                        // Get first image
                        $img_sql = "SELECT filename FROM pet_images WHERE pet_id = ? LIMIT 1";
                        $img_stmt = $conn->prepare($img_sql);
                        $img_stmt->bind_param("i", $pet['id']);
                        $img_stmt->execute();
                        $img_result = $img_stmt->get_result();
                        $image = $img_result->fetch_assoc();
                        $image_url = $image ? $image['filename'] : ($pet['image'] ?: 'default-pet.jpg');
                        ?>
                        <div class="pet-card">
                            <img src="../uploads/<?php echo htmlspecialchars($image_url); ?>" 
                                 alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                                 class="pet-image"
                                 onerror="this.src='../uploads/default-pet.jpg'">
                            <div class="pet-content">
                                <div class="pet-header">
                                    <h3 class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></h3>
                                    <span class="status-badge status-<?php echo strtolower($pet['status']); ?>">
                                        <?php echo htmlspecialchars($pet['status']); ?>
                                    </span>
                                </div>

                                <div class="pet-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Breed:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($pet['breed']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Age:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($pet['age']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Type:</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($pet['type']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Price:</span>
                                        <span class="price">₱<?php echo number_format($pet['price'], 2); ?></span>
                                    </div>
                                </div>

                                <div class="pet-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $pet['order_count']; ?></div>
                                        <div class="stat-label">Orders</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $pet['pending_orders']; ?></div>
                                        <div class="stat-label">Pending</div>
                                    </div>
                                </div>

                                <div class="pet-actions">
                                    <a href="view-pet-orders.php?pet_id=<?php echo $pet['id']; ?>" 
                                       class="btn btn-primary">
                                        View Orders
                                    </a>
                                    <a href="edit-pet.php?id=<?php echo $pet['id']; ?>" 
                                       class="btn btn-secondary">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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