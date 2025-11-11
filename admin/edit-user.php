<?php
session_start();
require_once "../config/db.php";

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

// Get user_id from URL
if (!isset($_GET['id'])) {
    header("Location: users.php?error=invalid");
    exit;
}

$user_id = intval($_GET['id']);

// Fetch user info
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: users.php?error=invalid");
    exit;
}

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $verification_status = $_POST['verification_status'];
    
    // Validate inputs
    if (empty($username) || empty($email)) {
        $error = 'Username and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if username/email already exists for other users
        $checkSql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ssi", $username, $email, $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Update user
            $updateSql = "UPDATE users SET username = ?, email = ?, role = ?, verification_status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssssi", $username, $email, $role, $verification_status, $user_id);
            
            if ($updateStmt->execute()) {
                $success = 'User updated successfully!';
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = 'Failed to update user.';
            }
        }
    }
}

include __DIR__ . "/../includes/admin-sidebar.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
</head>
<body>

<style>
body {
    background: #faf8f5;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.edit-user-page {
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
    background: rgba(212, 165, 116, 0.15);
    color: #C19A6B;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.back-btn:hover {
    background: rgba(212, 165, 116, 0.25);
    border-color: #F5E6D3;
}

/* Alert Messages */
.alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

/* User Info Card */
.user-info-card {
    background: #faf8f5;
    padding: 20px;
    border-radius: 12px;
    border: 1px solid #F5E6D3;
    margin-bottom: 24px;
}

.user-info-card h3 {
    margin: 0 0 12px 0;
    color: #5C4A3A;
    font-size: 16px;
    font-weight: 600;
}

.user-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    font-size: 14px;
}

.user-info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-weight: 600;
    color: #8B7355;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: #5C4A3A;
}

/* Form Styles */
.edit-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #5C4A3A;
    font-size: 14px;
}

.form-group input,
.form-group select {
    padding: 12px 16px;
    border: 2px solid #F5E6D3;
    border-radius: 10px;
    font-size: 14px;
    color: #5C4A3A;
    background: #fff;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #D4A574;
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
}

.form-group input:disabled {
    background: #faf8f5;
    color: #8B7355;
    cursor: not-allowed;
}

.form-help-text {
    font-size: 12px;
    color: #8B7355;
    font-style: italic;
}

/* Role Badge */
.role-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
}

.role-badge.admin {
    background: linear-gradient(135deg, #D4A574, #C19A6B);
}

.role-badge.customer {
    background: linear-gradient(135deg, #8B7355, #6B5949);
}

/* Verification Badge */
.verification-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
}

.verification-badge.verified {
    background: linear-gradient(135deg, #28a745, #20883b);
}

.verification-badge.pending {
    background: linear-gradient(135deg, #ffc107, #f0ad00);
    color: #000;
}

.verification-badge.not-verified {
    background: linear-gradient(135deg, #8B7355, #6B5949);
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.btn-submit,
.btn-cancel {
    flex: 1;
    padding: 14px 20px;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-submit {
    background: linear-gradient(135deg, #D4A574, #C19A6B);
    color: white;
    box-shadow: 0 4px 12px rgba(212, 165, 116, 0.3);
}

.btn-submit:hover {
    background: linear-gradient(135deg, #C19A6B, #A67C52);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(212, 165, 116, 0.4);
}

.btn-cancel {
    background: rgba(139, 115, 85, 0.15);
    color: #8B7355;
}

.btn-cancel:hover {
    background: rgba(139, 115, 85, 0.25);
}

/* Responsive Design */
@media (max-width: 768px) {
    .edit-user-page {
        margin-left: 0;
        width: 100%;
        padding: 20px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .user-info-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }
}
</style>

<div class="edit-user-page">
    <div class="content-wrapper">
        <div class="page-header">
            <h2>✏️ Edit User</h2>
            <a href="users.php" class="back-btn">← Back to Users</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                ✗ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Current User Info -->
        <div class="user-info-card">
            <h3>Current User Information</h3>
            <div class="user-info-grid">
                <div class="user-info-item">
                    <span class="info-label">User ID</span>
                    <span class="info-value">#<?= $user['id'] ?></span>
                </div>
                <div class="user-info-item">
                    <span class="info-label">Current Role</span>
                    <span class="info-value">
                        <span class="role-badge <?= $user['role'] ?>">
                            <?= $user['role'] === 'admin' ? '👑 Admin' : '👤 Customer' ?>
                        </span>
                    </span>
                </div>
                <div class="user-info-item">
                    <span class="info-label">Verification Status</span>
                    <span class="info-value">
                        <span class="verification-badge <?= str_replace(' ', '-', strtolower($user['verification_status'])) ?>">
                            <?= ucfirst($user['verification_status']) ?>
                        </span>
                    </span>
                </div>
                <div class="user-info-item">
                    <span class="info-label">Date Registered</span>
                    <span class="info-value"><?= date('M d, Y g:i A', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <form method="POST" class="edit-form">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?= htmlspecialchars($user['username']) ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?= htmlspecialchars($user['email']) ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="role">User Role *</label>
                <select id="role" name="role" required>
                    <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>
                        Customer
                    </option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>
                        Admin
                    </option>
                </select>
                <span class="form-help-text">⚠️ Be careful when changing user roles</span>
            </div>

            <div class="form-group">
                <label for="verification_status">Verification Status *</label>
                <select id="verification_status" name="verification_status" required>
                    <option value="not verified" <?= $user['verification_status'] === 'not verified' ? 'selected' : '' ?>>
                        Not Verified
                    </option>
                    <option value="pending" <?= $user['verification_status'] === 'pending' ? 'selected' : '' ?>>
                        Pending
                    </option>
                    <option value="verified" <?= $user['verification_status'] === 'verified' ? 'selected' : '' ?>>
                        Verified
                    </option>
                </select>
                <span class="form-help-text">This determines if the user can make purchases</span>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="text" value="••••••••" disabled>
                <span class="form-help-text">Password cannot be changed from this form</span>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">💾 Save Changes</button>
                <a href="users.php" class="btn-cancel" style="text-decoration: none; text-align: center; display: flex; align-items: center; justify-content: center;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>