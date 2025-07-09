<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "../includes/db_connect.php";

if (!isset($_SESSION['staff_id'])) {
  header("Location: login.php");
  exit();
}

$avg_time = (file_exists('../avg_time.txt')) ? intval(file_get_contents('../avg_time.txt')) : 5;

$waiting_result = $conn->query("SELECT COUNT(*) AS total_waiting FROM tokens WHERE status = 'waiting'");
$total_waiting = ($waiting_result && $waiting_result->num_rows > 0) ? $waiting_result->fetch_assoc()['total_waiting'] : 0;

$total_time = round($avg_time * $total_waiting, 2);

$last_served = $conn->query("SELECT u.name, t.updated_at FROM tokens t JOIN users u ON t.user_id = u.id WHERE t.status = 'served' AND t.updated_at IS NOT NULL ORDER BY t.updated_at DESC LIMIT 1");
$last_name = "N/A";
$last_time = "-";
if ($last_served && $last_served->num_rows > 0) {
  $last_row = $last_served->fetch_assoc();
  $last_name = $last_row['name'];
  $last_time = date("d-m-Y h:i A", strtotime($last_row['updated_at']));
}

$waiting_users = $conn->query("SELECT u.name, u.email, t.created_at FROM tokens t JOIN users u ON t.user_id = u.id WHERE t.status = 'waiting' ORDER BY t.created_at ASC");

if (!isset($_SESSION['graph_data'])) $_SESSION['graph_data'] = [];
array_push($_SESSION['graph_data'], $total_waiting);
if (count($_SESSION['graph_data']) > 10) array_shift($_SESSION['graph_data']);
$graph_data = $_SESSION['graph_data'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Dashboard - QueuLess</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --bg: #f3f4f6;
      --text: #333;
      --card: #ffffff;
      --highlight: #2a2a72;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--bg);
      color: var(--text);
      margin: 0;
      padding: 20px;
    }

    h2 {
      text-align: center;
      font-size: 36px;
      color: var(--highlight);
    }

    .info-box {
      background: var(--card);
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      padding: 30px;
      max-width: 800px;
      margin: 0 auto 30px auto;
      text-align: center;
    }

    .info-box p {
      font-size: 20px;
      margin: 10px 0;
    }

    .highlight {
      font-weight: bold;
      color: var(--highlight);
    }

    table {
      width: 90%;
      margin: 0 auto 30px auto;
      border-collapse: collapse;
      border-radius: 10px;
      overflow: hidden;
      background: var(--card);
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 14px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: var(--highlight);
      color: white;
    }

    .export-btn, .analytics-btn {
      padding: 10px 20px;
      background: #4caf50;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin: 10px 8px 0 8px;
    }

    .analytics-btn {
      background: #3f51b5;
    }

    .export-btn:hover,
    .analytics-btn:hover {
      opacity: 0.9;
    }

    .time-now {
      text-align: center;
      font-size: 14px;
      color: #777;
    }

    canvas {
      max-width: 700px;
      margin: 0 auto;
      display: block;
    }

    .suggestion {
      text-align: center;
      font-size: 18px;
      padding: 12px;
      margin: 20px auto;
      background: #fffae6;
      border-left: 6px solid orange;
      max-width: 700px;
      border-radius: 8px;
    }

    .back-btn {
      display: inline-block;
      margin-bottom: 20px;
      background-color: #2a2a72;
      color: white;
      padding: 10px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 16px;
      transition: background 0.3s;
    }

    .back-btn:hover {
      background-color: #1d1d4f;
    }

    .calendar-form {
      margin-top: 20px;
    }

    .calendar-form input[type="date"] {
      padding: 8px 14px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    .calendar-form button {
      padding: 10px 20px;
      background: #ff9800;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      margin-left: 8px;
      cursor: pointer;
    }

    .calendar-form button:hover {
      background-color: #e68900;
    }
  </style>
</head>
<body>
<a href="javascript:history.back()" class="back-btn">← Back</a>
<a href="../index.php" class="back-btn">🏠 Home</a>
<h2>📊 Admin Dashboard</h2>

<div class="info-box">
  <p>Total Waiting Patients: <span class="highlight" id="waitCount"><?= $total_waiting ?></span></p>
  <p>Avg. Time per Token: <span class="highlight"><?= $avg_time ?> mins</span></p>
  <p>Estimated Completion Time: <span class="highlight"><?= $total_time ?> mins</span></p>
  <p>Last Served: <span class="highlight"><?= htmlspecialchars($last_name) ?></span> at <?= $last_time ?></p>

  <!-- Buttons -->
  <button onclick="exportToExcel()" class="export-btn">📁 Export to Excel</button>
  <button onclick="location.href='analytics.php'" class="analytics-btn">📈 View Analytics</button>

  <!-- Calendar Form -->
  <form action="view_tokens_by_date.php" method="GET" class="calendar-form">
    <input type="date" name="selected_date" required>
    <button type="submit">📅 View Date-wise Tokens</button>
  </form>
</div>

<div class="suggestion" id="suggestBox" style="display:none;"></div>

<canvas id="queueChart" height="100"></canvas>

<table id="tokenTable">
  <thead>
    <tr><th>Name</th><th>Email</th><th>Token Booked At</th></tr>
  </thead>
  <tbody>
    <?php while($row = $waiting_users->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= date("d-m-Y h:i A", strtotime($row['created_at'])) ?></td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<div class="time-now">⏱️ Last refreshed at <?= date("h:i:s A") ?></div>

<audio id="beep" src="https://www.soundjay.com/button/sounds/beep-07.mp3" preload="auto"></audio>

<script>
  setInterval(() => location.reload(), 15000);

  function exportToExcel() {
    const table = document.getElementById("tokenTable").outerHTML;
    const html = table.replace(/ /g, '%20');
    const a = document.createElement('a');
    a.href = 'data:application/vnd.ms-excel,' + html;
    a.download = 'waiting_patients.xls';
    a.click();
  }

  const waitCount = parseInt(document.getElementById("waitCount").innerText);
  if (waitCount > 5) {
    document.getElementById("beep").play();
  }

  const suggest = document.getElementById("suggestBox");
  if (waitCount > 10) {
    suggest.innerText = "⚠️ High queue! Consider assigning additional staff or increasing speed.";
    suggest.style.display = 'block';
  }

  const ctx = document.getElementById('queueChart').getContext('2d');
  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: [<?= implode(',', array_map(fn($i) => "'T-".(10 - $i)."'", array_keys($graph_data))) ?>],
      datasets: [{
        label: 'Queue Size Over Time',
        data: [<?= implode(',', $graph_data) ?>],
        borderColor: '#2a2a72',
        backgroundColor: 'rgba(42,42,114,0.1)',
        tension: 0.3
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } },
      }
    }
  });
</script>
</body>
</html>
