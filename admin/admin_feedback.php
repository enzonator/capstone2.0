<?php
session_start();
include '../config/db.php';

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

// Get filter and sort options
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$query = "
    SELECT f.*, u.username, u.email 
    FROM feedback f 
    JOIN users u ON f.user_id = u.id
";

if ($filter !== 'all') {
    $query .= " WHERE f.status = ?";
}

switch($sort) {
    case 'oldest':
        $query .= " ORDER BY f.created_at ASC";
        break;
    case 'highest':
        $query .= " ORDER BY f.rating DESC, f.created_at DESC";
        break;
    case 'lowest':
        $query .= " ORDER BY f.rating ASC, f.created_at DESC";
        break;
    default:
        $query .= " ORDER BY f.created_at DESC";
}

$stmt = $conn->prepare($query);
if ($filter !== 'all') {
    $stmt->bind_param('s', $filter);
}
$stmt->execute();
$result = $stmt->get_result();
$feedbacks = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$statsResult = $conn->query("
    SELECT 
        COUNT(*) as total,
        AVG(rating) as avg_rating,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'reviewed' THEN 1 END) as reviewed
    FROM feedback
");
$stats = $statsResult->fetch_assoc();

include __DIR__ . "/../includes/admin-sidebar.php"; // Sidebar
?>

<!-- Feedback Content -->
<div class="feedback-page">
  <div class="content-wrapper">
    <div class="page-header">
      <h2>🐾 Feedback Management</h2>
      <p class="subtitle">Manage and review customer feedback</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total Feedback</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?>★</div>
        <div class="stat-label">Average Rating</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['pending']; ?></div>
        <div class="stat-label">Pending Review</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['reviewed']; ?></div>
        <div class="stat-label">Reviewed</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-container">
      <div class="filter-group">
        <label>Filter:</label>
        <select id="filterSelect" onchange="updateFilters()">
          <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Feedback</option>
          <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="reviewed" <?php echo $filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
          <option value="archived" <?php echo $filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Sort by:</label>
        <select id="sortSelect" onchange="updateFilters()">
          <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
          <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
          <option value="highest" <?php echo $sort === 'highest' ? 'selected' : ''; ?>>Highest Rating</option>
          <option value="lowest" <?php echo $sort === 'lowest' ? 'selected' : ''; ?>>Lowest Rating</option>
        </select>
      </div>
    </div>

    <!-- Feedback Grid -->
    <div class="feedback-grid" id="feedbackGrid">
      <?php if (empty($feedbacks)): ?>
        <div class="empty-state">
          <span class="empty-icon">💬</span>
          <p>No feedback found</p>
          <span class="empty-subtitle">There are no feedback entries matching your filters.</span>
        </div>
      <?php else: ?>
        <?php foreach ($feedbacks as $feedback): ?>
          <div class="feedback-card">
            <div class="feedback-header">
              <div class="user-info">
                <div class="username"><?php echo htmlspecialchars($feedback['username']); ?></div>
                <div class="email"><?php echo htmlspecialchars($feedback['email']); ?></div>
              </div>
              <div class="rating-display">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <span class="<?php echo $i <= $feedback['rating'] ? 'star-filled' : 'star-empty'; ?>">★</span>
                <?php endfor; ?>
              </div>
            </div>

            <div class="feedback-message">
              <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
            </div>

            <div class="feedback-footer">
              <div class="date-text">
                <?php echo date('M d, Y, g:i a', strtotime($feedback['created_at'])); ?>
              </div>
              <div class="actions-group">
                <span class="status-badge status-<?php echo $feedback['status']; ?>">
                  <?php 
                    $statusIcon = $feedback['status'] === 'pending' ? '⏳' : 
                                 ($feedback['status'] === 'reviewed' ? '✓' : '📦');
                    echo $statusIcon . ' ' . ucfirst($feedback['status']);
                  ?>
                </span>
                <?php if ($feedback['status'] === 'pending'): ?>
                  <button class="btn-action btn-review" onclick="updateStatus(<?php echo $feedback['id']; ?>, 'reviewed')">
                    ✓ Mark Reviewed
                  </button>
                <?php endif; ?>
                <?php if ($feedback['status'] !== 'archived'): ?>
                  <button class="btn-action btn-archive" onclick="updateStatus(<?php echo $feedback['id']; ?>, 'archived')">
                    📦 Archive
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Popup Notification -->
<div id="notification-popup" class="notification-popup">
  <div class="notification-content">
    <span class="notification-icon"></span>
    <span class="notification-message"></span>
  </div>
  <button class="notification-close">&times;</button>
</div>

<!-- Scoped CSS for Feedback Page -->
<style>
  body {
    background: #faf8f5;
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .feedback-page {
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

  /* Popup Notification Styles */
  .notification-popup {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #fff;
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 320px;
    max-width: 450px;
    transform: translateX(500px);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 10000;
    border-left: 4px solid;
  }

  .notification-popup.show {
    transform: translateX(0);
    opacity: 1;
  }

  .notification-popup.success {
    border-left-color: #28a745;
    background: linear-gradient(135deg, #ffffff 0%, #f1f9f3 100%);
  }

  .notification-popup.warning {
    border-left-color: #ffc107;
    background: linear-gradient(135deg, #ffffff 0%, #fff9e6 100%);
  }

  .notification-popup.error {
    border-left-color: #dc3545;
    background: linear-gradient(135deg, #ffffff 0%, #fff0f1 100%);
  }

  .notification-content {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
  }

  .notification-icon {
    font-size: 24px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .notification-popup.success .notification-icon {
    background: rgba(40, 167, 69, 0.15);
    color: #28a745;
  }

  .notification-popup.warning .notification-icon {
    background: rgba(255, 193, 7, 0.15);
    color: #d39e00;
  }

  .notification-popup.error .notification-icon {
    background: rgba(220, 53, 69, 0.15);
    color: #dc3545;
  }

  .notification-message {
    color: #5C4A3A;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.4;
  }

  .notification-close {
    background: transparent;
    border: none;
    font-size: 24px;
    color: #8B7355;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
    flex-shrink: 0;
  }

  .notification-close:hover {
    background: rgba(139, 115, 85, 0.1);
    color: #5C4A3A;
  }

  /* Statistics Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
  }

  .stat-card {
    background: linear-gradient(135deg, #5C4A3A 0%, #4a3a2a 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(92, 74, 58, 0.2);
  }

  .stat-value {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 8px;
  }

  .stat-label {
    font-size: 13px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  /* Filters */
  .filters-container {
    background: rgba(245, 230, 211, 0.3);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
    border: 1px solid #F5E6D3;
  }

  .filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .filter-group label {
    font-weight: 600;
    color: #5C4A3A;
    font-size: 14px;
  }

  .filter-group select {
    padding: 10px 14px;
    border: 2px solid #F5E6D3;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    background: white;
    color: #5C4A3A;
    font-weight: 500;
    transition: all 0.3s ease;
  }

  .filter-group select:hover {
    border-color: #D4A574;
  }

  .filter-group select:focus {
    outline: none;
    border-color: #C19A6B;
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.1);
  }

  /* Feedback Grid */
  .feedback-grid {
    display: grid;
    gap: 20px;
  }

  .feedback-card {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    border: 1px solid #F5E6D3;
    transition: all 0.3s ease;
  }

  .feedback-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(92, 74, 58, 0.12);
    border-color: #D4A574;
  }

  .feedback-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
  }

  .user-info {
    flex: 1;
  }

  .username {
    font-weight: 600;
    font-size: 16px;
    color: #5C4A3A;
    margin-bottom: 4px;
  }

  .email {
    color: #8B7355;
    font-size: 13px;
  }

  .rating-display {
    display: flex;
    gap: 2px;
    font-size: 18px;
  }

  .star-filled {
    color: #FFD700;
  }

  .star-empty {
    color: #E0E0E0;
  }

  .feedback-message {
    background: rgba(245, 230, 211, 0.2);
    padding: 16px;
    border-radius: 10px;
    margin: 16px 0;
    line-height: 1.6;
    color: #5C4A3A;
    font-size: 14px;
    border-left: 3px solid #D4A574;
  }

  .feedback-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #F5E6D3;
    flex-wrap: wrap;
    gap: 12px;
  }

  .date-text {
    color: #8B7355;
    font-size: 13px;
  }

  .actions-group {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
  }

  .status-badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  }

  .status-pending {
    background: linear-gradient(135deg, #ffc107, #f0ad00);
    color: #000;
  }

  .status-reviewed {
    background: linear-gradient(135deg, #28a745, #20883b);
    color: #fff;
  }

  .status-archived {
    background: linear-gradient(135deg, #8B7355, #6B5949);
    color: #fff;
  }

  .btn-action {
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  .btn-review {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
  }

  .btn-review:hover {
    background: rgba(40, 167, 69, 0.2);
    transform: translateY(-1px);
  }

  .btn-archive {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
    border: 1px solid rgba(108, 117, 125, 0.3);
  }

  .btn-archive:hover {
    background: rgba(108, 117, 125, 0.2);
    transform: translateY(-1px);
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 80px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  .empty-icon {
    font-size: 64px;
    opacity: 0.3;
  }

  .empty-state p {
    color: #5C4A3A;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
  }

  .empty-subtitle {
    color: #8B7355;
    font-size: 14px;
  }

  /* Responsive Design */
  @media (max-width: 768px) {
    .feedback-page {
      margin-left: 0;
      width: 100%;
      padding: 20px;
    }

    .stats-grid {
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 12px;
    }

    .stat-card {
      padding: 16px;
    }

    .stat-value {
      font-size: 24px;
    }

    .filters-container {
      flex-direction: column;
      align-items: stretch;
    }

    .filter-group {
      flex-direction: column;
      align-items: stretch;
    }

    .filter-group select {
      width: 100%;
    }

    .feedback-header {
      flex-direction: column;
    }

    .feedback-footer {
      flex-direction: column;
      align-items: stretch;
    }

    .actions-group {
      justify-content: flex-start;
    }

    .notification-popup {
      right: 10px;
      left: 10px;
      min-width: unset;
      max-width: unset;
    }
  }
</style>

<script>
  // Check for status parameter on page load
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    
    if (status) {
      showNotification(status);
      
      // Remove status from URL without page reload
      const url = new URL(window.location);
      url.searchParams.delete('status');
      window.history.replaceState({}, '', url);
    }
  });

  function updateFilters() {
    const filter = document.getElementById('filterSelect').value;
    const sort = document.getElementById('sortSelect').value;
    window.location.href = `admin_feedback.php?filter=${filter}&sort=${sort}`;
  }

  function updateStatus(feedbackId, status) {
    const confirmMessage = status === 'reviewed' 
      ? 'Are you sure you want to mark this feedback as reviewed?' 
      : 'Are you sure you want to archive this feedback?';
      
    if (confirm(confirmMessage)) {
      fetch('update_feedback_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `feedback_id=${feedbackId}&status=${status}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Redirect with status parameter
          const currentUrl = new URL(window.location);
          const filter = currentUrl.searchParams.get('filter') || 'all';
          const sort = currentUrl.searchParams.get('sort') || 'newest';
          window.location.href = `admin_feedback.php?filter=${filter}&sort=${sort}&status=${status}`;
        } else {
          showNotification('error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('error');
      });
    }
  }

  function showNotification(status) {
    const popup = document.getElementById('notification-popup');
    const icon = popup.querySelector('.notification-icon');
    const message = popup.querySelector('.notification-message');
    
    // Reset classes
    popup.className = 'notification-popup';
    
    // Set content based on status
    if (status === 'reviewed') {
      popup.classList.add('success');
      icon.textContent = '✓';
      message.textContent = 'Feedback marked as reviewed successfully!';
    } else if (status === 'archived') {
      popup.classList.add('warning');
      icon.textContent = '📦';
      message.textContent = 'Feedback archived successfully!';
    } else if (status === 'error') {
      popup.classList.add('error');
      icon.textContent = '⚠';
      message.textContent = 'Failed to update feedback status. Please try again.';
    }
    
    // Show popup
    popup.classList.add('show');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
      hideNotification();
    }, 5000);
  }

  function hideNotification() {
    const popup = document.getElementById('notification-popup');
    popup.classList.remove('show');
  }

  // Close button functionality
  document.querySelector('.notification-close').addEventListener('click', hideNotification);
</script>