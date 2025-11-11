<?php
session_start();
require_once "../config/db.php";

// Get pet_id from URL
if (!isset($_GET['id'])) {
    die("Pet not found.");
}

$pet_id = intval($_GET['id']);

// Fetch pet info
$sql = "SELECT p.*, u.username, u.id as user_id 
        FROM pets p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();
$pet = $result->fetch_assoc();

if (!$pet) {
    die("Pet not found.");
}

// Fetch images
$imgSql = "SELECT filename FROM pet_images WHERE pet_id = ?";
$imgStmt = $conn->prepare($imgSql);
$imgStmt->bind_param("i", $pet_id);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Current logged-in user
$current_user_id = $_SESSION['user_id'] ?? null;

// Check verification status BEFORE any output
$verificationStatus = null;
if ($current_user_id) {
    $verifyQuery = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
    $verifyQuery->bind_param("i", $current_user_id);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    if ($verifyResult->num_rows > 0) {
        $verifyData = $verifyResult->fetch_assoc();
        $verificationStatus = $verifyData['verification_status'];
    }
}

// Handle POST requests BEFORE including header
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$current_user_id) {
        header("Location: login.php");
        exit();
    }

    $pet_id_post = intval($_POST['pet_id']);

    // Handle Buy Now - Check verification and redirect BEFORE any output
    if (isset($_POST['buy_now'])) {
        if ($verificationStatus == 'verified') {
            // User is verified - redirect to checkout
            header("Location: checkout.php?pet_ids=" . $pet_id_post);
            exit();
        } else {
            // Set session variable to show popup after page loads
            if ($verificationStatus == 'not verified') {
                $_SESSION['show_verification_popup'] = 'not_verified';
            } elseif ($verificationStatus == 'pending') {
                $_SESSION['show_verification_popup'] = 'pending';
            } else {
                $_SESSION['show_verification_popup'] = 'not_verified';
            }
            // Redirect back to same page to show popup
            header("Location: pet-details.php?id=" . $pet_id);
            exit();
        }
    }

    // Handle Remove from Cart
    if (isset($_POST['remove_from_cart'])) {
        $deleteSql = "DELETE FROM cart WHERE user_id = ? AND pet_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("ii", $current_user_id, $pet_id_post);
        $deleteStmt->execute();
        $_SESSION['cart_message'] = 'Pet removed from cart successfully!';
        header("Location: pet-details.php?id=" . $pet_id);
        exit();
    }

    // Handle Add to Cart
    if (isset($_POST['add_to_cart'])) {
        $checkSql = "SELECT * FROM cart WHERE user_id = ? AND pet_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        
        if ($checkStmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        
        $checkStmt->bind_param("ii", $current_user_id, $pet_id_post);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();

        if ($existing) {
            $_SESSION['cart_message'] = 'This pet is already in your cart.';
        } else {
            $insertSql = "INSERT INTO cart (user_id, pet_id) VALUES (?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            
            if ($insertStmt === false) {
                die("Error preparing statement: " . $conn->error);
            }
            
            $insertStmt->bind_param("ii", $current_user_id, $pet_id_post);
            
            if ($insertStmt->execute()) {
                $_SESSION['cart_message'] = 'Pet added to cart successfully!';
            } else {
                $_SESSION['cart_message'] = 'Error adding to cart.';
            }
        }
        header("Location: pet-details.php?id=" . $pet_id);
        exit();
    }
}

// NOW include header after all redirects are done
include_once "../includes/header.php";

// Check if pet is already in cart
$inCart = false;
if ($current_user_id) {
    $checkSql = "SELECT * FROM cart WHERE user_id = ? AND pet_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $current_user_id, $pet_id);
    $checkStmt->execute();
    $existing = $checkStmt->get_result()->fetch_assoc();
    $inCart = !empty($existing);
}

// Check for session messages
$showVerificationPopup = false;
$popupType = '';
if (isset($_SESSION['show_verification_popup'])) {
    $showVerificationPopup = true;
    $popupType = $_SESSION['show_verification_popup'];
    unset($_SESSION['show_verification_popup']);
}

$cartMessage = '';
if (isset($_SESSION['cart_message'])) {
    $cartMessage = $_SESSION['cart_message'];
    unset($_SESSION['cart_message']);
}
?>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($cartMessage): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?= addslashes($cartMessage) ?>',
        confirmButtonColor: '#5a4a3a',
        timer: 2000
    });
});
</script>
<?php endif; ?>

<?php if ($showVerificationPopup): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showVerificationPopup('<?= $popupType ?>');
});
</script>
<?php endif; ?>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.page-wrapper {
    background: linear-gradient(135deg, #f5ede0 0%, #EADDCA 100%);
    min-height: 100vh;
    padding: 40px 20px;
}

.details-container {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 40px;
    margin: 0 auto;
    max-width: 1200px;
    background: #ffffff;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

/* Gallery Styles */
.gallery-section {
    position: relative;
}

.gallery-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.main-image-container {
    position: relative;
    width: 100%;
    height: 500px;
    background: #f5ede0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(139, 111, 71, 0.15);
}

.main-image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.main-image-container:hover img {
    transform: scale(1.02);
}

.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.95);
    color: #333;
    border: none;
    width: 50px;
    height: 50px;
    font-size: 24px;
    cursor: pointer;
    border-radius: 50%;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10;
}

.nav-btn:hover {
    background: #fff;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.nav-btn.prev { left: 15px; }
.nav-btn.next { right: 15px; }

.thumbnails-container {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding: 10px 0;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

.thumbnails-container::-webkit-scrollbar {
    height: 8px;
}

.thumbnails-container::-webkit-scrollbar-track {
    background: #f5ede0;
    border-radius: 10px;
}

.thumbnails-container::-webkit-scrollbar-thumb {
    background: #c9a882;
    border-radius: 10px;
}

.thumbnails-container img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    cursor: pointer;
    border: 3px solid transparent;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.thumbnails-container img:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
}

.thumbnails-container img.active {
    border-color: #8b6f47;
    box-shadow: 0 6px 16px rgba(139, 111, 71, 0.4);
}

/* Pet Info Section */
.pet-info {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.pet-header {
    border-bottom: 2px solid #d4c4b0;
    padding-bottom: 20px;
}

.pet-header h1 {
    font-size: 32px;
    color: #2d3748;
    margin-bottom: 12px;
    font-weight: 700;
}

.price-tag {
    font-size: 28px;
    color: #8b6f47;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.price-tag::before {
    content: "💰";
    font-size: 24px;
}

.info-grid {
    display: grid;
    gap: 16px;
}

.info-item {
    display: flex;
    padding: 16px;
    background: #f5ede0;
    border-radius: 12px;
    border-left: 4px solid #c9a882;
    transition: all 0.3s ease;
}

.info-item:hover {
    background: #EADDCA;
    transform: translateX(4px);
}

.info-label {
    font-weight: 600;
    color: #4a5568;
    min-width: 100px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-value {
    color: #2d3748;
    flex: 1;
}

.description-box {
    background: #f5ede0;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid #c9a882;
}

.description-box h3 {
    color: #2d3748;
    margin-bottom: 12px;
    font-size: 18px;
}

.description-box p {
    color: #4a5568;
    line-height: 1.6;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(135deg, #c9a882 0%, #b89968 100%);
    border-radius: 12px;
    color: white;
}

.seller-info::before {
    content: "👤";
    font-size: 24px;
}

.seller-info strong {
    opacity: 0.9;
}

/* Action Buttons */
.action-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 8px;
}

.primary-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn {
    padding: 14px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.btn:active {
    transform: translateY(0);
}

.btn-cart {
    background: linear-gradient(135deg, #e8c9a0 0%, #d4b896 100%);
    color: #5a4438;
}

.btn-remove {
    background: linear-gradient(135deg, #d4a89d 0%, #c9988a 100%);
    color: #fff;
}

.btn-buy {
    background: linear-gradient(135deg, #a8917d 0%, #8b7a68 100%);
    color: #fff;
}

.btn-inquire {
    background: linear-gradient(135deg, #c9a882 0%, #b89968 100%);
    color: #fff;
    grid-column: 1 / -1;
}

.btn-edit {
    background: linear-gradient(135deg, #a8917d 0%, #8b7a68 100%);
    color: #fff;
    grid-column: 1 / -1;
}

/* Map Section */
.map-section {
    margin-top: 16px;
}

.map-section h3 {
    color: #2d3748;
    margin-bottom: 16px;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.map-section h3::before {
    content: "📍";
    font-size: 24px;
}

#map {
    width: 100%;
    height: 350px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.no-location {
    padding: 40px;
    text-align: center;
    background: #f5ede0;
    border-radius: 12px;
    color: #8b7a68;
    font-style: italic;
}

/* Responsive Design */
@media (max-width: 968px) {
    .details-container {
        grid-template-columns: 1fr;
        padding: 24px;
        gap: 32px;
    }

    .main-image-container {
        height: 400px;
    }

    .pet-header h1 {
        font-size: 28px;
    }

    .price-tag {
        font-size: 24px;
    }
}

@media (max-width: 640px) {
    .page-wrapper {
        padding: 20px 12px;
    }

    .details-container {
        padding: 20px;
        border-radius: 16px;
    }

    .main-image-container {
        height: 300px;
    }

    .nav-btn {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }

    .primary-actions {
        grid-template-columns: 1fr;
    }

    .thumbnails-container img {
        width: 80px;
        height: 80px;
    }

    .pet-header h1 {
        font-size: 24px;
    }

    .price-tag {
        font-size: 22px;
    }

    .info-item {
        flex-direction: column;
        gap: 8px;
    }

    .info-label {
        min-width: auto;
    }
}
</style>

<div class="page-wrapper">
    <div class="details-container">
        <!-- Left: Gallery -->
        <div class="gallery-section">
            <div class="gallery-container">
                <div class="main-image-container">
                    <?php if (!empty($images)): ?>
                        <img id="currentImage" src="../uploads/<?= htmlspecialchars($images[0]['filename']); ?>" alt="<?= htmlspecialchars($pet['name']); ?>">
                        <?php if (count($images) > 1): ?>
                            <button class="nav-btn prev" onclick="prevImage()" aria-label="Previous image">‹</button>
                            <button class="nav-btn next" onclick="nextImage()" aria-label="Next image">›</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <img src="../uploads/no-image.jpg" alt="No image available">
                    <?php endif; ?>
                </div>

                <?php if (count($images) > 1): ?>
                <div class="thumbnails-container">
                    <?php foreach ($images as $index => $img): ?>
                        <img src="../uploads/<?= htmlspecialchars($img['filename']); ?>" 
                             alt="Thumbnail <?= $index + 1 ?>"
                             class="<?= $index === 0 ? 'active' : '' ?>"
                             onclick="changeImage(this, <?= $index ?>)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Pet Information -->
        <div class="pet-info">
            <div class="pet-header">
                <h1><?= htmlspecialchars($pet['name']); ?></h1>
                <div class="price-tag">₱<?= number_format($pet['price'], 2); ?></div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">🐾 Breed:</span>
                    <span class="info-value"><?= htmlspecialchars($pet['breed']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">
                        <?= $pet['gender'] === 'Male' ? '♂️' : '♀️' ?> Gender:
                    </span>
                    <span class="info-value"><?= htmlspecialchars($pet['gender']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">🎂 Age:</span>
                    <span class="info-value"><?= htmlspecialchars($pet['age']); ?></span>
                </div>
            </div>

            <div class="description-box">
                <h3>About <?= htmlspecialchars($pet['name']); ?></h3>
                <p><?= nl2br(htmlspecialchars($pet['description'])); ?></p>
            </div>

            <div class="seller-info">
                <strong>Listed by:</strong> <?= htmlspecialchars($pet['username']); ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-section">
                <?php if ($current_user_id && $current_user_id == $pet['user_id']): ?>
                    <a href="edit-pet.php?id=<?= $pet['id']; ?>" class="btn btn-edit">
                        ✏️ Edit Your Listing
                    </a>
                <?php else: ?>
                    <a href="message-seller.php?pet_id=<?= $pet['id']; ?>&seller_id=<?= $pet['user_id']; ?>" class="btn btn-inquire">
                        💬 Contact Seller
                    </a>

                    <?php if ($current_user_id): ?>
                        <form method="POST" style="display: contents;">
                            <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                            <div class="primary-actions">
                                <?php if ($inCart): ?>
                                    <button type="submit" name="remove_from_cart" class="btn btn-remove">
                                        🗑️ Remove from Cart
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="add_to_cart" class="btn btn-cart">
                                        🛒 Add to Cart
                                    </button>
                                <?php endif; ?>
                                <button type="submit" name="buy_now" class="btn btn-buy">
                                    💳 Buy Now
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="primary-actions">
                            <a href="login.php" class="btn btn-cart">🛒 Add to Cart</a>
                            <a href="login.php" class="btn btn-buy">💳 Buy Now</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Pet Location -->
            <div class="map-section">
                <h3>Location</h3>
                <?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
                    <div id="map"></div>
                <?php else: ?>
                    <div class="no-location">📍 Location not available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let images = <?php echo json_encode(array_column($images, 'filename')); ?>;
let currentIndex = 0;

function changeImage(el, index) {
    const mainImage = document.getElementById("currentImage");
    mainImage.style.opacity = '0';
    
    setTimeout(() => {
        mainImage.src = el.src;
        currentIndex = index;
        mainImage.style.opacity = '1';
        
        document.querySelectorAll(".thumbnails-container img").forEach(img => img.classList.remove("active"));
        el.classList.add("active");
    }, 150);
}

function prevImage() {
    if (images.length === 0) return;
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    updateImage();
}

function nextImage() {
    if (images.length === 0) return;
    currentIndex = (currentIndex + 1) % images.length;
    updateImage();
}

function updateImage() {
    const mainImage = document.getElementById("currentImage");
    mainImage.style.opacity = '0';
    
    setTimeout(() => {
        mainImage.src = "../uploads/" + images[currentIndex];
        mainImage.style.opacity = '1';
        
        let thumbs = document.querySelectorAll(".thumbnails-container img");
        thumbs.forEach(img => img.classList.remove("active"));
        if (thumbs[currentIndex]) {
            thumbs[currentIndex].classList.add("active");
            thumbs[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }, 150);
}

// Add smooth transition to main image
document.getElementById("currentImage").style.transition = 'opacity 0.3s ease';

// Verification popup function
function showVerificationPopup(status) {
    if (status === 'not_verified') {
        Swal.fire({
            title: 'Account Not Verified',
            text: 'Please verify your account to purchase pets.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Verify Now',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#5a4a3a',
            cancelButtonColor: '#8d7d6d',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'verify.php';
            }
        });
    } else if (status === 'pending') {
        Swal.fire({
            title: 'Verification Pending',
            text: 'Your verification has been submitted. Please wait for admin approval before you can purchase pets.',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#5a4a3a',
        });
    }
}
</script>

<?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
<!-- Leaflet -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

<!-- Fullscreen Plugin -->
<script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>
<link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css"/>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let lat = <?= $pet['latitude'] ?>;
    let lon = <?= $pet['longitude'] ?>;

    let map = L.map("map", {
        fullscreenControl: true
    }).setView([lat, lon], 13);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    L.marker([lat, lon]).addTo(map)
        .bindPopup("<b><?= htmlspecialchars($pet['name']); ?></b><br>Pet Location")
        .openPopup();
});
</script>
<?php endif; ?>

<?php include_once "../includes/footer.php"; ?>