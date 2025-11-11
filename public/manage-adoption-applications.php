<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

// If cat_id is provided, show applications for that specific cat
if ($cat_id > 0) {
    // Verify cat ownership
    $cat_check_sql = "SELECT id, name, breed, age, gender, adoption_fee, image_url FROM adoption_cats WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($cat_check_sql);
    $stmt->bind_param("ii", $cat_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cat = $result->fetch_assoc();
    
    if (!$cat) {
        die("Cat not found or you don't have permission to view its applications.");
    }
    
    // Fetch applications for this specific cat
    $apps_sql = "SELECT aa.*, ac.name as cat_name, ac.image_url
                 FROM adoption_applications aa
                 INNER JOIN adoption_cats ac ON aa.cat_id = ac.id
                 WHERE aa.cat_id = ? AND ac.user_id = ?
                 ORDER BY aa.submitted_at DESC";
    $stmt = $conn->prepare($apps_sql);
    $stmt->bind_param("ii", $cat_id, $user_id);
} else {
    // Fetch all applications for all user's cats
    $apps_sql = "SELECT aa.*, ac.name as cat_name, ac.image_url, ac.id as cat_id
                 FROM adoption_applications aa
                 INNER JOIN adoption_cats ac ON aa.cat_id = ac.id
                 WHERE ac.user_id = ?
                 ORDER BY aa.submitted_at DESC";
    $stmt = $conn->prepare($apps_sql);
    $stmt->bind_param("i", $user_id);
    $cat = null;
}

$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_apps = count($applications);
$pending_apps = count(array_filter($applications, fn($a) => $a['status'] === 'Pending'));
$approved_apps = count(array_filter($applications, fn($a) => $a['status'] === 'Approved'));
$rejected_apps = count(array_filter($applications, fn($a) => $a['status'] === 'Rejected'));

include_once "../includes/header.php";
?>

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

    .page-header {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 20px;
        transition: color 0.3s;
    }

    .back-btn:hover {
        color: #495057;
    }

    .page-header h1 {
        color: #2c3e50;
        font-size: 2.5em;
        margin-bottom: 10px;
    }

    .page-header p {
        color: #7f8c8d;
        font-size: 1.1em;
    }

    /* Cat Info Card (when viewing specific cat) */
    .cat-info-card {
        background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        gap: 25px;
        box-shadow: 0 5px 20px rgba(234, 221, 202, 0.4);
    }

    .cat-image {
        width: 150px;
        height: 150px;
        border-radius: 12px;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .cat-info-content {
        flex: 1;
        color: #3d3020;
    }

    .cat-info-content h2 {
        font-size: 2em;
        margin-bottom: 15px;
    }

    .cat-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
    }

    .info-item {
        background: rgba(255, 255, 255, 0.3);
        padding: 10px 15px;
        border-radius: 8px;
    }

    .info-label {
        font-size: 0.85em;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .info-value {
        font-size: 1.1em;
        font-weight: 700;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        font-size: 3em;
        margin-bottom: 10px;
    }

    .stat-number {
        font-size: 2.5em;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 1em;
    }

    /* Filters */
    .filters {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-label {
        font-weight: 600;
        color: #2c3e50;
    }

    .search-box, .filter-select {
        padding: 10px 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1em;
        transition: border-color 0.3s;
    }

    .search-box {
        flex: 1;
        min-width: 250px;
    }

    .search-box:focus, .filter-select:focus {
        outline: none;
        border-color: #c9b896;
    }

    .filter-select {
        cursor: pointer;
    }

    /* Applications Grid */
    .applications-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }

    .application-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .application-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .application-card.hidden {
        display: none;
    }

    .app-header {
        background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .cat-thumb {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        object-fit: cover;
        border: 3px solid white;
    }

    .app-header-info {
        flex: 1;
        color: #3d3020;
    }

    .app-header-info h3 {
        font-size: 1.3em;
        margin-bottom: 5px;
    }

    .app-id {
        font-size: 0.9em;
        opacity: 0.8;
    }

    .app-content {
        padding: 20px;
    }

    .applicant-info {
        margin-bottom: 20px;
    }

    .applicant-info h4 {
        color: #2c3e50;
        font-size: 1.2em;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-label {
        color: #7f8c8d;
        font-weight: 600;
        font-size: 0.9em;
    }

    .detail-value {
        color: #2c3e50;
        font-weight: 500;
        text-align: right;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 600;
        margin-top: 15px;
    }

    .status-pending {
        background: #fff3cd;
        color: #856404;
    }

    .status-approved {
        background: #d4edda;
        color: #155724;
    }

    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }

    .status-completed {
        background: #d1ecf1;
        color: #0c5460;
    }

    .app-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 2px solid #f0f0f0;
    }

    .btn {
        flex: 1;
        padding: 10px 15px;
        border: none;
        border-radius: 8px;
        font-size: 0.95em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        text-align: center;
        display: inline-block;
    }

    .btn-view {
        background: #c9b896;
        color: white;
    }

    .btn-view:hover {
        background: #b8a785;
        transform: translateY(-2px);
    }

    .btn-approve {
        background: #28a745;
        color: white;
    }

    .btn-approve:hover {
        background: #218838;
    }

    .btn-reject {
        background: #dc3545;
        color: white;
    }

    .btn-reject:hover {
        background: #c82333;
    }

    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .empty-state .icon {
        font-size: 5em;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #2c3e50;
        font-size: 1.8em;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #7f8c8d;
        font-size: 1.1em;
    }

    .date-info {
        font-size: 0.85em;
        color: #7f8c8d;
        margin-top: 10px;
    }

    @media (max-width: 768px) {
        .applications-grid {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }

        .cat-info-card {
            flex-direction: column;
        }

        .cat-image {
            width: 100%;
            height: 200px;
        }

        .filters {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box, .filter-select {
            width: 100%;
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <a href="my-listed-pets.php" class="back-btn">
            ← Back to My Listed Pets
        </a>
        <h1>📋 <?php echo $cat ? 'Applications for ' . htmlspecialchars($cat['name']) : 'All Adoption Applications'; ?></h1>
        <p>Review and manage adoption applications</p>
    </div>

    <!-- Cat Info Card (only when viewing specific cat) -->
    <?php if ($cat): ?>
    <div class="cat-info-card">
        <img src="../uploads/<?php echo htmlspecialchars($cat['image_url'] ?: 'default-cat.jpg'); ?>" 
             alt="<?php echo htmlspecialchars($cat['name']); ?>" 
             class="cat-image"
             onerror="this.src='../uploads/default-cat.jpg'">
        <div class="cat-info-content">
            <h2><?php echo htmlspecialchars($cat['name']); ?></h2>
            <div class="cat-info-grid">
                <div class="info-item">
                    <div class="info-label">Breed</div>
                    <div class="info-value"><?php echo htmlspecialchars($cat['breed']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Age</div>
                    <div class="info-value"><?php echo $cat['age']; ?> year(s)</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?php echo htmlspecialchars($cat['gender']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Adoption Fee</div>
                    <div class="info-value">₱<?php echo number_format($cat['adoption_fee'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-number"><?php echo $total_apps; ?></div>
            <div class="stat-label">Total Applications</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-number"><?php echo $pending_apps; ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?php echo $approved_apps; ?></div>
            <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">❌</div>
            <div class="stat-number"><?php echo $rejected_apps; ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <span class="filter-label">🔍</span>
        <input type="text" id="searchInput" placeholder="Search by applicant name or email..." class="search-box">
        <select id="statusFilter" class="filter-select">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Completed">Completed</option>
        </select>
        <?php if (!$cat): ?>
        <select id="catFilter" class="filter-select">
            <option value="">All Cats</option>
            <?php
            // Get unique cats from applications
            $cats = array_unique(array_column($applications, 'cat_name'));
            foreach ($cats as $cat_name):
            ?>
            <option value="<?php echo htmlspecialchars($cat_name); ?>"><?php echo htmlspecialchars($cat_name); ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>

    <!-- Applications Grid -->
    <div class="applications-grid" id="applicationsGrid">
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>No Applications Yet</h3>
                <p><?php echo $cat ? 'This cat hasn\'t received any adoption applications yet.' : 'You haven\'t received any adoption applications yet.'; ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <div class="application-card" 
                     data-name="<?php echo htmlspecialchars(strtolower($app['applicant_name'])); ?>"
                     data-email="<?php echo htmlspecialchars(strtolower($app['email'])); ?>"
                     data-status="<?php echo htmlspecialchars($app['status']); ?>"
                     data-cat="<?php echo htmlspecialchars($app['cat_name']); ?>">
                    
                    <div class="app-header">
                        <img src="../uploads/<?php echo htmlspecialchars($app['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($app['cat_name']); ?>"
                             class="cat-thumb"
                             onerror="this.src='../uploads/default-cat.jpg'">
                        <div class="app-header-info">
                            <h3><?php echo htmlspecialchars($app['cat_name']); ?></h3>
                            <div class="app-id">Application #<?php echo $app['id']; ?></div>
                        </div>
                    </div>

                    <div class="app-content">
                        <div class="applicant-info">
                            <h4>👤 Applicant Details</h4>
                            <div class="detail-row">
                                <span class="detail-label">Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($app['applicant_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($app['email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($app['phone']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Home Type:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($app['home_type']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Has Other Pets:</span>
                                <span class="detail-value"><?php echo $app['has_other_pets'] ? 'Yes' : 'No'; ?></span>
                            </div>
                        </div>

                        <div class="date-info">
                            📅 Submitted: <?php echo date('M d, Y \a\t g:i A', strtotime($app['submitted_at'])); ?>
                        </div>

                        <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                            <?php echo htmlspecialchars($app['status']); ?>
                        </span>

                        <div class="app-actions">
                            <a href="view-adoption-application.php?id=<?php echo $app['id']; ?>" class="btn btn-view">
                                View Full Details
                            </a>
                            <?php if ($app['status'] === 'Pending'): ?>
                                <button class="btn btn-approve" onclick="quickUpdateStatus(<?php echo $app['id']; ?>, 'Approved')">
                                    ✓ Approve
                                </button>
                                <button class="btn btn-reject" onclick="quickUpdateStatus(<?php echo $app['id']; ?>, 'Rejected')">
                                    ✗ Reject
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const catFilter = document.getElementById('catFilter');
    const applicationCards = document.querySelectorAll('.application-card');

    function filterApplications() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedStatus = statusFilter.value;
        const selectedCat = catFilter ? catFilter.value : '';

        applicationCards.forEach(card => {
            const name = card.dataset.name;
            const email = card.dataset.email;
            const status = card.dataset.status;
            const cat = card.dataset.cat;

            // Search filter
            const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);

            // Status filter
            const matchesStatus = !selectedStatus || status === selectedStatus;

            // Cat filter
            const matchesCat = !selectedCat || cat === selectedCat;

            // Show or hide card
            if (matchesSearch && matchesStatus && matchesCat) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    }

    // Add event listeners
    searchInput.addEventListener('input', filterApplications);
    statusFilter.addEventListener('change', filterApplications);
    if (catFilter) {
        catFilter.addEventListener('change', filterApplications);
    }
});

// Quick status update
function quickUpdateStatus(applicationId, newStatus) {
    Swal.fire({
        title: `${newStatus} Application?`,
        text: `Are you sure you want to ${newStatus.toLowerCase()} this application?`,
        icon: 'question',
        input: 'textarea',
        inputLabel: 'Add notes (optional)',
        inputPlaceholder: 'Enter any notes or comments...',
        showCancelButton: true,
        confirmButtonText: `Yes, ${newStatus.toLowerCase()}`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: newStatus === 'Approved' ? '#28a745' : '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update-application-status.php';
            
            const appIdInput = document.createElement('input');
            appIdInput.type = 'hidden';
            appIdInput.name = 'application_id';
            appIdInput.value = applicationId;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = newStatus;
            
            const notesInput = document.createElement('input');
            notesInput.type = 'hidden';
            notesInput.name = 'admin_notes';
            notesInput.value = result.value || '';
            
            form.appendChild(appIdInput);
            form.appendChild(statusInput);
            form.appendChild(notesInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<?php include_once "../includes/footer.php"; ?>