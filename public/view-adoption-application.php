<?php
session_start();
require_once "../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$application_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug logging
error_log("Viewing application - user_id: " . $user_id . ", application_id: " . $application_id);

// Fetch application details
// Fetch application details
$sql = "SELECT aa.*, ac.name as cat_name, ac.breed, ac.age, ac.gender, ac.adoption_fee,
        ac.user_id as cat_owner_id,
        u.username as owner_username, u.email as owner_email
        FROM adoption_applications aa
        LEFT JOIN adoption_cats ac ON aa.cat_id = ac.id
        LEFT JOIN users u ON ac.user_id = u.id
        WHERE aa.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

// Debug logging
if ($application) {
    error_log("Application found - cat_owner_id: " . $application['cat_owner_id'] . ", current user_id: " . $user_id);
} else {
    error_log("No application found with id: " . $application_id);
}

if (!$application) {
    die("Application not found.");
}

// Check permission
if ($application['cat_owner_id'] != $user_id) {
    error_log("Permission denied - cat_owner_id: " . $application['cat_owner_id'] . " != user_id: " . $user_id);
    die("You don't have permission to view this application.");
}

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
        max-width: 1000px;
        margin: 0 auto;
    }

    .application-wrapper {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }

    .application-header {
        background: linear-gradient(135deg, #d4c4a8 0%, #c9b896 100%);
        color: #3d3020;
        padding: 40px 30px;
    }

    .application-header h1 {
        font-size: 2.2em;
        margin-bottom: 10px;
    }

    .header-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
    }

    .status-badge {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 700;
        font-size: 1em;
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

    .application-content {
        padding: 40px 30px;
    }

    .section {
        margin-bottom: 35px;
        padding-bottom: 25px;
        border-bottom: 2px solid #f0f0f0;
    }

    .section:last-child {
        border-bottom: none;
    }

    .section h2 {
        color: #2c3e50;
        font-size: 1.5em;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #c9b896;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .info-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 3px solid #c9b896;
    }

    .info-item label {
        display: block;
        font-weight: 600;
        color: #555;
        font-size: 0.9em;
        margin-bottom: 5px;
    }

    .info-item .value {
        color: #2c3e50;
        font-size: 1.05em;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-approve {
        background: #28a745;
        color: white;
    }

    .btn-approve:hover {
        background: #218838;
        transform: translateY(-2px);
    }

    .btn-reject {
        background: #dc3545;
        color: white;
    }

    .btn-reject:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .cat-info-box {
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
    }

    .cat-info-box h3 {
        color: #2e7d32;
        margin-bottom: 15px;
        font-size: 1.3em;
    }

    .cat-details {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }

    .cat-detail-item {
        text-align: center;
        background: white;
        padding: 12px;
        border-radius: 8px;
    }

    .cat-detail-item label {
        display: block;
        font-weight: 600;
        color: #666;
        font-size: 0.85em;
        margin-bottom: 5px;
    }

    .cat-detail-item .value {
        color: #2e7d32;
        font-size: 1.1em;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }

        .cat-details {
            grid-template-columns: 1fr 1fr;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="container">
    <div class="application-wrapper">
        <div class="application-header">
            <h1>📋 Adoption Application</h1>
            <div class="header-info">
                <div>
                    <strong>Application ID:</strong> #<?php echo $application['id']; ?><br>
                    <strong>Submitted:</strong> <?php echo date('F d, Y \a\t g:i A', strtotime($application['submitted_at'])); ?>
                </div>
                <div>
                    <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                        <?php echo htmlspecialchars($application['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="application-content">
            <!-- Cat Information -->
            <div class="cat-info-box">
                <h3>🐱 Cat Being Adopted</h3>
                <div class="cat-details">
                    <div class="cat-detail-item">
                        <label>Name</label>
                        <div class="value"><?php echo htmlspecialchars($application['cat_name']); ?></div>
                    </div>
                    <div class="cat-detail-item">
                        <label>Breed</label>
                        <div class="value"><?php echo htmlspecialchars($application['breed']); ?></div>
                    </div>
                    <div class="cat-detail-item">
                        <label>Age</label>
                        <div class="value"><?php echo $application['age']; ?> year(s)</div>
                    </div>
                    <div class="cat-detail-item">
                        <label>Adoption Fee</label>
                        <div class="value">₱<?php echo number_format($application['adoption_fee'], 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Applicant Information -->
            <section class="section">
                <h2>👤 Applicant Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <div class="value"><?php echo htmlspecialchars($application['applicant_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <div class="value"><?php echo htmlspecialchars($application['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <div class="value"><?php echo htmlspecialchars($application['phone']); ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label>Address</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($application['address'])); ?></div>
                    </div>
                </div>
            </section>

            <!-- Living Situation -->
            <section class="section">
                <h2>🏠 Living Situation</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Home Type</label>
                        <div class="value"><?php echo htmlspecialchars($application['home_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Has Yard</label>
                        <div class="value"><?php echo $application['has_yard'] ? 'Yes' : 'No'; ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label>Living With</label>
                        <div class="value"><?php echo htmlspecialchars($application['living_with'] ?: 'Not specified'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Household Members Allergic</label>
                        <div class="value"><?php echo htmlspecialchars($application['household_allergic'] ?: 'Not specified'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Hours Pet Will Be Alone</label>
                        <div class="value"><?php echo htmlspecialchars($application['hours_alone'] ?: 'Not specified'); ?></div>
                    </div>
                </div>
            </section>

            <!-- Responsibility & Care -->
            <section class="section">
                <h2>💼 Responsibility & Care</h2>
                <div class="info-grid">
                    <div class="info-item full-width">
                        <label>Person Responsible for Daily Care</label>
                        <div class="value"><?php echo htmlspecialchars($application['responsible_person'] ?: 'Not specified'); ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label>Financially Responsible</label>
                        <div class="value"><?php echo htmlspecialchars($application['financially_responsible'] ?: 'Not specified'); ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label>Vacation/Emergency Care Plan</label>
                        <div class="value"><?php echo htmlspecialchars($application['vacation_care'] ?: 'Not specified'); ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label>Introduction Steps</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($application['introduction_steps'] ?: 'Not specified')); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Family Support</label>
                        <div class="value"><?php echo htmlspecialchars($application['family_support'] ?: 'Not specified'); ?></div>
                    </div>
                </div>
            </section>

            <!-- Pet Experience -->
            <section class="section">
                <h2>🐾 Pet Experience</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Currently Has Other Pets</label>
                        <div class="value"><?php echo $application['has_other_pets'] ? 'Yes' : 'No'; ?></div>
                    </div>
                    <?php if ($application['has_other_pets']): ?>
                    <div class="info-item full-width">
                        <label>Other Pets Details</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($application['other_pets_details'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item full-width">
                        <label>Experience with Cats</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($application['experience_with_cats'])); ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label>Reason for Adoption</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($application['reason_for_adoption'])); ?></div>
                    </div>
                </div>
            </section>

            <!-- References -->
            <section class="section">
                <h2>📞 References</h2>
                <div class="info-grid">
                    <div class="info-item full-width">
                        <label>Veterinarian Information</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($application['veterinarian_info'] ?: 'Not provided')); ?></div>
                    </div>
                    <div class="info-item full-width">
                        <label>Personal References</label>
                        <div class="value"><?php echo nl2br(htmlspecialchars($application['references'] ?: 'Not provided')); ?></div>
                    </div>
                </div>
            </section>

            <!-- Admin Notes (if any) -->
            <?php if (!empty($application['admin_notes'])): ?>
            <section class="section">
                <h2>📝 Admin Notes</h2>
                <div class="info-item full-width">
                    <div class="value"><?php echo nl2br(htmlspecialchars($application['admin_notes'])); ?></div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="manage-adoption-applications.php" class="btn btn-secondary">← Back to Applications</a>
                
                <?php if ($application['status'] === 'Pending'): ?>
                    <button class="btn btn-approve" onclick="updateStatus('Approved')">✓ Approve Application</button>
                    <button class="btn btn-reject" onclick="updateStatus('Rejected')">✗ Reject Application</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function updateStatus(newStatus) {
    Swal.fire({
        title: 'Update Application Status',
        text: `Are you sure you want to ${newStatus.toLowerCase()} this application?`,
        icon: 'question',
        input: 'textarea',
        inputLabel: 'Add notes (optional)',
        inputPlaceholder: 'Enter any notes or comments...',
        showCancelButton: true,
        confirmButtonText: 'Yes, ' + newStatus.toLowerCase(),
        cancelButtonText: 'Cancel',
        confirmButtonColor: newStatus === 'Approved' ? '#28a745' : '#dc3545',
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the status update
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'update-application-status.php';
            
            const appIdInput = document.createElement('input');
            appIdInput.type = 'hidden';
            appIdInput.name = 'application_id';
            appIdInput.value = '<?php echo $application_id; ?>';
            
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