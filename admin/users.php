<?php
session_start();
include '../config/db.php';

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

include __DIR__ . "/../includes/admin-sidebar.php"; // Sidebar
?>

<!-- Alert Messages -->
<?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
  <div class="alert alert-success">✓ User deleted successfully.</div>
<?php elseif (isset($_GET['error'])): ?>
  <?php if ($_GET['error'] === 'invalid'): ?>
    <div class="alert alert-danger">✗ Invalid user ID.</div>
  <?php elseif ($_GET['error'] === 'cannot_delete_admin'): ?>
    <div class="alert alert-warning">⚠ You cannot delete the main admin.</div>
  <?php else: ?>
    <div class="alert alert-danger">✗ Failed to delete user.</div>
  <?php endif; ?>
<?php endif; ?>

<!-- Users Content -->
<div class="users-page">
  <div class="content-wrapper">
    <div class="page-header">
      <h2>👥 Manage Users</h2>
      <p class="subtitle">View and manage all registered users</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <div class="info-alert">
        <?php echo htmlspecialchars($_GET['msg']); ?>
      </div>
    <?php endif; ?>

    <div class="table-container">
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Date Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY id DESC");

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
        ?>
          <tr>
            <td><span class="id-badge">#<?php echo $row['id']; ?></span></td>
            <td><strong class="username"><?php echo htmlspecialchars($row['username']); ?></strong></td>
            <td><span class="email-text"><?php echo htmlspecialchars($row['email']); ?></span></td>
            <td>
              <span class="role-badge <?php echo ($row['role'] === 'admin') ? 'admin' : 'customer'; ?>">
                <?php echo $row['role'] === 'admin' ? '👑 Admin' : '👤 Customer'; ?>
              </span>
            </td>
            <td><span class="date-text"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
            <td>
              <div class="actions">
                <a href="edit-user.php?id=<?php echo $row['id']; ?>" class="btn-edit" title="Edit User">✏️</a>
                <a class="btn-delete"
                  href="delete-user.php?id=<?= (int)$row['id'] ?>"
                  onclick="return confirm('Are you sure you want to delete this user?');"
                  title="Delete User">
                  🗑️
                </a>
              </div>
            </td>
          </tr>
        <?php
            endwhile;
        else:
        ?>
          <tr>
            <td colspan='6' class="empty">
              <div class="empty-state">
                <span class="empty-icon">👥</span>
                <p>No users found</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Scoped CSS for Users Page -->
<style>
  body {
    background: #faf8f5;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .users-page {
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
  }

  .page-header h2 {
    margin: 0 0 8px 0;
    font-weight: 600;
    color: #5C4A3A;
    font-size: 28px;
    letter-spacing: -0.5px;
  }

  .subtitle {
    margin: 0;
    color: #8B7355;
    font-size: 14px;
  }

  /* Alert Styles */
  .alert, .info-alert {
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

  .alert-warning {
    background: rgba(255, 193, 7, 0.1);
    color: #d39e00;
    border: 1px solid rgba(255, 193, 7, 0.3);
  }

  .info-alert {
    background: rgba(212, 165, 116, 0.1);
    color: #8B7355;
    border: 1px solid rgba(212, 165, 116, 0.3);
  }

  .table-container {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid #F5E6D3;
  }

  .users-table {
    width: 100%;
    border-collapse: collapse;
  }

  .users-table thead {
    background: linear-gradient(135deg, #F5E6D3, #E8D5BA);
    color: #5C4A3A;
  }

  .users-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #D4A574;
  }

  .users-table td {
    padding: 16px;
    border-bottom: 1px solid #F5E6D3;
    color: #5C4A3A;
    font-size: 14px;
  }

  .users-table tbody tr {
    transition: all 0.2s ease;
  }

  .users-table tbody tr:hover {
    background: rgba(245, 230, 211, 0.3);
  }

  .id-badge {
    background: #F5E6D3;
    color: #5C4A3A;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }

  .username {
    color: #5C4A3A;
    font-weight: 600;
  }

  .email-text {
    color: #8B7355;
    font-size: 13px;
  }

  .role-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  }

  .role-badge.admin {
    background: linear-gradient(135deg, #D4A574, #C19A6B);
  }

  .role-badge.customer {
    background: linear-gradient(135deg, #8B7355, #6B5949);
  }

  .date-text {
    color: #8B7355;
    font-size: 13px;
  }

  .actions {
    display: flex;
    gap: 6px;
  }

  .btn-edit, .btn-delete {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .btn-edit {
    background: rgba(212, 165, 116, 0.15);
    color: #C19A6B;
  }

  .btn-edit:hover {
    background: rgba(212, 165, 116, 0.25);
    transform: translateY(-1px);
  }

  .btn-delete {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
  }

  .btn-delete:hover {
    background: rgba(220, 53, 69, 0.2);
    transform: translateY(-1px);
  }

  .empty {
    text-align: center;
    padding: 60px 20px;
  }

  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .empty-icon {
    font-size: 48px;
    opacity: 0.3;
  }

  .empty-state p {
    color: #8B7355;
    font-size: 16px;
    margin: 0;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .users-page {
      margin-left: 0;
      width: 100%;
      padding: 20px;
    }
  }
</style>