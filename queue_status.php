<?php
session_start();
require_once "../includes/db_connect.php";
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, status, created_at FROM tokens WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$token = $result->fetch_assoc();
$status = $token ? ucfirst($token['status']) : 'No Token';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Queue Status</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f9f9f9;
      --card: #ffffff;
      --text: #333;
      --accent: #2a2a72;
    }
    body.dark {
      --bg: #121212;
      --card: #1e1e1e;
      --text: #f0f0f0;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text);
      margin: 0;
      padding: 20px;
      transition: background 0.3s, color 0.3s;
    }
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .btn, .theme-toggle, .lang-toggle {
      background: var(--accent);
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      margin: 5px;
    }
    .container {
      max-width: 500px;
      margin: auto;
      background: var(--card);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: var(--accent);
      margin-bottom: 20px;
    }
    .status-box {
      font-size: 18px;
      text-align: center;
      padding: 15px;
      background: #e0e0ff;
      border-radius: 8px;
      margin-bottom: 10px;
      animation: pulse 1.5s infinite;
    }
    .tip {
      text-align: center;
      font-size: 13px;
      opacity: 0.75;
    }
    body.dark .status-box {
      background: #333366;
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.02); }
      100% { transform: scale(1); }
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
    <h2 id="status-title">Queue Status</h2>
    <div class="status-box" id="status-text">Your current token status: <strong><?= $status ?></strong></div>
    <div class="tip" id="status-tip">
      <?= $status === 'Waiting' ? '⏳ Please wait for your token to be called.' : ($status === 'Served' ? '✅ You have been served.' : 'ℹ️ No active token found.') ?>
    </div>
  </div>

  <script>
    function toggleTheme() {
      document.body.classList.toggle("dark");
      localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");
    }
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
    }

    let currentLang = localStorage.getItem('lang') || 'en';
    function switchLang() {
      currentLang = currentLang === 'en' ? 'ta' : 'en';
      localStorage.setItem('lang', currentLang);
      updateLang();
    }

    function updateLang() {
      const strings = {
        en: {
          title: "Queue Status",
          statusText: "Your current token status: <strong><?= $status ?></strong>",
          tipWaiting: "⏳ Please wait for your token to be called.",
          tipServed: "✅ You have been served.",
          tipNone: "ℹ️ No active token found."
        },
        ta: {
          title: "வரிசை நிலை",
          statusText: "உங்கள் தற்போதைய டோக்கன் நிலை: <strong><?= $status ?></strong>",
          tipWaiting: "⏳ உங்கள் டோக்கன் அழைக்கப்படும் வரை காத்திருக்கவும்.",
          tipServed: "✅ உங்களுக்கு சேவை வழங்கப்பட்டுள்ளது.",
          tipNone: "ℹ️ டோக்கன் பதிவு இல்லை."
        }
      };
      document.getElementById("status-title").textContent = strings[currentLang].title;
      document.getElementById("status-text").innerHTML = strings[currentLang].statusText;

      let tip;
      switch ("<?= $status ?>") {
        case "Waiting": tip = strings[currentLang].tipWaiting; break;
        case "Served": tip = strings[currentLang].tipServed; break;
        default: tip = strings[currentLang].tipNone; break;
      }
      document.getElementById("status-tip").textContent = tip;
    }

    updateLang();
  </script>
</body>
</html>
