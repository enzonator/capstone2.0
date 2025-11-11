<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch pets in wishlist
$sql = "SELECT p.*, 
        (SELECT filename FROM pet_images WHERE pet_id = p.id LIMIT 1) as image1,
        (SELECT filename FROM pet_images WHERE pet_id = p.id LIMIT 1,1) as image2
        FROM wishlist w
        JOIN pets p ON w.pet_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$wishlist = $result->fetch_all(MYSQLI_ASSOC);
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

.wishlist-container {
    flex-grow: 1;
    padding: 30px;
    max-width: 100%;
    width: 100%;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%);
}

/* Header Section */
.wishlist-header {
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    padding: 35px 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 8px 24px rgba(139, 111, 71, 0.25);
    position: relative;
    overflow: hidden;
}

.wishlist-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
}

.wishlist-header-content {
    position: relative;
    z-index: 1;
}

.wishlist-header h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.wishlist-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.05rem;
    font-weight: 400;
}

.wishlist-count {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    margin-left: 10px;
}

/* Wishlist Grid */
.wishlist-content {
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(139, 111, 71, 0.12);
    border: 1px solid rgba(212, 196, 176, 0.3);
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
}

.wishlist-card {
    border: 2px solid rgba(139, 111, 71, 0.15);
    border-radius: 16px;
    overflow: hidden;
    background: #fafafa;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 2px 8px rgba(139, 111, 71, 0.08);
}

.wishlist-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(139, 111, 71, 0.2);
    border-color: rgba(139, 111, 71, 0.3);
}

.wishlist-card .image-wrapper {
    position: relative;
    width: 100%;
    height: 220px;
    overflow: hidden;
    background: linear-gradient(135deg, #E8DCC8 0%, #D4C4B0 100%);
}

.wishlist-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.4s ease;
}

.wishlist-card .image-wrapper img.second {
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
}

.wishlist-card .image-wrapper:hover img.first {
    opacity: 0;
    transform: scale(1.1);
}

.wishlist-card .image-wrapper:hover img.second {
    opacity: 1;
    transform: scale(1.1);
}

.wishlist-card .info {
    padding: 18px;
}

.wishlist-card h4 {
    margin: 0 0 12px;
    font-size: 1.15rem;
    color: #5D4E37;
    font-weight: 700;
}

.wishlist-card p {
    margin: 6px 0;
    font-size: 0.9rem;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 6px;
}

.wishlist-card p strong {
    color: #5D4E37;
    font-weight: 600;
}

.wishlist-card .price {
    font-size: 1.3rem;
    color: #8B6F47;
    font-weight: 700;
    margin: 12px 0;
}

.wishlist-card a {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 12px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #6B5540 0%, #8B6F47 100%);
    color: white;
    border-radius: 10px;
    font-size: 0.9rem;
    text-decoration: none;
    text-align: center;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(107, 85, 64, 0.4);
}

.wishlist-card a i {
    font-size: 1rem;
    font-weight: bold;
}

.wishlist-card a:hover {
    background: linear-gradient(135deg, #5D4E37 0%, #6B5540 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(93, 78, 55, 0.5);
}

.remove-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid rgba(220, 53, 69, 0.3);
    border-radius: 50%;
    padding: 8px 11px;
    cursor: pointer;
    font-size: 16px;
    color: #dc3545;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.remove-btn:hover {
    transform: scale(1.15) rotate(90deg);
    background: #dc3545;
    color: white;
    border-color: #dc3545;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

/* Empty State */
.empty-wishlist {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-wishlist i {
    font-size: 4rem;
    color: #A0826D;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-wishlist h3 {
    color: #5D4E37;
    font-size: 1.5rem;
    margin-bottom: 10px;
}

.empty-wishlist p {
    font-size: 1.05rem;
    margin-bottom: 25px;
}

.empty-wishlist a {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(139, 111, 71, 0.3);
}

.empty-wishlist a:hover {
    background: linear-gradient(135deg, #A0826D 0%, #8B6F47 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 111, 71, 0.4);
}

/* Responsive */
@media (max-width: 991px) {
    .dashboard {
        flex-direction: column;
    }
    
    .wishlist-container {
        padding: 20px;
    }

    .wishlist-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px;
    }
}

@media (max-width: 768px) {
    .wishlist-content {
        padding: 20px;
    }

    .wishlist-header h2 {
        font-size: 1.8rem;
    }

    .wishlist-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 576px) {
    .wishlist-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard">
    <!-- Sidebar -->
    <?php include_once "../includes/sidebar.php"; ?>

    <!-- Wishlist Section -->
    <div class="wishlist-container">
        <!-- Header -->
        <div class="wishlist-header">
            <div class="wishlist-header-content">
                <h2>
                    <i class="bi bi-heart-fill"></i>
                    My Wishlisted Pets
                    <span class="wishlist-count"><?= count($wishlist); ?> <?= count($wishlist) === 1 ? 'Pet' : 'Pets' ?></span>
                </h2>
                <p>Your favorite pets all in one place</p>
            </div>
        </div>

        <!-- Wishlist Content -->
        <div class="wishlist-content">
            <?php if (!empty($wishlist)): ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist as $pet): ?>
                        <div class="wishlist-card">
                            <div class="image-wrapper">
                                <form method="POST" action="wishlist_remove.php">
                                    <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                                    <button type="submit" class="remove-btn" title="Remove from Wishlist">✖</button>
                                </form>

                                <img src="../uploads/<?= htmlspecialchars($pet['image1'] ?? 'no-image.png'); ?>" 
                                     alt="<?= htmlspecialchars($pet['name']); ?>" class="first">
                                <?php if (!empty($pet['image2'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($pet['image2']); ?>" 
                                         alt="<?= htmlspecialchars($pet['name']); ?> (alternate)" class="second">
                                <?php endif; ?>
                            </div>
                            <div class="info">
                                <h4><?= htmlspecialchars($pet['name']); ?></h4>
                                <p><strong><i class="bi bi-tag"></i> Type:</strong> <?= htmlspecialchars($pet['type']); ?></p>
                                <p><strong><i class="bi bi-award"></i> Breed:</strong> <?= htmlspecialchars($pet['breed']); ?></p>
                                <p class="price">₱<?= number_format($pet['price'], 2); ?></p>
                                <a href="pet-details.php?id=<?= $pet['id']; ?>">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-wishlist">
                    <i class="bi bi-heart"></i>
                    <h3>Your Wishlist is Empty</h3>
                    <p>Start adding your favorite pets to keep track of them!</p>
                    <a href="browse-pets.php">
                        <i class="bi bi-search"></i> Browse Pets
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>