<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['staff_id'])) {
  header("Location: login.php");
  exit();
}

$stmt = $conn->prepare("SELECT t.id AS token_id, u.name, t.priority, t.created_at 
                        FROM tokens t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.status = 'waiting' 
                        ORDER BY t.priority DESC, t.created_at ASC 
                        LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

$calledMessage = "";
$userName = "";
$hasToken = false;

if ($row = $result->fetch_assoc()) {
  $tokenId = $row['token_id'];
  $userName = htmlspecialchars($row['name']);
  $isPriority = $row['priority'] == 1;
  $tokenTime = $row['created_at'];

  file_put_contents('../current_token.txt', $userName);

  $update = $conn->prepare("UPDATE tokens SET status = 'called', notified = 1 WHERE id = ?");
  $update->bind_param("i", $tokenId);
  $update->execute();

  if ($isPriority) {
    $bypass = $conn->prepare("UPDATE tokens SET notified = 2 
                              WHERE status = 'waiting' AND priority = 0 AND created_at < ?");
    $bypass->bind_param("s", $tokenTime);
    $bypass->execute();
  }

  $hasToken = true;
  $calledMessage = "<div class='call-box'>
    <h2>🔊 Now Calling</h2>
    <p><strong>$userName</strong> " . ($isPriority ? "<span style='color:red;'>(🚨 Emergency)</span>" : "") . " — please proceed to the counter.</p>
  </div>";
} else {
  $calledMessage = "<div class='call-box empty'>
    <h2>😔 No Waiting Tokens</h2>
    <p>Please try again in a moment.</p>
  </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Call Next - QueuLess</title>
  <style>
    :root {
      --bg: linear-gradient(135deg, #fdfbfb, #ebedee);
      --card-bg: rgba(255, 255, 255, 0.8);
      --text: #222;
      --highlight: #e91e63;
      --glow: 0 0 10px #ccc;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      overflow: hidden;
      position: relative;
    }

    .call-box {
      background: var(--card-bg);
      padding: 40px;
      border-radius: 20px;
      backdrop-filter: blur(12px);
      text-align: center;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
      animation: fadeIn 1s ease-in-out;
    }

    .call-box h2 {
      font-size: 32px;
      margin-bottom: 12px;
    }

    .call-box p {
      font-size: 22px;
      font-weight: bold;
      margin: 8px 0;
      color: var(--highlight);
    }

    .call-box.empty {
      background: rgba(255, 0, 0, 0.1);
      color: #b71c1c;
    }

    .btn {
      margin-top: 25px;
      display: inline-block;
      padding: 12px 25px;
      font-size: 16px;
      background: #3949ab;
      color: white;
      border-radius: 10px;
      text-decoration: none;
      transition: 0.3s ease;
      box-shadow: var(--glow);
    }

    .btn:hover {
      background: #1a237e;
      transform: scale(1.05);
    }

    #clock {
      margin-top: 20px;
      font-size: 20px;
      color: #555;
      text-shadow: 0 0 5px #eee;
      animation: pulse 1s infinite alternate;
    }

    canvas#confetti {
      position: fixed;
      pointer-events: none;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
      from { opacity: 0.6; }
      to { opacity: 1; }
    }
  </style>
</head>
<body>

<canvas id="confetti"></canvas>
<?= $calledMessage ?>
<div id="clock"></div>
<br>
<a href="dashboard.php" class="btn">🏠 Back to Dashboard</a>
<a href="call_next.php" class="btn">🔁 Call Again</a>

<!-- 🔊 Audio Alert -->
<audio id="alertSound" src="voice.mp3" preload="auto"></audio>

<?php if ($hasToken): ?>
<script>
  // 🎉 Confetti animation
  const canvas = document.getElementById('confetti');
  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  const colors = ["#f44336", "#4caf50", "#2196f3", "#ffeb3b", "#9c27b0"];
  let particles = [];

  function createParticle() {
    return {
      x: Math.random() * canvas.width,
      y: -10,
      radius: Math.random() * 6 + 2,
      color: colors[Math.floor(Math.random() * colors.length)],
      speed: Math.random() * 2 + 2,
      dx: Math.random() * 2 - 1
    };
  }

  function drawConfetti() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
      ctx.fillStyle = p.color;
      ctx.fill();
      p.y += p.speed;
      p.x += p.dx;
    });
    particles = particles.filter(p => p.y < canvas.height);
    while (particles.length < 120) {
      particles.push(createParticle());
    }
    requestAnimationFrame(drawConfetti);
  }
  drawConfetti();

  // 🗣️ Text-to-Speech
  const msg = new SpeechSynthesisUtterance("<?= $userName ?>, please proceed to the counter.");
  msg.lang = "en-IN";
  msg.pitch = 1;
  msg.rate = 1;
  speechSynthesis.speak(msg);

  // 🔊 Play Audio
  document.getElementById('alertSound').play();
</script>
<?php endif; ?>

<script>
  // ⏰ Clock
  function updateClock() {
    const now = new Date();
    document.getElementById("clock").textContent = "⏱️ Current time: " + now.toLocaleTimeString();
  }
  setInterval(updateClock, 1000);
  updateClock();
</script>

</body>
</html>
