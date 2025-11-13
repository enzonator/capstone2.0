<?php
session_start();
require_once "../config/db.php"; // uses $conn (MySQLi)

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$user_id = $_SESSION['user_id'];
$showSuccessPopup = false;

// Check verification status
$verification_status = 'not verified';
$verifyQuery = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
$verifyQuery->bind_param("i", $user_id);
$verifyQuery->execute();
$verifyResult = $verifyQuery->get_result();
if ($verifyResult->num_rows > 0) {
    $verifyUser = $verifyResult->fetch_assoc();
    $verification_status = $verifyUser['verification_status'] ?? 'not verified';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["valid_id"]) && $_FILES["valid_id"]["error"] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES["valid_id"]["tmp_name"];
        $fileName = $_FILES["valid_id"]["name"];
        $fileSize = $_FILES["valid_id"]["size"];
        $fileType = $_FILES["valid_id"]["type"];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ["jpg", "jpeg", "png", "pdf"];

        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = "../uploads/verifications/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = "valid_id_" . $user_id . "_" . time() . "." . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert verification record
                    $stmt = $conn->prepare("INSERT INTO verifications (user_id, id_image, status, created_at) VALUES (?, ?, 'Pending', NOW())");
                    $stmt->bind_param("is", $user_id, $newFileName);
                    $stmt->execute();
                    $stmt->close();

                    // Update user verification status to pending
                    $updateStmt = $conn->prepare("UPDATE users SET verification_status = 'pending' WHERE id = ?");
                    $updateStmt->bind_param("i", $user_id);
                    $updateStmt->execute();
                    $updateStmt->close();

                    // Commit transaction
                    $conn->commit();

                    // Set flag to show success popup
                    $showSuccessPopup = true;
                    
                } catch (Exception $e) {
                    // Rollback on error
                    $conn->rollback();
                    $message = "Failed to save verification request.";
                }
            } else {
                $message = "Error moving uploaded file.";
            }
        } else {
            $message = "Only JPG, JPEG, PNG, and PDF files are allowed.";
        }
    } else {
        $message = "Please upload a valid ID.";
    }
}
?>

<?php include_once "../includes/header.php"; ?>

<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($showSuccessPopup): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Verification Submitted!',
        text: 'Your verification request has been submitted successfully. Please wait for admin confirmation.',
        icon: 'success',
        confirmButtonColor: '#8B6F47',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        window.location.href = 'index.php';
    });
});
</script>
<?php endif; ?>

<style>
  /* Color scheme matching the header */
  :root {
      --primary-bg: #EADDCA;
      --primary-text: #5D4E37;
      --accent-color: #8B6F47;
      --hover-color: #A0826D;
      --badge-color: #D2691E;
      --white: #ffffff;
      --light-accent: rgba(139, 111, 71, 0.1);
      --success-color: #2e7d32;
      --error-color: #c82333;
  }

  body {
      background: linear-gradient(135deg, #f5f0e8 0%, #e8dcc8 100%);
      min-height: 100vh;
      padding: 40px 20px;
  }

  .verify-wrapper {
      max-width: 800px;
      margin: 0 auto;
  }

  .verify-container {
      background: var(--white);
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(93, 78, 55, 0.15);
      border: 2px solid var(--primary-bg);
      overflow: hidden;
      margin-bottom: 25px;
  }

  .verify-header {
      background: linear-gradient(135deg, var(--primary-bg) 0%, #d4c4a8 100%);
      padding: 35px 40px;
      text-align: center;
      border-bottom: 3px solid var(--accent-color);
  }

  .verify-header h2 {
      margin: 0 0 10px 0;
      color: var(--primary-text);
      font-size: 28px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
  }

  .verify-header p {
      margin: 0;
      color: var(--accent-color);
      font-size: 15px;
  }

  .verify-content {
      padding: 40px;
  }

  /* Verified Status Display */
  .verified-status {
      text-align: center;
      padding: 40px 20px;
  }

  .verified-icon {
      font-size: 80px;
      color: var(--success-color);
      margin-bottom: 20px;
      animation: scaleIn 0.5s ease;
  }

  @keyframes scaleIn {
      from {
          transform: scale(0);
      }
      to {
          transform: scale(1);
      }
  }

  .verified-status h3 {
      color: var(--success-color);
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
  }

  .verified-status p {
      color: var(--accent-color);
      font-size: 16px;
      line-height: 1.6;
  }

  .verified-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
      color: var(--success-color);
      padding: 12px 24px;
      border-radius: 50px;
      font-weight: 600;
      margin-top: 20px;
      border: 2px solid var(--success-color);
  }

  /* Pending Status Display */
  .pending-status {
      text-align: center;
      padding: 40px 20px;
  }

  .pending-icon {
      font-size: 80px;
      color: #ff9800;
      margin-bottom: 20px;
      animation: pulse 2s infinite;
  }

  @keyframes pulse {
      0%, 100% {
          opacity: 1;
      }
      50% {
          opacity: 0.5;
      }
  }

  .pending-status h3 {
      color: #ff9800;
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
  }

  .pending-status p {
      color: var(--accent-color);
      font-size: 16px;
      line-height: 1.6;
  }

  .pending-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
      color: #ff9800;
      padding: 12px 24px;
      border-radius: 50px;
      font-weight: 600;
      margin-top: 20px;
      border: 2px solid #ff9800;
  }

  .message {
      margin-bottom: 25px;
      padding: 15px 20px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      animation: slideDown 0.3s ease;
      background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
      color: var(--error-color);
      border: 2px solid var(--error-color);
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

  .upload-form {
      display: flex;
      flex-direction: column;
      gap: 25px;
  }

  .form-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
  }

  .form-label {
      font-weight: 600;
      color: var(--primary-text);
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
  }

  .form-label i {
      color: var(--accent-color);
      font-size: 18px;
  }

  .file-upload-area {
      position: relative;
      border: 3px dashed var(--accent-color);
      border-radius: 12px;
      padding: 40px 20px;
      text-align: center;
      background: var(--primary-bg);
      transition: all 0.3s ease;
      cursor: pointer;
  }

  .file-upload-area:hover {
      background: var(--accent-color);
      border-style: solid;
  }

  .file-upload-area:hover .upload-icon,
  .file-upload-area:hover .upload-text,
  .file-upload-area:hover .upload-hint {
      color: var(--white);
  }

  .upload-icon {
      font-size: 48px;
      color: var(--accent-color);
      margin-bottom: 15px;
      transition: color 0.3s ease;
  }

  .upload-text {
      font-size: 18px;
      font-weight: 600;
      color: var(--primary-text);
      margin-bottom: 8px;
      transition: color 0.3s ease;
  }

  .upload-hint {
      font-size: 14px;
      color: var(--accent-color);
      transition: color 0.3s ease;
  }

  .file-input {
      position: absolute;
      opacity: 0;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      cursor: pointer;
  }

  .file-selected {
      background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
      border-color: var(--success-color);
      padding: 20px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 15px;
      margin-top: 15px;
  }

  .file-selected i {
      font-size: 32px;
      color: var(--success-color);
  }

  .file-info {
      flex: 1;
      text-align: left;
  }

  .file-name {
      font-weight: 600;
      color: var(--success-color);
      margin-bottom: 5px;
  }

  .file-size {
      font-size: 13px;
      color: var(--accent-color);
  }

  .btn-submit {
      background: var(--accent-color);
      color: var(--white);
      padding: 16px 32px;
      border: none;
      border-radius: 10px;
      font-size: 17px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
  }

  .btn-submit:hover {
      background: var(--hover-color);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(139, 111, 71, 0.3);
  }

  .btn-submit:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
  }

  .btn-back {
      background: var(--primary-bg);
      color: var(--primary-text);
      padding: 14px 28px;
      border: 2px solid var(--accent-color);
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
  }

  .btn-back:hover {
      background: var(--accent-color);
      color: var(--white);
      transform: translateY(-2px);
  }

  /* Privacy Notice Box */
  .privacy-notice {
      background: var(--white);
      border-radius: 16px;
      padding: 30px;
      border: 2px solid var(--primary-bg);
      box-shadow: 0 8px 24px rgba(93, 78, 55, 0.15);
  }

  .privacy-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--primary-bg);
  }

  .privacy-header i {
      font-size: 28px;
      color: var(--accent-color);
  }

  .privacy-header h3 {
      margin: 0;
      color: var(--primary-text);
      font-size: 20px;
      font-weight: 700;
  }

  .privacy-content {
      color: var(--primary-text);
      line-height: 1.8;
  }

  .privacy-content h4 {
      color: var(--accent-color);
      font-size: 16px;
      font-weight: 700;
      margin: 20px 0 10px 0;
  }

  .privacy-content ul {
      margin: 10px 0;
      padding-left: 25px;
  }

  .privacy-content li {
      margin: 8px 0;
  }

  .privacy-content strong {
      color: var(--accent-color);
  }

  .highlight-box {
      background: var(--light-accent);
      padding: 15px;
      border-radius: 8px;
      border-left: 4px solid var(--accent-color);
      margin: 15px 0;
  }

  /* Responsive */
  @media (max-width: 768px) {
      body {
          padding: 20px 10px;
      }

      .verify-header {
          padding: 25px 20px;
      }

      .verify-header h2 {
          font-size: 24px;
      }

      .verify-content {
          padding: 25px 20px;
      }

      .privacy-notice {
          padding: 20px;
      }

      .file-upload-area {
          padding: 30px 15px;
      }

      .upload-icon {
          font-size: 36px;
      }

      .verified-icon, .pending-icon {
          font-size: 60px;
      }

      .verified-status h3, .pending-status h3 {
          font-size: 24px;
      }
  }
</style>

<div class="verify-wrapper">
  <div class="verify-container">
    <div class="verify-header">
      <h2>
        <i class="bi bi-shield-check"></i>
        Account Verification
      </h2>
      <p>
        <?php if ($verification_status === 'verified'): ?>
          Your account is verified and secure
        <?php elseif ($verification_status === 'pending'): ?>
          Your verification request is being processed
        <?php else: ?>
          Upload a valid government-issued ID to verify your account
        <?php endif; ?>
      </p>
    </div>

    <div class="verify-content">
      <?php if ($verification_status === 'verified'): ?>
        <!-- Already Verified Display -->
        <div class="verified-status">
          <div class="verified-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <h3>Account Verified Successfully!</h3>
          <p>
            Your account has been verified and you now have full access to all platform features. 
            You can buy, sell, and list pets for adoption with confidence.
          </p>
          <div class="verified-badge">
            <i class="bi bi-shield-fill-check"></i>
            Verified User
          </div>
          <div style="margin-top: 30px;">
            <a href="index.php" class="btn-back">
              <i class="bi bi-house-fill"></i>
              Back to Home
            </a>
          </div>
        </div>

      <?php elseif ($verification_status === 'pending'): ?>
        <!-- Pending Verification Display -->
        <div class="pending-status">
          <div class="pending-icon">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <h3>Verification Pending</h3>
          <p>
            Your verification documents have been submitted and are currently under review. 
            Our admin team will process your request shortly. You will be notified once your account is verified.
          </p>
          <div class="pending-badge">
            <i class="bi bi-clock-fill"></i>
            Under Review
          </div>
          <div style="margin-top: 30px;">
            <a href="index.php" class="btn-back">
              <i class="bi bi-house-fill"></i>
              Back to Home
            </a>
          </div>
        </div>

      <?php else: ?>
        <!-- Upload Form for Not Verified Users -->
        <?php if (!empty($message)) : ?>
          <div class="message">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="upload-form" id="verifyForm">
          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-card-image"></i>
              Valid Government-Issued ID
            </label>
            
            <div class="file-upload-area" id="uploadArea">
              <i class="bi bi-cloud-upload upload-icon"></i>
              <div class="upload-text">Click to upload or drag and drop</div>
              <div class="upload-hint">Accepted formats: JPG, PNG, PDF (Max 5MB)</div>
              <input type="file" name="valid_id" id="valid_id" class="file-input" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <div class="file-selected" id="fileSelected" style="display: none;">
              <i class="bi bi-file-earmark-check-fill"></i>
              <div class="file-info">
                <div class="file-name" id="fileName"></div>
                <div class="file-size" id="fileSize"></div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-submit" id="submitBtn" disabled>
            <i class="bi bi-send-fill"></i>
            Submit Verification Request
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Privacy Notice -->
  <div class="privacy-notice">
    <div class="privacy-header">
      <i class="bi bi-shield-lock-fill"></i>
      <h3>Data Privacy Notice (RA 10173)</h3>
    </div>
    
    <div class="privacy-content">
      <p>
        In compliance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>, 
        we are committed to protecting your personal information.
      </p>

      <h4>Purpose of Data Collection:</h4>
      <ul>
        <li>To verify your identity and authenticate your account</li>
        <li>To ensure the security and integrity of our platform</li>
        <li>To comply with legal and regulatory requirements</li>
        <li>To prevent fraud and unauthorized access</li>
      </ul>

      <h4>Data We Collect:</h4>
      <ul>
        <li>Government-issued ID (image or PDF)</li>
        <li>Personal information visible on the ID (name, ID number, photo)</li>
        <li>Submission date and verification status</li>
      </ul>

      <h4>How We Protect Your Data:</h4>
      <ul>
        <li>All uploaded documents are encrypted and stored securely</li>
        <li>Access is restricted to authorized personnel only</li>
        <li>Data is retained only as long as necessary for verification</li>
        <li>We will never share your information with third parties without consent</li>
      </ul>

      <div class="highlight-box">
        <strong>Your Rights:</strong> You have the right to access, correct, and request deletion 
        of your personal data. For concerns, please contact our Data Protection Officer.
      </div>

      <p style="margin-top: 15px; font-size: 14px; color: var(--accent-color);">
        <i class="bi bi-info-circle"></i> 
        By submitting your verification request, you consent to the collection and processing 
        of your personal data as described above.
      </p>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('valid_id');
    const uploadArea = document.getElementById('uploadArea');
    const fileSelected = document.getElementById('fileSelected');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');

    // Only run if elements exist (i.e., user is not verified/pending)
    if (fileInput && uploadArea && submitBtn) {
        // Handle file selection
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileSizeKB = (file.size / 1024).toFixed(2);
                
                fileName.textContent = file.name;
                fileSize.textContent = `Size: ${fileSizeKB} KB`;
                fileSelected.style.display = 'flex';
                submitBtn.disabled = false;
            }
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--accent-color)';
            this.style.background = 'var(--accent-color)';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.background = '';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.background = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    }
});
</script>

<?php include_once "../includes/footer.php"; ?>