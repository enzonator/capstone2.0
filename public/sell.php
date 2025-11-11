<?php
session_start();
require_once "../config/db.php";

// Only logged-in users can sell
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check verification status
$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$verification_status = $user['verification_status'] ?? 'not verified';

$is_verified = ($verification_status === 'verified');
$is_pending = ($verification_status === 'pending');

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $is_verified) {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $address = $_POST['address'];
    
    // Health information
    $vaccinated = isset($_POST['vaccinated']) ? 1 : 0;
    $neutered = isset($_POST['neutered']) ? 1 : 0;
    $health_status = $_POST['health_status'] ?? '';

    $sql = "INSERT INTO pets (user_id, name, breed, gender, age, price, description, latitude, longitude, address, vaccinated, neutered, health_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("issssdssdssis", $user_id, $name, $breed, $gender, $age, $price, $description, $latitude, $longitude, $address, $vaccinated, $neutered, $health_status);

    if ($stmt->execute()) {
        $pet_id = $stmt->insert_id;

        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $filename = time() . "_" . basename($_FILES["images"]["name"][$key]);
                $targetFilePath = $targetDir . $filename;

                if (move_uploaded_file($tmpName, $targetFilePath)) {
                    $imgSql = "INSERT INTO pet_images (pet_id, filename) VALUES (?, ?)";
                    $imgStmt = $conn->prepare($imgSql);
                    $imgStmt->bind_param("is", $pet_id, $filename);
                    $imgStmt->execute();
                }
            }
        }

        $message = "success";
    } else {
        $message = "Error: " . $stmt->error;
    }
}
?>

<?php include_once "../includes/header.php"; ?>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #f5ede0 0%, #EADDCA 100%);
    }

    .page-header {
        background: linear-gradient(135deg, #8b6f47 0%, #a8917d 100%);
        padding: 60px 20px;
        text-align: center;
        color: #fff;
        margin-bottom: 40px;
        box-shadow: 0 4px 20px rgba(139, 111, 71, 0.2);
    }

    .page-header h1 {
        font-size: 42px;
        font-weight: 700;
        margin-bottom: 12px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .page-header p {
        font-size: 18px;
        opacity: 0.95;
    }

    .sell-container {
        max-width: 1000px;
        margin: 0 auto 60px;
        padding: 0 20px;
    }

    .sell-card {
        background: #fff;
        border: 2px solid #d4c4b0;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 8px 30px rgba(139, 111, 71, 0.12);
    }

    .success-message {
        padding: 16px 20px;
        margin-bottom: 30px;
        border-radius: 12px;
        background: #d4edda;
        border: 2px solid #c3e6cb;
        color: #155724;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-section {
        margin-bottom: 35px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: #8b6f47;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 2px solid #EADDCA;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 14px;
        font-weight: 600;
        color: #8b6f47;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .form-input,
    .form-select,
    .form-textarea {
        padding: 14px 16px;
        border: 2px solid #EADDCA;
        border-radius: 10px;
        font-size: 15px;
        font-family: inherit;
        color: #5a4438;
        background: #fff;
        transition: all 0.3s ease;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #8b6f47;
        box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .select2-container .select2-selection--single {
        height: 52px !important;
        padding: 12px 16px !important;
        border: 2px solid #EADDCA !important;
        border-radius: 10px !important;
        font-size: 15px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
        color: #5a4438 !important;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #8b6f47 !important;
        box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1) !important;
    }

    /* Gender Selection */
    .gender-selection {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .gender-option {
        position: relative;
    }

    .gender-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .gender-card {
        background: #fff;
        border: 2px solid #EADDCA;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .gender-card:hover {
        border-color: #8b6f47;
        background: #fefdfb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 111, 71, 0.15);
    }

    .gender-option input[type="radio"]:checked + .gender-card {
        border-color: #8b6f47;
        background: linear-gradient(135deg, #f5ede0 0%, #EADDCA 100%);
        box-shadow: 0 4px 16px rgba(139, 111, 71, 0.2);
    }

    .gender-icon {
        font-size: 48px;
        margin-bottom: 8px;
    }

    .gender-label {
        font-size: 16px;
        font-weight: 600;
        color: #8b6f47;
    }

    /* Health Section */
    .health-section {
        background: #f5ede0;
        border: 2px solid #d4c4b0;
        border-radius: 12px;
        padding: 25px;
    }

    .health-checkboxes {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .checkbox-card {
        background: #fff;
        border: 2px solid #EADDCA;
        border-radius: 10px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .checkbox-card:hover {
        border-color: #8b6f47;
        background: #fefdfb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(139, 111, 71, 0.1);
    }

    .checkbox-card input[type="checkbox"] {
        width: 22px;
        height: 22px;
        margin-top: 2px;
        cursor: pointer;
        accent-color: #8b6f47;
    }

    .checkbox-card input[type="checkbox"]:checked ~ .checkbox-content {
        color: #8b6f47;
    }

    .checkbox-content {
        flex: 1;
    }

    .checkbox-content strong {
        display: block;
        font-size: 15px;
        color: #8b6f47;
        margin-bottom: 4px;
    }

    .checkbox-content small {
        font-size: 13px;
        color: #7d6d5d;
    }

    /* Image Upload */
    .image-upload-wrapper {
        position: relative;
        border: 3px dashed #d4c4b0;
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        background: #fefdfb;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .image-upload-wrapper:hover {
        border-color: #8b6f47;
        background: #f5ede0;
    }

    .image-upload-wrapper input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        opacity: 0;
        cursor: pointer;
    }

    .upload-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }

    .upload-text {
        font-size: 16px;
        color: #8b6f47;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .upload-hint {
        font-size: 14px;
        color: #7d6d5d;
    }

    /* Image Preview Styles */
    .preview-item {
        position: relative;
        border: 2px solid #EADDCA;
        border-radius: 10px;
        overflow: hidden;
        aspect-ratio: 1;
    }

    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-remove {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(212, 168, 157, 0.95);
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .preview-remove:hover {
        background: #c9988a;
        transform: scale(1.1);
    }

    /* Map Section */
    .map-container {
        border: 2px solid #d4c4b0;
        border-radius: 12px;
        overflow: hidden;
        height: 350px;
        margin-top: 10px;
    }

    #map {
        height: 100%;
        width: 100%;
    }

    .location-info {
        padding: 16px;
        background: #f5ede0;
        border: 2px solid #d4c4b0;
        border-radius: 10px;
        font-size: 14px;
        color: #5a4438;
        margin-top: 15px;
    }

    .location-info strong {
        display: block;
        margin-bottom: 8px;
        font-size: 15px;
        color: #8b6f47;
    }

    .location-info span {
        display: block;
        color: #7d6d5d;
        line-height: 1.6;
    }

    /* Buttons */
    .btn-submit {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #8b6f47 0%, #a8917d 100%);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 20px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(139, 111, 71, 0.3);
    }

    .btn-verify {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #d4a89d 0%, #c9988a 100%);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-verify:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(212, 168, 157, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            padding: 40px 20px;
        }

        .page-header h1 {
            font-size: 32px;
        }

        .page-header p {
            font-size: 16px;
        }

        .sell-card {
            padding: 25px;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .gender-selection {
            grid-template-columns: 1fr;
        }

        .health-checkboxes {
            grid-template-columns: 1fr;
        }

        .map-container {
            height: 300px;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1>🐾 List Your Cat for Sale</h1>
    <p>Find a loving home for your furry friend</p>
</div>

<div class="sell-container">
    <div class="sell-card">
        <?php if ($message === "success"): ?>
            <div class="success-message">
                ✅ <span>Your pet has been listed successfully!</span>
            </div>
        <?php elseif (!empty($message) && $message !== "success"): ?>
            <div class="success-message" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;">
                ❌ <span><?= htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="sellForm">
            <!-- Basic Information -->
            <div class="form-section">
                <div class="section-title">
                    📝 Basic Information
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Pet Name *</label>
                        <input type="text" name="name" class="form-input" placeholder="e.g., Fluffy" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Breed *</label>
                        <select name="breed" id="breed" class="form-select" required>
                            <option value="">Select Breed</option>
                            <option value="Abyssinian">Abyssinian</option>
                            <option value="American Bobtail">American Bobtail</option>
                            <option value="American Curl">American Curl</option>
                            <option value="American Shorthair">American Shorthair</option>
                            <option value="American Wirehair">American Wirehair</option>
                            <option value="Balinese">Balinese</option>
                            <option value="Bengal">Bengal</option>
                            <option value="Birman">Birman</option>
                            <option value="Bombay">Bombay</option>
                            <option value="British Longhair">British Longhair</option>
                            <option value="British Shorthair">British Shorthair</option>
                            <option value="Burmese">Burmese</option>
                            <option value="Burmilla">Burmilla</option>
                            <option value="Chartreux">Chartreux</option>
                            <option value="Chausie">Chausie</option>
                            <option value="Cornish Rex">Cornish Rex</option>
                            <option value="Cymric">Cymric</option>
                            <option value="Devon Rex">Devon Rex</option>
                            <option value="Egyptian Mau">Egyptian Mau</option>
                            <option value="Exotic Shorthair">Exotic Shorthair</option>
                            <option value="Havana Brown">Havana Brown</option>
                            <option value="Himalayan">Himalayan</option>
                            <option value="Japanese Bobtail">Japanese Bobtail</option>
                            <option value="Khao Manee">Khao Manee</option>
                            <option value="Korat">Korat</option>
                            <option value="Kurilian Bobtail">Kurilian Bobtail</option>
                            <option value="LaPerm">LaPerm</option>
                            <option value="Lykoi">Lykoi</option>
                            <option value="Maine Coon">Maine Coon</option>
                            <option value="Manx">Manx</option>
                            <option value="Munchkin">Munchkin</option>
                            <option value="Nebelung">Nebelung</option>
                            <option value="Norwegian Forest Cat">Norwegian Forest Cat</option>
                            <option value="Ocicat">Ocicat</option>
                            <option value="Oriental Longhair">Oriental Longhair</option>
                            <option value="Oriental Shorthair">Oriental Shorthair</option>
                            <option value="Persian">Persian</option>
                            <option value="Peterbald">Peterbald</option>
                            <option value="Pixiebob">Pixiebob</option>
                            <option value="Ragdoll">Ragdoll</option>
                            <option value="Russian Blue">Russian Blue</option>
                            <option value="Savannah">Savannah</option>
                            <option value="Scottish Fold">Scottish Fold</option>
                            <option value="Selkirk Rex">Selkirk Rex</option>
                            <option value="Serengeti">Serengeti</option>
                            <option value="Siamese">Siamese</option>
                            <option value="Siberian">Siberian</option>
                            <option value="Singapura">Singapura</option>
                            <option value="Snowshoe">Snowshoe</option>
                            <option value="Somali">Somali</option>
                            <option value="Sphynx">Sphynx</option>
                            <option value="Tonkinese">Tonkinese</option>
                            <option value="Toyger">Toyger</option>
                            <option value="Turkish Angora">Turkish Angora</option>
                            <option value="Turkish Van">Turkish Van</option>
                            <option value="York Chocolate">York Chocolate</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Gender *</label>
                        <div class="gender-selection">
                            <div class="gender-option">
                                <input type="radio" name="gender" value="Male" id="male" required>
                                <label for="male" class="gender-card">
                                    <div class="gender-icon">♂️</div>
                                    <div class="gender-label">Male</div>
                                </label>
                            </div>
                            <div class="gender-option">
                                <input type="radio" name="gender" value="Female" id="female" required>
                                <label for="female" class="gender-card">
                                    <div class="gender-icon">♀️</div>
                                    <div class="gender-label">Female</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Age *</label>
                        <input type="text" name="age" class="form-input" placeholder="e.g., 2 years" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price (₱) *</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="0.00" required>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" placeholder="Tell us about your cat's personality, habits, and any special traits..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Health Information -->
            <div class="form-section">
                <div class="section-title">
                    🏥 Health Information
                </div>
                <div class="health-section">
                    <div class="health-checkboxes">
                        <label class="checkbox-card">
                            <input type="checkbox" name="vaccinated" value="1">
                            <div class="checkbox-content">
                                <strong>Vaccinated</strong>
                                <small>Pet has received necessary vaccinations</small>
                            </div>
                        </label>

                        <label class="checkbox-card">
                            <input type="checkbox" name="neutered" value="1">
                            <div class="checkbox-content">
                                <strong>Neutered/Spayed</strong>
                                <small>Pet has been neutered or spayed</small>
                            </div>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Health Status (Optional)</label>
                        <textarea name="health_status" class="form-textarea" placeholder="Describe any health conditions, recent vet visits, or special care requirements..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Photos -->
            <div class="form-section">
                <div class="section-title">
                    📸 Pet Photos
                </div>
                <div class="image-upload-wrapper" id="uploadWrapper">
                    <input type="file" name="images[]" id="imageInput" accept="image/*" multiple required>
                    <div class="upload-icon">🖼️</div>
                    <div class="upload-text">Click to upload photos</div>
                    <div class="upload-hint">You can select multiple images (Max 5)</div>
                </div>
                <div id="imagePreview" style="display: none; margin-top: 20px;">
                    <div style="font-size: 14px; font-weight: 600; color: #8b6f47; margin-bottom: 12px;">Selected Images:</div>
                    <div id="previewContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px;"></div>
                </div>
            </div>

            <!-- Location -->
            <div class="form-section">
                <div class="section-title">
                    📍 Pet Location
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Address</label>
                    <input type="text" id="address" name="address" class="form-input" placeholder="Click on the map to set location" readonly>
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                </div>

                <div class="map-container">
                    <div id="map"></div>
                </div>

                <div id="location-info" class="location-info" style="display: none;">
                    <strong>📍 Current Location:</strong>
                    <span id="loc-address">Not set</span>
                    <span id="loc-coords"></span>
                </div>
            </div>

            <!-- Submit Button -->
            <?php if (!$is_verified): ?>
                <button type="button" id="verifyButton" class="btn-verify">
                    🔒 Verify First to List a Pet
                </button>
            <?php else: ?>
                <button type="submit" class="btn-submit">
                    List Pet for Sale
                </button>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Leaflet CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Select2 CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Show verification popup on page load if not verified
<?php if (!$is_verified): ?>
    <?php if ($is_pending): ?>
        Swal.fire({
            title: 'Verification Pending',
            text: 'Your verification request has been submitted. Please wait for admin confirmation before you can list pets.',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#8b6f47',
            allowOutsideClick: false
        });
    <?php else: ?>
        Swal.fire({
            title: 'Account Not Verified',
            text: 'You need to verify your account before you can list pets for sale.',
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: 'Not Now',
            cancelButtonColor: '#8d7d6d',
            confirmButtonText: 'Verify Now',
            confirmButtonColor: '#8b6f47',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'verify.php';
            }
        });
    <?php endif; ?>
<?php endif; ?>

// Handle verify button click
document.getElementById('verifyButton')?.addEventListener('click', function() {
    <?php if ($is_pending): ?>
        Swal.fire({
            title: 'Verification Pending',
            text: 'Your verification request has been submitted. Please wait for admin confirmation.',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#8b6f47'
        });
    <?php else: ?>
        window.location.href = 'verify.php';
    <?php endif; ?>
});

$(document).ready(function() {
    // Enhance breed dropdown with Select2
    $('#breed').select2({
        placeholder: "Search or select a breed",
        allowClear: true,
        width: '100%'
    });

    // Image Upload Preview
    let selectedFiles = [];
    const maxFiles = 5;

    $('#imageInput').on('change', function(e) {
        const files = Array.from(e.target.files);
        
        if (files.length > maxFiles) {
            Swal.fire({
                icon: 'warning',
                title: 'Too Many Images',
                text: `Please select a maximum of ${maxFiles} images.`,
                confirmButtonColor: '#8b6f47'
            });
            this.value = '';
            return;
        }

        selectedFiles = files;
        displayImagePreviews(files);
    });

    function displayImagePreviews(files) {
        const previewContainer = $('#previewContainer');
        previewContainer.empty();

        if (files.length === 0) {
            $('#imagePreview').hide();
            return;
        }

        $('#imagePreview').show();

        files.forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewItem = $(`
                    <div class="preview-item">
                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                        <button type="button" class="preview-remove" data-index="${index}">✕</button>
                    </div>
                `);
                
                previewContainer.append(previewItem);
            };
            
            reader.readAsDataURL(file);
        });
    }

    // Remove image from preview
    $(document).on('click', '.preview-remove', function() {
        const index = $(this).data('index');
        selectedFiles.splice(index, 1);
        
        // Update file input
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        document.getElementById('imageInput').files = dt.files;
        
        displayImagePreviews(selectedFiles);
    });

    // Make upload wrapper clickable
    $('#uploadWrapper').on('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            $('#imageInput').click();
        }
    });

    // Initialize Map (default center: Manila)
    var map = L.map('map').setView([14.5995, 120.9842], 13);

    // OpenStreetMap Tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var marker = null;

    // Function to update form + info box
    function updateLocation(lat, lon) {
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`, {
            headers: {
                'User-Agent': 'MyLeafletApp/1.0 (your-email@example.com)',
                'Accept-Language': 'en'
            }
        })
        .then(response => response.json())
        .then(data => {
            let address = data.display_name || "Unknown location";

            $("#address").val(address);
            $("#latitude").val(lat);
            $("#longitude").val(lon);

            $("#loc-address").text(address);
            $("#loc-coords").text(`Lat: ${lat.toFixed(5)}, Lng: ${lon.toFixed(5)}`);
            $("#location-info").show();
        })
        .catch(err => {
            console.error("Geocoding error:", err);
            $("#address").val("Could not get address");
            $("#loc-address").text("Could not get address");
        });
    }

    // Click to drop/move marker
    map.on('click', function(e) {
        var lat = e.latlng.lat;
        var lon = e.latlng.lng;

        if (!marker) {
            marker = L.marker([lat, lon], { draggable: true }).addTo(map);

            // Update location when dragged
            marker.on('dragend', function(event) {
                var newLat = event.target.getLatLng().lat;
                var newLon = event.target.getLatLng().lng;
                updateLocation(newLat, newLon);
            });
        } else {
            marker.setLatLng([lat, lon]);
        }

        updateLocation(lat, lon);
    });

    // Auto-center map to user's GPS
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;

            map.setView([lat, lon], 15);

            // Drop a marker at current GPS
            if (!marker) {
                marker = L.marker([lat, lon], { draggable: true }).addTo(map);
                marker.on('dragend', function(event) {
                    var newLat = event.target.getLatLng().lat;
                    var newLon = event.target.getLatLng().lng;
                    updateLocation(newLat, newLon);
                });
            } else {
                marker.setLatLng([lat, lon]);
            }

            updateLocation(lat, lon);
        });
    }
});
</script>

<?php include_once "../includes/footer.php"; ?>