<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
require_once "../includes/db_connect.php";

if (!isset($_SESSION['staff_id'])) {
  header("Location: login.php");
  exit();
}

$selected_date = $_GET['selected_date'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$from_time = $_GET['from_time'] ?? '';
$to_time = $_GET['to_time'] ?? '';

if (!$selected_date) {
  echo "Date is required.";
  exit();
}

$where_clause = "DATE(t.created_at) = ?";
$params = [$selected_date];
$types = "s";

if ($status_filter !== 'all') {
  $where_clause .= " AND t.status = ?";
  $params[] = $status_filter;
  $types .= "s";
}

if ($from_time && $to_time) {
  $from_datetime = "$selected_date $from_time";
  $to_datetime = "$selected_date $to_time";
  $where_clause .= " AND t.created_at BETWEEN ? AND ?";
  $params[] = $from_datetime;
  $params[] = $to_datetime;
  $types .= "ss";
}

$query = "SELECT u.name, u.email, t.status, t.created_at FROM tokens t JOIN users u ON t.user_id = u.id WHERE $where_clause ORDER BY t.created_at ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Tokens on <?= htmlspecialchars($selected_date) ?></title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f3f4f6;
      padding: 30px;
      color: #333;
    }
    h2 {
      text-align: center;
      color: #2a2a72;
      font-size: 30px;
    }
    form {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 30px;
    }
    input, select, button {
      padding: 8px 12px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    button {
      background: #2a2a72;
      color: white;
      cursor: pointer;
    }
    button:hover {
      background: #1d1d4f;
    }
    table {
      width: 90%;
      margin: 0 auto 20px auto;
      border-collapse: collapse;
      background: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 14px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #2a2a72;
      color: white;
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
    }
    .export-btn {
      background: #4caf50;
      margin: 0 8px;
    }
    .pdf-btn {
      background: #f44336;
    }
    .btn-group {
      text-align: center;
      margin-bottom: 30px;
    }
    .no-data {
      text-align: center;
      color: #888;
      font-size: 18px;
      margin-top: 30px;
    }
  </style>
</head>
<body>
  <a href="../index.php" class="back-btn">🏠 Home</a>
  <a href="staff_dashboard.php" class="back-btn">← Back to Dashboard</a>
  <h2>📅 Tokens on <?= htmlspecialchars(date("d-m-Y", strtotime($selected_date))) ?></h2>

  <form method="GET">
    <input type="date" name="selected_date" value="<?= $selected_date ?>" required>
    <input type="time" name="from_time" value="<?= $from_time ?>">
    <input type="time" name="to_time" value="<?= $to_time ?>">
    <select name="status">
      <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
      <option value="waiting" <?= $status_filter === 'waiting' ? 'selected' : '' ?>>Waiting</option>
      <option value="served" <?= $status_filter === 'served' ? 'selected' : '' ?>>Served</option>
      <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>
    <button type="submit">🔍 Filter</button>
  </form>

  <?php if ($result->num_rows > 0): ?>
    <div class="btn-group">
      <button onclick="exportToExcel()" class="export-btn">📁 Export to Excel</button>
      <button onclick="window.print()" class="pdf-btn">🧾 Print as PDF</button>
    </div>
    <table id="exportTable">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Status</th>
          <th>Booked At</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= date("d-m-Y h:i A", strtotime($row['created_at'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="no-data">No tokens found for the given filters.</div>
  <?php endif; ?>

  <script>
    function exportToExcel() {
      const table = document.getElementById("exportTable").outerHTML;
      const data = 'data:application/vnd.ms-excel,' + encodeURIComponent(table);
      const a = document.createElement('a');
      a.href = data;
      a.download = 'filtered_tokens.xls';
      a.click();
    }
  </script>
</body>
</html>
