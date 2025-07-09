<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "../includes/db_connect.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

$user_info_query = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_info_query->bind_param("i", $user_id);
$user_info_query->execute();
$user_info_result = $user_info_query->get_result();
$user_info = $user_info_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - QueuLess</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 20px;
      background: #f4f6f8;
      color: #333;
    }
    .navbar {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
    }
    .navbar .button {
      margin: 0;
    }
    .container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #2a2a72;
    }
    .info {
      margin-top: 20px;
    }
    .info p {
      margin: 10px 0;
      font-size: 16px;
    }
    .button {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      background: #2a2a72;
      color: white;
      text-decoration: none;
      border-radius: 6px;
    }
    .visit-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .visit-table th, .visit-table td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: left;
    }
    .visit-table th {
      background: #f0f0f0;
    }
    .pdf-button {
      margin-top: 10px;
      background: #00b894;
    }
  </style>
  <script>
    function printProfile() {
      window.print();
    }
  </script>
</head>
<body>
  <div class="navbar">
    <a href="dashboard.php" class="button">🔙 Back to Dashboard</a>
    <a href="#" onclick="printProfile()" class="button pdf-button">📄 Download PDF</a>
  </div>
  <div class="container">
    <h2>User Profile</h2>
    <div class="info">
      <p><strong>Name:</strong> <?= htmlspecialchars($user_info['name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($user_info['email']) ?></p>
    </div>

    <h3>Visit History</h3>
    <table class="visit-table">
      <tr>
        <th>Date</th>
        <th>Token Number</th>
        <th>Status</th>
      </tr>
      <?php
      $visit_query = $conn->prepare("SELECT created_at, id, status FROM tokens WHERE user_id = ? ORDER BY created_at DESC");
      $visit_query->bind_param("i", $user_id);
      $visit_query->execute();
      $visit_result = $visit_query->get_result();
      while ($row = $visit_result->fetch_assoc()): ?>
        <tr>
          <td><?= date("d-m-Y h:i A", strtotime($row['created_at'])) ?></td>
          <td><?= htmlspecialchars($row['id']) ?></td>
          <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</body>
</html>
