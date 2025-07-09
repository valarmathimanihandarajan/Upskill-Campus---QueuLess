<?php
session_start();
require_once "../includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    if ($password === $row['password']) {
      $_SESSION['user_id'] = $row['id'];
      header("Location: dashboard.php");
      exit();
    }
  }
  $error = "Invalid credentials";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>QueuLess – Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: linear-gradient(135deg, #e3f2fd, #fce4ec);
      --card: rgba(255, 255, 255, 0.8);
      --text: #333;
      --btn: #2a2a72;
      --btn-hover: #1a1a52;
    }
    body.dark {
      --bg: linear-gradient(135deg, #1e1e1e, #121212);
      --card: rgba(30, 30, 30, 0.95);
      --text: #eee;
      --btn: #90caf9;
      --btn-hover: #64b5f6;
    }

    body {
      margin: 0;
      padding: 0;
      background: var(--bg);
      font-family: 'Montserrat', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      color: var(--text);
      transition: background 0.3s ease;
    }

    .form-box {
      background: var(--card);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      animation: slideIn 0.6s ease;
      backdrop-filter: blur(10px);
    }

    .form-box h2 {
      margin-bottom: 20px;
      text-align: center;
      color: var(--btn);
    }

    .input-group {
      position: relative;
      margin-bottom: 30px;
    }

    input {
      width: 100%;
      padding: 12px 10px;
      font-size: 16px;
      background: transparent;
      border: none;
      border-bottom: 2px solid #ccc;
      color: inherit;
      outline: none;
      transition: all 0.3s ease;
    }

    input:focus {
      border-color: var(--btn);
    }

    label {
      position: absolute;
      top: 10px;
      left: 10px;
      font-size: 14px;
      color: #888;
      pointer-events: none;
      transition: 0.3s ease all;
    }

    input:focus ~ label, input:valid ~ label {
      top: -10px;
      font-size: 12px;
      color: var(--btn);
    }

    .password-wrapper {
      position: relative;
    }

    .password-wrapper i {
      position: absolute;
      right: 10px;
      top: 14px;
      cursor: pointer;
      color: #888;
    }

    .error {
      color: red;
      animation: shake 0.4s ease-in-out 2;
      text-align: center;
    }

    button {
      width: 100%;
      padding: 12px;
      background: var(--btn);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease, transform 0.2s ease;
    }

    button:hover {
      background: var(--btn-hover);
      transform: scale(1.03);
    }

    .navbar {
      position: absolute;
      top: 20px;
      left: 20px;
      right: 20px;
      display: flex;
      justify-content: space-between;
      font-size: 14px;
    }

    .navbar a, .toggle {
      color: var(--btn);
      cursor: pointer;
      margin: 0 10px;
      text-decoration: none;
    }

    .tip {
      margin-top: 20px;
      font-size: 13px;
      color: #555;
      text-align: center;
    }

    @keyframes slideIn {
      from { transform: translateY(60px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes shake {
      0% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      50% { transform: translateX(5px); }
      75% { transform: translateX(-5px); }
      100% { transform: translateX(0); }
    }
  </style>
</head>
<body>
  <div class="navbar">
    <a href="../index.php">🏠 Home</a>
    <div>
      <span class="toggle" onclick="switchLang()">🌐</span>
      <span class="toggle" onclick="toggleTheme()">🌓</span>
    </div>
  </div>

  <div class="form-box">
    <h2 id="login-title">Login</h2>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post" onsubmit="return animateSubmit()">
      <div class="input-group">
        <input type="text" name="email" id="email" required />
        <label for="email" id="email-label">Email</label>
      </div>
      <div class="input-group password-wrapper">
        <input type="password" name="password" id="password" required />
        <label for="password" id="pass-label">Password</label>
        <i onclick="togglePassword()">👁️</i>
      </div>
      <button id="loginBtn">Login</button>
    </form>
    <p style="text-align:center;"><a href="register.php" id="register-link">Don't have an account? Register</a></p>
    <div class="tip" id="tip">💡 Tip loading...</div>
  </div>

  <script>
    // Theme Toggle
    function toggleTheme() {
      document.body.classList.toggle("dark");
      localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");
    }
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
    }

    // Multilingual Toggle
    let currentLang = localStorage.getItem('lang') || 'en';
    function switchLang() {
      currentLang = currentLang === 'en' ? 'ta' : 'en';
      localStorage.setItem('lang', currentLang);
      updateLang();
    }

    function updateLang() {
      const strings = {
        en: {
          login: "Login",
          email: "Email",
          password: "Password",
          register: "Don't have an account? Register"
        },
        ta: {
          login: "உள்நுழை",
          email: "மின்னஞ்சல்",
          password: "கடவுச்சொல்",
          register: "கணக்கு இல்லையா? பதிவு செய்க"
        }
      };
      document.getElementById("login-title").textContent = strings[currentLang].login;
      document.getElementById("email-label").textContent = strings[currentLang].email;
      document.getElementById("pass-label").textContent = strings[currentLang].password;
      document.getElementById("register-link").textContent = strings[currentLang].register;
    }
    updateLang();

    // Password Toggle
    function togglePassword() {
      const pwd = document.getElementById("password");
      pwd.type = pwd.type === "password" ? "text" : "password";
    }

    // Tip Generator
    const tips = [
      "Use a strong password with numbers and uppercase letters.",
      "Your email is case-insensitive. Check spelling!",
      "Click the 👁️ icon to reveal password.",
      "Dark mode reduces eye strain.",
      "Refresh browser if anything seems stuck."
    ];
    document.getElementById("tip").innerText = "💡 " + tips[Math.floor(Math.random() * tips.length)];

    // Login button animation
    function animateSubmit() {
      const btn = document.getElementById("loginBtn");
      btn.innerText = "⏳ Logging in...";
      btn.disabled = true;
      return true;
    }
  </script>
</body>
</html>
