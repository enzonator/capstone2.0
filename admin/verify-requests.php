<?php
session_start();
require_once "../config/db.php";

// ✅ Restrict access to admin only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit;
}

include __DIR__ . "/../includes/admin-sidebar.php"; // Sidebar
?>

<!-- Verification Requests Page -->
<div class="verify-page">
  <div class="content-wrapper">
    <div class="page-header">
      <h2>🧾 Verification Requests</h2>
      <p class="subtitle">Review and manage user verification submissions</p>
    </div>

    <div class="table-container">
      <table class="verify-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>ID Image</th>
            <th>Request Status</th>
            <th>User Status</th>
            <th>Date Submitted</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="verificationsTableBody">
          <?php
          $sql = "SELECT v.id, v.user_id, v.id_image, v.status, v.created_at, 
                         u.username, u.email, u.verification_status
                  FROM verifications v
                  JOIN users u ON v.user_id = u.id
                  ORDER BY v.created_at DESC";
          $result = $conn->query($sql);

          if ($result && $result->num_rows > 0):
              while ($row = $result->fetch_assoc()):
          ?>
            <tr>
              <td><span class="id-badge">#<?= $row['id'] ?></span></td>
              <td>
                <div class="user-info">
                  <strong class="username"><?= htmlspecialchars($row['username']) ?></strong>
                  <span class="email-text"><?= htmlspecialchars($row['email']) ?></span>
                </div>
              </td>
              <td>
                <?php if (!empty($row['id_image'])): ?>
                  <a href="../uploads/verifications/<?= htmlspecialchars($row['id_image']) ?>" 
                     target="_blank" 
                     class="file-link">
                     📎 View Document
                  </a>
                <?php else: ?>
                  <span class="no-file">No file</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="status-badge <?= strtolower($row['status']) ?>">
                  <?php 
                    $statusIcon = $row['status'] === 'Pending' ? '⏳' : 
                                 ($row['status'] === 'Approved' ? '✓' : '✗');
                    echo $statusIcon . ' ' . htmlspecialchars($row['status']);
                  ?>
                </span>
              </td>
              <td>
                <span class="user-status-badge <?= str_replace(' ', '-', strtolower($row['verification_status'])) ?>">
                  <?php
                    $userIcon = $row['verification_status'] === 'verified' ? '✓' :
                               ($row['verification_status'] === 'pending' ? '⏳' : '○');
                    echo $userIcon . ' ' . htmlspecialchars(ucfirst($row['verification_status']));
                  ?>
                </span>
              </td>
              <td><span class="date-text"><?= date('M d, Y g:i A', strtotime($row['created_at'])) ?></span></td>
              <td>
                <?php if ($row['status'] === 'Pending'): ?>
                  <div class="actions">
                    <a href="verify-action.php?id=<?= $row['id'] ?>&action=approve" 
                       class="btn-approve"
                       onclick="return confirm('Approve this verification request?');"
                       title="Approve Request">
                       ✓
                    </a>
                    <a href="verify-action.php?id=<?= $row['id'] ?>&action=reject" 
                       class="btn-reject"
                       onclick="return confirm('Reject this verification request?');"
                       title="Reject Request">
                       ✗
                    </a>
                  </div>
                <?php else: ?>
                  <span class="processed-text">Processed</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php
              endwhile;
          else:
          ?>
            <tr>
              <td colspan='7' class="empty">
                <div class="empty-state">
                  <span class="empty-icon">🧾</span>
                  <p>No verification requests found</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
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

<!-- JavaScript for Popup and Real-time Updates -->
<script>
let lastRequestCount = 0;

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

  // Initialize request count
  const tbody = document.getElementById('verificationsTableBody');
  lastRequestCount = tbody.querySelectorAll('tr').length;

  // Start fetching verification requests
  fetchVerificationRequests();
  setInterval(fetchVerificationRequests, 5000); // Check every 5 seconds
});

function fetchVerificationRequests() {
  fetch('fetch-verification-requests.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        updateVerificationTable(data.requests);
      }
    })
    .catch(error => {
      console.error('Error fetching verification requests:', error);
    });
}

function updateVerificationTable(requests) {
  const tbody = document.getElementById('verificationsTableBody');
  
  // Only update if the count changed
  if (requests.length !== lastRequestCount) {
    tbody.innerHTML = '';
    
    if (requests.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan='7' class="empty">
            <div class="empty-state">
              <span class="empty-icon">🧾</span>
              <p>No verification requests found</p>
            </div>
          </td>
        </tr>
      `;
    } else {
      requests.forEach(req => {
        const row = document.createElement('tr');
        
        // Determine status icon
        let statusIcon = req.status === 'Pending' ? '⏳' : 
                        (req.status === 'Approved' ? '✓' : '✗');
        
        // Determine user status icon
        let userIcon = req.verification_status === 'verified' ? '✓' :
                      (req.verification_status === 'pending' ? '⏳' : '○');
        
        // Format date
        const date = new Date(req.created_at);
        const formattedDate = date.toLocaleDateString('en-US', { 
          month: 'short', 
          day: 'numeric', 
          year: 'numeric' 
        }) + ' ' + date.toLocaleTimeString('en-US', { 
          hour: 'numeric', 
          minute: '2-digit', 
          hour12: true 
        });
        
        // Build ID image cell
        let idImageCell = '';
        if (req.id_image) {
          idImageCell = `<a href="../uploads/verifications/${escapeHtml(req.id_image)}" 
                            target="_blank" 
                            class="file-link">
                            📎 View Document
                         </a>`;
        } else {
          idImageCell = '<span class="no-file">No file</span>';
        }
        
        // Build action cell
        let actionCell = '';
        if (req.status === 'Pending') {
          actionCell = `
            <div class="actions">
              <a href="verify-action.php?id=${req.id}&action=approve" 
                 class="btn-approve"
                 onclick="return confirm('Approve this verification request?');"
                 title="Approve Request">
                 ✓
              </a>
              <a href="verify-action.php?id=${req.id}&action=reject" 
                 class="btn-reject"
                 onclick="return confirm('Reject this verification request?');"
                 title="Reject Request">
                 ✗
              </a>
            </div>
          `;
        } else {
          actionCell = '<span class="processed-text">Processed</span>';
        }
        
        row.innerHTML = `
          <td><span class="id-badge">#${req.id}</span></td>
          <td>
            <div class="user-info">
              <strong class="username">${escapeHtml(req.username)}</strong>
              <span class="email-text">${escapeHtml(req.email)}</span>
            </div>
          </td>
          <td>${idImageCell}</td>
          <td>
            <span class="status-badge ${req.status.toLowerCase()}">
              ${statusIcon} ${escapeHtml(req.status)}
            </span>
          </td>
          <td>
            <span class="user-status-badge ${req.verification_status.replace(' ', '-').toLowerCase()}">
              ${userIcon} ${escapeHtml(req.verification_status.charAt(0).toUpperCase() + req.verification_status.slice(1))}
            </span>
          </td>
          <td><span class="date-text">${formattedDate}</span></td>
          <td>${actionCell}</td>
        `;
        
        tbody.appendChild(row);
      });
    }
    
    lastRequestCount = requests.length;
  }
}

function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

function showNotification(status) {
  const popup = document.getElementById('notification-popup');
  const icon = popup.querySelector('.notification-icon');
  const message = popup.querySelector('.notification-message');
  
  // Reset classes
  popup.className = 'notification-popup';
  
  // Set content based on status
  if (status === 'approved') {
    popup.classList.add('success');
    icon.textContent = '✓';
    message.textContent = 'Verification approved! User status changed to \'verified\'.';
  } else if (status === 'rejected') {
    popup.classList.add('warning');
    icon.textContent = '✗';
    message.textContent = 'Verification rejected. User status changed to \'not verified\'.';
  } else if (status === 'error') {
    popup.classList.add('error');
    icon.textContent = '⚠';
    message.textContent = 'Something went wrong.';
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

<!-- Scoped CSS -->
<style>
body {
  background: #faf8f5;
  margin: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.verify-page {
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

.table-container {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #F5E6D3;
}

.verify-table {
  width: 100%;
  border-collapse: collapse;
}

.verify-table thead {
  background: linear-gradient(135deg, #F5E6D3, #E8D5BA);
  color: #5C4A3A;
}

.verify-table th {
  padding: 16px;
  text-align: left;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
  border-bottom: 2px solid #D4A574;
}

.verify-table td {
  padding: 16px;
  border-bottom: 1px solid #F5E6D3;
  color: #5C4A3A;
  font-size: 14px;
}

.verify-table tbody tr {
  transition: all 0.2s ease;
}

.verify-table tbody tr:hover {
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

.user-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.username {
  color: #5C4A3A;
  font-weight: 600;
  font-size: 14px;
}

.email-text {
  color: #8B7355;
  font-size: 12px;
}

.file-link {
  color: #C19A6B;
  text-decoration: none;
  font-weight: 500;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: rgba(212, 165, 116, 0.1);
  border-radius: 8px;
  transition: all 0.3s ease;
}

.file-link:hover {
  background: rgba(212, 165, 116, 0.2);
  color: #A67C52;
}

.no-file {
  color: #8B7355;
  font-style: italic;
  font-size: 13px;
}

.status-badge, .user-status-badge {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.status-badge.pending { 
  background: linear-gradient(135deg, #ffc107, #f0ad00);
  color: #000;
}

.status-badge.approved { 
  background: linear-gradient(135deg, #28a745, #20883b);
}

.status-badge.rejected { 
  background: linear-gradient(135deg, #dc3545, #c82333);
}

.user-status-badge.not-verified { 
  background: linear-gradient(135deg, #8B7355, #6B5949);
}

.user-status-badge.pending { 
  background: linear-gradient(135deg, #ffc107, #f0ad00);
  color: #000;
}

.user-status-badge.verified { 
  background: linear-gradient(135deg, #28a745, #20883b);
}

.date-text {
  color: #8B7355;
  font-size: 13px;
}

.actions {
  display: flex;
  gap: 6px;
}

.btn-approve, .btn-reject {
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 16px;
  text-decoration: none;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-weight: 600;
}

.btn-approve {
  background: rgba(40, 167, 69, 0.15);
  color: #28a745;
}

.btn-approve:hover {
  background: rgba(40, 167, 69, 0.25);
  transform: translateY(-1px);
}

.btn-reject {
  background: rgba(220, 53, 69, 0.1);
  color: #dc3545;
}

.btn-reject:hover {
  background: rgba(220, 53, 69, 0.2);
  transform: translateY(-1px);
}

.processed-text {
  color: #8B7355;
  font-style: italic;
  font-size: 13px;
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
  .verify-page {
    margin-left: 0;
    width: 100%;
    padding: 20px;
  }
  
  .notification-popup {
    right: 10px;
    left: 10px;
    min-width: unset;
    max-width: unset;
  }
  
  .table-container {
    overflow-x: scroll;
  }
}
</style>