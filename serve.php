<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['staff_id'])) {
  header("Location: login.php");
  exit();
}

// Get the next called token along with user name and ID
$stmt = $conn->prepare("
  SELECT t.id AS token_id, u.name 
  FROM tokens t 
  JOIN users u ON t.user_id = u.id 
  WHERE t.status = 'called' 
  ORDER BY t.created_at ASC 
  LIMIT 1
");
$stmt->execute();
$result = $stmt->get_result();

$userName = null;
$position = null;

if ($row = $result->fetch_assoc()) {
  $tokenId = $row['token_id'];
  $userName = htmlspecialchars($row['name']);

  // Get position in queue
  $positionStmt = $conn->prepare("SELECT COUNT(*) AS position FROM tokens WHERE status = 'waiting' AND id < ?");
  $positionStmt->bind_param("i", $tokenId);
  $positionStmt->execute();
  $positionResult = $positionStmt->get_result();
  $positionRow = $positionResult->fetch_assoc();
  $position = $positionRow['position'] + 1;

  // Mark as served
  $update = $conn->prepare("UPDATE tokens SET status = 'served', updated_at = NOW() WHERE id = ?");
  $update->bind_param("i", $tokenId);
  $update->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Token Served</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background: linear-gradient(135deg, #fceabb, #f8b500);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      color: #333;
      overflow: hidden;
    }

    .message-box {
      background: white;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      text-align: center;
      animation: fadeIn 1s ease-in-out;
      position: relative;
      z-index: 10;
    }

    .success {
      color: #28a745;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .error {
      color: #dc3545;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .btn {
      display: inline-block;
      padding: 12px 28px;
      margin-top: 20px;
      font-size: 16px;
      background: #007bff;
      color: white;
      text-decoration: none;
      border-radius: 30px;
      transition: all 0.3s ease;
    }

    .btn:hover {
      background: #0056b3;
      transform: scale(1.05);
    }

    .time {
      font-size: 18px;
      color: #444;
      margin-top: 10px;
    }

    canvas#confetti {
      position: fixed;
      pointer-events: none;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<canvas id="confetti"></canvas>

<div class="message-box">
  <?php if ($userName): ?>
    <div class="success"><strong><?= $userName ?> – #<?= $position ?></strong> is served ✅</div>
    <div class="time" id="clock">Loading time...</div>
    <a href="dashboard.php" class="btn">🔙 Back to Dashboard</a>
  <?php else: ?>
    <div class="error">No tokens to serve.</div>
    <a href="dashboard.php" class="btn">🔙 Back to Dashboard</a>
  <?php endif; ?>
</div>

<script>
  // Confetti animation 🎉
  const canvas = document.getElementById("confetti");
  const ctx = canvas.getContext("2d");
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  let pieces = [];
  const colors = ["#ffc107", "#28a745", "#17a2b8", "#dc3545", "#007bff"];

  function createPiece() {
    return {
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height - canvas.height,
      radius: Math.random() * 6 + 4,
      color: colors[Math.floor(Math.random() * colors.length)],
      velocity: Math.random() * 3 + 2
    };
  }

  function updateConfetti() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    pieces.forEach(p => {
      p.y += p.velocity;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
      ctx.fillStyle = p.color;
      ctx.fill();
    });
    pieces = pieces.filter(p => p.y < canvas.height);
    while (pieces.length < 150) pieces.push(createPiece());
    requestAnimationFrame(updateConfetti);
  }

  <?php if ($userName): ?>
    // Only trigger confetti and voice if token served
    updateConfetti();

    // Text-to-speech 🎙️
    const msg = new SpeechSynthesisUtterance("<?= $userName ?>, number <?= $position ?> is served.");
    msg.lang = 'en-IN';
    msg.rate = 1;
    speechSynthesis.speak(msg);
  <?php endif; ?>

  // Live clock 🕒
  function updateClock() {
    const now = new Date();
    document.getElementById("clock").textContent = now.toLocaleTimeString();
  }
  setInterval(updateClock, 1000);
  updateClock();
</script>

</body>
</html>
