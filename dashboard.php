<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

$token_query = $conn->prepare("SELECT id, notified FROM tokens WHERE user_id = ? AND status = 'waiting' ORDER BY id DESC LIMIT 1");
$token_query->bind_param("i", $user_id);
$token_query->execute();
$token_result = $token_query->get_result();
$token = $token_result->fetch_assoc();
$token_id = $token['id'] ?? null;
$notified = $token['notified'] ?? 0;

$position_query = $conn->prepare("
  SELECT COUNT(*) AS position 
  FROM tokens 
  WHERE status = 'waiting' 
    AND id < (SELECT id FROM tokens WHERE user_id = ? AND status = 'waiting' ORDER BY id DESC LIMIT 1)");
$position_query->bind_param("i", $user_id);
$position_query->execute();
$position_result = $position_query->get_result();
$position = ($row = $position_result->fetch_assoc()) ? $row['position'] + 1 : 'N/A';

$total_query = $conn->query("SELECT COUNT(*) AS total FROM tokens WHERE status = 'waiting'");
$total = ($total_row = $total_query->fetch_assoc()) ? $total_row['total'] : 0;

$avg_time = file_exists('../avg_time.txt') ? intval(file_get_contents('../avg_time.txt')) : 5;
$estimated_wait = is_numeric($position) ? ($position - 1) * $avg_time : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard - QueuLess</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style> 
    :root {
      --primary: #2a2a72;
      --accent: #00c9ff;
      --bg: #f9f9f9;
      --text: #333;
    }
    body.dark {
      --bg: #121212;
      --text: #eee;
      --primary: #90caf9;
    }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 20px;
      transition: background 0.4s, color 0.4s;
    }
    .navbar {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .lang-toggle, .theme-toggle {
      cursor: pointer;
      color: var(--primary);
      font-weight: bold;
    }
    .container {
      max-width: 600px;
      margin: auto;
      background: rgba(255,255,255,0.9);
      backdrop-filter: blur(8px);
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    body.dark .container {
      background: rgba(30,30,30,0.85);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: var(--primary);
    }
    .meter-circle {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      background: conic-gradient(var(--primary) calc(var(--progress, 0) * 1%), #ddd 0);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      font-weight: bold;
      color: white;
      margin: 20px auto;
      position: relative;
    }
    .meter-circle::after {
      content: "You";
      position: absolute;
      bottom: 8px;
      font-size: 14px;
      color: var(--text);
    }
    .info-box {
      background: #e0f7fa;
      padding: 15px;
      border-radius: 10px;
      margin: 10px 0;
      font-size: 16px;
    }
    body.dark .info-box {
      background: #004d40;
      color: #e0f7fa;
    }
    .button {
      display: block;
      text-align: center;
      padding: 12px;
      margin: 10px 0;
      background: var(--primary);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: background 0.3s;
    }
    .button:hover {
      background: #1a1a52;
    }
    .tips {
      font-size: 13px;
      text-align: center;
      margin-top: 10px;
      opacity: 0.7;
    }
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: var(--primary);
      color: white;
      padding: 10px 16px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      display: none;
    }
  </style>
</head>
<body>
  <div class="navbar">
    <a href="profile.php" class="button">👤 Profile</a>
    <a href="../index.php" id="home-link">🏠 Home</a>
    <div>
      <span class="lang-toggle" onclick="switchLang()">🌐</span>
      <span class="theme-toggle" onclick="toggleTheme()">🌃</span>
    </div>
  </div>

  <?php if ($notified == 2): ?>
  <div id="emergencyModal" class="info-box" style="background-color: #ffdddd; color: #b00000;">
    ⚠️ Your position was changed due to an emergency token.
    <audio id="alertSound" autoplay>
      <source src="../user/alert.mp3" type="audio/mpeg">
    </audio>
  </div>
  <?php endif; ?>

  <div class="container">
    <h2 id="dashboard-title">Welcome to Your Dashboard</h2>

    <div class="meter-circle" id="circle" style="--progress:<?= is_numeric($position) && $total > 0 ? round((1 - ($position - 1) / max($total, 1)) * 100) : 0 ?>;">
      <?= $position ?>
    </div>

    <div class="info-box">
      <p id="total-waiting"><strong>Total Waiting:</strong> <?= $total ?></p>
      <p id="your-position"><strong>Your Position:</strong> <span id="position"><?= $position ?></span></p>
      <p id="est-wait"><strong>Est. Wait Time:</strong> <?= is_numeric($estimated_wait) ? "$estimated_wait minutes" : 'N/A' ?></p>
    </div>

    <a href="book_token.php" class="button" id="book-token">🎫 Book Token</a>
    <a href="cancel_token.php" class="button" id="cancel-token">❌ Cancel Token</a>
    <a href="queue_status.php" class="button" id="queue-status">📊 Queue Status</a>
    <a href="feedback.php" class="button" id="feedback-btn">📝 Give Feedback</a>
    <a href="logout.php" class="button" id="logout-btn">🚪 Logout</a>

    <div class="tips" id="tip">💡 Use browser refresh to update your position.</div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      updateLang();
      <?php if ($notified == 2): ?>
      const alertSound = document.getElementById("alertSound");
      if (alertSound) alertSound.play();
      const speakAlert = new SpeechSynthesisUtterance("Your position was changed due to an emergency token.");
      speechSynthesis.speak(speakAlert);
      <?php endif; ?>
    });

    function toggleTheme() {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    }
    if (localStorage.getItem('theme') === 'dark') {
      document.body.classList.add('dark');
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
          title: "Welcome to Your Dashboard",
          book: "🎫 Book Token",
          cancel: "❌ Cancel Token",
          status: "📊 Queue Status",
          feedback: "📝 Give Feedback",
          logout: "🚪 Logout",
          tip: "💡 Use browser refresh to update your position.",
          totalWaiting: "Total Waiting:",
          yourPosition: "Your Position:",
          estWait: "Est. Wait Time:",
          home: "🏠 Home"
        },
        ta: {
          title: "உங்கள் முகப்புப் பக்கம் வரவேற்கிறீருக்கிறாக்",
          book: "🎫 டோக்கன் பதிப்பு செய்யவும்",
          cancel: "❌ டோக்கனை ரத்துசெய்யவும்",
          status: "📊 வரிசை நிலையைப் பார்வையிடவும்",
          feedback: "📝 கருத்துகளை பகிரவும்",
          logout: "🚪 வெளியேறு",
          tip: "💡 உங்கள் நிலையைப் புதுப்பிக்க Refresh ஐ கிளிக் செய்யவும்.",
          totalWaiting: "மொத்த காத்திருப்பு:",
          yourPosition: "உங்கள் நிலை:",
          estWait: "முன்னறிவு காத்திருப்பு நேரம்:",
          home: "🏠 முகப்பு"
        }
      };
      const s = strings[currentLang];
      document.getElementById("dashboard-title").textContent = s.title;
      document.getElementById("book-token").textContent = s.book;
      document.getElementById("cancel-token").textContent = s.cancel;
      document.getElementById("queue-status").textContent = s.status;
      document.getElementById("feedback-btn").textContent = s.feedback;
      document.getElementById("logout-btn").textContent = s.logout;
      document.getElementById("tip").textContent = s.tip;
      document.getElementById("total-waiting").querySelector("strong").textContent = s.totalWaiting;
      document.getElementById("your-position").querySelector("strong").textContent = s.yourPosition;
      document.getElementById("est-wait").querySelector("strong").textContent = s.estWait;
      document.getElementById("home-link").textContent = s.home;
    }
  </script>
</body>
</html>

<?php
if ($notified == 2 && $token_id) {
  $reset = $conn->prepare("UPDATE tokens SET notified = 0 WHERE id = ?");
  $reset->bind_param("i", $token_id);
  $reset->execute();
}
?>
