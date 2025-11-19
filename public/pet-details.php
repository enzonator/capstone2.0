<?php
session_start();
require_once "../config/db.php";

// Get pet_id from URL
if (!isset($_GET['id'])) {
    die("Pet not found.");
}

$pet_id = intval($_GET['id']);

// Fetch pet info (including seller's profile picture)
$sql = "SELECT p.*, u.username, u.id as user_id, u.profile_pic as seller_profile_pic 
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

    // Handle Comment Submission
    if (isset($_POST['submit_comment']) && !empty($_POST['comment_text'])) {
        $comment_text = trim($_POST['comment_text']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        
        if ($parent_id) {
            $insertComment = "INSERT INTO pet_comments (pet_id, user_id, comment_text, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())";
            $commentStmt = $conn->prepare($insertComment);
            $commentStmt->bind_param("iisi", $pet_id_post, $current_user_id, $comment_text, $parent_id);
        } else {
            $insertComment = "INSERT INTO pet_comments (pet_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
            $commentStmt = $conn->prepare($insertComment);
            $commentStmt->bind_param("iis", $pet_id_post, $current_user_id, $comment_text);
        }
        
        if ($commentStmt->execute()) {
            $_SESSION['comment_message'] = $parent_id ? 'Reply posted successfully!' : 'Comment posted successfully!';
        } else {
            $_SESSION['comment_message'] = 'Error posting comment.';
        }
    }

    // Handle Delete Comment
    if (isset($_POST['delete_comment'])) {
        $comment_id = intval($_POST['comment_id']);
        $deleteComment = "DELETE FROM pet_comments WHERE id = ? AND user_id = ?";
        $deleteStmt = $conn->prepare($deleteComment);
        $deleteStmt->bind_param("ii", $comment_id, $current_user_id);
        
        if ($deleteStmt->execute()) {
            $_SESSION['comment_message'] = 'Comment deleted successfully!';
        }
        header("Location: pet-details.php?id=" . $pet_id);
        exit();
    }

    // Handle Buy Now - Check verification and redirect BEFORE any output
    if (isset($_POST['buy_now'])) {
        if ($verificationStatus == 'verified') {
            header("Location: checkout.php?pet_ids=" . $pet_id_post);
            exit();
        } else {
            if ($verificationStatus == 'not verified') {
                $_SESSION['show_verification_popup'] = 'not_verified';
            } elseif ($verificationStatus == 'pending') {
                $_SESSION['show_verification_popup'] = 'pending';
            } else {
                $_SESSION['show_verification_popup'] = 'not_verified';
            }
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

// Fetch comments for this pet (only parent comments) - UPDATED TO INCLUDE profile_pic
$commentsSql = "SELECT c.*, u.username, u.id as commenter_id, u.profile_pic 
                FROM pet_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.pet_id = ? AND c.parent_id IS NULL
                ORDER BY c.created_at DESC";
$commentsStmt = $conn->prepare($commentsSql);
$commentsStmt->bind_param("i", $pet_id);
$commentsStmt->execute();
$comments = $commentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch replies for each comment - UPDATED TO INCLUDE profile_pic
function getReplies($conn, $comment_id) {
    $repliesSql = "SELECT c.*, u.username, u.id as commenter_id, u.profile_pic 
                   FROM pet_comments c
                   JOIN users u ON c.user_id = u.id
                   WHERE c.parent_id = ?
                   ORDER BY c.created_at ASC";
    $repliesStmt = $conn->prepare($repliesSql);
    $repliesStmt->bind_param("i", $comment_id);
    $repliesStmt->execute();
    return $repliesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Helper function to get profile picture path
function getProfilePicturePath($profile_pic) {
    if (empty($profile_pic)) {
        return "../uploads/profile_pics/default.jpg";
    }
    
    $serverPath = "../uploads/" . $profile_pic;
    if (file_exists($serverPath)) {
        return "../uploads/" . $profile_pic;
    }
    
    return "../uploads/profile_pics/default.jpg";
}

// Count total comments including replies
$totalCommentsSql = "SELECT COUNT(*) as total FROM pet_comments WHERE pet_id = ?";
$totalStmt = $conn->prepare($totalCommentsSql);
$totalStmt->bind_param("i", $pet_id);
$totalStmt->execute();
$totalCommentsResult = $totalStmt->get_result()->fetch_assoc();
$totalComments = $totalCommentsResult['total'];

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

/* Health Information Section */
.health-info-section {
    background: #e8f5e9;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid #4caf50;
}

.health-info-title {
    color: #2d3748;
    font-size: 18px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.health-info-grid {
    display: grid;
    gap: 12px;
}

.health-info-item {
    display: flex;
    padding: 12px;
    background: white;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.health-info-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.health-info-item.full-width {
    flex-direction: column;
    gap: 8px;
}

.health-info-label {
    font-weight: 600;
    color: #4a5568;
    min-width: 150px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.health-info-value {
    color: #2d3748;
    flex: 1;
    line-height: 1.6;
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

.seller-profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    overflow: hidden;
    position: relative;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.seller-profile-pic img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
}

.seller-profile-pic .seller-avatar-text {
    position: relative;
    z-index: 1;
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
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: #fff;
}

.btn-cart:hover {
    background: linear-gradient(135deg, #45a049 0%, #3d8b40 100%);
}

.btn-remove {
    background: linear-gradient(135deg, #f44336 0%, #da190b 100%);
    color: #fff;
}

.btn-remove:hover {
    background: linear-gradient(135deg, #da190b 0%, #c41206 100%);
}

.btn-buy {
    background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
    color: #fff;
}

.btn-buy:hover {
    background: linear-gradient(135deg, #F57C00 0%, #E65100 100%);
}

.btn-inquire {
    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
    color: #fff;
    grid-column: 1 / -1;
}

.btn-inquire:hover {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
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

/* Additional Address Display Styling */
.additional-address-box {
    background: #fff3e0;
    border: 2px solid #ffe0b2;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.additional-address-box strong {
    color: #e65100;
    font-size: 14px;
    font-weight: 600;
}

.additional-address-box span {
    color: #5a4438;
    font-size: 14px;
    padding-left: 8px;
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

/* Comments Section */
.comments-section {
    background: #f5ede0;
    padding: 24px;
    border-radius: 12px;
    margin-top: 24px;
}

.comments-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #d4c4b0;
}

.comments-header h3 {
    color: #2d3748;
    font-size: 20px;
    margin: 0;
}

.comments-count {
    background: #c9a882;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
}

.comment-form {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.comment-form textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e2d5c5;
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    min-height: 80px;
    transition: border-color 0.3s ease;
}

.comment-form textarea:focus {
    outline: none;
    border-color: #c9a882;
}

.comment-form button {
    margin-top: 12px;
    background: linear-gradient(135deg, #c9a882 0%, #b89968 100%);
    color: white;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.comment-form button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(201, 168, 130, 0.4);
}

.comments-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.comment-item {
    background: white;
    padding: 16px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.comment-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.comment-author {
    display: flex;
    align-items: center;
    gap: 8px;
}

.comment-author-icon {
    background: linear-gradient(135deg, #c9a882 0%, #b89968 100%);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    overflow: hidden;
    position: relative;
}

.comment-author-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
}

.comment-author-icon .avatar-text {
    position: relative;
    z-index: 1;
}

.comment-author-name {
    font-weight: 600;
    color: #2d3748;
}

.comment-date {
    font-size: 12px;
    color: #8b7a68;
}

.comment-text {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 8px;
}

.comment-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.reply-btn {
    background: #e8f5e9;
    color: #4caf50;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}

.reply-btn:hover {
    background: #c8e6c9;
}

.delete-comment-btn {
    background: #f0d5d5;
    color: #c53030;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.delete-comment-btn:hover {
    background: #ffc9c9;
}

.no-comments {
    text-align: center;
    padding: 40px;
    color: #8b7a68;
    font-style: italic;
}

.login-prompt {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.login-prompt p {
    color: #4a5568;
    margin-bottom: 12px;
}

.login-prompt a {
    display: inline-block;
    background: linear-gradient(135deg, #c9a882 0%, #b89968 100%);
    color: white;
    padding: 10px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.login-prompt a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(201, 168, 130, 0.4);
}

/* Reply Form Styles */
.reply-form button {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.reply-submit-btn {
    background: linear-gradient(135deg, #c9a882 0%, #b89968 100%);
    color: white;
}

.reply-submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(201, 168, 130, 0.4);
}

.reply-cancel-btn {
    background: #e2d5c5;
    color: #5a4438;
}

.reply-cancel-btn:hover {
    background: #d4c4b0;
}

/* Replies Container */
.replies-container {
    margin-top: 12px;
    margin-left: 40px;
    border-left: 2px solid #e2d5c5;
    padding-left: 16px;
}

.reply-item {
    background: #f9f9f9;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.reply-item .comment-author-icon {
    width: 28px;
    height: 28px;
    font-size: 12px;
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

    .comments-section {
        padding: 16px;
    }

    .replies-container {
        margin-left: 20px;
        padding-left: 12px;
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

            <!-- Comments Section -->
            <div class="comments-section">
                <div class="comments-header">
                    <h3>💬 Comments</h3>
                    <span class="comments-count"><?= $totalComments ?></span>
                </div>

                <?php if ($current_user_id): ?>
                    <div class="comment-form">
                        <form method="POST">
                            <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                            <textarea name="comment_text" placeholder="Share your thoughts about <?= htmlspecialchars($pet['name']); ?>..." required></textarea>
                            <button type="submit" name="submit_comment">Post Comment</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="login-prompt">
                        <p>Please login to leave a comment</p>
                        <a href="login.php">Login</a>
                    </div>
                <?php endif; ?>

                <div class="comments-list">
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                            <?php 
                            $commentProfilePic = getProfilePicturePath($comment['profile_pic']);
                            $hasCommentPic = !empty($comment['profile_pic']) && file_exists("../uploads/" . $comment['profile_pic']);
                            ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <div class="comment-author-icon">
                                            <?php if ($hasCommentPic): ?>
                                                <img src="<?= htmlspecialchars($commentProfilePic); ?>" 
                                                     alt="<?= htmlspecialchars($comment['username']); ?>"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="avatar-text" style="display: none;">
                                                    <?= strtoupper(substr($comment['username'], 0, 1)) ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="avatar-text">
                                                    <?= strtoupper(substr($comment['username'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="comment-author-name"><?= htmlspecialchars($comment['username']); ?></span>
                                    </div>
                                    <span class="comment-date"><?= date('M d, Y', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <p class="comment-text"><?= nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                
                                <div class="comment-actions">
                                    <?php if ($current_user_id): ?>
                                        <button class="reply-btn" onclick="toggleReplyForm(<?= $comment['id'] ?>)">
                                            💬 Reply
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($current_user_id == $comment['commenter_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                                            <input type="hidden" name="comment_id" value="<?= $comment['id']; ?>">
                                            <button type="submit" name="delete_comment" class="delete-comment-btn" onclick="return confirm('Delete this comment?')">🗑️ Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <!-- Reply Form -->
                                <?php if ($current_user_id): ?>
                                <div class="reply-form" id="reply-form-<?= $comment['id'] ?>">
                                    <form method="POST">
                                        <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                                        <input type="hidden" name="parent_id" value="<?= $comment['id']; ?>">
                                        <textarea name="comment_text" placeholder="Write your reply..." required></textarea>
                                        <div class="reply-form-actions">
                                            <button type="submit" name="submit_comment" class="reply-submit-btn">Post Reply</button>
                                            <button type="button" class="reply-cancel-btn" onclick="toggleReplyForm(<?= $comment['id'] ?>)">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>

                                <!-- Display Replies -->
                                <?php 
                                $replies = getReplies($conn, $comment['id']);
                                if (count($replies) > 0): 
                                ?>
                                <div class="replies-container">
                                    <?php foreach ($replies as $reply): ?>
                                        <?php 
                                        $replyProfilePic = getProfilePicturePath($reply['profile_pic']);
                                        $hasReplyPic = !empty($reply['profile_pic']) && file_exists("../uploads/" . $reply['profile_pic']);
                                        ?>
                                        <div class="reply-item">
                                            <div class="comment-header">
                                                <div class="comment-author">
                                                    <div class="comment-author-icon">
                                                        <?php if ($hasReplyPic): ?>
                                                            <img src="<?= htmlspecialchars($replyProfilePic); ?>" 
                                                                 alt="<?= htmlspecialchars($reply['username']); ?>"
                                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                            <div class="avatar-text" style="display: none;">
                                                                <?= strtoupper(substr($reply['username'], 0, 1)) ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="avatar-text">
                                                                <?= strtoupper(substr($reply['username'], 0, 1)) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <span class="comment-author-name"><?= htmlspecialchars($reply['username']); ?></span>
                                                </div>
                                                <span class="comment-date"><?= date('M d, Y', strtotime($reply['created_at'])); ?></span>
                                            </div>
                                            <p class="comment-text"><?= nl2br(htmlspecialchars($reply['comment_text'])); ?></p>
                                            
                                            <?php if ($current_user_id == $reply['commenter_id']): ?>
                                                <div class="comment-actions">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="pet_id" value="<?= $pet['id']; ?>">
                                                        <input type="hidden" name="comment_id" value="<?= $reply['id']; ?>">
                                                        <button type="submit" name="delete_comment" class="delete-comment-btn" onclick="return confirm('Delete this reply?')">🗑️ Delete</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-comments">
                            No comments yet. Be the first to comment!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Pet Information -->
        <div class="pet-info">
            <div class="pet-header">
                <h1><?= htmlspecialchars($pet['name']); ?></h1>
                <div class="price-tag">₱<?= number_format($pet['price'], 2); ?></div>
            </div>

            <div class="description-box">
                <h3>About <?= htmlspecialchars($pet['name']); ?></h3>
                <p><?= nl2br(htmlspecialchars($pet['description'])); ?></p>
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

            <!-- Health Information Section -->
            <div class="health-info-section">
                <h3 class="health-info-title">🏥 Health Information</h3>
                <div class="health-info-grid">
                    <div class="health-info-item">
                        <span class="health-info-label">💉 Vaccinated:</span>
                        <span class="health-info-value">
                            <?php if ($pet['vaccinated'] == 1): ?>
                                <span style="color: #4caf50; font-weight: 600;">✓ Yes</span>
                            <?php else: ?>
                                <span style="color: #999;">✗ No</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="health-info-item">
                        <span class="health-info-label">🏥 Neutered/Spayed:</span>
                        <span class="health-info-value">
                            <?php if ($pet['neutered'] == 1): ?>
                                <span style="color: #4caf50; font-weight: 600;">✓ Yes</span>
                            <?php else: ?>
                                <span style="color: #999;">✗ No</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!empty($pet['health_status'])): ?>
                    <div class="health-info-item full-width">
                        <span class="health-info-label">🩺 Health Status:</span>
                        <span class="health-info-value"><?= nl2br(htmlspecialchars($pet['health_status'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="seller-info">
                <?php 
                $sellerProfilePic = getProfilePicturePath($pet['seller_profile_pic']);
                $hasSellerPic = !empty($pet['seller_profile_pic']) && file_exists("../uploads/" . $pet['seller_profile_pic']);
                ?>
                <div class="seller-profile-pic">
                    <?php if ($hasSellerPic): ?>
                        <img src="<?= htmlspecialchars($sellerProfilePic); ?>" 
                             alt="<?= htmlspecialchars($pet['username']); ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="seller-avatar-text" style="display: none;">
                            <?= strtoupper(substr($pet['username'], 0, 1)) ?>
                        </div>
                    <?php else: ?>
                        <div class="seller-avatar-text">
                            <?= strtoupper(substr($pet['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
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
                
                <!-- Additional Address Information Display -->
                <?php if (!empty($pet['address'])): ?>
                    <div class="additional-address-box">
                        <strong>📌 Additional Location Info:</strong>
                        <span><?php echo htmlspecialchars($pet['address']); ?></span>
                    </div>
                <?php endif; ?>
                
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

// Toggle reply form
function toggleReplyForm(commentId) {
    const replyForm = document.getElementById('reply-form-' + commentId);
    if (replyForm.classList.contains('active')) {
        replyForm.classList.remove('active');
    } else {
        // Hide all other reply forms
        document.querySelectorAll('.reply-form').forEach(form => {
            form.classList.remove('active');
        });
        // Show this reply form
        replyForm.classList.add('active');
        // Focus on textarea
        replyForm.querySelector('textarea').focus();
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

    // Create popup content with additional address if available
    let popupContent = "<b><?= htmlspecialchars($pet['name']); ?></b><br>Pet Location";
    
    <?php if (!empty($pet['address'])): ?>
        popupContent += "<br><br><strong>Additional Info:</strong><br><?= htmlspecialchars($pet['address']); ?>";
    <?php endif; ?>

    L.marker([lat, lon]).addTo(map)
        .bindPopup(popupContent)
        .openPopup();
});
</script>
<?php endif; ?>

<?php include_once "../includes/footer.php"; ?> {
