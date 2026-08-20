<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HealthCare Pro - Create Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }
    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-card {
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(15, 23, 42, 0.25);
      width: 100%;
      max-width: 420px;
      padding: 40px;
      text-align: center;
    }

    .logo-icon {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, #3b82f6, #06b6d4);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 28px;
      font-weight: 700;
      margin: 0 auto 20px;
    }

    h1 {
      font-size: 26px;
      color: #0f172a;
      margin-bottom: 6px;
      font-weight: 700;
    }

    p.subtitle {
      color: #475569;
      margin-bottom: 32px;
      font-size: 15px;
    }

    .form-group {
      text-align: left;
      margin-bottom: 18px;
    }

    label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 8px;
    }

    .form-input {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      font-size: 15px;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    select.form-input {
      background: #fff;
    }

    .btn-submit {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #3b82f6, #06b6d4);
      color: white;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s, opacity 0.2s;
      margin-top: 10px;
    }

    .btn-submit:hover {
      transform: translateY(-1px);
      opacity: 0.95;
    }

    .helper-text {
      margin-top: 20px;
      color: #64748b;
      font-size: 14px;
    }
    .helper-text a {
      color: #3b82f6;
      font-weight: 600;
      text-decoration: none;
    }
    .helper-text a:hover {
      text-decoration: underline;
    }

    .alert {
      background: rgba(244, 63, 94, 0.1);
      color: #be123c;
      padding: 12px;
      border-radius: 10px;
      font-size: 14px;
      margin-bottom: 16px;
      display: none;
    }

    .alert.success {
      background: rgba(34, 197, 94, 0.12);
      color: #15803d;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="logo-icon">H</div>
    <h1>Create Account</h1>
    <p class="subtitle">Sign up to manage your hospital operations</p>

    <div id="signupAlert" class="alert"></div>

    <form id="signupForm">
      <div class="form-group">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" class="form-input" placeholder="Enter full name" required />
      </div>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" class="form-input" placeholder="Choose a username" required minlength="3" />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" class="form-input" placeholder="Choose a password" required minlength="6" />
      </div>
      <div class="form-group">
        <label for="role">Role</label>
        <select id="role" class="form-input">
          <option value="staff">Staff</option>
          <option value="doctor">Doctor</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <button type="submit" class="btn-submit">Create Account</button>
    </form>

    <p class="helper-text">
      Already have an account? <a href="login.php">Log in</a>
    </p>
  </div>

  <script>
    const form = document.getElementById('signupForm');
    const alertBox = document.getElementById('signupAlert');

    function showMessage(message, isSuccess = false) {
      alertBox.textContent = message;
      alertBox.classList.toggle('success', isSuccess);
      alertBox.style.display = 'block';
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      alertBox.style.display = 'none';

      const formData = new URLSearchParams();
      formData.append('action', 'register');
      formData.append('full_name', document.getElementById('full_name').value.trim());
      formData.append('username', document.getElementById('username').value.trim());
      formData.append('password', document.getElementById('password').value);
      formData.append('role', document.getElementById('role').value);

      try {
        const response = await fetch('auth.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formData,
          credentials: 'include'
        });

        const result = await response.json();

        if (!result.success) {
          showMessage(result.message || 'Registration failed');
          return;
        }

        showMessage('Account created! Redirecting to login...', true);
        setTimeout(() => window.location.href = 'login.php', 1000);
      } catch (error) {
        showMessage('Unable to connect to server');
        console.error('Signup error:', error);
      }
    });
  </script>
</body>
</html>
