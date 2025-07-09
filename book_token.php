<?php
session_start();
require_once "../includes/db_connect.php";
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
$userId = $_SESSION['user_id'];

$justBooked = false;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $priority = isset($_POST['priority']) ? 1 : 0;

  // Check if user already has a token
  $check = $conn->prepare("SELECT id FROM tokens WHERE user_id = ? AND status = 'waiting'");
  $check->bind_param("i", $userId);
  $check->execute();
  $result = $check->get_result();
  if ($result->num_rows === 0) {
    $insert = $conn->prepare("INSERT INTO tokens (user_id, priority) VALUES (?, ?)");
    $insert->bind_param("ii", $userId, $priority);
    $insert->execute();
    $message = "🎉 Token booked successfully!";
    $justBooked = true;
  } else {
    $message = "⚠️ You already have a waiting token.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Book Token - QueuLess</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #2a2a72;
      --accent: #00c9ff;
      --bg: #f0f0f0;
      --text: #333;
    }
    body.dark {
      --bg: #121212;
      --text: #eee;
    }
    body {
      margin: 0;
      padding: 20px;
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text);
      transition: all 0.4s ease;
    }
    .navbar {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .btn, .lang-toggle, .theme-toggle {
      padding: 10px 16px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      margin-right: 8px;
      cursor: pointer;
      font-weight: bold;
      text-decoration: none;
    }
    .container {
      max-width: 500px;
      margin: auto;
      background: rgba(255, 255, 255, 0.9);
      padding: 30px;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      text-align: center;
    }
    body.dark .container {
      background: rgba(30,30,30,0.9);
    }
    h2 {
      font-size: 26px;
      margin-bottom: 20px;
      color: var(--primary);
    }
    .message {
      font-size: 18px;
      background: #e0f7fa;
      padding: 15px;
      border-radius: 10px;
      color: #006064;
      animation: fadeIn 0.5s ease-in-out;
    }
    body.dark .message {
      background: #004d40;
      color: #e0f7fa;
    }
    .tip {
      font-size: 14px;
      margin-top: 10px;
      opacity: 0.6;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(10px);}
      to {opacity: 1; transform: translateY(0);}
    }

    form {
      margin-top: 20px;
    }

    .priority-label {
      font-size: 16px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .priority-label input[type="checkbox"] {
      transform: scale(1.4);
      accent-color: red;
    }

    input[type="submit"] {
      padding: 12px 24px;
      border: none;
      border-radius: 10px;
      background: var(--primary);
      color: white;
      font-weight: bold;
      cursor: pointer;
    }

    .confetti {
      position: fixed;
      top: 0; left: 0;
      width: 100vw;
      height: 100vh;
      pointer-events: none;
      z-index: 999;
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
      <span class="lang-toggle btn" onclick="switchLang()">🌐</span>
      <span class="theme-toggle btn" onclick="toggleTheme()">🌓</span>
    </div>
  </div>

  <div class="container">
    <h2 id="book-heading">Token Booking</h2>
    <p id="book-status" class="message"><?= $message ?></p>

    <?php if (!$justBooked): ?>
    <form method="POST" action="">
      <label class="priority-label">
        <input type="checkbox" name="priority" value="1">
        <span>🚨 Emergency Booking</span>
      </label>
      <input type="submit" value="🎫 Book Token Now">
    </form>
    <?php endif; ?>

    <div class="tip" id="extra-tip">⏳ Please wait patiently for your turn.</div>
  </div>

  <?php if ($justBooked): ?>
    <canvas class="confetti" id="confetti-canvas"></canvas>
  <?php endif; ?>

  <script>
    // Theme Toggle
    function toggleTheme() {
      document.body.classList.toggle("dark");
      localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");
    }
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
    }

    // Language Switch
    let currentLang = localStorage.getItem('lang') || 'en';
    function switchLang() {
      currentLang = currentLang === 'en' ? 'ta' : 'en';
      localStorage.setItem('lang', currentLang);
      updateLang();
    }

    function updateLang() {
      const strings = {
        en: {
          heading: "Token Booking",
          tip: "⏳ Please wait patiently for your turn."
        },
        ta: {
          heading: "டோக்கன் பதிவு",
          tip: "⏳ உங்கள் வரிசையை பொறுமையாக காத்திருக்கவும்."
        }
      };
      document.getElementById("book-heading").textContent = strings[currentLang].heading;
      document.getElementById("extra-tip").textContent = strings[currentLang].tip;
    }
    updateLang();

    // Confetti
    <?php if ($justBooked): ?>
    const confetti = document.getElementById('confetti-canvas');
    const ctx = confetti.getContext('2d');
    confetti.width = window.innerWidth;
    confetti.height = window.innerHeight;
    const particles = Array.from({length: 150}, () => ({
      x: Math.random() * confetti.width,
      y: Math.random() * confetti.height - confetti.height,
      r: Math.random() * 6 + 4,
      d: Math.random() * 5 + 1,
      color: `hsl(${Math.random() * 360}, 70%, 60%)`
    }));
    function draw() {
      ctx.clearRect(0, 0, confetti.width, confetti.height);
      for (let p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
        p.y += p.d;
        if (p.y > confetti.height) p.y = -10;
      }
      requestAnimationFrame(draw);
    }
    draw();
    <?php endif; ?>
  </script>
</body>
</html>
