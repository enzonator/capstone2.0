<?php
session_start();
require_once "../config/db.php";
include_once "../includes/header.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT username, email, first_name, last_name, profile_pic FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL error: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Handle remove profile picture
    if (isset($_POST['remove_profile_pic'])) {
        // Delete old profile picture (if not default)
        if (!empty($user['profile_pic']) && $user['profile_pic'] !== "default.jpg" && $user['profile_pic'] !== "profile_pics/default.jpg") {
            $oldPath = "../uploads/" . $user['profile_pic'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        // Set to default
        $profile_pic = "profile_pics/default.jpg";
        $updateSql = "UPDATE users SET profile_pic = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $profile_pic, $user_id);
        
        if ($updateStmt->execute()) {
            $success = "Profile picture removed successfully!";
            $user['profile_pic'] = $profile_pic;
        } else {
            $error = "Failed to remove profile picture.";
        }
    } else {
        // Handle normal form submission
        $email = $_POST['email'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name  = $_POST['last_name'] ?? '';
        $profile_pic = $user['profile_pic']; // Keep old picture if not updated

        // Handle profile picture upload
        if (!empty($_FILES['profile_pic']['name'])) {
            $targetDir = "../uploads/profile_pics/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
            $targetFilePath = $targetDir . $fileName;
            $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

            // Allowed file types
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileType, $allowedTypes)) {

                // Delete old profile picture (if not default)
                if (!empty($user['profile_pic']) && $user['profile_pic'] !== "default.jpg" && $user['profile_pic'] !== "profile_pics/default.jpg") {
                    $oldPath = "../uploads/" . $user['profile_pic'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // Move uploaded file
                if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFilePath)) {
                    // Save relative path under uploads folder
                    $profile_pic = "profile_pics/" . $fileName;
                } else {
                    $error = "Error uploading profile picture.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        }

        // Update user info
        $updateSql = "UPDATE users SET email = ?, first_name = ?, last_name = ?, profile_pic = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            die("SQL error: " . $conn->error);
        }
        $updateStmt->bind_param("ssssi", $email, $first_name, $last_name, $profile_pic, $user_id);

        if ($updateStmt->execute()) {
            $success = "Profile updated successfully";
            $user['email'] = $email;
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['profile_pic'] = $profile_pic;
        } else {
            $error = "Failed to update profile.";
        }
    }
}

// ✅ Determine correct profile picture path FOR BROWSER DISPLAY
$profilePicPath = "../uploads/profile_pics/default.jpg";
$hasProfilePic = false;

if (!empty($user['profile_pic'])) {
    // Check if file exists on server
    $serverPath = "../uploads/" . $user['profile_pic'];
    if (file_exists($serverPath)) {
        // Path for browser (relative to web root)
        $profilePicPath = "../uploads/" . $user['profile_pic'];
        $hasProfilePic = true;
    }
}
?>

<style>
/* Remove default container margin from header */
body {
    margin: 0 !important;
    padding: 0 !important;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%) !important;
}

.container.mt-4 {
    margin: 0 !important;
    padding: 0 !important;
    max-width: 100% !important;
}

.dashboard {
    display: flex;
    min-height: 100vh;
    margin: 0;
    width: 100%;
}

.profile-container {
    flex-grow: 1;
    padding: 30px;
    max-width: 100%;
    width: 100%;
    background: linear-gradient(135deg, #F5F0E8 0%, #E8DCC8 100%);
}

/* Header Section */
.profile-header {
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    color: white;
    padding: 35px 30px;
    border-radius: 20px;
    margin-bottom: 30px;
    box-shadow: 0 8px 24px rgba(139, 111, 71, 0.25);
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
}

.profile-header-content {
    position: relative;
    z-index: 1;
}

.profile-header h2 {
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.profile-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.05rem;
    font-weight: 400;
}

/* Alert Messages */
.success, .error {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
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

.success {
    background: linear-gradient(135deg, #d1e7dd 0%, #badbcc 100%);
    color: #0f5132;
    border: 1px solid #a3cfbb;
}

.success::before {
    content: "✓";
    font-size: 1.2rem;
    font-weight: bold;
}

.error {
    background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%);
    color: #842029;
    border: 1px solid #f5c2c7;
}

.error::before {
    content: "⚠";
    font-size: 1.2rem;
}

/* Profile Box */
.profile-box {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 4px 16px rgba(139, 111, 71, 0.12);
    border: 1px solid rgba(212, 196, 176, 0.3);
}

.profile-pic {
    text-align: center;
    margin-bottom: 30px;
}

.profile-pic .profile-avatar {
    width: 150px;
    height: 150px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3.5rem;
    font-weight: 700;
    box-shadow: 0 4px 20px rgba(139, 111, 71, 0.3);
    overflow: hidden;
    position: relative;
}

.profile-pic .profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    position: absolute;
    top: 0;
    left: 0;
}

.profile-pic .avatar-text {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    position: relative;
    z-index: 1;
}

.profile-box label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 10px 0 8px;
    font-weight: 600;
    color: #5D4E37;
    font-size: 0.95rem;
}

.profile-box input[type="text"],
.profile-box input[type="email"] {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    font-family: inherit;
}

.profile-box input[type="text"]:focus,
.profile-box input[type="email"]:focus {
    outline: none;
    border-color: #8B6F47;
    box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
}

.profile-box input[type="text"]:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
    color: #6c757d;
}

.profile-box input[type="file"] {
    display: none;
}

.file-upload-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border: none;
    color: #fff;
    border-radius: 10px;
    cursor: pointer;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
}

.file-upload-btn:hover {
    background: linear-gradient(135deg, #5a6268 0%, #545b62 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
}

.file-upload-btn.file-selected {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.file-upload-btn.file-selected:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
}

.profile-box button[type="submit"] {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
    border: none;
    color: #fff;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(139, 111, 71, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.profile-box button[type="submit"]:hover {
    background: linear-gradient(135deg, #A0826D 0%, #8B6F47 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 111, 71, 0.4);
}

.btn-remove {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border: none;
    color: #fff;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
    margin-top: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-remove:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
}

/* Responsive */
@media (max-width: 991px) {
    .dashboard {
        flex-direction: column;
    }
    
    .profile-container {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .profile-box {
        padding: 25px 20px;
    }

    .profile-pic .profile-avatar {
        width: 120px;
        height: 120px;
        font-size: 2.5rem;
    }

    .profile-header h2 {
        font-size: 1.8rem;
    }
}
</style>

<script>
// Show selected file name
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('profile_pic');
    const fileLabel = document.querySelector('.file-upload-btn');
    const originalHTML = '<i class="bi bi-upload"></i> Choose Profile Picture';
    
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            fileLabel.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + this.files[0].name;
            fileLabel.classList.add('file-selected');
        } else {
            fileLabel.innerHTML = originalHTML;
            fileLabel.classList.remove('file-selected');
        }
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.success, .error');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}
</script>

<div class="dashboard">
    <!-- Sidebar -->
    <?php include_once "../includes/sidebar.php"; ?>

    <!-- Profile Section -->
    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="profile-header-content">
                <h2>
                    <i class="bi bi-person-circle"></i>
                    My Profile
                </h2>
                <p>Manage your personal information and preferences</p>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <p class="success"><?= htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="profile-box">
            <div class="profile-pic">
                <div class="profile-avatar">
                    <?php if ($hasProfilePic): ?>
                        <img src="<?= htmlspecialchars($profilePicPath); ?>" alt="Profile" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="avatar-text" style="display: none;">
                            <?= strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="avatar-text">
                            <?= strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_pic" id="profile_pic" accept=".jpg,.jpeg,.png,.gif">
                <label for="profile_pic" class="file-upload-btn">
                    <i class="bi bi-upload"></i>
                    Choose Profile Picture
                </label>

                <label><i class="bi bi-person"></i> Username</label>
                <input type="text" value="<?= htmlspecialchars($user['username']); ?>" disabled>

                <label><i class="bi bi-person-badge"></i> First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" placeholder="Enter your first name">

                <label><i class="bi bi-person-badge"></i> Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" placeholder="Enter your last name">

                <label><i class="bi bi-envelope"></i> Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" placeholder="Enter your email">

                <button type="submit">
                    <i class="bi bi-check-circle"></i>
                    Update Profile
                </button>
            </form>

            <!-- Remove Profile Picture Button -->
            <?php if (!empty($user['profile_pic']) && $user['profile_pic'] !== "profile_pics/default.jpg"): ?>
            <form method="POST">
                <button type="submit" name="remove_profile_pic" class="btn-remove" onclick="return confirm('Are you sure you want to remove your profile picture?');">
                    <i class="bi bi-trash"></i>
                    Remove Profile Picture
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once "../includes/footer.php"; ?>