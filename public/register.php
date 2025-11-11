<?php
include '../config/db.php';

$message = '';
$first_name = $last_name = $username = $email = $password = $confirm_password = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Confirm password check
    if ($password !== $confirm_password) {
        $message = "password_mismatch";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Check if username or email already exists
        $check = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "⚠️ Username or email already taken.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $hashedPassword);

            if ($stmt->execute()) {
                $message = "success";
                // Clear values after success
                $first_name = $last_name = $username = $email = $password = $confirm_password = '';
            } else {
                $message = "❌ Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Account - CatShop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      overflow-x: hidden;
    }

    body {
      background: linear-gradient(135deg, #EADDCA 0%, #F5E6D3 100%);
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      padding: 40px 20px;
      position: relative;
    }

    /* Animated background elements */
    body::before,
    body::after {
      content: '';
      position: fixed;
      border-radius: 50%;
      opacity: 0.1;
      animation: float 20s infinite ease-in-out;
      pointer-events: none;
    }

    body::before {
      width: 300px;
      height: 300px;
      background: #8B6F47;
      top: -100px;
      left: -100px;
      animation-delay: 0s;
    }

    body::after {
      width: 400px;
      height: 400px;
      background: #A0826D;
      bottom: -150px;
      right: -150px;
      animation-delay: 5s;
    }

    @keyframes float {
      0%, 100% { transform: translate(0, 0) rotate(0deg); }
      33% { transform: translate(30px, -30px) rotate(120deg); }
      66% { transform: translate(-20px, 20px) rotate(240deg); }
    }

    .register-container {
      max-width: 520px;
      width: 100%;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 45px;
      box-shadow: 0 15px 35px rgba(93, 78, 55, 0.2);
      animation: slideUp 0.6s ease-out;
      position: relative;
      z-index: 1;
      margin: 0 auto;
    }

    @keyframes slideUp {
      from { 
        opacity: 0; 
        transform: translateY(30px); 
      }
      to { 
        opacity: 1; 
        transform: translateY(0); 
      }
    }

    .logo-section {
      text-align: center;
      margin-bottom: 25px;
      animation: fadeIn 0.8s ease-in;
    }

    .logo-section img {
      height: 90px;
      margin-bottom: 15px;
      filter: drop-shadow(0 4px 8px rgba(139, 111, 71, 0.3));
      transition: transform 0.3s ease;
    }

    .logo-section img:hover {
      transform: scale(1.1) rotate(5deg);
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .register-container h2 {
      text-align: center;
      margin-bottom: 10px;
      color: #5D4E37;
      font-weight: 700;
      font-size: 1.85rem;
    }

    .subtitle {
      text-align: center;
      color: #8B6F47;
      margin-bottom: 30px;
      font-size: 0.95rem;
    }

    .form-label {
      color: #5D4E37;
      font-weight: 600;
      margin-bottom: 8px;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .input-group-custom {
      position: relative;
      margin-bottom: 18px;
    }

    .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #8B6F47;
      font-size: 1rem;
      z-index: 10;
      transition: all 0.3s ease;
    }

    .form-control {
      border: 2px solid #EADDCA;
      border-radius: 12px;
      padding: 0.8rem 1rem;
      padding-left: 45px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      background-color: #FFFBF7;
    }

    .form-control:focus {
      border-color: #8B6F47;
      box-shadow: 0 0 0 0.25rem rgba(139, 111, 71, 0.15);
      background-color: white;
      transform: translateY(-2px);
    }

    .form-control:focus ~ .input-icon {
      color: #5D4E37;
      transform: translateY(-50%) scale(1.1);
    }

    .form-control.is-invalid {
      border-color: #E74C3C;
      padding-right: calc(1.5em + 0.75rem);
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23E74C3C'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23E74C3C' stroke='none'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right calc(0.375em + 0.1875rem) center;
      background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .form-control.is-valid {
      border-color: #27AE60;
      padding-right: calc(1.5em + 0.75rem);
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2327AE60' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right calc(0.375em + 0.1875rem) center;
      background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .form-control::placeholder {
      color: #B8A598;
    }

    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #8B6F47;
      font-size: 1.1rem;
      z-index: 10;
      transition: color 0.3s ease;
    }

    .password-toggle:hover {
      color: #5D4E37;
    }

    .btn-custom {
      width: 100%;
      background: linear-gradient(135deg, #8B6F47 0%, #A0826D 100%);
      border: none;
      padding: 0.95rem;
      border-radius: 12px;
      font-weight: 600;
      font-size: 1.05rem;
      transition: all 0.3s ease;
      margin-top: 10px;
      box-shadow: 0 4px 15px rgba(139, 111, 71, 0.3);
      position: relative;
      overflow: hidden;
    }

    .btn-custom::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s ease;
    }

    .btn-custom:hover::before {
      left: 100%;
    }

    .btn-custom:hover {
      background: linear-gradient(135deg, #A0826D 0%, #8B6F47 100%);
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(139, 111, 71, 0.4);
    }

    .btn-custom:active {
      transform: translateY(-1px);
    }

    .alert {
      border-radius: 12px;
      border: none;
      padding: 14px 18px;
      margin-bottom: 25px;
      animation: slideDown 0.5s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .alert-danger {
      background: linear-gradient(135deg, #FFE8E8 0%, #FFD5D5 100%);
      color: #C0392B;
      border-left: 4px solid #E74C3C;
    }

    .alert-success {
      background: linear-gradient(135deg, #D4EDDA 0%, #C3E6CB 100%);
      color: #155724;
      border-left: 4px solid #28A745;
    }

    .alert-info {
      background: linear-gradient(135deg, #FFF3CD 0%, #FFE69C 100%);
      color: #856404;
      border-left: 4px solid #FFC107;
    }

    .alert i {
      font-size: 1.3rem;
    }

    .error-text {
      color: #E74C3C;
      font-size: 0.85rem;
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 5px;
      animation: shake 0.3s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .password-strength {
      height: 6px;
      border-radius: 3px;
      margin-top: 8px;
      background-color: #EADDCA;
      overflow: hidden;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .password-strength-bar {
      height: 100%;
      transition: all 0.3s ease;
      border-radius: 3px;
    }

    .password-strength-text {
      font-size: 0.8rem;
      margin-top: 5px;
      font-weight: 600;
    }

    .row {
      margin-left: -8px;
      margin-right: -8px;
    }

    .row > div {
      padding-left: 8px;
      padding-right: 8px;
    }

    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 30px 0 20px 0;
      color: #B8A598;
      font-size: 0.85rem;
      font-weight: 500;
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      border-bottom: 2px solid #EADDCA;
    }

    .divider span {
      padding: 0 15px;
    }

    .link-group {
      text-align: center;
      margin-top: 20px;
    }

    .link-item {
      color: #5D4E37;
      font-size: 0.95rem;
      margin: 8px 0;
      display: block;
    }

    .link-item a {
      color: #8B6F47;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .link-item a:hover {
      color: #A0826D;
      gap: 8px;
    }

    .features {
      display: flex;
      justify-content: space-around;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 2px solid #EADDCA;
    }

    .feature-item {
      text-align: center;
      flex: 1;
      padding: 0 10px;
    }

    .feature-item i {
      font-size: 1.8rem;
      color: #8B6F47;
      margin-bottom: 8px;
      display: block;
    }

    .feature-item span {
      font-size: 0.75rem;
      color: #5D4E37;
      font-weight: 500;
    }

    @media (max-width: 576px) {
      body {
        padding: 20px 15px;
      }
      
      .register-container {
        padding: 35px 25px;
      }
      .logo-section img {
        height: 75px;
      }
      .register-container h2 {
        font-size: 1.6rem;
      }
      .features {
        flex-direction: column;
        gap: 15px;
      }
    }

    /* Loading state */
    .btn-custom.loading {
      pointer-events: none;
      opacity: 0.7;
    }

    .btn-custom.loading::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      top: 50%;
      left: 50%;
      margin-left: -10px;
      margin-top: -10px;
      border: 3px solid rgba(255,255,255,0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

<div class="register-container">
  <div class="logo-section">
    <img src="../assets/images/catshoplogo.png" alt="CatShop Logo">
  </div>
  
  <h2>Join CatShop! 🐾</h2>
  <p class="subtitle">Create your account and start shopping</p>

  <?php if ($message === "success"): ?>
    <div class="alert alert-success">
      <i class="bi bi-check-circle-fill"></i>
      <div>
        <strong>Success!</strong><br>
        <small>Account created! <a href="login.php" style="color: #155724; font-weight: 700;">Login here</a></small>
      </div>
    </div>
  <?php elseif ($message && $message !== "password_mismatch"): ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div><?= $message; ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" id="registerForm">
    <div class="row">
      <div class="col-md-6">
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-person"></i> First Name
          </label>
          <div class="input-group-custom">
            <i class="bi bi-person-fill input-icon"></i>
            <input type="text" name="first_name" id="first_name" 
                   value="<?= htmlspecialchars($first_name) ?>" 
                   class="form-control" placeholder="John" required>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-person"></i> Last Name
          </label>
          <div class="input-group-custom">
            <i class="bi bi-person-fill input-icon"></i>
            <input type="text" name="last_name" id="last_name" 
                   value="<?= htmlspecialchars($last_name) ?>" 
                   class="form-control" placeholder="Doe" required>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">
        <i class="bi bi-at"></i> Username
      </label>
      <div class="input-group-custom">
        <i class="bi bi-person-circle input-icon"></i>
        <input type="text" name="username" id="username" 
               value="<?= htmlspecialchars($username) ?>" 
               class="form-control" placeholder="johndoe123" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">
        <i class="bi bi-envelope"></i> Email
      </label>
      <div class="input-group-custom">
        <i class="bi bi-envelope-fill input-icon"></i>
        <input type="email" name="email" id="email" 
               value="<?= htmlspecialchars($email) ?>" 
               class="form-control" placeholder="john@example.com" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">
        <i class="bi bi-lock"></i> Password
      </label>
      <div class="input-group-custom">
        <i class="bi bi-key-fill input-icon"></i>
        <input type="password" name="password" id="password" 
               value="<?= htmlspecialchars($password) ?>" 
               class="form-control" placeholder="Min. 6 characters" required>
        <i class="bi bi-eye password-toggle" id="togglePassword"></i>
      </div>
      <div class="password-strength">
        <div class="password-strength-bar" id="strengthBar"></div>
      </div>
      <small class="password-strength-text" id="strengthText"></small>
    </div>

    <div class="mb-3">
      <label class="form-label">
        <i class="bi bi-lock-fill"></i> Confirm Password
      </label>
      <div class="input-group-custom">
        <i class="bi bi-shield-lock-fill input-icon"></i>
        <input 
          type="password" 
          name="confirm_password" 
          id="confirm_password" 
          value="<?= htmlspecialchars($confirm_password) ?>"
          class="form-control <?php if($message === "password_mismatch") echo 'is-invalid'; ?>" 
          placeholder="Re-enter password"
          required
        >
        <i class="bi bi-eye password-toggle" id="toggleConfirmPassword"></i>
      </div>
      <?php if($message === "password_mismatch"): ?>
        <div class="error-text">
          <i class="bi bi-exclamation-circle-fill"></i>
          Passwords do not match.
        </div>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-custom text-white" id="registerBtn">
      <i class="bi bi-person-plus me-2"></i>Create Account
    </button>
  </form>

  <div class="divider">
    <span>Already a member?</span>
  </div>

  <div class="link-group">
    <div class="link-item">
      <a href="login.php">
        <i class="bi bi-box-arrow-in-right"></i>
        Login to your account
      </a>
    </div>
    <div class="link-item">
      <a href="index.php">
        <i class="bi bi-arrow-left"></i>
        Back to Home
      </a>
    </div>
  </div>

  <div class="features">
    <div class="feature-item">
      <i class="bi bi-shield-check"></i>
      <span>Secure</span>
    </div>
    <div class="feature-item">
      <i class="bi bi-gift"></i>
      <span>Rewards</span>
    </div>
    <div class="feature-item">
      <i class="bi bi-truck"></i>
      <span>Fast Delivery</span>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Password visibility toggle
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
const confirmPasswordInput = document.getElementById('confirm_password');

togglePassword.addEventListener('click', function() {
  const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  passwordInput.setAttribute('type', type);
  this.classList.toggle('bi-eye');
  this.classList.toggle('bi-eye-slash');
});

toggleConfirmPassword.addEventListener('click', function() {
  const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  confirmPasswordInput.setAttribute('type', type);
  this.classList.toggle('bi-eye');
  this.classList.toggle('bi-eye-slash');
});

// Password strength indicator
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

passwordInput.addEventListener('input', function() {
  const password = this.value;
  let strength = 0;
  
  if (password.length >= 6) strength++;
  if (password.length >= 10) strength++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
  if (/\d/.test(password)) strength++;
  if (/[^a-zA-Z0-9]/.test(password)) strength++;
  
  const colors = ['#E74C3C', '#E67E22', '#F39C12', '#27AE60', '#229954'];
  const texts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
  const widths = ['20%', '40%', '60%', '80%', '100%'];
  
  if (password.length > 0) {
    strengthBar.style.width = widths[strength - 1] || '20%';
    strengthBar.style.backgroundColor = colors[strength - 1] || colors[0];
    strengthText.textContent = texts[strength - 1] || texts[0];
    strengthText.style.color = colors[strength - 1] || colors[0];
  } else {
    strengthBar.style.width = '0%';
    strengthText.textContent = '';
  }
});

// Password match validation
confirmPasswordInput.addEventListener('input', function() {
  const password = passwordInput.value;
  const confirmPassword = this.value;
  
  if (confirmPassword.length > 0) {
    if (password === confirmPassword) {
      this.classList.remove('is-invalid');
      this.classList.add('is-valid');
    } else {
      this.classList.remove('is-valid');
      this.classList.add('is-invalid');
    }
  } else {
    this.classList.remove('is-valid', 'is-invalid');
  }
});

// Form submission with loading state
const registerForm = document.getElementById('registerForm');
const registerBtn = document.getElementById('registerBtn');

registerForm.addEventListener('submit', function(e) {
  const password = passwordInput.value;
  const confirmPassword = confirmPasswordInput.value;
  
  if (password !== confirmPassword) {
    e.preventDefault();
    confirmPasswordInput.classList.add('is-invalid');
    alert('Passwords do not match!');
    return false;
  }
  
  if (password.length < 6) {
    e.preventDefault();
    alert('Password must be at least 6 characters long.');
    return false;
  }

  // Add loading state
  registerBtn.classList.add('loading');
  registerBtn.innerHTML = '<span style="opacity: 0;">Creating account...</span>';
});

// Input focus effects
const inputs = document.querySelectorAll('.form-control');
inputs.forEach(input => {
  input.addEventListener('focus', function() {
    this.parentElement.style.transform = 'translateY(-2px)';
  });
  
  input.addEventListener('blur', function() {
    this.parentElement.style.transform = 'translateY(0)';
  });
});

// Auto-dismiss alerts after 5 seconds
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-20px)';
    setTimeout(() => alert.remove(), 500);
  }, 5000);
});
</script>

</body>
</html>