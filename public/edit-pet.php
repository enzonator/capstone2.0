<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get pet ID from URL
if (!isset($_GET['id'])) {
    die("Pet not found.");
}

$pet_id = intval($_GET['id']);
$message = "";
$messageType = "";

// Fetch pet info (only if it belongs to the logged-in user)
$sql = "SELECT * FROM pets WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Pet not found or you don't have permission to edit this listing.");
}

$pet = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $address = $_POST['address'];

    $sql_update = "UPDATE pets 
                   SET name=?, breed=?, age=?, price=?, description=?, latitude=?, longitude=?, address=? 
                   WHERE id=? AND user_id=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssdssdsii", $name, $breed, $age, $price, $description, $latitude, $longitude, $address, $pet_id, $user_id);

    if ($stmt_update->execute()) {
        // Handle new image uploads (optional, add more images)
        if (!empty($_FILES['images']['name'][0])) {
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
        }

        $message = "Pet listing updated successfully!";
        $messageType = "success";
        // Refresh pet info
        $pet['name'] = $name;
        $pet['breed'] = $breed;
        $pet['age'] = $age;
        $pet['price'] = $price;
        $pet['description'] = $description;
        $pet['latitude'] = $latitude;
        $pet['longitude'] = $longitude;
        $pet['address'] = $address;
    } else {
        $message = "Error updating pet listing.";
        $messageType = "error";
    }
}
?>

<?php include_once "../includes/header.php"; ?>

<style>
    /* Color scheme matching the header */
    :root {
        --primary-bg: #EADDCA;
        --primary-text: #5D4E37;
        --accent-color: #8B6F47;
        --hover-color: #A0826D;
        --badge-color: #D2691E;
        --white: #ffffff;
        --light-accent: rgba(139, 111, 71, 0.1);
        --success-color: #2e7d32;
        --error-color: #c82333;
    }

    body {
        background: linear-gradient(135deg, #f5f0e8 0%, #e8dcc8 100%);
        min-height: 100vh;
        padding-bottom: 40px;
    }

    .edit-container {
        max-width: 1000px;
        margin: 40px auto;
        background: var(--white);
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(93, 78, 55, 0.15);
        border: 2px solid var(--primary-bg);
        overflow: hidden;
    }

    .page-header {
        background: linear-gradient(135deg, var(--primary-bg) 0%, #d4c4a8 100%);
        padding: 35px 40px;
        border-bottom: 3px solid var(--accent-color);
    }

    .page-header h2 {
        margin: 0;
        color: var(--primary-text);
        font-size: 28px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-header p {
        margin: 10px 0 0 0;
        color: var(--accent-color);
        font-size: 15px;
    }

    .alert-message {
        margin: 25px 40px;
        padding: 15px 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        color: var(--success-color);
        border: 2px solid var(--success-color);
    }

    .alert-error {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        color: var(--error-color);
        border: 2px solid var(--error-color);
    }

    .form-content {
        padding: 40px;
    }

    .edit-form {
        display: grid;
        gap: 25px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-label {
        font-weight: 600;
        color: var(--primary-text);
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-label i {
        color: var(--accent-color);
    }

    .form-input, .form-select, .form-textarea {
        padding: 14px 16px;
        border: 2px solid var(--primary-bg);
        border-radius: 10px;
        font-size: 15px;
        font-family: inherit;
        color: var(--primary-text);
        background: var(--white);
        transition: all 0.3s ease;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px var(--light-accent);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .file-upload-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 16px;
        background: var(--primary-bg);
        border: 2px dashed var(--accent-color);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: var(--primary-text);
        font-weight: 600;
    }

    .file-upload-label:hover {
        background: var(--accent-color);
        color: var(--white);
        border-style: solid;
    }

    .file-upload-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .location-section {
        background: var(--primary-bg);
        padding: 25px;
        border-radius: 12px;
        border: 2px solid var(--accent-color);
    }

    .location-section h3 {
        margin: 0 0 15px 0;
        color: var(--primary-text);
        font-size: 18px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #map {
        height: 400px;
        width: 100%;
        border-radius: 10px;
        margin-bottom: 15px;
        border: 2px solid var(--accent-color);
        box-shadow: 0 4px 12px rgba(93, 78, 55, 0.1);
    }

    .location-info {
        padding: 15px;
        background: var(--white);
        border: 2px solid var(--accent-color);
        border-radius: 10px;
        font-size: 14px;
        color: var(--primary-text);
    }

    .location-info strong {
        color: var(--accent-color);
    }

    .location-info .loc-detail {
        margin-top: 8px;
        padding-left: 24px;
    }

    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn {
        flex: 1;
        padding: 14px 24px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-primary {
        background: var(--accent-color);
        color: var(--white);
    }

    .btn-primary:hover {
        background: var(--hover-color);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 111, 71, 0.3);
    }

    .btn-secondary {
        background: var(--white);
        color: var(--accent-color);
        border: 2px solid var(--accent-color);
    }

    .btn-secondary:hover {
        background: var(--primary-bg);
    }

    /* Select2 customization */
    .select2-container--default .select2-selection--single {
        height: 50px !important;
        border: 2px solid var(--primary-bg) !important;
        border-radius: 10px !important;
        padding: 8px !important;
    }

    .select2-container--default .select2-selection--single:focus {
        border-color: var(--accent-color) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px !important;
        color: var(--primary-text) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 48px !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .edit-container {
            margin: 20px;
        }

        .page-header, .form-content {
            padding: 25px;
        }

        .alert-message {
            margin: 20px 25px;
        }

        .button-group {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }

        #map {
            height: 300px;
        }
    }
</style>

<div class="edit-container">
    <div class="page-header">
        <h2>
            <i class="bi bi-pencil-square"></i>
            Edit Pet Listing
        </h2>
        <p>Update your pet's information and location</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert-message alert-<?= $messageType ?>">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="form-content">
        <form method="POST" enctype="multipart/form-data" class="edit-form">
            
            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-tag-fill"></i>
                    Pet Name
                </label>
                <input type="text" name="name" value="<?= htmlspecialchars($pet['name']); ?>" placeholder="Enter pet name" required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-list-ul"></i>
                    Breed
                </label>
                <select name="breed" id="breed" class="form-select" required>
                    <option value="">Select Breed</option>
                    <?php
                    $breeds = ["Abyssinian","American Bobtail","American Curl","American Shorthair","American Wirehair",
                               "Balinese","Bengal","Birman","Bombay","British Longhair","British Shorthair","Burmese","Burmilla",
                               "Chartreux","Chausie","Cornish Rex","Cymric","Devon Rex","Egyptian Mau","Exotic Shorthair",
                               "Havana Brown","Himalayan","Japanese Bobtail","Khao Manee","Korat","Kurilian Bobtail","LaPerm",
                               "Lykoi","Maine Coon","Manx","Munchkin","Nebelung","Norwegian Forest Cat","Ocicat",
                               "Oriental Longhair","Oriental Shorthair","Persian","Peterbald","Pixiebob","Ragdoll","Russian Blue",
                               "Savannah","Scottish Fold","Selkirk Rex","Serengeti","Siamese","Siberian","Singapura","Snowshoe",
                               "Somali","Sphynx","Tonkinese","Toyger","Turkish Angora","Turkish Van","York Chocolate","Other"];
                    foreach ($breeds as $b) {
                        $selected = ($pet['breed'] == $b) ? "selected" : "";
                        echo "<option value=\"$b\" $selected>$b</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-calendar-check"></i>
                    Age
                </label>
                <input type="text" name="age" value="<?= htmlspecialchars($pet['age']); ?>" placeholder="e.g., 2 years" required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-currency-dollar"></i>
                    Price (₱)
                </label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($pet['price']); ?>" placeholder="Enter price" required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-file-text"></i>
                    Description
                </label>
                <textarea name="description" placeholder="Describe your pet..." required class="form-textarea"><?= htmlspecialchars($pet['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="bi bi-images"></i>
                    Add More Images (Optional)
                </label>
                <div class="file-upload-wrapper">
                    <label for="images" class="file-upload-label">
                        <i class="bi bi-cloud-upload"></i>
                        Choose images to upload
                    </label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple class="file-upload-input">
                </div>
            </div>

            <div class="location-section">
                <h3>
                    <i class="bi bi-geo-alt-fill"></i>
                    Pet Location
                </h3>
                
                <div class="form-group">
                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($pet['address']); ?>" placeholder="Click on the map to set location" readonly class="form-input">
                    <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($pet['latitude']); ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($pet['longitude']); ?>">
                </div>

                <div id="map"></div>

                <div id="location-info" class="location-info" style="<?= empty($pet['address']) ? 'display:none;' : ''; ?>">
                    <strong>📍 Current Location:</strong>
                    <div class="loc-detail">
                        <i class="bi bi-house-door"></i> <span id="loc-address"><?= htmlspecialchars($pet['address']); ?></span>
                    </div>
                    <div class="loc-detail">
                        <i class="bi bi-pin-map"></i> <span id="loc-coords"><?= ($pet['latitude'] && $pet['longitude']) ? "Lat: ".round($pet['latitude'],5).", Lng: ".round($pet['longitude'],5) : ""; ?></span>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <a href="pet-details.php?id=<?= $pet['id']; ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i>
                    Save Changes
                </button>
            </div>
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
$(document).ready(function() {
    // Enhance breed dropdown
    $('#breed').select2({
        placeholder: "Search or select a breed",
        allowClear: true,
        width: '100%'
    });

    // Initialize Map
    var lat = <?= $pet['latitude'] ? $pet['latitude'] : 14.5995 ?>;
    var lon = <?= $pet['longitude'] ? $pet['longitude'] : 120.9842 ?>;
    var map = L.map('map').setView([lat, lon], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var marker = L.marker([lat, lon], { draggable: true }).addTo(map);

    // Function to update form + info box
    function updateLocation(lat, lon) {
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`, {
            headers: { 'User-Agent': 'MyLeafletApp/1.0', 'Accept-Language': 'en' }
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
        .catch(err => console.error("Geocoding error:", err));
    }

    // Click to move marker
    map.on('click', function(e) {
        var lat = e.latlng.lat;
        var lon = e.latlng.lng;
        marker.setLatLng([lat, lon]);
        updateLocation(lat, lon);
    });

    // Update when dragging marker
    marker.on('dragend', function(event) {
        var newLat = event.target.getLatLng().lat;
        var newLon = event.target.getLatLng().lng;
        updateLocation(newLat, newLon);
    });

    // File upload name display
    $('#images').on('change', function() {
        const files = this.files;
        if (files.length > 0) {
            const label = $(this).siblings('.file-upload-label');
            label.html(`<i class="bi bi-check-circle"></i> ${files.length} file(s) selected`);
        }
    });
});
</script>

<?php include_once "../includes/footer.php"; ?>