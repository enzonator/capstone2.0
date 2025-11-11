<?php
session_start();
include '../config/db.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Save session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($_SESSION['role'] === 'admin') {
               header("Location: /catshop/admin/products.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $message = "invalid_password";
        }
    } else {
        $message = "user_not_found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - CatShop</title>
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

    .login-container {
      max-width: 450px;
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
      margin-bottom: 30px;
      animation: fadeIn 0.8s ease-in;
    }

    .logo-section img {
      height: 100px;
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

    .login-container h2 {
      text-align: center;
      margin-bottom: 10px;
      color: #5D4E37;
      font-weight: 700;
      font-size: 1.9rem;
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
      margin-bottom: 20px;
    }

    .input-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #8B6F47;
      font-size: 1.1rem;
      z-index: 10;
      transition: all 0.3s ease;
    }

    .form-control {
      border: 2px solid #EADDCA;
      border-radius: 12px;
      padding: 0.85rem 1rem;
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
      animation: shake 0.5s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .alert-danger {
      background: linear-gradient(135deg, #FFE8E8 0%, #FFD5D5 100%);
      color: #C0392B;
      border-left: 4px solid #E74C3C;
    }

    .alert i {
      font-size: 1.3rem;
    }

    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 30px 0;
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
      margin-top: 25px;
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

    .remember-me {
      display: flex;
      align-items: center;
      gap: 8px;
      margin: 15px 0;
    }

    .remember-me input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
      accent-color: #8B6F47;
    }

    .remember-me label {
      margin: 0;
      cursor: pointer;
      font-size: 0.9rem;
      color: #5D4E37;
    }

    @media (max-width: 576px) {
      body {
        padding: 20px 15px;
      }
      
      .login-container {
        padding: 35px 25px;
      }
      .logo-section img {
        height: 80px;
      }
      .login-container h2 {
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

<div class="login-container">
  <div class="logo-section">
    <img src="../assets/images/catshoplogo.png" alt="CatShop Logo">
  </div>
  
  <h2>Welcome Back! 🐾</h2>
  <p class="subtitle">Login to continue your shopping adventure</p>

  <?php if ($message === "invalid_password"): ?>
    <div class="alert alert-danger">
      <i class="bi bi-shield-x"></i>
      <div>
        <strong>Invalid Password</strong><br>
        <small>Please check your password and try again</small>
      </div>
    </div>
  <?php elseif ($message === "user_not_found"): ?>
    <div class="alert alert-danger">
      <i class="bi bi-person-x"></i>
      <div>
        <strong>User Not Found</strong><br>
        <small>No account found with this username or email</small>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" id="loginForm">
    <div class="mb-3">
      <label class="form-label">
        <i class="bi bi-person-circle"></i> Username or Email
      </label>
      <div class="input-group-custom">
        <i class="bi bi-person-fill input-icon"></i>
        <input 
          type="text" 
          name="username" 
          id="username" 
          class="form-control" 
          placeholder="Enter your username or email" 
          required
          autocomplete="username"
        >
      </div>
    </div>

    <div class="mb-2">
      <label class="form-label">
        <i class="bi bi-lock-fill"></i> Password
      </label>
      <div class="input-group-custom">
        <i class="bi bi-key-fill input-icon"></i>
        <input 
          type="password" 
          name="password" 
          id="password" 
          class="form-control" 
          placeholder="Enter your password" 
          required
          autocomplete="current-password"
        >
        <i class="bi bi-eye password-toggle" id="togglePassword"></i>
      </div>
    </div>

    <div class="remember-me">
      <input type="checkbox" id="rememberMe" name="remember_me">
      <label for="rememberMe">Remember me</label>
    </div>

    <button type="submit" class="btn btn-custom text-white" id="loginBtn">
      <i class="bi bi-box-arrow-in-right me-2"></i>Login to CatShop
    </button>
  </form>

  <div class="divider">
    <span>New to CatShop?</span>
  </div>

  <div class="link-group">
    <div class="link-item">
      <a href="register.php">
        <i class="bi bi-person-plus-fill"></i>
        Create a new account
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
      <i class="bi bi-lightning-charge"></i>
      <span>Fast</span>
    </div>
    <div class="feature-item">
      <i class="bi bi-heart"></i>
      <span>Trusted</span>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Password visibility toggle
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

togglePassword.addEventListener('click', function() {
  const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
  passwordInput.setAttribute('type', type);
  
  // Toggle eye icon
  this.classList.toggle('bi-eye');
  this.classList.toggle('bi-eye-slash');
});

// Form submission with loading state
const loginForm = document.getElementById('loginForm');
const loginBtn = document.getElementById('loginBtn');

loginForm.addEventListener('submit', function(e) {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();

  if (username.length < 3) {
    e.preventDefault();
    alert('Please enter a valid username or email (at least 3 characters).');
    return false;
  }

  if (password.length < 6) {
    e.preventDefault();
    alert('Password must be at least 6 characters long.');
    return false;
  }

  // Add loading state
  loginBtn.classList.add('loading');
  loginBtn.innerHTML = '<span style="opacity: 0;">Logging in...</span>';
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