<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check verification status
$verification_status = 'not verified';
$verifyQuery = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
$verifyQuery->bind_param("i", $user_id);
$verifyQuery->execute();
$verifyResult = $verifyQuery->get_result();
if ($verifyResult->num_rows > 0) {
    $verifyUser = $verifyResult->fetch_assoc();
    $verification_status = $verifyUser['verification_status'] ?? 'not verified';
}

// Redirect to verification page if not verified or pending
$showVerificationPopup = false;
$popupType = '';
if ($verification_status === 'not verified') {
    $showVerificationPopup = true;
    $popupType = 'not_verified';
} elseif ($verification_status === 'pending') {
    $showVerificationPopup = true;
    $popupType = 'pending';
}

$success_message = '';
$error_message = '';

// Handle form submission - only if verified
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Double-check verification before processing
    if ($verification_status !== 'verified') {
        $error_message = "You must be verified to add cats for adoption.";
    } else {
        $name = $_POST['name'];
        $age = intval($_POST['age']);
        $gender = $_POST['gender'];
        $breed = $_POST['breed'];
        $description = $_POST['description'];
        $health_status = $_POST['health_status'];
        $vaccinated = isset($_POST['vaccinated']) ? 1 : 0;
        $neutered = isset($_POST['neutered']) ? 1 : 0;
        $adoption_fee = floatval($_POST['adoption_fee']);
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $address = $_POST['address']; // General area only
        $additional_address = $_POST['additional_address'] ?? ''; // Optional specific details
        
        // Handle multiple image uploads
        $image_url = '';
        $uploaded_images = [];
        
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if (!empty($tmp_name) && $_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = time() . '_' . $key . '_' . basename($_FILES['images']['name'][$key]);
                    $target_file = $upload_dir . $file_name;
                    
                    // Check if file is an image
                    $check = getimagesize($tmp_name);
                    if ($check !== false) {
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $uploaded_images[] = $file_name;
                            // Set first image as main image
                            if (empty($image_url)) {
                                $image_url = $file_name;
                            }
                        }
                    }
                }
            }
            
            if (empty($uploaded_images)) {
                $error_message = "Error uploading images.";
            }
        } else {
            $error_message = "Please upload at least one image.";
        }
        
        if (empty($error_message)) {
            $sql = "INSERT INTO adoption_cats 
                    (name, age, gender, breed, description, health_status, vaccinated, neutered, image_url, adoption_fee, latitude, longitude, address, additional_address, status, user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available', ?)";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                die("SQL Error: " . $conn->error);
            }
            
            $stmt->bind_param("sissssiisdssssi", 
                $name, $age, $gender, $breed, $description, $health_status,
                $vaccinated, $neutered, $image_url, $adoption_fee, $latitude, $longitude, $address, $additional_address, $user_id
            );
            
            if ($stmt->execute()) {
                $cat_id = $stmt->insert_id;
                
                // Insert additional images into adoption_cat_images table
                if (count($uploaded_images) > 0) {
                    foreach ($uploaded_images as $img_filename) {
                        $imgSql = "INSERT INTO adoption_cat_images (cat_id, filename) VALUES (?, ?)";
                        $imgStmt = $conn->prepare($imgSql);
                        $imgStmt->bind_param("is", $cat_id, $img_filename);
                        $imgStmt->execute();
                    }
                }
                
                $success_message = "Cat added for adoption successfully!";
                echo "<script>
                    alert('Cat added for adoption successfully!');
                    window.location.href = 'adoption.php';
                </script>";
                exit();
            } else {
                $error_message = "Error adding cat. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Cat for Adoption</title>
    
    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Leaflet CSS/JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <!-- Select2 CSS/JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
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
            max-width: 800px;
            margin: 0 auto;
        }

        .form-wrapper {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
            color: #3d3020;
            padding: 40px 30px;
            text-align: center;
        }

        .form-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .form-header p {
            font-size: 1.1em;
            opacity: 0.95;
        }

        .add-form {
            padding: 40px 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #2c3e50;
            font-size: 1.4em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #c9b896;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select,
        .form-group input[type="file"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c9b896;
            box-shadow: 0 0 0 3px rgba(234, 221, 202, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
            margin-right: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
            color: #3d3020;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(234, 221, 202, 0.5);
            background: linear-gradient(135deg, #EADDCA 0%, #d4c4a8 100%);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #555;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }

        .image-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .image-preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #ddd;
        }

        .image-preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        .image-preview-item .badge-main {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: bold;
        }

        #map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .select2-container .select2-selection--single {
            height: 46px !important;
            padding: 10px 15px;
            border: 2px solid #ddd !important;
            border-radius: 8px !important;
            font-size: 1em;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px;
        }

        @media (max-width: 768px) {
            .form-header h1 {
                font-size: 2em;
            }
            
            .add-form {
                padding: 30px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-wrapper">
            <div class="form-header">
                <h1>🐱 Add Cat for Adoption</h1>
                <p>Help a cat find their forever home</p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" class="add-form" id="addCatForm">
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Basic Information -->
                <section class="form-section">
                    <h2>Basic Information</h2>
                    
                    <div class="form-group">
                        <label for="name">Cat Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Age (years) *</label>
                            <input type="number" id="age" name="age" min="0" max="30" step="1" required>
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="breed">Breed *</label>
                        <select name="breed" id="breed" required>
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

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="5" 
                                  placeholder="Describe the cat's personality, behavior, likes/dislikes..." required></textarea>
                    </div>
                </section>

                <!-- Health Information -->
                <section class="form-section">
                    <h2>Health Information</h2>

                    <div class="form-group">
                        <label for="health_status">Health Status *</label>
                        <textarea id="health_status" name="health_status" rows="3" 
                                  placeholder="Describe any health conditions, medications, or special needs..." required></textarea>
                    </div>

                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="vaccinated" value="1">
                            Vaccinated
                        </label>
                        <label>
                            <input type="checkbox" name="neutered" value="1">
                            Neutered/Spayed
                        </label>
                    </div>
                </section>

                <!-- Image and Fee -->
                <section class="form-section">
                    <h2>Image & Adoption Fee</h2>

                    <div class="form-group">
                        <label for="images">Cat Images (Multiple) *</label>
                        <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            You can select multiple images. The first image will be the main display image.
                        </small>
                        <div class="image-preview-container" id="imagePreviewContainer"></div>
                    </div>

                    <div class="form-group">
                        <label for="adoption_fee">Adoption Fee (₱) *</label>
                        <input type="number" id="adoption_fee" name="adoption_fee" min="0" step="0.01" 
                               placeholder="0.00" required>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Note: Many adoption fees cover basic veterinary care and help support rescue operations.
                        </small>
                    </div>
                </section>

                <!-- Location Section -->
                <section class="form-section">
                    <h2>Cat Location</h2>

                    <input type="hidden" id="address" name="address">
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">

                    <div id="map"></div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label for="additional_address">Additional Address Info (Optional)</label>
                        <input type="text" id="additional_address" name="additional_address" 
                               placeholder="e.g., Near landmark, Building name, Floor number, etc.">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            💡 Add specific details to help adopters find the location (your exact address won't be shown publicly)
                        </small>
                    </div>
                </section>

                <div class="form-actions">
                    <a href="adoption.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Cat for Adoption</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($showVerificationPopup): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const verificationStatus = '<?= $popupType ?>';
        
        if (verificationStatus === 'not_verified') {
            Swal.fire({
                title: 'Account Not Verified',
                text: 'You must verify your account before you can add cats for adoption.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Verify Now',
                cancelButtonText: 'Go Back',
                confirmButtonColor: '#5a4a3a',
                cancelButtonColor: '#8d7d6d',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'verify.php';
                } else {
                    window.location.href = 'adoption.php';
                }
            });
            
            // Disable form
            document.getElementById('addCatForm').querySelectorAll('input, textarea, select, button').forEach(el => {
                el.disabled = true;
            });
        } else if (verificationStatus === 'pending') {
            Swal.fire({
                title: 'Verification Pending',
                text: 'Your verification has been submitted. Please wait for admin approval before you can add cats for adoption.',
                icon: 'info',
                confirmButtonText: 'Go Back',
                confirmButtonColor: '#5a4a3a',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = 'adoption.php';
            });
            
            // Disable form
            document.getElementById('addCatForm').querySelectorAll('input, textarea, select, button').forEach(el => {
                el.disabled = true;
            });
        }
    });
    </script>
    <?php endif; ?>

    <script>
        $(document).ready(function() {
            // Enhance breed dropdown with Select2
            $('#breed').select2({
                placeholder: "Search or select a breed",
                allowClear: true,
                width: '100%'
            });

            // Image preview for multiple images
            document.getElementById('images').addEventListener('change', function(e) {
                const files = e.target.files;
                const container = document.getElementById('imagePreviewContainer');
                container.innerHTML = '';
                
                if (files.length > 0) {
                    Array.from(files).forEach((file, index) => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewDiv = document.createElement('div');
                            previewDiv.className = 'image-preview-item';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            
                            previewDiv.appendChild(img);
                            
                            if (index === 0) {
                                const badge = document.createElement('span');
                                badge.className = 'badge-main';
                                badge.textContent = 'Main';
                                previewDiv.appendChild(badge);
                            }
                            
                            container.appendChild(previewDiv);
                        }
                        reader.readAsDataURL(file);
                    });
                }
            });

            // ==================== ENHANCED INTERACTIVE GEOLOCATION ====================
            
            // Initialize Map (default center: Davao City, Philippines)
            var map = L.map('map').setView([7.1907, 125.4553], 13);

            // OpenStreetMap Tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            var marker = null;
            var isLocationSet = false;

            // Enhanced reverse geocoding - only get general area (no exact address)
            async function reverseGeocode(lat, lon) {
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`, {
                        headers: {
                            'User-Agent': 'PetMarketplace/1.0'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error('Location not found');
                    }
                    
                    // Build general address (NO exact street address)
                    const addr = data.address || {};
                    let addressParts = [];
                    
                    // Local area (neighborhood/suburb)
                    if (addr.neighbourhood) addressParts.push(addr.neighbourhood);
                    else if (addr.suburb) addressParts.push(addr.suburb);
                    
                    // City/Municipality
                    if (addr.city) addressParts.push(addr.city);
                    else if (addr.municipality) addressParts.push(addr.municipality);
                    else if (addr.town) addressParts.push(addr.town);
                    
                    // Region
                    if (addr.state) addressParts.push(addr.state);
                    else if (addr.province) addressParts.push(addr.province);
                    
                    // Country
                    if (addr.country) addressParts.push(addr.country);
                    
                    let formattedAddress = addressParts.join(', ') || 'Location set';
                    
                    return {
                        address: formattedAddress,
                        lat: parseFloat(data.lat),
                        lon: parseFloat(data.lon)
                    };
                } catch (error) {
                    console.error('Geocoding error:', error);
                    throw error;
                }
            }

            // Function to update location in form
            async function updateLocation(lat, lon) {
                try {
                    const result = await reverseGeocode(lat, lon);
                    
                    // Update hidden form fields (store general address only)
                    $("#address").val(result.address);
                    $("#latitude").val(result.lat);
                    $("#longitude").val(result.lon);
                    
                    isLocationSet = true;
                    
                    // Update marker popup
                    if (marker) {
                        marker.bindPopup(`
                            <div style="min-width: 200px;">
                                <strong style="color: #3d3020; font-size: 14px;">📍 Selected Location</strong><br><br>
                                <strong>Area:</strong> ${result.address}<br><br>
                                <em style="font-size: 12px; color: #666;">💡 Drag marker to adjust</em>
                            </div>
                        `).openPopup();
                    }
                } catch (err) {
                    console.error("Location update error:", err);
                    
                    // Fallback: Still save coordinates with generic location
                    $("#address").val(`Location: ${lat.toFixed(6)}, ${lon.toFixed(6)}`);
                    $("#latitude").val(lat);
                    $("#longitude").val(lon);
                    isLocationSet = true;
                    
                    if (marker) {
                        marker.bindPopup(`
                            <strong>Location Set</strong><br>
                            Lat: ${lat.toFixed(6)}<br>
                            Lon: ${lon.toFixed(6)}
                        `);
                    }
                }
            }

            // Add or update marker with drag capability
            function addMarker(lat, lon) {
                if (!marker) {
                    marker = L.marker([lat, lon], { 
                        draggable: true,
                        title: 'Drag to adjust location',
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).addTo(map);

                    // Update location when marker is dragged
                    marker.on('dragend', function(event) {
                        const newPos = event.target.getLatLng();
                        updateLocation(newPos.lat, newPos.lng);
                    });
                    
                    marker.bindTooltip('Click or drag to set location', {
                        permanent: false,
                        direction: 'top'
                    });
                } else {
                    marker.setLatLng([lat, lon]);
                }
            }

            // Click handler - pin marker anywhere on map
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lon = e.latlng.lng;
                
                addMarker(lat, lon);
                updateLocation(lat, lon);
            });

            // Auto-center map to user's GPS location on page load
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lon = position.coords.longitude;

                    map.setView([lat, lon], 15);

                    if (!marker) {
                        marker = L.marker([lat, lon], { 
                            draggable: true,
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                            })
                        }).addTo(map);
                        
                        marker.on('dragend', function(event) {
                            var newLat = event.target.getLatLng().lat;
                            var newLon = event.target.getLatLng().lng;
                            updateLocation(newLat, newLon);
                        });
                    } else {
                        marker.setLatLng([lat, lon]);
                    }

                    updateLocation(lat, lon);
                }, function(error) {
                    console.warn('Geolocation error:', error.message);
                });
            }

            // Form validation
            document.getElementById('addCatForm').addEventListener('submit', function(e) {
                const verificationStatus = '<?= $verification_status ?>';
                
                if (verificationStatus !== 'verified') {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Account Not Verified',
                        text: 'You must be verified to add cats for adoption.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#5a4a3a'
                    });
                    return false;
                }
                
                if (!isLocationSet || !$('#latitude').val() || !$('#longitude').val()) {
                    e.preventDefault();
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Location Required',
                        text: 'Please click on the map to set the cat\'s general location.',
                        confirmButtonColor: '#5a4a3a',
                        confirmButtonText: 'OK'
                    });
                    
                    $('html, body').animate({
                        scrollTop: $('#map').offset().top - 100
                    }, 500);
                    
                    return false;
                }
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Adding Cat...';
            });
        });
    </script>
</body>
</html>