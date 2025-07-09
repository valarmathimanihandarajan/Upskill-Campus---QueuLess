<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "../includes/db_connect.php";

if (!isset($_SESSION['staff_id'])) {
  header("Location: login.php");
  exit();
}

$avg_time = file_exists('../avg_time.txt') ? intval(file_get_contents('../avg_time.txt')) : 5;

// 1. Total waiting
$waiting_result = $conn->query("SELECT COUNT(*) AS total_waiting FROM tokens WHERE status = 'waiting'");
$total_waiting = $waiting_result->fetch_assoc()['total_waiting'] ?? 0;
$total_time = $avg_time * $total_waiting;

// 2. Last served
$last_served = $conn->query("SELECT u.name, t.updated_at FROM tokens t JOIN users u ON t.user_id = u.id WHERE t.status = 'served' ORDER BY t.updated_at DESC LIMIT 1");
$last_row = $last_served->fetch_assoc();
$last_name = $last_row['name'] ?? 'N/A';
$last_time = $last_row['updated_at'] ? date("d-m-Y h:i A", strtotime($last_row['updated_at'])) : '-';

// 3. Average serve time today
$today = date('Y-m-d');
$serve_times = [];
$serve_query = $conn->query("SELECT updated_at, created_at FROM tokens WHERE status = 'served' AND DATE(updated_at) = '$today'");
while ($row = $serve_query->fetch_assoc()) {
  $created = strtotime($row['created_at']);
  $served = strtotime($row['updated_at']);
  if ($created && $served && $served > $created) {
    $serve_times[] = ($served - $created);
  }
}
$avg_serve_today = count($serve_times) > 0 ? round(array_sum($serve_times) / count($serve_times) / 60, 2) : 0;

// 4. Tokens Summary
$tokens_issued = $conn->query("SELECT COUNT(*) AS cnt FROM tokens")->fetch_assoc()['cnt'];
$tokens_served = $conn->query("SELECT COUNT(*) AS cnt FROM tokens WHERE status = 'served'")->fetch_assoc()['cnt'];
$tokens_cancelled = $conn->query("SELECT COUNT(*) AS cnt FROM tokens WHERE status = 'cancelled'")->fetch_assoc()['cnt'];
$emergency_tokens = $conn->query("SELECT COUNT(*) AS cnt FROM tokens WHERE priority = 1")->fetch_assoc()['cnt'];
$returning_users_query = $conn->query("
  SELECT COUNT(*) AS cnt FROM (
    SELECT user_id, COUNT(*) AS c 
    FROM tokens 
    WHERE DATE(created_at) = '$today' 
    GROUP BY user_id 
    HAVING c > 1
  ) AS sub
");
$returning_users = $returning_users_query->fetch_assoc()['cnt'] ?? 0;

// 5. Served tokens per last 5 days
$served_daily = [];
for ($i = 4; $i >= 0; $i--) {
  $date = date("Y-m-d", strtotime("-$i days"));
  $label = date("M d", strtotime($date));
  $result = $conn->query("SELECT COUNT(*) AS cnt FROM tokens WHERE status = 'served' AND DATE(updated_at) = '$date'");
  $count = $result->fetch_assoc()['cnt'] ?? 0;
  $served_daily[$label] = $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Analytics - QueuLess</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 20px;
      background: linear-gradient(to right, #f9f9f9, #e0eafc);
      color: #333;
    }
    h2 {
      text-align: center;
      font-size: 36px;
      color: #2a2a72;
    }
    .card {
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      max-width: 1000px;
      margin: 20px auto;
    }
    .card p {
      font-size: 18px;
      margin: 10px 0;
    }
    .highlight {
      color: #2a2a72;
      font-weight: bold;
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

    canvas {
      max-width: 100%;
      margin: 20px auto;
      display: block;
    }
    .chart-controls {
      text-align: center;
      margin-bottom: 10px;
    }
    .chart-controls button {
      margin: 5px;
      padding: 8px 16px;
      font-size: 14px;
      border: none;
      border-radius: 6px;
      background: #2a2a72;
      color: white;
      cursor: pointer;
    }
    .chart-controls button:hover {
      background: #1d1d4f;
    }
  </style>
</head>
<body>

<a href="javascript:history.back()" class="back-btn">← Back</a>
<h2>📈 Analytics Dashboard</h2>

<div class="card">
  <p>📥 <strong>Tokens Issued:</strong> <span class="highlight"><?= $tokens_issued ?></span></p>
  <p>✅ <strong>Tokens Served:</strong> <span class="highlight"><?= $tokens_served ?></span></p>
  <p>❌ <strong>Tokens Cancelled:</strong> <span class="highlight"><?= $tokens_cancelled ?></span></p>
  <p>⚠️ <strong>Emergency Tokens:</strong> <span class="highlight"><?= $emergency_tokens ?></span></p>
  <p>⏱️ <strong>Avg Serve Time Today:</strong> <span class="highlight"><?= $avg_serve_today ?> mins</span></p>
  <p>🔁 <strong>Returning Users Today:</strong> <span class="highlight"><?= $returning_users ?></span></p>
  <p>🕒 <strong>Total Waiting:</strong> <span class="highlight"><?= $total_waiting ?></span></p>
  <p>📊 <strong>Estimated Completion Time:</strong> <span class="highlight"><?= $total_time ?> mins</span></p>
  <p>🧍‍♂️ <strong>Last Served:</strong> <span class="highlight"><?= $last_name ?></span> at <?= $last_time ?></p>
</div>

<div class="card">
  <div class="chart-controls">
    <button onclick="updateGraphType('bar')">Bar</button>
    <button onclick="updateGraphType('line')">Line</button>
    <button onclick="updateGraphType('pie')">Pie</button>
  </div>
  <canvas id="servedChart"></canvas>
</div>

<script>
let chartType = 'bar';
const labels = <?= json_encode(array_keys($served_daily)) ?>;
const dataValues = <?= json_encode(array_values($served_daily)) ?>;

let ctx = document.getElementById('servedChart').getContext('2d');
let chart = new Chart(ctx, {
  type: chartType,
  data: {
    labels: labels,
    datasets: [{
      label: 'Patients Served (Last 5 Days)',
      data: dataValues,
      backgroundColor: ['#2a2a72', '#0099cc', '#66ccff', '#003f5c', '#7a5195']
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        display: chartType === 'pie',
        position: 'bottom'
      }
    },
    scales: chartType !== 'pie' ? {
      y: {
        beginAtZero: true
      }
    } : {}
  }
});

function updateGraphType(type) {
  chart.destroy();
  chart = new Chart(ctx, {
    type: type,
    data: {
      labels: labels,
      datasets: [{
        label: 'Patients Served (Last 5 Days)',
        data: dataValues,
        backgroundColor: ['#2a2a72', '#0099cc', '#66ccff', '#003f5c', '#7a5195']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: type === 'pie',
          position: 'bottom'
        }
      },
      scales: type !== 'pie' ? {
        y: {
          beginAtZero: true
        }
      } : {}
    }
  });
}
</script>

</body>
</html>
