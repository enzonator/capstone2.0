<?php
// map_pets_standalone.php - Standalone version with direct DB connection
session_start();

// Direct database connection
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "catshop";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Include header
include_once "../includes/header.php";

// Fetch available selling pets with location
$selling_query = "SELECT p.*, u.username, 
                  GROUP_CONCAT(pi.filename) as images
                  FROM pets p
                  LEFT JOIN users u ON p.user_id = u.id
                  LEFT JOIN pet_images pi ON p.id = pi.pet_id
                  WHERE p.status = 'available' 
                  AND p.latitude IS NOT NULL 
                  AND p.longitude IS NOT NULL
                  AND p.latitude != '' 
                  AND p.longitude != ''
                  AND p.latitude != '0'
                  AND p.longitude != '0'
                  GROUP BY p.id
                  ORDER BY p.created_at DESC";
$selling_result = mysqli_query($conn, $selling_query);

// Fetch available adoption cats with location
$adoption_query = "SELECT ac.*, u.username,
                   GROUP_CONCAT(aci.filename) as images
                   FROM adoption_cats ac
                   LEFT JOIN users u ON ac.user_id = u.id
                   LEFT JOIN adoption_cat_images aci ON ac.id = aci.cat_id
                   WHERE ac.status = 'Available'
                   AND ac.latitude IS NOT NULL 
                   AND ac.longitude IS NOT NULL
                   AND ac.latitude != ''
                   AND ac.longitude != ''
                   GROUP BY ac.id
                   ORDER BY ac.created_at DESC";
$adoption_result = mysqli_query($conn, $adoption_query);

// Prepare data for JavaScript
$selling_pets = [];
if ($selling_result) {
    while ($row = mysqli_fetch_assoc($selling_result)) {
        $images = $row['images'] ? explode(',', $row['images']) : [];
        
        // Determine the correct image path
        $imagePath = 'images/default-pet.jpg'; // Default fallback
        if (!empty($images)) {
            $firstImage = $images[0];
            // Check multiple possible locations
            $possiblePaths = [
                'uploads/pets/' . $firstImage,
                '../uploads/pets/' . $firstImage,
                'uploads/' . $firstImage,
                '../uploads/' . $firstImage,
                $firstImage
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $imagePath = $path;
                    break;
                }
            }
        }
        
        $selling_pets[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name']),
            'breed' => htmlspecialchars($row['breed'] ?? 'Unknown'),
            'price' => $row['price'],
            'age' => htmlspecialchars($row['age'] ?? 'N/A'),
            'gender' => htmlspecialchars($row['gender']),
            'lat' => floatval($row['latitude']),
            'lng' => floatval($row['longitude']),
            'address' => htmlspecialchars($row['address'] ?? 'Address not available'),
            'image' => $imagePath,
            'username' => htmlspecialchars($row['username'] ?? 'N/A')
        ];
    }
}

$adoption_cats = [];
if ($adoption_result) {
    while ($row = mysqli_fetch_assoc($adoption_result)) {
        $images = $row['images'] ? explode(',', $row['images']) : [];
        
        // Determine the correct image path
        $imagePath = 'images/default-cat.jpg'; // Default fallback
        if (!empty($images)) {
            $firstImage = $images[0];
            // Check multiple possible locations
            $possiblePaths = [
                'uploads/adoption/' . $firstImage,
                '../uploads/adoption/' . $firstImage,
                'uploads/' . $firstImage,
                '../uploads/' . $firstImage,
                $firstImage
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $imagePath = $path;
                    break;
                }
            }
        }
        
        $adoption_cats[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name']),
            'breed' => htmlspecialchars($row['breed'] ?? 'Unknown'),
            'fee' => $row['adoption_fee'],
            'age' => htmlspecialchars($row['age'] ?? 'N/A'),
            'gender' => htmlspecialchars($row['gender']),
            'lat' => floatval($row['latitude']),
            'lng' => floatval($row['longitude']),
            'address' => htmlspecialchars($row['address'] ?? 'Address not available'),
            'image' => $imagePath,
            'username' => htmlspecialchars($row['username'] ?? 'N/A')
        ];
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Locations Map - Cat Shop</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f5f0;
            min-height: 100vh;
        }

        .page-content {
            padding: 40px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #5a4a3a;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: none;
        }

        .map-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 30px;
            background: #fff;
            border: 2px solid #EADDCA;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #7d6d5d;
            box-shadow: 0 2px 10px rgba(90, 74, 58, 0.08);
        }

        .tab-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(90, 74, 58, 0.15);
            border-color: #5a4a3a;
            color: #5a4a3a;
        }

        .tab-button.active {
            background: #5a4a3a;
            border-color: #5a4a3a;
            color: #EADDCA;
            box-shadow: 0 4px 15px rgba(90, 74, 58, 0.3);
        }

        .map-container {
            background: #fff;
            border: 2px solid #EADDCA;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(90, 74, 58, 0.08);
            padding: 25px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .map-container.active {
            display: block;
        }

        .map {
            height: 600px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(90, 74, 58, 0.08);
            border: 2px solid #EADDCA;
        }

        .map-info {
            padding: 20px;
            background: #f8f5f0;
            border-radius: 12px;
            margin-top: 15px;
            border: 1px solid #EADDCA;
        }

        .map-info h3 {
            color: #5a4a3a;
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .location-info {
            background: #fff;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #5a4a3a;
            font-size: 14px;
            border-left: 4px solid #EADDCA;
            box-shadow: 0 2px 5px rgba(90, 74, 58, 0.05);
        }

        .location-info i {
            font-size: 18px;
            color: #7d6d5d;
        }

        .legend {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 10px 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(90, 74, 58, 0.08);
            border: 1px solid #EADDCA;
        }

        .legend-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 5px rgba(90, 74, 58, 0.15);
        }

        .legend-icon.selling {
            background: #5a4a3a;
            color: #EADDCA;
        }

        .legend-icon.adoption {
            background: #EADDCA;
            color: #5a4a3a;
        }

        /* Custom popup styles */
        .leaflet-popup-content-wrapper {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .popup-content {
            min-width: 280px;
        }

        .popup-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .popup-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .popup-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .popup-price {
            font-size: 18px;
            font-weight: bold;
            color: #5a4a3a;
            margin: 12px 0;
        }

        .popup-button {
            display: inline-block;
            padding: 10px 20px;
            background: #5a4a3a;
            color: #EADDCA;
            text-decoration: none;
            border-radius: 50px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(90, 74, 58, 0.2);
            border: 2px solid #5a4a3a;
        }

        .popup-button:hover {
            background: transparent;
            color: #5a4a3a;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(90, 74, 58, 0.3);
        }

        .stats-bar {
            display: flex;
            justify-content: space-around;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(90, 74, 58, 0.08);
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 20px;
            border: 2px solid #EADDCA;
        }

        .stat-item {
            text-align: center;
            min-width: 150px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #5a4a3a;
        }

        .stat-label {
            font-size: 14px;
            color: #7d6d5d;
            margin-top: 8px;
            font-weight: 600;
        }

        .no-data-message {
            text-align: center;
            padding: 40px;
            color: #7d6d5d;
            font-size: 1.1em;
        }

        .no-data-message i {
            color: #EADDCA;
        }

        @media (max-width: 768px) {
            .map {
                height: 400px;
            }

            h1 {
                font-size: 1.8em;
            }

            .nav-title {
                font-size: 1.2em;
            }

            .map-tabs {
                flex-direction: column;
                padding: 0 10px;
            }

            .tab-button {
                width: 100%;
            }

            .stats-bar {
                flex-direction: column;
            }

            .stat-item {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="page-content">
        <div class="container">
        <h1>🐾 Discover Pets Near You</h1>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($selling_pets); ?></div>
                <div class="stat-label"><i class="fas fa-shopping-cart"></i> Pets for Sale</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($adoption_cats); ?></div>
                <div class="stat-label"><i class="fas fa-heart"></i> Cats for Adoption</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($selling_pets) + count($adoption_cats); ?></div>
                <div class="stat-label"><i class="fas fa-map-pin"></i> Total Available</div>
            </div>
        </div>

        <?php if (count($selling_pets) + count($adoption_cats) > 0): ?>
        <div class="map-tabs">
            <button class="tab-button active" onclick="switchMap('selling')">
                <i class="fas fa-shopping-cart"></i> Pets for Sale
            </button>
            <button class="tab-button" onclick="switchMap('adoption')">
                <i class="fas fa-heart"></i> Cats for Adoption
            </button>
            <button class="tab-button" onclick="switchMap('all')">
                <i class="fas fa-map"></i> All Pets
            </button>
        </div>

        <div class="map-container active" id="selling-map-container">
            <div id="selling-map" class="map"></div>
            <div class="map-info">
                <div class="location-info">
                    <i class="fas fa-location-arrow"></i>
                    <span>Map centered on your location. Blue dot shows where you are.</span>
                </div>
                <h3><i class="fas fa-shopping-cart"></i> Pets for Sale Locations</h3>
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-icon selling">🐾</div>
                        <span><strong><?php echo count($selling_pets); ?></strong> pets available for purchase</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="map-container" id="adoption-map-container">
            <div id="adoption-map" class="map"></div>
            <div class="map-info">
                <div class="location-info">
                    <i class="fas fa-location-arrow"></i>
                    <span>Map centered on your location. Blue dot shows where you are.</span>
                </div>
                <h3><i class="fas fa-heart"></i> Cats for Adoption Locations</h3>
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-icon adoption">❤️</div>
                        <span><strong><?php echo count($adoption_cats); ?></strong> cats looking for homes</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="map-container" id="all-map-container">
            <div id="all-map" class="map"></div>
            <div class="map-info">
                <div class="location-info">
                    <i class="fas fa-location-arrow"></i>
                    <span>Map centered on your location. Blue dot shows where you are.</span>
                </div>
                <h3><i class="fas fa-map"></i> All Available Pets</h3>
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-icon selling">🐾</div>
                        <span>Pet for Sale</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-icon adoption">❤️</div>
                        <span>Cat for Adoption</span>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="map-container active">
            <div class="no-data-message">
                <i class="fas fa-exclamation-circle" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                <p>No pets with location data are currently available.</p>
                <p style="margin-top: 10px; font-size: 0.9em;">Please check back later!</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Pet data from PHP
        const sellingPets = <?php echo json_encode($selling_pets); ?>;
        const adoptionCats = <?php echo json_encode($adoption_cats); ?>;

        let sellingMap, adoptionMap, allMap;
        let sellingMarkers = [], adoptionMarkers = [], allMarkers = [];

        // Custom icons with color scheme
        const sellingIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background:#5a4a3a;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 4px 10px rgba(90,74,58,0.3);font-size:20px;color:#EADDCA;">🐾</div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        const adoptionIcon = L.divIcon({
            className: 'custom-marker',
            html: '<div style="background:#EADDCA;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 4px 10px rgba(234,221,202,0.5);font-size:20px;color:#5a4a3a;">❤️</div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        // Initialize maps
        function initMaps() {
            if (sellingPets.length === 0 && adoptionCats.length === 0) {
                return; // Don't initialize maps if no data
            }

            // Default center (Davao City) - will be updated with user location
            let defaultCenter = [7.0731, 125.6128];
            const defaultZoom = 12;

            // Try to get user's location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // User location found
                        defaultCenter = [position.coords.latitude, position.coords.longitude];
                        
                        // Update all maps to user's location
                        sellingMap.setView(defaultCenter, defaultZoom);
                        adoptionMap.setView(defaultCenter, defaultZoom);
                        allMap.setView(defaultCenter, defaultZoom);
                        
                        // Add user location marker to all maps
                        const userIcon = L.divIcon({
                            className: 'user-location-marker',
                            html: '<div style="background:#5a4a3a;width:20px;height:20px;border-radius:50%;border:4px solid #EADDCA;box-shadow:0 2px 8px rgba(90,74,58,0.3);"></div>',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        });
                        
                        L.marker(defaultCenter, { icon: userIcon })
                            .addTo(sellingMap)
                            .bindPopup("<b>Your Location</b>");
                        
                        L.marker(defaultCenter, { icon: userIcon })
                            .addTo(adoptionMap)
                            .bindPopup("<b>Your Location</b>");
                        
                        L.marker(defaultCenter, { icon: userIcon })
                            .addTo(allMap)
                            .bindPopup("<b>Your Location</b>");
                    },
                    function(error) {
                        console.log("Location access denied or unavailable:", error);
                        // Keep default location if user denies or error occurs
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            }

            // Selling pets map
            sellingMap = L.map('selling-map').setView(defaultCenter, defaultZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(sellingMap);

            // Adoption cats map
            adoptionMap = L.map('adoption-map').setView(defaultCenter, defaultZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(adoptionMap);

            // All pets map
            allMap = L.map('all-map').setView(defaultCenter, defaultZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(allMap);

            // Add markers
            addSellingMarkers();
            addAdoptionMarkers();
            addAllMarkers();
        }

        function createPopupContent(pet, type) {
            const isAdoption = type === 'adoption';
            const price = isAdoption ? `Adoption Fee: ₱${parseFloat(pet.fee || 0).toLocaleString()}` : `₱${parseFloat(pet.price || 0).toLocaleString()}`;
            
            // IMPORTANT: Using hyphens not underscores in filenames
            // Adoption redirects to adoption-form.php with cat_id and cat_name parameters
            let detailsPage;
            if (isAdoption) {
                detailsPage = `adoption-form.php?cat_id=${pet.id}&cat_name=${encodeURIComponent(pet.name)}`;
            } else {
                detailsPage = `pet-details.php?id=${pet.id}`;
            }
            
            // Image handling with multiple fallbacks
            const imageUrl = pet.image || 'https://via.placeholder.com/300x200/667eea/ffffff?text=No+Image';
            
            return `
                <div class="popup-content">
                    <img src="${imageUrl}" 
                         alt="${pet.name}" 
                         class="popup-image" 
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/300x200/667eea/ffffff?text=Pet+Image';">
                    <div class="popup-title">${pet.name}</div>
                    <div class="popup-details">
                        <i class="fas fa-paw"></i> <strong>Breed:</strong> ${pet.breed}
                    </div>
                    <div class="popup-details">
                        <i class="fas fa-calendar"></i> <strong>Age:</strong> ${pet.age}
                    </div>
                    <div class="popup-details">
                        <i class="fas fa-venus-mars"></i> <strong>Gender:</strong> ${pet.gender}
                    </div>
                    <div class="popup-details">
                        <i class="fas fa-user"></i> <strong>${isAdoption ? 'Owner' : 'Seller'}:</strong> ${pet.username}
                    </div>
                    <div class="popup-details">
                        <i class="fas fa-map-marker-alt"></i> ${pet.address}
                    </div>
                    <div class="popup-price">${price}</div>
                    <a href="${detailsPage}" class="popup-button">
                        <i class="fas fa-${isAdoption ? 'heart' : 'info-circle'}"></i> ${isAdoption ? 'Apply for Adoption' : 'View Details'}
                    </a>
                </div>
            `;
        }

        function addSellingMarkers() {
            sellingPets.forEach(pet => {
                const marker = L.marker([pet.lat, pet.lng], { icon: sellingIcon })
                    .addTo(sellingMap)
                    .bindPopup(createPopupContent(pet, 'selling'));
                
                sellingMarkers.push(marker);
            });

            if (sellingMarkers.length > 0) {
                const group = L.featureGroup(sellingMarkers);
                sellingMap.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function addAdoptionMarkers() {
            adoptionCats.forEach(cat => {
                const marker = L.marker([cat.lat, cat.lng], { icon: adoptionIcon })
                    .addTo(adoptionMap)
                    .bindPopup(createPopupContent(cat, 'adoption'));
                
                adoptionMarkers.push(marker);
            });

            if (adoptionMarkers.length > 0) {
                const group = L.featureGroup(adoptionMarkers);
                adoptionMap.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function addAllMarkers() {
            sellingPets.forEach(pet => {
                const marker = L.marker([pet.lat, pet.lng], { icon: sellingIcon })
                    .addTo(allMap)
                    .bindPopup(createPopupContent(pet, 'selling'));
                
                allMarkers.push(marker);
            });

            adoptionCats.forEach(cat => {
                const marker = L.marker([cat.lat, cat.lng], { icon: adoptionIcon })
                    .addTo(allMap)
                    .bindPopup(createPopupContent(cat, 'adoption'));
                
                allMarkers.push(marker);
            });

            if (allMarkers.length > 0) {
                const group = L.featureGroup(allMarkers);
                allMap.fitBounds(group.getBounds().pad(0.1));
            }
        }

        function switchMap(type) {
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Update map containers
            document.querySelectorAll('.map-container').forEach(container => {
                container.classList.remove('active');
            });

            // Show selected map
            if (type === 'selling') {
                document.getElementById('selling-map-container').classList.add('active');
                setTimeout(() => sellingMap.invalidateSize(), 100);
            } else if (type === 'adoption') {
                document.getElementById('adoption-map-container').classList.add('active');
                setTimeout(() => adoptionMap.invalidateSize(), 100);
            } else if (type === 'all') {
                document.getElementById('all-map-container').classList.add('active');
                setTimeout(() => allMap.invalidateSize(), 100);
            }
        }

        // Initialize on page load
        window.addEventListener('load', () => {
            initMaps();
        });
    </script>
</body>
</html>

<?php
// Include footer
include_once "../includes/footer.php";
mysqli_close($conn);
?>