<?php
session_start();
require_once "../includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = $_POST['name'];
  $email = $_POST['email'];
  $password = $_POST['password']; // Still plaintext as per your structure

  $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $name, $email, $password);
  if ($stmt->execute()) {
    header("Location: login.php");
    exit();
  } else {
    $error = "Registration failed. Try again.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>QueuLess - Register</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: linear-gradient(135deg, #e0f7fa, #fce4ec);
      --card: rgba(255, 255, 255, 0.85);
      --text: #333;
      --btn: #2a2a72;
      --btn-hover: #1a1a52;
    }
    body.dark {
      --bg: linear-gradient(135deg, #1e1e1e, #111);
      --card: rgba(30, 30, 30, 0.9);
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
      align-items: center;
      justify-content: center;
      height: 100vh;
      color: var(--text);
      transition: background 0.3s ease;
    }

    .form-box {
      background: var(--card);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      animation: slideIn 0.6s ease;
      backdrop-filter: blur(12px);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: var(--btn);
    }

    input[type="text"], input[type="email"], input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 12px 0;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 16px;
      background: white;
    }

    body.dark input {
      background: #2c2c2c;
      color: white;
      border: 1px solid #666;
    }

    .password-wrapper {
      position: relative;
    }

    .password-wrapper i {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #888;
    }

    button {
      background: var(--btn);
      color: white;
      padding: 12px;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      width: 100%;
      margin-top: 10px;
      transition: background 0.3s ease, transform 0.2s;
      cursor: pointer;
    }

    button:hover {
      background: var(--btn-hover);
      transform: scale(1.03);
    }

    .error {
      color: red;
      animation: shake 0.3s ease-in-out 2;
      margin-top: 10px;
    }

    .tip {
      margin-top: 20px;
      font-size: 14px;
      font-style: italic;
      color: #666;
    }

    .toggle {
      position: absolute;
      top: 15px;
      right: 20px;
    }

    .toggle button {
      background: var(--btn);
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 14px;
      cursor: pointer;
    }

    .strength {
      font-size: 13px;
      margin: 5px 0;
      font-weight: bold;
    }

    @keyframes slideIn {
      from { transform: translateY(50px); opacity: 0; }
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
  <div class="toggle">
    <button onclick="toggleTheme()">🌗 Theme</button>
  </div>

  <div class="form-box">
    <h2>Register</h2>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>

      <div class="password-wrapper">
        <input type="password" name="password" id="password" placeholder="Password" required onkeyup="checkStrength(this.value)">
        <i onclick="togglePassword()">👁️</i>
      </div>
      <div class="strength" id="strength"></div>

      <button type="submit">Register</button>
    </form>
    <p style="text-align:center; margin-top: 10px;"><a href="login.php">Already have an account? Login</a></p>
    <div class="tip" id="tipBox">💡 Tip loading...</div>
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

    // Password toggle
    function togglePassword() {
      const pwd = document.getElementById("password");
      pwd.type = pwd.type === "password" ? "text" : "password";
    }

    // Password strength checker
    function checkStrength(password) {
      const strengthText = document.getElementById("strength");
      let strength = "Weak 🔴";
      if (password.length >= 8 && /[0-9]/.test(password) && /[A-Z]/.test(password)) {
        strength = "Strong 🟢";
      } else if (password.length >= 6) {
        strength = "Moderate 🟡";
      }
      strengthText.innerText = "Password Strength: " + strength;
    }

    // Tips
    const tips = [
      "Use a strong password with uppercase, numbers and symbols.",
      "Avoid using your name or email in the password.",
      "You can toggle dark mode from the top.",
      "Keep your login secure and confidential.",
      "Passwords are case-sensitive!"
    ];
    document.getElementById("tipBox").innerText = "💡 " + tips[Math.floor(Math.random() * tips.length)];
  </script>
</body>
</html>
