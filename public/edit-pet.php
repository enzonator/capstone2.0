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

        $message = "✅ Pet listing updated successfully!";
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
        $message = "❌ Error updating pet.";
    }
}
?>

<?php include_once "../includes/header.php"; ?>

<div class="container" style="max-width: 900px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0px 4px 8px rgba(0,0,0,0.1);">
    <h2 style="text-align:center; margin-bottom:20px; color:#333;">✏️ Edit Pet Listing</h2>

    <?php if (!empty($message)): ?>
        <div style="padding: 10px; margin-bottom: 15px; border-radius: 5px; background: #f0f8ff; color: #333;">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 15px;">
        <input type="text" name="name" value="<?= htmlspecialchars($pet['name']); ?>" placeholder="Pet Name" required class="form-input">

        <!-- Breed Dropdown with Search -->
        <select name="breed" id="breed" class="form-input" required>
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

        <input type="text" name="age" value="<?= htmlspecialchars($pet['age']); ?>" placeholder="Age (e.g. 2 years)" required class="form-input">
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($pet['price']); ?>" placeholder="Price (₱)" required class="form-input">

        <textarea name="description" placeholder="Description" rows="4" class="form-input"><?= htmlspecialchars($pet['description']); ?></textarea>

        <!-- Upload more images -->
        <input type="file" name="images[]" accept="image/*" multiple class="form-input">

        <!-- Location Section -->
        <label style="font-weight:bold;">Pet Location:</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($pet['address']); ?>" placeholder="Click on the map to set location" readonly class="form-input">
        <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($pet['latitude']); ?>">
        <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($pet['longitude']); ?>">

        <div id="map" style="height: 300px; width: 100%; border-radius: 8px; margin-bottom: 10px;"></div>

        <div id="location-info" style="padding:10px; background:#f8f9fa; border:1px solid #ddd; border-radius:6px; font-size:14px; color:#333; <?= empty($pet['address']) ? 'display:none;' : ''; ?>">
            📍 <b>Current Location:</b><br>
            <span id="loc-address"><?= htmlspecialchars($pet['address']); ?></span><br>
            <span id="loc-coords"><?= ($pet['latitude'] && $pet['longitude']) ? "Lat: ".round($pet['latitude'],5).", Lng: ".round($pet['longitude'],5) : ""; ?></span>
        </div>

        <button type="submit" style="padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            💾 Save Changes
        </button>
        <a href="pet-details.php?id=<?= $pet['id']; ?>" style="text-align:center; display:block; margin-top:10px;">⬅ Back to Pet Details</a>
    </form>
</div>

<style>
    .form-input {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
        width: 100%;
    }
    .form-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0,123,255,0.3);
    }
</style>

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
});
</script>

<?php include_once "../includes/footer.php"; ?>
