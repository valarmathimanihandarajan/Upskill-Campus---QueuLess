<?php
session_start();
require_once "../includes/db_connect.php";
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("DELETE FROM tokens WHERE user_id = ? AND status = 'waiting'");
$stmt->bind_param("i", $userId);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Token Cancelled - QueuLess</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f9f9f9;
      --text: #333;
      --card: #fff;
      --accent: #a62828;
    }
    body.dark {
      --bg: #121212;
      --text: #eee;
      --card: #1e1e1e;
    }
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 20px;
      background: var(--bg);
      color: var(--text);
      transition: background 0.3s, color 0.3s;
    }
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .btn, .lang-toggle, .theme-toggle {
      background: var(--accent);
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      text-decoration: none;
    }
    .container {
      max-width: 500px;
      margin: auto;
      background: var(--card);
      padding: 30px;
      border-radius: 14px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      animation: slideIn 0.6s ease;
    }
    h2 {
      margin-top: 0;
      color: var(--accent);
    }
    .tip {
      font-size: 13px;
      opacity: 0.7;
      margin-top: 10px;
      text-align: center;
    }
    @keyframes slideIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="navbar">
    <div>
      <a class="btn" href="dashboard.php">🏠 Home</a>
      <a class="btn" href="javascript:history.back()">🔙 Back</a>
    </div>
    <div>
      <button class="lang-toggle" onclick="switchLang()">🌐</button>
      <button class="theme-toggle" onclick="toggleTheme()">🌓</button>
    </div>
  </div>

  <div class="container">
    <h2 id="cancel-title">Token Cancelled</h2>
    <p id="cancel-msg">Your token has been cancelled successfully.</p>
    <div class="tip" id="cancel-tip">💡 You can rebook a token anytime from your dashboard.</div>
  </div>

  <script>
    function toggleTheme() {
      document.body.classList.toggle("dark");
      localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");
    }
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
    }

    let currentLang = localStorage.getItem("lang") || "en";
    function switchLang() {
      currentLang = currentLang === "en" ? "ta" : "en";
      localStorage.setItem("lang", currentLang);
      updateLang();
    }

    function updateLang() {
      const strings = {
        en: {
          title: "Token Cancelled",
          msg: "Your token has been cancelled successfully.",
          tip: "💡 You can rebook a token anytime from your dashboard."
        },
        ta: {
          title: "டோக்கன் நீக்கப்பட்டது",
          msg: "உங்கள் டோக்கன் வெற்றிகரமாக நீக்கப்பட்டது.",
          tip: "💡 முகப்புப் பக்கத்தில் இருந்து நீங்கள் மீண்டும் டோக்கன் பதிவு செய்யலாம்."
        }
      };
      document.getElementById("cancel-title").textContent = strings[currentLang].title;
      document.getElementById("cancel-msg").textContent = strings[currentLang].msg;
      document.getElementById("cancel-tip").textContent = strings[currentLang].tip;
    }
    updateLang();
  </script>
</body>
</html>
