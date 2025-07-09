<?php
session_start();
require_once "../includes/db_connect.php";

if (!isset($_SESSION['staff_id'])) {
  header("Location: login.php");
  exit();
}

$query = "SELECT user_name, rating, comment, submitted_at FROM feedback ORDER BY submitted_at DESC";
$result = $conn->query($query);

if (!$result) {
  die("Query Failed: " . $conn->error);
}

$feedbacks = [];
$ratings = [];
while ($row = $result->fetch_assoc()) {
    $feedbacks[] = $row;
    if ($row['rating']) $ratings[] = $row['rating'];
}

$total_feedbacks = count($feedbacks);
$avg_rating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : "N/A";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Feedback Analytics - QueuLess</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
      margin: 0;
      padding: 30px;
      color: #333;
    }
    h2 {
      text-align: center;
      color: #2a2a72;
      font-size: 36px;
      margin-bottom: 30px;
      text-shadow: 1px 1px #fff;
    }
    .stats {
      display: flex;
      justify-content: center;
      gap: 40px;
      margin-bottom: 40px;
      flex-wrap: wrap;
    }
    .card {
      background: #ffffffdd;
      padding: 25px 30px;
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
      min-width: 200px;
    }
    .card h3 {
      margin: 0;
      font-size: 20px;
      color: #4b0082;
    }
    .card p {
      font-size: 26px;
      font-weight: bold;
      margin-top: 10px;
      color: #111;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 16px;
      text-align: left;
    }
    th {
      background: #2a2a72;
      color: white;
      font-size: 16px;
    }
    tr:nth-child(even) {
      background: #f9f9f9;
    }
    .back-btn {
      display: inline-block;
      margin-bottom: 20px;
      background-color: #2a2a72;
      color: white;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 16px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }
    .no-feedback {
      text-align: center;
      color: #666;
      font-size: 18px;
      margin-top: 30px;
    }
    .rating-stars {
      color: #f4c430;
    }
    #refreshTime {
      text-align: center;
      font-size: 14px;
      color: #555;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
  <h2>💬 User Feedback Analytics</h2>

  <div class="stats">
    <div class="card">
      <h3>Total Feedbacks</h3>
      <p><?= $total_feedbacks ?></p>
    </div>
    <div class="card">
      <h3>Average Rating</h3>
      <p><?= $avg_rating ?>/5</p>
    </div>
  </div>

  <?php if ($total_feedbacks > 0): ?>
  <table>
    <thead>
      <tr>
        <th>User Name</th>
        <th>Rating</th>
        <th>Comment</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($feedbacks as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['user_name']) ?></td>
          <td class="rating-stars">
            <?php for ($i = 0; $i < $row['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
          </td>
          <td><?= htmlspecialchars($row['comment']) ?: '—' ?></td>
          <td><?= date("d-m-Y h:i A", strtotime($row['submitted_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="no-feedback">No feedback has been submitted yet.</p>
  <?php endif; ?>

  <p id="refreshTime">🔄 Last refreshed at: Loading...</p>

  <script>
    setInterval(() => {
      location.reload();
    }, 5000);

    function updateRefreshTime() {
      const now = new Date();
      const timeString = now.toLocaleTimeString();
      document.getElementById("refreshTime").innerText = "🔄 Last refreshed at: " + timeString;
    }
    updateRefreshTime();
  </script>
</body>
</html>
