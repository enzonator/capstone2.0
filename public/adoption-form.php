<?php
session_start();
require_once "../config/db.php";

$success_message = '';
$error_message = '';

// Get current user and verification status
$current_user_id = $_SESSION['user_id'] ?? null;
$verificationStatus = null;
$isVerified = false;

// Check verification status BEFORE any output
if ($current_user_id) {
    $verifyQuery = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
    $verifyQuery->bind_param("i", $current_user_id);
    $verifyQuery->execute();
    $verifyResult = $verifyQuery->get_result();
    if ($verifyResult->num_rows > 0) {
        $verifyData = $verifyResult->fetch_assoc();
        $verificationStatus = $verifyData['verification_status'];
        $isVerified = ($verificationStatus === 'verified');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$current_user_id) {
        header("Location: login.php");
        exit();
    }

    // Check verification before processing
    if (!$isVerified) {
        if ($verificationStatus == 'not verified') {
            $_SESSION['show_verification_popup'] = 'not_verified';
        } elseif ($verificationStatus == 'pending') {
            $_SESSION['show_verification_popup'] = 'pending';
        } else {
            $_SESSION['show_verification_popup'] = 'not_verified';
        }
        header("Location: " . $_SERVER['PHP_SELF'] . '?cat_id=' . intval($_GET['cat_id'] ?? 0));
        exit();
    }

    // Get form data
    $cat_id = intval($_POST['cat_id']);
    $applicant_name = $_POST['applicant_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $has_other_pets = isset($_POST['has_other_pets']) ? 1 : 0;
    $other_pets_details = $_POST['other_pets_details'] ?? '';
    $home_type = $_POST['home_type'];
    $has_yard = isset($_POST['has_yard']) ? 1 : 0;
    $experience_with_cats = $_POST['experience_with_cats'];
    $reason_for_adoption = $_POST['reason_for_adoption'];
    $veterinarian_info = $_POST['veterinarian_info'] ?? '';
    $references = $_POST['references'] ?? '';
    
    // New fields
    $living_with = isset($_POST['living_with']) ? implode(', ', $_POST['living_with']) : '';
    $household_allergic = $_POST['household_allergic'] ?? '';
    $responsible_person = $_POST['responsible_person'] ?? '';
    $financially_responsible = $_POST['financially_responsible'] ?? '';
    $vacation_care = $_POST['vacation_care'] ?? '';
    $hours_alone = $_POST['hours_alone'] ?? '';
    $introduction_steps = $_POST['introduction_steps'] ?? '';
    $family_support = $_POST['family_support'] ?? '';
    
    // IMPORTANT: Capture the logged-in user's ID
    $applicant_user_id = $current_user_id;
    
    // First, get cat info for notification
    $catInfoSql = "SELECT ac.*, u.id as owner_id, u.username as owner_name 
                   FROM adoption_cats ac 
                   LEFT JOIN users u ON ac.user_id = u.id 
                   WHERE ac.id = ?";
    $catInfoStmt = $conn->prepare($catInfoSql);
    $catInfoStmt->bind_param("i", $cat_id);
    $catInfoStmt->execute();
    $catInfoResult = $catInfoStmt->get_result();
    $catInfo = $catInfoResult->fetch_assoc();
    
    // UPDATED SQL: Now includes user_id column
    $sql = "INSERT INTO adoption_applications 
            (cat_id, user_id, applicant_name, email, phone, address, has_other_pets, other_pets_details, 
            home_type, has_yard, experience_with_cats, reason_for_adoption, veterinarian_info, `references`,
            living_with, household_allergic, responsible_person, financially_responsible, vacation_care, 
            hours_alone, introduction_steps, family_support)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }
    
    // UPDATED bind_param: Added 'i' for user_id (integer) at the beginning
    $stmt->bind_param("iissssisssssssssssssss", 
        $cat_id, $applicant_user_id, $applicant_name, $email, $phone, $address, 
        $has_other_pets, $other_pets_details, $home_type, $has_yard,
        $experience_with_cats, $reason_for_adoption, $veterinarian_info, $references,
        $living_with, $household_allergic, $responsible_person, $financially_responsible, 
        $vacation_care, $hours_alone, $introduction_steps, $family_support
    );
    
    if ($stmt->execute()) {
        $application_id = $conn->insert_id; // Get the inserted application ID
        
        // Update cat status to pending
        $updateSql = "UPDATE adoption_cats SET status = 'Pending' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $cat_id);
        $updateStmt->execute();
        
        // Send notification to cat owner
        if ($catInfo && isset($catInfo['owner_id'])) {
            $owner_id = $catInfo['owner_id'];
            $cat_name_notif = $catInfo['name'];
            $notif_message = "New adoption application received for " . htmlspecialchars($cat_name_notif) . " from " . htmlspecialchars($applicant_name);
            $notif_type = "adoption_application";
            
            $notifSql = "INSERT INTO notifications (user_id, message, type, cat_id, is_read, created_at) 
                         VALUES (?, ?, ?, ?, 0, NOW())";
            $notifStmt = $conn->prepare($notifSql);
            
            if ($notifStmt === false) {
                error_log("Notification prepare failed: " . $conn->error);
            } else {
                $notifStmt->bind_param("issi", $owner_id, $notif_message, $notif_type, $cat_id);
                if ($notifStmt->execute()) {
                    error_log("Notification sent successfully to user_id: " . $owner_id);
                } else {
                    error_log("Notification execute failed: " . $notifStmt->error);
                }
            }
        } else {
            error_log("No cat owner found for cat_id: " . $cat_id);
        }
        
        $success_message = "Your adoption application has been submitted successfully! We will review your application and contact you within 2-3 business days.";
    } else {
        $error_message = "Error submitting application. Please try again.";
    }
}


// Get cat information
$cat_id = $_GET['cat_id'] ?? 0;
$cat_name = $_GET['cat_name'] ?? 'Unknown Cat';
$cat = null;
$cat_images = [];
$is_owner = false;
$has_applied = false;
$user_application = null;
$cat_owner_name = 'Unknown';

if ($cat_id) {
    // Fetch cat info with owner information
    $sql = "SELECT ac.*, u.username 
            FROM adoption_cats ac 
            LEFT JOIN users u ON ac.user_id = u.id 
            WHERE ac.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cat = $result->fetch_assoc();
    if ($cat) {
        $cat_name = $cat['name'];
        $cat_owner_name = $cat['username'] ?? 'Unknown';
        
        // Check if current user is the owner
        if ($current_user_id && isset($cat['user_id']) && $current_user_id == $cat['user_id']) {
            $is_owner = true;
        }
        
        // Check if user has already applied
        if ($current_user_id) {
            $userSql = "SELECT email FROM users WHERE id = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("i", $current_user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $currentUser = $userResult->fetch_assoc();
            
            if ($currentUser) {
                $checkAppSql = "SELECT * FROM adoption_applications WHERE cat_id = ? AND email = ? ORDER BY submitted_at DESC LIMIT 1";
                $checkAppStmt = $conn->prepare($checkAppSql);
                $checkAppStmt->bind_param("is", $cat_id, $currentUser['email']);
                $checkAppStmt->execute();
                $appResult = $checkAppStmt->get_result();
                if ($appResult->num_rows > 0) {
                    $has_applied = true;
                    $user_application = $appResult->fetch_assoc();
                }
            }
        }
        
        // Fetch all images for this cat
        $imgSql = "SELECT filename FROM adoption_cat_images WHERE cat_id = ? ORDER BY id ASC";
        $imgStmt = $conn->prepare($imgSql);
        $imgStmt->bind_param("i", $cat_id);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();
        while ($imgRow = $imgResult->fetch_assoc()) {
            $cat_images[] = $imgRow['filename'];
        }
    }
}

// Check for session messages (verification popup)
$showVerificationPopup = false;
$popupType = '';
if (isset($_SESSION['show_verification_popup'])) {
    $showVerificationPopup = true;
    $popupType = $_SESSION['show_verification_popup'];
    unset($_SESSION['show_verification_popup']);
}

include_once "../includes/header.php";
?>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($showVerificationPopup): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showVerificationPopup('<?= $popupType ?>');
});
</script>
<?php endif; ?>

<!-- Leaflet CSS/JS for map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

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
        max-width: 1400px;
        margin: 0 auto;
    }

    .form-wrapper {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
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

    .cat-name {
        font-size: 1.2em;
        opacity: 0.95;
    }

    .adoption-form {
        padding: 40px 30px;
    }

    .form-section {
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 2px solid #f0f0f0;
    }

    .form-section:last-of-type {
        border-bottom: none;
    }

    .form-section h2 {
        color: #2c3e50;
        font-size: 1.6em;
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
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group textarea,
    .form-group select {
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
        min-height: 80px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
    }

    .checkbox-group label {
        display: flex;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .checkbox-group input[type="checkbox"] {
        margin-right: 10px;
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .agreement-box {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
    }

    .agreement-box h3 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.3em;
    }

    .agreement-box p {
        color: #555;
        margin-bottom: 10px;
    }

    .agreement-box ul {
        margin: 15px 0 20px 25px;
        color: #555;
    }

    .agreement-box li {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 30px;
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
        font-weight: 700;
    }

    .btn-primary:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(234, 221, 202, 0.5);
        background: linear-gradient(135deg, #EADDCA 0%, #d4c4a8 100%);
    }

    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .btn-secondary {
        background: #e0e0e0;
        color: #555;
        font-weight: 600;
    }

    .btn-secondary:hover {
        background: #d0d0d0;
    }

    .btn-verify {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #3d3020;
        font-weight: 700;
    }

    .btn-verify:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(255, 193, 7, 0.5);
        background: linear-gradient(135deg, #ffca28 0%, #ffa726 100%);
    }

    .verification-notice {
        background: #fff3cd;
        border: 2px solid #ffc107;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }

    .verification-notice.pending {
        background: #cfe2ff;
        border-color: #0d6efd;
    }

    .verification-notice h4 {
        color: #856404;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .verification-notice.pending h4 {
        color: #084298;
    }

    .verification-notice p {
        color: #856404;
        margin-bottom: 15px;
    }

    .verification-notice.pending p {
        color: #084298;
    }

    .login-notice {
        background: #fff3cd;
        border: 2px solid #ffc107;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }

    .login-notice h4 {
        color: #856404;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .login-notice p {
        color: #856404;
        margin-bottom: 0;
    }

    .alert {
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        animation: slideIn 0.5s;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: #d4edda;
        border: 2px solid #c3e6cb;
        color: #155724;
    }

    .alert-error {
        background: #f8d7da;
        border: 2px solid #f5c6cb;
        color: #721c24;
    }

    .btn-link {
        color: #8b7355;
        text-decoration: none;
        font-weight: 600;
    }

    .btn-link:hover {
        text-decoration: underline;
    }

    .cat-location-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        margin-bottom: 20px;
    }

    .cat-location-box h3 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.2em;
    }

    #catMap {
        width: 100%;
        height: 350px;
        border-radius: 10px;
        margin-top: 10px;
        border: 2px solid #ddd;
    }

    .cat-images-gallery {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .cat-images-gallery h3 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.3em;
    }

    .pet-overview-section {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }

    .gallery-container {
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }

    .thumbnails {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 500px;
        overflow-y: auto;
    }

    .thumbnails img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: border 0.2s ease;
    }

    .thumbnails img.active {
        border: 2px solid #c9b896;
    }

    .main-image-container {
        flex: 1;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 350px;
    }

    .main-image-container img {
        max-width: 100%;
        max-height: 450px;
        border-radius: 8px;
        object-fit: contain;
        box-shadow: 0px 4px 8px rgba(0,0,0,0.2);
    }

    .nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(212, 196, 168, 0.8);
        color: #3d3020;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 10px 14px;
        border-radius: 50%;
        transition: background 0.3s;
    }

    .nav-btn:hover {
        background: rgba(234, 221, 202, 1);
    }

    .nav-btn.prev { left: 10px; }
    .nav-btn.next { right: 10px; }

    .cat-info-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .cat-info-box h3 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-size: 1.3em;
    }

    .cat-info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
        margin-top: 15px;
    }

    .info-item-box {
        padding: 12px;
        background: #f8f9fa;
        border-radius: 5px;
        border-left: 3px solid #c9b896;
    }

    .info-item-box strong {
        color: #2c3e50;
        display: block;
        margin-bottom: 5px;
        font-size: 0.9em;
    }

    .badge-inline {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.85em;
        font-weight: bold;
        color: white;
        margin-right: 5px;
        margin-top: 5px;
    }

    .badge-inline.vaccinated {
        background: #27ae60;
    }

    .badge-inline.neutered {
        background: #3498db;
    }

    .description-box {
        margin-top: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        line-height: 1.6;
    }

    .health-box {
        margin-top: 15px;
        padding: 15px;
        background: #e8f5e9;
        border-radius: 5px;
        color: #2e7d32;
        line-height: 1.6;
    }

    .owner-actions-box {
        background: #e3f2fd;
        border: 2px solid #90caf9;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        margin-bottom: 20px;
    }

    .owner-actions-box h3 {
        color: #1565c0;
        margin-bottom: 15px;
        font-size: 1.3em;
    }

    .owner-badge {
        display: inline-block;
        background: #4caf50;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .edit-pet-btn {
        display: inline-block;
        padding: 12px 30px;
        background: #2196f3;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        margin-top: 15px;
    }

    .edit-pet-btn:hover {
        background: #1976d2;
        transform: translateY(-2px);
    }

    .application-status-box {
        background: white;
        border: 2px solid #e0e0e0;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .application-status-box h3 {
        color: #2c3e50;
        margin-bottom: 20px;
        font-size: 1.4em;
    }

    .status-badge-large {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 700;
        font-size: 1.1em;
        margin-bottom: 20px;
    }

    .status-badge-large.status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge-large.status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-badge-large.status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .status-badge-large.status-completed {
        background: #d1ecf1;
        color: #0c5460;
    }

    @media (max-width: 992px) {
        .pet-overview-section {
            grid-template-columns: 1fr;
        }

        .gallery-container {
            flex-direction: column-reverse;
        }

        .thumbnails {
            flex-direction: row;
            max-height: none;
            overflow-x: auto;
        }

        .thumbnails img {
            width: 70px;
            height: 70px;
        }
    }

    @media (max-width: 768px) {
        .form-header h1 {
            font-size: 2em;
        }
        
        .adoption-form {
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

<div class="container">
    <div class="form-wrapper">
        <div class="form-header">
            <h1>🐱 Adoption Application</h1>
            <p class="cat-name">Applying to adopt: <strong><?php echo htmlspecialchars($cat_name); ?></strong></p>
        </div>

        <div class="adoption-form">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                    <br><br>
                    <a href="adoption.php" class="btn-link">← Back to Adoption Listings</a>
                </div>
            <?php elseif ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success_message): ?>
                <!-- Pet Overview Section: Images + Info Side by Side -->
                <div class="pet-overview-section">
                    <!-- Left: Cat Images Gallery -->
                    <?php if ($cat && !empty($cat_images)): ?>
                        <div class="cat-images-gallery">
                            <h3>🖼️ Photos of <?php echo htmlspecialchars($cat_name); ?></h3>
                            <div class="gallery-container">
                                <?php if (count($cat_images) > 1): ?>
                                    <div class="thumbnails">
                                        <?php foreach ($cat_images as $index => $img): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($img); ?>" 
                                                 alt="Thumbnail"
                                                 class="<?php echo $index === 0 ? 'active' : ''; ?>"
                                                 onclick="changeImage(this, <?php echo $index; ?>)">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="main-image-container">
                                    <img id="currentImage" src="../uploads/<?php echo htmlspecialchars($cat_images[0]); ?>" alt="<?php echo htmlspecialchars($cat_name); ?>">
                                    <?php if (count($cat_images) > 1): ?>
                                        <button type="button" class="nav-btn prev" onclick="prevImage()">&#10094;</button>
                                        <button type="button" class="nav-btn next" onclick="nextImage()">&#10095;</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Right: Cat Information Box -->
                    <?php if ($cat): ?>
                        <div class="cat-info-box">
                            <h3>📋 About <?php echo htmlspecialchars($cat_name); ?></h3>
                            <div class="cat-info-grid">
                                <div class="info-item-box">
                                    <strong>Breed:</strong>
                                    <?php echo htmlspecialchars($cat['breed']); ?>
                                </div>
                                <div class="info-item-box">
                                    <strong>Age:</strong>
                                    <?php echo $cat['age']; ?> year(s)
                                </div>
                                <div class="info-item-box">
                                    <strong>Gender:</strong>
                                    <?php echo htmlspecialchars($cat['gender']); ?>
                                </div>
                                <div class="info-item-box">
                                    <strong>Adoption Fee:</strong>
                                    ₱<?php echo number_format($cat['adoption_fee'], 2); ?>
                                </div>
                                <div class="info-item-box" style="grid-column: 1 / -1;">
                                    <strong>Listed by:</strong>
                                    <?php echo htmlspecialchars($cat_owner_name); ?>
                                </div>
                            </div>
                            
                            <?php if ($cat['vaccinated'] || $cat['neutered']): ?>
                                <div style="margin-top: 15px;">
                                    <?php if ($cat['vaccinated']): ?>
                                        <span class="badge-inline vaccinated">✓ Vaccinated</span>
                                    <?php endif; ?>
                                    <?php if ($cat['neutered']): ?>
                                        <span class="badge-inline neutered">✓ Neutered/Spayed</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($cat['description'])): ?>
                                <div class="description-box">
                                    <strong style="display: block; margin-bottom: 8px; color: #2c3e50;">Description:</strong>
                                    <?php echo nl2br(htmlspecialchars($cat['description'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($cat['health_status'])): ?>
                                <div class="health-box">
                                    <strong style="display: block; margin-bottom: 8px;">Health Status:</strong>
                                    <?php echo nl2br(htmlspecialchars($cat['health_status'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Cat Location Display -->
                <?php if ($cat && !empty($cat['latitude']) && !empty($cat['longitude'])): ?>
                    <div class="cat-location-box">
                        <h3>📍 Cat's Current Location</h3>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($cat['address'] ?? 'Location provided'); ?></p>
                        <div id="catMap"></div>
                    </div>
                <?php elseif ($cat): ?>
                    <div class="cat-location-box" style="text-align: center;">
                        <h3>📍 Cat's Location</h3>
                        <div style="padding: 30px; background: #f8f9fa; border-radius: 8px; margin-top: 15px;">
                            <span style="font-size: 3em; opacity: 0.3;">📍</span>
                            <p style="color: #666; margin-top: 15px; font-size: 1.05em;">No location submitted by the owner.</p>
                            <p style="color: #999; margin-top: 5px; font-size: 0.95em;">You can ask about the location when you submit your application.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Check if user is owner or has already applied -->
                <?php if ($is_owner): ?>
                    <!-- Owner sees edit button -->
                    <div class="owner-actions-box">
                        <div class="icon-header">👤</div>
                        <span class="owner-badge">✓ You Own This Pet</span>
                        <h3>Manage Your Listing</h3>
                        <p>This is your listed cat for adoption. You can edit the listing details, update photos, or manage applications below.</p>
                        <a href="edit-adoption-cat.php?id=<?php echo $cat_id; ?>" class="edit-pet-btn">
                            <span class="icon">✏️</span>
                            <span>Edit Pet Listing</span>
                        </a>
                    </div>
                <?php elseif ($has_applied): ?>
                    <!-- User has already applied -->
                    <div class="application-status-box">
                        <h3>📝 Your Application Status</h3>
                        <span class="status-badge-large status-<?php echo strtolower($user_application['status']); ?>">
                            <?php echo htmlspecialchars($user_application['status']); ?>
                        </span>
                        <div class="application-details">
                            <p><strong>Applied on:</strong> <?php echo date('F d, Y', strtotime($user_application['submitted_at'])); ?></p>
                            <p><strong>Your Name:</strong> <?php echo htmlspecialchars($user_application['applicant_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_application['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user_application['phone']); ?></p>
                            
                            <?php if ($user_application['status'] === 'Pending'): ?>
                                <p style="margin-top: 15px; color: #856404;">
                                    ⏳ Your application is being reviewed. We will contact you within 2-3 business days.
                                </p>
                            <?php elseif ($user_application['status'] === 'Approved'): ?>
                                <p style="margin-top: 15px; color: #155724;">
                                    ✅ Congratulations! Your application has been approved. Please check your email for next steps.
                                </p>
                            <?php elseif ($user_application['status'] === 'Rejected'): ?>
                                <p style="margin-top: 15px; color: #721c24;">
                                    ❌ Unfortunately, your application was not approved at this time.
                                </p>
                            <?php elseif ($user_application['status'] === 'Completed'): ?>
                                <p style="margin-top: 15px; color: #0c5460;">
                                    🎉 Adoption completed! Enjoy your new furry friend!
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($user_application['admin_notes'])): ?>
                                <div style="margin-top: 15px; padding: 10px; background: #e7f3ff; border-radius: 5px;">
                                    <strong>Admin Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($user_application['admin_notes'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 20px;">
                            <a href="adoption.php" class="btn btn-secondary">← Back to Adoption Listings</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Check for verification status -->
                    <?php if (!$current_user_id): ?>
                        <!-- Not logged in -->
                        <div class="login-notice">
                            <h4>🔐 Login Required</h4>
                            <p>You need to be logged in to submit an adoption application. Please login or create an account to proceed.</p>
                        </div>
                    <?php elseif ($verificationStatus === 'pending'): ?>
                        <!-- Pending verification -->
                        <div class="verification-notice pending">
                            <h4>⏳ Verification Pending</h4>
                            <p>Your account verification is currently being reviewed by our admin team. Please wait for approval before you can submit adoption applications.</p>
                            <p style="margin-bottom: 0;"><strong>We will notify you once your account is verified!</strong></p>
                        </div>
                    <?php elseif (!$isVerified): ?>
                        <!-- Not verified -->
                        <div class="verification-notice">
                            <h4>⚠️ Account Verification Required</h4>
                            <p>To ensure the safety and well-being of our adoptable pets, you must verify your account before submitting an adoption application.</p>
                            <a href="verify.php" class="btn btn-verify" style="text-decoration: none; display: inline-block;">
                                ✓ Verify Account Now
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Show application form -->
                    <form method="POST" action="" id="adoptionForm">
                        <input type="hidden" name="cat_id" value="<?php echo htmlspecialchars($cat_id); ?>">

                        <!-- Personal Information -->
                        <section class="form-section">
                            <h2>Personal Information</h2>
                            
                            <div class="form-group">
                                <label for="applicant_name">Full Name *</label>
                                <input type="text" id="applicant_name" name="applicant_name" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Full Address *</label>
                                <textarea id="address" name="address" rows="3" <?php echo $isVerified ? 'required' : 'disabled'; ?>></textarea>
                            </div>
                        </section>

                        <!-- Living Situation -->
                        <section class="form-section">
                            <h2>Living Situation</h2>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="home_type">Type of Home *</label>
                                    <select id="home_type" name="home_type" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                        <option value="">Select...</option>
                                        <option value="House">House</option>
                                        <option value="Apartment">Apartment</option>
                                        <option value="Condo">Condo</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="form-group checkbox-group">
                                    <label>
                                        <input type="checkbox" name="has_yard" value="1" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        I have a yard
                                    </label>
                                </div>
                            </div>
                        </section>

                        <!-- Pet Experience -->
                        <section class="form-section">
                            <h2>Pet Experience & Household</h2>

                            <div class="form-group">
                                <label>Who do you live with? *</label>
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="checkbox" name="living_with[]" value="Living alone" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Living alone</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="checkbox" name="living_with[]" value="Spouse" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Spouse</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="checkbox" name="living_with[]" value="Parents" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Parents</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="checkbox" name="living_with[]" value="Children over 18" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Children over 18</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="checkbox" name="living_with[]" value="Children below 18" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Children below 18</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="checkbox" name="living_with[]" value="Relatives" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Relatives</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="checkbox" name="living_with[]" value="Roommate(s)" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Roommate(s)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Are any members of your household allergic to animals? *</label>
                                <div style="display: flex; gap: 20px;">
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="radio" name="household_allergic" value="Yes" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Yes</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="radio" name="household_allergic" value="No" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">No</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="responsible_person">Who will be responsible for feeding, grooming, and generally caring for your pet? *</label>
                                <input type="text" id="responsible_person" name="responsible_person" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                            </div>

                            <div class="form-group">
                                <label for="financially_responsible">Who will be financially responsible for your pet's needs (i.e. food, vet bills, etc.)? *</label>
                                <input type="text" id="financially_responsible" name="financially_responsible" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                            </div>

                            <div class="form-group">
                                <label for="vacation_care">Who will look after your pet if you go on vacation or in case of emergency? *</label>
                                <input type="text" id="vacation_care" name="vacation_care" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                            </div>

                            <div class="form-group">
                                <label for="hours_alone">How many hours in an average workday will your pet be left alone? *</label>
                                <input type="text" id="hours_alone" name="hours_alone" placeholder="e.g., 6-8 hours" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                            </div>

                            <div class="form-group">
                                <label for="introduction_steps">What steps will you take to introduce your new pet to his/her new surroundings? *</label>
                                <textarea id="introduction_steps" name="introduction_steps" rows="4" <?php echo $isVerified ? 'required' : 'disabled'; ?>></textarea>
                            </div>

                            <div class="form-group">
                                <label>Does everyone in the family support your decision to adopt a pet? *</label>
                                <div style="display: flex; gap: 20px;">
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="radio" name="family_support" value="Yes" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">Yes</span>
                                    </label>
                                    <label style="display: flex; align-items: center; font-weight: normal;">
                                        <input type="radio" name="family_support" value="No" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                        <span style="margin-left: 8px;">No</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="has_other_pets" value="1" id="hasOtherPets" <?php echo $isVerified ? '' : 'disabled'; ?>>
                                    I currently have other pets
                                </label>
                            </div>

                            <div class="form-group" id="otherPetsGroup" style="display: none;">
                                <label for="other_pets_details">Please describe your other pets (type, breed, age)</label>
                                <textarea id="other_pets_details" name="other_pets_details" rows="3" <?php echo $isVerified ? '' : 'disabled'; ?>></textarea>
                            </div>

                            <div class="form-group">
                                <label for="experience_with_cats">Describe your experience with cats *</label>
                                <textarea id="experience_with_cats" name="experience_with_cats" rows="4" 
                                          placeholder="Have you owned cats before? How long?" <?php echo $isVerified ? 'required' : 'disabled'; ?>></textarea>
                            </div>

                            <div class="form-group">
                                <label for="reason_for_adoption">Why do you want to adopt this cat? *</label>
                                <textarea id="reason_for_adoption" name="reason_for_adoption" rows="4" <?php echo $isVerified ? 'required' : 'disabled'; ?>></textarea>
                            </div>
                        </section>

                        <!-- References -->
                        <section class="form-section">
                            <h2>References</h2>

                            <div class="form-group">
                                <label for="veterinarian_info">Veterinarian Information (if applicable)</label>
                                <textarea id="veterinarian_info" name="veterinarian_info" rows="2" 
                                          placeholder="Veterinarian name, clinic, phone number" <?php echo $isVerified ? '' : 'disabled'; ?>></textarea>
                            </div>

                            <div class="form-group">
                                <label for="references">Personal References</label>
                                <textarea id="references" name="references" rows="3" 
                                          placeholder="Name and contact information of 2 personal references" <?php echo $isVerified ? '' : 'disabled'; ?>></textarea>
                            </div>
                        </section>

                        <!-- Agreement -->
                        <section class="form-section">
                            <div class="agreement-box">
                                <h3>Adoption Agreement</h3>
                                <p>By submitting this application, I agree to:</p>
                                <ul>
                                    <li>Provide a safe, loving home for this cat</li>
                                    <li>Ensure proper veterinary care including vaccinations</li>
                                    <li>Keep the cat indoors or provide supervised outdoor time</li>
                                    <li>Return the cat if I can no longer care for it</li>
                                    <li>Allow a home visit if requested</li>
                                </ul>
                                <label class="checkbox-group">
                                    <input type="checkbox" id="agreement" <?php echo $isVerified ? 'required' : 'disabled'; ?>>
                                    I have read and agree to the terms above *
                                </label>
                            </div>
                        </section>

                        <div class="form-actions">
                            <?php if (!$current_user_id): ?>
                                <a href="adoption.php" class="btn btn-secondary">Back to Listings</a>
                                <a href="login.php" class="btn btn-primary" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                    🔐 Login to Apply
                                </a>
                            <?php elseif (!$isVerified): ?>
                                <a href="adoption.php" class="btn btn-secondary">Back to Listings</a>
                                <?php if ($verificationStatus === 'pending'): ?>
                                    <button type="button" class="btn btn-primary" disabled>
                                        ⏳ Verification Pending
                                    </button>
                                <?php else: ?>
                                    <a href="verify.php" class="btn btn-verify" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                                        ✓ Verify Account First
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="adoption.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Submit Application</button>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Verification popup function
function showVerificationPopup(status) {
    if (status === 'not_verified') {
        Swal.fire({
            title: 'Account Not Verified',
            text: 'Please verify your account to submit adoption applications.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Verify Now',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'verify.php';
            }
        });
    } else if (status === 'pending') {
        Swal.fire({
            title: 'Verification Pending',
            text: 'Your verification has been submitted. Please wait for admin approval before you can submit adoption applications.',
            icon: 'info',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0d6efd',
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle other pets details field
    const hasOtherPetsCheckbox = document.getElementById('hasOtherPets');
    const otherPetsGroup = document.getElementById('otherPetsGroup');

    if (hasOtherPetsCheckbox && otherPetsGroup) {
        hasOtherPetsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                otherPetsGroup.style.display = 'block';
                document.getElementById('other_pets_details').required = true;
            } else {
                otherPetsGroup.style.display = 'none';
                document.getElementById('other_pets_details').required = false;
                document.getElementById('other_pets_details').value = '';
            }
        });
    }

    // Form validation
    const form = document.getElementById('adoptionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const agreement = document.getElementById('agreement');
            
            if (!agreement.checked) {
                e.preventDefault();
                alert('Please agree to the adoption terms before submitting.');
                agreement.focus();
                return false;
            }

            // Optional: Add loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }
        });
    }

    // Phone number formatting (optional)
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            e.target.value = value;
        });
    }

    // Email validation
    const emailInput = document.getElementById('email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value && !emailPattern.test(this.value)) {
                this.style.borderColor = '#e74c3c';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    }

    // Initialize map if cat location exists
    <?php if ($cat && !empty($cat['latitude']) && !empty($cat['longitude'])): ?>
        const lat = <?php echo $cat['latitude']; ?>;
        const lon = <?php echo $cat['longitude']; ?>;
        
        const catMap = L.map('catMap').setView([lat, lon], 14);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(catMap);
        
        L.marker([lat, lon]).addTo(catMap)
            .bindPopup("<?php echo htmlspecialchars($cat['name']); ?>'s Location")
            .openPopup();
    <?php endif; ?>

    // Image gallery functionality
    <?php if (!empty($cat_images) && count($cat_images) > 1): ?>
        let images = <?php echo json_encode($cat_images); ?>;
        let currentIndex = 0;

        window.changeImage = function(el, index) {
            document.getElementById("currentImage").src = el.src;
            currentIndex = index;

            document.querySelectorAll(".thumbnails img").forEach(img => img.classList.remove("active"));
            el.classList.add("active");
        }

        window.prevImage = function() {
            if (images.length === 0) return;
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            updateImage();
        }

        window.nextImage = function() {
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
    <?php endif; ?>
});
</script>

<?php include_once "../includes/footer.php"; ?>