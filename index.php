<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login / Signup - KernelPenguin</title>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      display: flex;
      height: 100vh;
      justify-content: center;
      align-items: center;
      margin: 0;
      background: linear-gradient(135deg, #1f1b1b, #2c2a4a);
      font-family: 'Segoe UI', sans-serif;
      color: #ffffff;
    }
    .container {
      background: #2e2e3e;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
      width: 350px;
      text-align: center;
    }
    h2 {
      margin-bottom: 1.2rem;
      font-weight: 600;
      font-size: 1.4rem;
      color: #f5f5f5;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 0.75rem;
      border-radius: 6px;
      border: none;
      margin-bottom: 1rem;
      font-size: 1rem;
      background-color: #444;
      color: #fff;
    }
    input::placeholder {
      color: #aaa;
    }
    button {
      width: 100%;
      padding: 0.75rem;
      background: #7f39fb;
      border: none;
      color: white;
      font-size: 1rem;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s ease;
      margin-bottom: 1rem;
    }
    button:hover {
      background: #9f5afd;
    }
    .toggle-link {
      color: #a7c7ff;
      cursor: pointer;
      text-decoration: underline;
      font-weight: 500;
    }
    .toggle-link:hover {
      color: #fff;
    }
    #message {
      color: #ffeb3b;
      font-size: 0.9rem;
      min-height: 1.2rem;
    }
    .footer {
      margin-top: 0.5rem;
      font-size: 0.85rem;
      color: #ccc;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2 id="form-title">Login to KernelPenguin</h2>
    
    <input type="text" id="username" placeholder="Enter username" autocomplete="off" required />
    <input type="password" id="password" placeholder="Enter password" autocomplete="off" required />
    <input type="password" id="passwordConfirm" placeholder="Confirm password" autocomplete="off" style="display:none;" />
    
    <button id="submitBtn" onclick="submitForm()">Login</button>
    <div class="footer">
      <span id="toggleText">Don't have an account?</span>
      <span class="toggle-link" onclick="toggleForm()">Signup here</span>
    </div>
    <p id="message"></p>
  </div>

  <script>
    let isLogin = true;

    function toggleForm() {
      isLogin = !isLogin;
      document.getElementById('form-title').textContent = isLogin ? 'Login to KernelPenguin' : 'Signup for KernelPenguin';
      document.getElementById('submitBtn').textContent = isLogin ? 'Login' : 'Signup';
      document.getElementById('passwordConfirm').style.display = isLogin ? 'none' : 'block';
      document.getElementById('toggleText').textContent = isLogin ? "Don't have an account?" : "Already have an account?";
      document.querySelector('.toggle-link').textContent = isLogin ? 'Signup here' : 'Login here';
      document.getElementById('message').textContent = '';
      document.getElementById('username').value = '';
      document.getElementById('password').value = '';
      document.getElementById('passwordConfirm').value = '';
    }

    function validateUsername(username) {
      return /^[a-zA-Z0-9]{3,20}$/.test(username);
    }

    async function postData(url = '', data = {}) {
      const response = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
      });
      return response.json();
    }

    async function submitForm() {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const passwordConfirm = document.getElementById('passwordConfirm').value;
      const message = document.getElementById('message');

      if (!validateUsername(username)) {
        message.textContent = 'Username must be 3-20 alphanumeric characters';
        return;
      }

      if (!password) {
        message.textContent = 'Password cannot be empty';
        return;
      }

      if (!isLogin && password !== passwordConfirm) {
        message.textContent = 'Passwords do not match';
        return;
      }

      message.textContent = isLogin ? "Logging in..." : "Signing up...";

      try {
        const url = isLogin ? 'login.php' : 'signup.php';
        const data = { username, password };
        const res = await postData(url, data);

        if (res.success) {
          window.location.href = 'dashboard.php';
        } else {
          message.textContent = res.message || 'An error occurred';
        }
      } catch {
        message.textContent = 'Error connecting to server';
      }
    }
  </script>
</body>
</html>
