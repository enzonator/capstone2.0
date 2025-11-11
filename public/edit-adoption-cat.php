<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';
$current_user_id = $_SESSION['user_id'];

// Get cat ID from URL
$cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$cat_id) {
    header("Location: adoption.php");
    exit();
}

// Fetch cat information
$sql = "SELECT * FROM adoption_cats WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $cat_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$cat = $result->fetch_assoc();

// Check if cat exists and belongs to current user
if (!$cat) {
    echo "<script>alert('Cat not found or you do not have permission to edit this listing.'); window.location.href='adoption.php';</script>";
    exit();
}

// Fetch existing images
$existing_images = [];
$imgSql = "SELECT id, filename FROM adoption_cat_images WHERE cat_id = ? ORDER BY id ASC";
$imgStmt = $conn->prepare($imgSql);
$imgStmt->bind_param("i", $cat_id);
$imgStmt->execute();
$imgResult = $imgStmt->get_result();
while ($imgRow = $imgResult->fetch_assoc()) {
    $existing_images[] = $imgRow;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $address = $_POST['address'];
    
    // Handle image deletion
    if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $img_id) {
            // Get filename first
            $getImgSql = "SELECT filename FROM adoption_cat_images WHERE id = ? AND cat_id = ?";
            $getImgStmt = $conn->prepare($getImgSql);
            $getImgStmt->bind_param("ii", $img_id, $cat_id);
            $getImgStmt->execute();
            $getImgResult = $getImgStmt->get_result();
            if ($imgData = $getImgResult->fetch_assoc()) {
                // Delete file
                $file_path = '../uploads/' . $imgData['filename'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                // Delete from database
                $delImgSql = "DELETE FROM adoption_cat_images WHERE id = ? AND cat_id = ?";
                $delImgStmt = $conn->prepare($delImgSql);
                $delImgStmt->bind_param("ii", $img_id, $cat_id);
                $delImgStmt->execute();
            }
        }
    }
    
    // Handle new image uploads
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
                    }
                }
            }
        }
    }
    
    // Get remaining images count
    $countSql = "SELECT COUNT(*) as count FROM adoption_cat_images WHERE cat_id = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("i", $cat_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countData = $countResult->fetch_assoc();
    $remaining_images = $countData['count'];
    
    // Check if we have at least one image (existing or new)
    if ($remaining_images == 0 && empty($uploaded_images)) {
        $error_message = "Please keep at least one image for the cat.";
    } else {
        // Update main cat record
        $image_url = $cat['image_url']; // Keep existing main image by default
        
        // If main image was deleted, update to first remaining or new image
        $checkMainSql = "SELECT filename FROM adoption_cat_images WHERE cat_id = ? AND filename = ?";
        $checkMainStmt = $conn->prepare($checkMainSql);
        $checkMainStmt->bind_param("is", $cat_id, $image_url);
        $checkMainStmt->execute();
        $checkMainResult = $checkMainStmt->get_result();
        
        if ($checkMainResult->num_rows == 0) {
            // Main image was deleted, get first remaining image
            if (!empty($uploaded_images)) {
                $image_url = $uploaded_images[0];
            } else {
                $getFirstSql = "SELECT filename FROM adoption_cat_images WHERE cat_id = ? ORDER BY id ASC LIMIT 1";
                $getFirstStmt = $conn->prepare($getFirstSql);
                $getFirstStmt->bind_param("i", $cat_id);
                $getFirstStmt->execute();
                $getFirstResult = $getFirstStmt->get_result();
                if ($firstImg = $getFirstResult->fetch_assoc()) {
                    $image_url = $firstImg['filename'];
                }
            }
        }
        
        $updateSql = "UPDATE adoption_cats 
                      SET name = ?, age = ?, gender = ?, breed = ?, description = ?, 
                          health_status = ?, vaccinated = ?, neutered = ?, image_url = ?, 
                          adoption_fee = ?, latitude = ?, longitude = ?, address = ?
                      WHERE id = ? AND user_id = ?";
        
        $updateStmt = $conn->prepare($updateSql);
        
        if (!$updateStmt) {
            die("SQL Error: " . $conn->error);
        }
        
        $updateStmt->bind_param("sissssiisdsssii", 
            $name, $age, $gender, $breed, $description, $health_status,
            $vaccinated, $neutered, $image_url, $adoption_fee, 
            $latitude, $longitude, $address, $cat_id, $current_user_id
        );
        
        if ($updateStmt->execute()) {
            // Insert new images into adoption_cat_images table
            if (count($uploaded_images) > 0) {
                foreach ($uploaded_images as $img_filename) {
                    $imgSql = "INSERT INTO adoption_cat_images (cat_id, filename) VALUES (?, ?)";
                    $imgStmt = $conn->prepare($imgSql);
                    $imgStmt->bind_param("is", $cat_id, $img_filename);
                    $imgStmt->execute();
                }
            }
            
            $success_message = "Cat listing updated successfully!";
            echo "<script>
                alert('Cat listing updated successfully!');
                window.location.href = 'adoption-form.php?cat_id=" . $cat_id . "';
            </script>";
            exit();
        } else {
            $error_message = "Error updating cat listing. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Cat Listing</title>
    
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

        .edit-form {
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

        .existing-images-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .existing-image-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #ddd;
        }

        .existing-image-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        .existing-image-item .badge-main {
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

        .existing-image-item .delete-checkbox {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            cursor: pointer;
        }

        .existing-image-item .delete-label {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: bold;
            display: none;
        }

        .existing-image-item input[type="checkbox"]:checked ~ .delete-label {
            display: block;
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

        .image-preview-item .badge-new {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #17a2b8;
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
            margin-bottom: 10px;
            border: 2px solid #ddd;
        }

        #location-info {
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            margin-top: 10px;
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
            
            .edit-form {
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
                <h1>✏️ Edit Cat Listing</h1>
                <p>Update <?php echo htmlspecialchars($cat['name']); ?>'s information</p>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" class="edit-form" id="editCatForm">
                
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
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($cat['name']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="age">Age (years) *</label>
                            <input type="number" id="age" name="age" min="0" max="30" step="1" value="<?php echo $cat['age']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $cat['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $cat['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="breed">Breed *</label>
                        <select name="breed" id="breed" required>
                            <option value="">Select Breed</option>
                            <option value="Abyssinian" <?php echo $cat['breed'] == 'Abyssinian' ? 'selected' : ''; ?>>Abyssinian</option>
                            <option value="American Bobtail" <?php echo $cat['breed'] == 'American Bobtail' ? 'selected' : ''; ?>>American Bobtail</option>
                            <option value="American Curl" <?php echo $cat['breed'] == 'American Curl' ? 'selected' : ''; ?>>American Curl</option>
                            <option value="American Shorthair" <?php echo $cat['breed'] == 'American Shorthair' ? 'selected' : ''; ?>>American Shorthair</option>
                            <option value="American Wirehair" <?php echo $cat['breed'] == 'American Wirehair' ? 'selected' : ''; ?>>American Wirehair</option>
                            <option value="Balinese" <?php echo $cat['breed'] == 'Balinese' ? 'selected' : ''; ?>>Balinese</option>
                            <option value="Bengal" <?php echo $cat['breed'] == 'Bengal' ? 'selected' : ''; ?>>Bengal</option>
                            <option value="Birman" <?php echo $cat['breed'] == 'Birman' ? 'selected' : ''; ?>>Birman</option>
                            <option value="Bombay" <?php echo $cat['breed'] == 'Bombay' ? 'selected' : ''; ?>>Bombay</option>
                            <option value="British Longhair" <?php echo $cat['breed'] == 'British Longhair' ? 'selected' : ''; ?>>British Longhair</option>
                            <option value="British Shorthair" <?php echo $cat['breed'] == 'British Shorthair' ? 'selected' : ''; ?>>British Shorthair</option>
                            <option value="Burmese" <?php echo $cat['breed'] == 'Burmese' ? 'selected' : ''; ?>>Burmese</option>
                            <option value="Burmilla" <?php echo $cat['breed'] == 'Burmilla' ? 'selected' : ''; ?>>Burmilla</option>
                            <option value="Chartreux" <?php echo $cat['breed'] == 'Chartreux' ? 'selected' : ''; ?>>Chartreux</option>
                            <option value="Chausie" <?php echo $cat['breed'] == 'Chausie' ? 'selected' : ''; ?>>Chausie</option>
                            <option value="Cornish Rex" <?php echo $cat['breed'] == 'Cornish Rex' ? 'selected' : ''; ?>>Cornish Rex</option>
                            <option value="Cymric" <?php echo $cat['breed'] == 'Cymric' ? 'selected' : ''; ?>>Cymric</option>
                            <option value="Devon Rex" <?php echo $cat['breed'] == 'Devon Rex' ? 'selected' : ''; ?>>Devon Rex</option>
                            <option value="Egyptian Mau" <?php echo $cat['breed'] == 'Egyptian Mau' ? 'selected' : ''; ?>>Egyptian Mau</option>
                            <option value="Exotic Shorthair" <?php echo $cat['breed'] == 'Exotic Shorthair' ? 'selected' : ''; ?>>Exotic Shorthair</option>
                            <option value="Havana Brown" <?php echo $cat['breed'] == 'Havana Brown' ? 'selected' : ''; ?>>Havana Brown</option>
                            <option value="Himalayan" <?php echo $cat['breed'] == 'Himalayan' ? 'selected' : ''; ?>>Himalayan</option>
                            <option value="Japanese Bobtail" <?php echo $cat['breed'] == 'Japanese Bobtail' ? 'selected' : ''; ?>>Japanese Bobtail</option>
                            <option value="Khao Manee" <?php echo $cat['breed'] == 'Khao Manee' ? 'selected' : ''; ?>>Khao Manee</option>
                            <option value="Korat" <?php echo $cat['breed'] == 'Korat' ? 'selected' : ''; ?>>Korat</option>
                            <option value="Kurilian Bobtail" <?php echo $cat['breed'] == 'Kurilian Bobtail' ? 'selected' : ''; ?>>Kurilian Bobtail</option>
                            <option value="LaPerm" <?php echo $cat['breed'] == 'LaPerm' ? 'selected' : ''; ?>>LaPerm</option>
                            <option value="Lykoi" <?php echo $cat['breed'] == 'Lykoi' ? 'selected' : ''; ?>>Lykoi</option>
                            <option value="Maine Coon" <?php echo $cat['breed'] == 'Maine Coon' ? 'selected' : ''; ?>>Maine Coon</option>
                            <option value="Manx" <?php echo $cat['breed'] == 'Manx' ? 'selected' : ''; ?>>Manx</option>
                            <option value="Munchkin" <?php echo $cat['breed'] == 'Munchkin' ? 'selected' : ''; ?>>Munchkin</option>
                            <option value="Nebelung" <?php echo $cat['breed'] == 'Nebelung' ? 'selected' : ''; ?>>Nebelung</option>
                            <option value="Norwegian Forest Cat" <?php echo $cat['breed'] == 'Norwegian Forest Cat' ? 'selected' : ''; ?>>Norwegian Forest Cat</option>
                            <option value="Ocicat" <?php echo $cat['breed'] == 'Ocicat' ? 'selected' : ''; ?>>Ocicat</option>
                            <option value="Oriental Longhair" <?php echo $cat['breed'] == 'Oriental Longhair' ? 'selected' : ''; ?>>Oriental Longhair</option>
                            <option value="Oriental Shorthair" <?php echo $cat['breed'] == 'Oriental Shorthair' ? 'selected' : ''; ?>>Oriental Shorthair</option>
                            <option value="Persian" <?php echo $cat['breed'] == 'Persian' ? 'selected' : ''; ?>>Persian</option>
                            <option value="Peterbald" <?php echo $cat['breed'] == 'Peterbald' ? 'selected' : ''; ?>>Peterbald</option>
                            <option value="Pixiebob" <?php echo $cat['breed'] == 'Pixiebob' ? 'selected' : ''; ?>>Pixiebob</option>
                            <option value="Ragdoll" <?php echo $cat['breed'] == 'Ragdoll' ? 'selected' : ''; ?>>Ragdoll</option>
                            <option value="Russian Blue" <?php echo $cat['breed'] == 'Russian Blue' ? 'selected' : ''; ?>>Russian Blue</option>
                            <option value="Savannah" <?php echo $cat['breed'] == 'Savannah' ? 'selected' : ''; ?>>Savannah</option>
                            <option value="Scottish Fold" <?php echo $cat['breed'] == 'Scottish Fold' ? 'selected' : ''; ?>>Scottish Fold</option>
                            <option value="Selkirk Rex" <?php echo $cat['breed'] == 'Selkirk Rex' ? 'selected' : ''; ?>>Selkirk Rex</option>
                            <option value="Serengeti" <?php echo $cat['breed'] == 'Serengeti' ? 'selected' : ''; ?>>Serengeti</option>
                            <option value="Siamese" <?php echo $cat['breed'] == 'Siamese' ? 'selected' : ''; ?>>Siamese</option>
                            <option value="Siberian" <?php echo $cat['breed'] == 'Siberian' ? 'selected' : ''; ?>>Siberian</option>
                            <option value="Singapura" <?php echo $cat['breed'] == 'Singapura' ? 'selected' : ''; ?>>Singapura</option>
                            <option value="Snowshoe" <?php echo $cat['breed'] == 'Snowshoe' ? 'selected' : ''; ?>>Snowshoe</option>
                            <option value="Somali" <?php echo $cat['breed'] == 'Somali' ? 'selected' : ''; ?>>Somali</option>
                            <option value="Sphynx" <?php echo $cat['breed'] == 'Sphynx' ? 'selected' : ''; ?>>Sphynx</option>
                            <option value="Tonkinese" <?php echo $cat['breed'] == 'Tonkinese' ? 'selected' : ''; ?>>Tonkinese</option>
                            <option value="Toyger" <?php echo $cat['breed'] == 'Toyger' ? 'selected' : ''; ?>>Toyger</option>
                            <option value="Turkish Angora" <?php echo $cat['breed'] == 'Turkish Angora' ? 'selected' : ''; ?>>Turkish Angora</option>
                            <option value="Turkish Van" <?php echo $cat['breed'] == 'Turkish Van' ? 'selected' : ''; ?>>Turkish Van</option>
                            <option value="York Chocolate" <?php echo $cat['breed'] == 'York Chocolate' ? 'selected' : ''; ?>>York Chocolate</option>
                            <option value="Other" <?php echo $cat['breed'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="5" 
                                  placeholder="Describe the cat's personality, behavior, likes/dislikes..." required><?php echo htmlspecialchars($cat['description']); ?></textarea>
                    </div>
                </section>

                <!-- Health Information -->
                <section class="form-section">
                    <h2>Health Information</h2>

                    <div class="form-group">
                        <label for="health_status">Health Status *</label>
                        <textarea id="health_status" name="health_status" rows="3" 
                                  placeholder="Describe any health conditions, medications, or special needs..." required><?php echo htmlspecialchars($cat['health_status']); ?></textarea>
                    </div>

                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="vaccinated" value="1" <?php echo $cat['vaccinated'] ? 'checked' : ''; ?>>
                            Vaccinated
                        </label>
                        <label>
                            <input type="checkbox" name="neutered" value="1" <?php echo $cat['neutered'] ? 'checked' : ''; ?>>
                            Neutered/Spayed
                        </label>
                    </div>
                </section>

                <!-- Image Management -->
                <section class="form-section">
                    <h2>Image Management</h2>

                    <?php if (!empty($existing_images)): ?>
                        <div class="form-group">
                            <label>Current Images (Check to delete)</label>
                            <div class="existing-images-container">
                                <?php foreach ($existing_images as $index => $img): ?>
                                    <div class="existing-image-item">
                                        <img src="../uploads/<?php echo htmlspecialchars($img['filename']); ?>" alt="Cat image">
                                        <?php if ($index === 0): ?>
                                            <span class="badge-main">Main</span>
                                        <?php endif; ?>
                                        <input type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>" class="delete-checkbox">
                                        <span class="delete-label">Delete</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small style="color: #666; display: block; margin-top: 10px;">
                                ⚠️ Note: You must keep at least one image. The first remaining image will become the main display image.
                            </small>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="images">Add New Images (Optional)</label>
                        <input type="file" id="images" name="images[]" accept="image/*" multiple>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Select multiple images to add. New images will be added to existing ones.
                        </small>
                        <div class="image-preview-container" id="imagePreviewContainer"></div>
                    </div>

                    <div class="form-group">
                        <label for="adoption_fee">Adoption Fee (₱) *</label>
                        <input type="number" id="adoption_fee" name="adoption_fee" min="0" step="0.01" 
                               placeholder="0.00" value="<?php echo $cat['adoption_fee']; ?>" required>
                    </div>
                </section>

                <!-- Location Section -->
                <section class="form-section">
                    <h2>Cat Location</h2>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" placeholder="Click on the map to set location" value="<?php echo htmlspecialchars($cat['address']); ?>" readonly>
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo $cat['latitude']; ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo $cat['longitude']; ?>">
                    </div>

                    <div id="map"></div>

                    <div id="location-info">
                        📍 <b>Current Location:</b><br>
                        <span id="loc-address"><?php echo htmlspecialchars($cat['address']) ?: 'Not set'; ?></span><br>
                        <span id="loc-coords"><?php echo $cat['latitude'] && $cat['longitude'] ? 'Lat: ' . $cat['latitude'] . ', Lng: ' . $cat['longitude'] : ''; ?></span>
                    </div>
                </section>

                <div class="form-actions">
                    <a href="adoption-form.php?cat_id=<?php echo $cat_id; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Cat Listing</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Enhance breed dropdown with Select2
            $('#breed').select2({
                placeholder: "Search or select a breed",
                allowClear: true,
                width: '100%'
            });

            // Image preview for new images
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
                            
                            const badge = document.createElement('span');
                            badge.className = 'badge-new';
                            badge.textContent = 'New';
                            previewDiv.appendChild(badge);
                            
                            container.appendChild(previewDiv);
                        }
                        reader.readAsDataURL(file);
                    });
                }
            });

            // Initialize Map
            var initialLat = <?php echo !empty($cat['latitude']) ? $cat['latitude'] : '14.5995'; ?>;
            var initialLon = <?php echo !empty($cat['longitude']) ? $cat['longitude'] : '120.9842'; ?>;
            var initialZoom = <?php echo !empty($cat['latitude']) ? '15' : '13'; ?>;
            
            var map = L.map('map').setView([initialLat, initialLon], initialZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            var marker = null;

            // Add existing marker if location exists
            <?php if (!empty($cat['latitude']) && !empty($cat['longitude'])): ?>
                marker = L.marker([initialLat, initialLon], { draggable: true }).addTo(map);
                
                marker.on('dragend', function(event) {
                    var newLat = event.target.getLatLng().lat;
                    var newLon = event.target.getLatLng().lng;
                    updateLocation(newLat, newLon);
                });
            <?php endif; ?>

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

            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lon = e.latlng.lng;

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

            // Form validation
            document.getElementById('editCatForm').addEventListener('submit', function(e) {
                const deleteCheckboxes = document.querySelectorAll('input[name="delete_images[]"]:checked');
                const existingImagesCount = <?php echo count($existing_images); ?>;
                const newImagesInput = document.getElementById('images');
                const newImagesCount = newImagesInput.files.length;
                
                const remainingImages = existingImagesCount - deleteCheckboxes.length + newImagesCount;
                
                if (remainingImages < 1) {
                    e.preventDefault();
                    alert('You must keep at least one image for the cat. Please uncheck some deletions or add new images.');
                    return false;
                }
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating...';
            });
        });
    </script>
</body>
</html>