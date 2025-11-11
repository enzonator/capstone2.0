<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Logged-in user
$user_id = $_SESSION['user_id'] ?? null;

// Get filter/sort parameters
$breed = $_GET['breed'] ?? '';
$gender = $_GET['gender'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$sort = $_GET['sort'] ?? 'recent';

// Fetch distinct breeds for sidebar (only from available pets)
$breedQuery = $conn->query("SELECT DISTINCT breed FROM pets WHERE status = 'available' ORDER BY breed");
$breeds = $breedQuery->fetch_all(MYSQLI_ASSOC);

// Build query - ONLY AVAILABLE PETS
$sql = "SELECT p.*,
        (SELECT filename FROM pet_images WHERE pet_id = p.id LIMIT 1) as image1,
        (SELECT filename FROM pet_images WHERE pet_id = p.id LIMIT 1,1) as image2
        FROM pets p 
        WHERE p.status = 'available'";

$params = [];
$types = "";

// Apply filters
if (!empty($breed)) {
    $sql .= " AND p.breed = ?";
    $params[] = $breed;
    $types .= "s";
}
if (!empty($gender)) {
    $sql .= " AND p.gender = ?";
    $params[] = $gender;
    $types .= "s";
}
if (!empty($price_min)) {
    $sql .= " AND p.price >= ?";
    $params[] = $price_min;
    $types .= "d";
}
if (!empty($price_max)) {
    $sql .= " AND p.price <= ?";
    $params[] = $price_max;
    $types .= "d";
}

// Apply sorting
switch ($sort) {
    case "price_high":
        $sql .= " ORDER BY p.price DESC";
        break;
    case "price_low":
        $sql .= " ORDER BY p.price ASC";
        break;
    default: // most recent
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$pets = $result->fetch_all(MYSQLI_ASSOC);

// Fetch wishlist items for current user
$wishlist = [];
if ($user_id) {
    $wishQuery = $conn->prepare("SELECT pet_id FROM wishlist WHERE user_id = ?");
    $wishQuery->bind_param("i", $user_id);
    $wishQuery->execute();
    $wishResult = $wishQuery->get_result();
    while ($row = $wishResult->fetch_assoc()) {
        $wishlist[] = $row['pet_id'];
    }
}
?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #FFF8F0 0%, #FFE8D6 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
}

.products-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Sidebar */
.sidebar {
    background: #FFFFFF;
    padding: 25px;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(210, 180, 140, 0.15);
    height: fit-content;
    position: sticky;
    top: 20px;
    border: 1px solid #F4E4D7;
}

.sidebar h3 {
    margin-bottom: 25px;
    font-size: 22px;
    color: #8B6F47;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar h3::before {
    content: "🔍";
    font-size: 24px;
}

.sidebar label {
    display: block;
    margin: 18px 0 8px;
    font-weight: 600;
    color: #6B5744;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.sidebar input, .sidebar select {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 12px;
    border: 2px solid #F4E4D7;
    border-radius: 12px;
    background: #FFF8F0;
    color: #5D4E37;
    font-size: 14px;
    transition: all 0.3s ease;
}

.sidebar input:focus, .sidebar select:focus {
    outline: none;
    border-color: #D4A574;
    background: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
}

.sidebar button {
    width: 100%;
    padding: 14px;
    margin-top: 10px;
    background: linear-gradient(135deg, #D4A574 0%, #B8936A 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(212, 165, 116, 0.3);
}

.sidebar button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(212, 165, 116, 0.4);
    background: linear-gradient(135deg, #B8936A 0%, #A0805E 100%);
}

.sidebar button:active {
    transform: translateY(0);
}

/* Main Content */
.products-main {
    background: #FFFFFF;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(210, 180, 140, 0.15);
    border: 1px solid #F4E4D7;
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #FFF8F0;
    flex-wrap: wrap;
    gap: 15px;
}

.products-header h2 {
    font-size: 28px;
    color: #8B6F47;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.products-header h2::before {
    content: "🐾";
    font-size: 30px;
}

.products-header form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.products-header label {
    font-weight: 600;
    color: #6B5744;
    font-size: 14px;
}

.products-header select {
    padding: 10px 15px;
    border-radius: 10px;
    border: 2px solid #F4E4D7;
    background: #FFF8F0;
    color: #5D4E37;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.products-header select:hover {
    border-color: #D4A574;
    background: #FFFFFF;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 25px;
}

.product-card {
    border: 2px solid #F4E4D7;
    border-radius: 16px;
    overflow: hidden;
    background: #FFFBF7;
    transition: all 0.3s ease;
    position: relative;
    box-shadow: 0 4px 12px rgba(210, 180, 140, 0.1);
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 32px rgba(212, 165, 116, 0.25);
    border-color: #D4A574;
}

.product-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    transition: opacity 0.3s ease;
}

.product-card .image-wrapper {
    position: relative;
    width: 100%;
    height: 220px;
    background: #F4E4D7;
    overflow: hidden;
}

.product-card .image-wrapper img.second {
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
}

.product-card .image-wrapper:hover img.first {
    opacity: 0;
}

.product-card .image-wrapper:hover img.second {
    opacity: 1;
}

.product-card .wishlist-form {
    position: absolute;
    top: 12px;
    right: 12px;
}

.product-card .wishlist-btn {
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid #F4E4D7;
    border-radius: 50%;
    padding: 10px;
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
    z-index: 10;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.product-card .wishlist-btn:hover {
    transform: scale(1.15);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    background: #FFFFFF;
}

.product-card .wishlist-btn.hearted {
    background: linear-gradient(135deg, #FFE8E8 0%, #FFD5D5 100%);
    border-color: #FFB8B8;
}

.product-card .info {
    padding: 20px;
    background: linear-gradient(to bottom, #FFFBF7 0%, #FFF8F0 100%);
}

.product-card h4 {
    margin: 0 0 12px;
    font-size: 19px;
    color: #5D4E37;
    font-weight: 700;
}

.product-card p {
    margin: 8px 0;
    font-size: 14px;
    color: #6B5744;
    display: flex;
    align-items: center;
    gap: 6px;
}

.product-card p strong {
    color: #8B6F47;
    min-width: 50px;
}

.product-card p:last-of-type {
    font-size: 22px;
    font-weight: 800;
    color: #D4A574;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #F4E4D7;
}

.product-card a {
    display: block;
    margin-top: 15px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #D4A574 0%, #B8936A 100%);
    color: white;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(212, 165, 116, 0.3);
}

.product-card a:hover {
    background: linear-gradient(135deg, #B8936A 0%, #A0805E 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(212, 165, 116, 0.4);
}

.no-pets-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, #FFF8F0 0%, #FFE8D6 100%);
    border-radius: 16px;
    border: 2px dashed #D4A574;
}

.no-pets-message h3 {
    font-size: 32px;
    color: #8B6F47;
    margin-bottom: 15px;
    font-weight: 700;
}

.no-pets-message p {
    font-size: 18px;
    color: #6B5744;
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .products-container {
        grid-template-columns: 1fr;
    }
    
    .sidebar {
        position: static;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
}

@media (max-width: 640px) {
    .products-container {
        padding: 20px 10px;
    }
    
    .products-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .products-header form {
        width: 100%;
    }
    
    .products-header select {
        flex: 1;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="products-container">
    <!-- Sidebar Filter -->
    <aside class="sidebar">
        <h3>Filter Pets</h3>
        <form method="GET" action="products.php">
            <label for="breed">🐕 Breed</label>
            <select name="breed" id="breed">
                <option value="">All Breeds</option>
                <?php foreach ($breeds as $b): ?>
                    <option value="<?= htmlspecialchars($b['breed']); ?>" 
                        <?= $breed == $b['breed'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['breed']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="gender">⚥ Gender</label>
            <select name="gender" id="gender">
                <option value="">All Genders</option>
                <option value="Male" <?= $gender == 'Male' ? 'selected' : '' ?>>♂️ Male</option>
                <option value="Female" <?= $gender == 'Female' ? 'selected' : '' ?>>♀️ Female</option>
            </select>

            <label for="price_min">💰 Min Price</label>
            <input type="number" name="price_min" placeholder="₱0" value="<?= htmlspecialchars($price_min); ?>">

            <label for="price_max">💰 Max Price</label>
            <input type="number" name="price_max" placeholder="₱999,999" value="<?= htmlspecialchars($price_max); ?>">

            <button type="submit">Apply Filters</button>
        </form>
    </aside>

    <!-- Main Products -->
    <section class="products-main">
        <div class="products-header">
            <h2>Available Pets (<?= count($pets) ?>)</h2>
            <form method="GET" action="products.php">
                <input type="hidden" name="breed" value="<?= htmlspecialchars($breed); ?>">
                <input type="hidden" name="gender" value="<?= htmlspecialchars($gender); ?>">
                <input type="hidden" name="price_min" value="<?= htmlspecialchars($price_min); ?>">
                <input type="hidden" name="price_max" value="<?= htmlspecialchars($price_max); ?>">

                <label for="sort">Sort By:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="recent" <?= $sort=='recent'?'selected':''; ?>>Most Recent</option>
                    <option value="price_high" <?= $sort=='price_high'?'selected':''; ?>>Price: High → Low</option>
                    <option value="price_low" <?= $sort=='price_low'?'selected':''; ?>>Price: Low → High</option>
                </select>
            </form>
        </div>

        <div class="products-grid">
            <?php if (!empty($pets)): ?>
                <?php foreach ($pets as $pet): ?>
                    <div class="product-card">
                        <div class="image-wrapper">
                            <!-- Wishlist Button (only for logged in users) -->
                            <?php if ($user_id): ?>
                                <form method="POST" action="wishlist.php" class="wishlist-form">
                                    <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                                    <button type="submit" class="wishlist-btn <?= in_array($pet['id'], $wishlist) ? 'hearted' : ''; ?>" title="Add to Wishlist">
                                        <?= in_array($pet['id'], $wishlist) ? "❤️" : "🤍"; ?>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <img src="../uploads/<?= htmlspecialchars($pet['image1'] ?? 'no-image.png'); ?>" 
                                 alt="<?= htmlspecialchars($pet['name']); ?>" class="first">
                            <?php if (!empty($pet['image2'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($pet['image2']); ?>" 
                                     alt="<?= htmlspecialchars($pet['name']); ?> alternate view" class="second">
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <h4><?= htmlspecialchars($pet['name']); ?></h4>
                            <p><strong>Breed:</strong> <?= htmlspecialchars($pet['breed']); ?></p>
                            <p><strong>Gender:</strong> <?= htmlspecialchars($pet['gender']); ?></p>
                            <p><strong>Age:</strong> <?= htmlspecialchars($pet['age']); ?> years old</p>
                            <p>₱<?= number_format($pet['price'], 2); ?></p>
                            <a href="pet-details.php?id=<?= $pet['id']; ?>">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-pets-message">
                    <h3>😿 No Available Pets Found</h3>
                    <p>Try adjusting your filters or check back later for new listings.<br>We're always adding adorable new companions!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include_once "../includes/footer.php"; ?>