<?php
session_start();
require_once "../config/db.php";

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

// Get pet_id from URL
if (!isset($_GET['id'])) {
    die("Pet not found.");
}

$pet_id = intval($_GET['id']);

// Fetch pet info
$sql = "SELECT p.*, u.username, u.email, u.id as user_id 
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

include __DIR__ . "/../includes/admin-sidebar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Details - Admin View</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css"/>
</head>
<body>

<style>
body {
    background: #faf8f5;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.pet-details-page {
    margin-left: 260px;
    padding: 30px;
    min-height: 100vh;
    width: calc(100% - 260px);
    box-sizing: border-box;
}

.content-wrapper {
    background: #fff;
    padding: 32px;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(92, 74, 58, 0.08);
    border: 1px solid rgba(245, 230, 211, 0.5);
}

.page-header {
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 2px solid #F5E6D3;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h2 {
    margin: 0;
    font-weight: 600;
    color: #5C4A3A;
    font-size: 28px;
    letter-spacing: -0.5px;
}

.back-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, #D4A574, #C19A6B);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(212, 165, 116, 0.3);
}

.back-btn:hover {
    background: linear-gradient(135deg, #C19A6B, #A67C52);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(212, 165, 116, 0.4);
}

.details-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

/* Gallery Styles */
.gallery-section {
    background: #faf8f5;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #F5E6D3;
}

.gallery-container {
    display: flex;
    gap: 15px;
}

.thumbnails {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 500px;
    overflow-y: auto;
}

.thumbnails::-webkit-scrollbar {
    width: 6px;
}

.thumbnails::-webkit-scrollbar-track {
    background: rgba(92, 74, 58, 0.05);
    border-radius: 3px;
}

.thumbnails::-webkit-scrollbar-thumb {
    background: rgba(92, 74, 58, 0.2);
    border-radius: 3px;
}

.thumbnails img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.thumbnails img:hover {
    border-color: #D4A574;
}

.thumbnails img.active {
    border-color: #C19A6B;
    box-shadow: 0 2px 8px rgba(193, 154, 107, 0.4);
}

.main-image {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.main-image img {
    max-width: 100%;
    max-height: 500px;
    border-radius: 8px;
    object-fit: contain;
}

.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(92, 74, 58, 0.7);
    color: white;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 10px 14px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.nav-btn:hover {
    background: rgba(92, 74, 58, 0.9);
}

.nav-btn.prev { left: 10px; }
.nav-btn.next { right: 10px; }

.no-images {
    text-align: center;
    color: #8B7355;
    padding: 40px;
}

/* Pet Info Section */
.info-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.info-card {
    background: #faf8f5;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #F5E6D3;
}

.info-card h3 {
    margin: 0 0 16px 0;
    color: #5C4A3A;
    font-size: 18px;
    font-weight: 600;
    padding-bottom: 12px;
    border-bottom: 2px solid #F5E6D3;
}

.info-row {
    display: flex;
    margin-bottom: 12px;
    font-size: 14px;
}

.info-label {
    font-weight: 600;
    color: #5C4A3A;
    min-width: 120px;
}

.info-value {
    color: #8B7355;
    flex: 1;
}

.price-badge {
    background: linear-gradient(135deg, #D4A574, #C19A6B);
    color: #fff;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    display: inline-block;
    box-shadow: 0 2px 6px rgba(212, 165, 116, 0.3);
}

.status-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.status-badge.available {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
}

.status-badge.sold {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545;
}

.description-text {
    color: #5C4A3A;
    line-height: 1.6;
    white-space: pre-wrap;
}

.seller-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.seller-name {
    color: #5C4A3A;
    font-weight: 600;
}

.seller-email {
    color: #8B7355;
    font-size: 13px;
}

/* Map Section */
.map-section {
    margin-top: 20px;
}

#map {
    width: 100%;
    height: 400px;
    border-radius: 12px;
    border: 2px solid #F5E6D3;
    margin-top: 12px;
}

.no-location {
    text-align: center;
    color: #8B7355;
    font-style: italic;
    padding: 40px;
    background: #faf8f5;
    border-radius: 12px;
    border: 1px solid #F5E6D3;
    margin-top: 12px;
}

/* Admin Notice */
.admin-notice {
    background: rgba(212, 165, 116, 0.1);
    border: 1px solid rgba(212, 165, 116, 0.3);
    color: #8B7355;
    padding: 14px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .details-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .pet-details-page {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }

    .gallery-container {
        flex-direction: column;
        align-items: center;
    }

    .thumbnails {
        flex-direction: row;
        gap: 8px;
        max-width: 100%;
        max-height: none;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .thumbnails img {
        width: 70px;
        height: 70px;
    }

    .main-image img {
        max-height: 350px;
    }
}
</style>

<div class="pet-details-page">
    <div class="content-wrapper">
        <div class="page-header">
            <h2>🐾 Pet Details (Admin View)</h2>
            <a href="products.php" class="back-btn">← Back to Manage Pets</a>
        </div>

        <div class="admin-notice">
            ℹ️ You are viewing this pet listing as an administrator. This is a read-only view.
        </div>

        <div class="details-container">
            <!-- Left: Gallery -->
            <div class="gallery-section">
                <div class="gallery-container">
                    <?php if (!empty($images)): ?>
                        <div class="thumbnails">
                            <?php foreach ($images as $index => $img): ?>
                                <img src="../uploads/<?= htmlspecialchars($img['filename']); ?>" 
                                     alt="Thumbnail"
                                     class="<?= $index === 0 ? 'active' : '' ?>"
                                     onclick="changeImage(this, <?= $index ?>)">
                            <?php endforeach; ?>
                        </div>

                        <div class="main-image">
                            <img id="currentImage" src="../uploads/<?= htmlspecialchars($images[0]['filename']); ?>" alt="Pet Image">
                            <button class="nav-btn prev" onclick="prevImage()">&#10094;</button>
                            <button class="nav-btn next" onclick="nextImage()">&#10095;</button>
                        </div>
                    <?php else: ?>
                        <div class="no-images">
                            <p>📷 No images available for this pet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Pet Information -->
            <div class="info-section">
                <!-- Basic Information -->
                <div class="info-card">
                    <h3>Basic Information</h3>
                    <div class="info-row">
                        <span class="info-label">Pet ID:</span>
                        <span class="info-value">#<?= $pet['id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><strong><?= htmlspecialchars($pet['name']); ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type:</span>
                        <span class="info-value"><?= htmlspecialchars($pet['type']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Breed:</span>
                        <span class="info-value"><?= htmlspecialchars($pet['breed']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Age:</span>
                        <span class="info-value"><?= htmlspecialchars($pet['age']); ?> years old</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Price:</span>
                        <span class="info-value">
                            <span class="price-badge">₱<?= number_format($pet['price'], 2); ?></span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="status-badge <?= strtolower($pet['status']); ?>">
                                <?= htmlspecialchars(ucfirst($pet['status'])); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date Listed:</span>
                        <span class="info-value"><?= date('M d, Y g:i A', strtotime($pet['created_at'])); ?></span>
                    </div>
                </div>

                <!-- Description -->
                <div class="info-card">
                    <h3>Description</h3>
                    <div class="description-text">
                        <?= nl2br(htmlspecialchars($pet['description'])); ?>
                    </div>
                </div>

                <!-- Seller Information -->
                <div class="info-card">
                    <h3>Seller Information</h3>
                    <div class="seller-info">
                        <div class="info-row">
                            <span class="info-label">Seller ID:</span>
                            <span class="info-value">#<?= $pet['user_id']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Username:</span>
                            <span class="info-value seller-name"><?= htmlspecialchars($pet['username']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value seller-email"><?= htmlspecialchars($pet['email']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="info-card map-section">
                    <h3>Pet Location</h3>
                    <?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
                        <div id="map"></div>
                    <?php else: ?>
                        <div class="no-location">
                            📍 No location information provided for this pet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image Gallery Script
let images = <?php echo json_encode(array_column($images, 'filename')); ?>;
let currentIndex = 0;

function changeImage(el, index) {
    document.getElementById("currentImage").src = el.src;
    currentIndex = index;

    document.querySelectorAll(".thumbnails img").forEach(img => img.classList.remove("active"));
    el.classList.add("active");
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
    mainImage.src = "../uploads/" + images[currentIndex];

    let thumbs = document.querySelectorAll(".thumbnails img");
    thumbs.forEach(img => img.classList.remove("active"));
    thumbs[currentIndex].classList.add("active");
}
</script>

<?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
<!-- Leaflet Map Scripts -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    let lat = <?= $pet['latitude'] ?>;
    let lon = <?= $pet['longitude'] ?>;

    let map = L.map("map", {
        fullscreenControl: true
    }).setView([lat, lon], 13);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
    }).addTo(map);

    L.marker([lat, lon]).addTo(map)
        .bindPopup("<?= htmlspecialchars($pet['name']); ?>'s Location")
        .openPopup();
});
</script>
<?php endif; ?>

</body>
</html>