<?php
session_start();
require_once "../includes/db_connect.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = trim($_POST['user_name']);
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);

    if (!empty($user_name) && is_numeric($rating)) {
        $stmt = $conn->prepare("INSERT INTO feedback (user_name, rating, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $user_name, $rating, $comment);
        if ($stmt->execute()) {
            $success = "Thank you for your feedback!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    } else {
        $error = "Please enter your name and rating.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Feedback - QueuLess</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      background: #fdf6f0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      padding: 30px;
      width: 400px;
      animation: slideUp 0.6s ease;
      text-align: center;
    }

    @keyframes slideUp {
      from {transform: translateY(20px); opacity: 0;}
      to {transform: translateY(0); opacity: 1;}
    }

    h2 {
      margin-bottom: 15px;
      color: #333;
    }

    .stars i {
      font-size: 30px;
      color: #ccc;
      cursor: pointer;
      transition: color 0.3s;
    }

    .stars i.selected {
      color: #f4c430;
    }

    textarea, input[type="text"] {
      width: 100%;
      margin: 10px 0;
      padding: 10px;
      border-radius: 10px;
      border: 1px solid #ccc;
      resize: none;
    }

    button {
      background: #f4c430;
      border: none;
      padding: 10px 20px;
      border-radius: 15px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background: #d1aa27;
    }

    .message {
      margin-top: 10px;
      color: green;
    }

    .error {
      color: red;
    }

    .nav-buttons {
      margin-top: 20px;
    }

    .nav-buttons a {
      margin: 0 10px;
      text-decoration: none;
      padding: 8px 14px;
      background: #eee;
      border-radius: 10px;
      color: #333;
      transition: 0.3s;
    }

    .nav-buttons a:hover {
      background: #f4c430;
      color: #fff;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Rate Your Experience</h2>

    <?php if (isset($success)): ?>
      <div class="message"><?= $success ?></div>
    <?php elseif (isset($error)): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="text" name="user_name" placeholder="Your Name" required>

      <div class="stars" id="stars">
        <i class="fa-regular fa-star" data-value="1"></i>
        <i class="fa-regular fa-star" data-value="2"></i>
        <i class="fa-regular fa-star" data-value="3"></i>
        <i class="fa-regular fa-star" data-value="4"></i>
        <i class="fa-regular fa-star" data-value="5"></i>
      </div>
      <input type="hidden" name="rating" id="rating" required>

      <textarea name="comment" rows="3" placeholder="Optional comment..."></textarea>
      <button type="submit">Submit Feedback</button>
    </form>

    <div class="nav-buttons">
      <a href="javascript:history.back()">← Back</a>
      <a href="dashboard.php">🏠 Home</a>
    </div>
  </div>

  <script>
    const stars = document.querySelectorAll('.stars i');
    const ratingInput = document.getElementById('rating');

    stars.forEach(star => {
      star.addEventListener('click', () => {
        let rating = star.dataset.value;
        ratingInput.value = rating;
        stars.forEach(s => {
          s.classList.toggle('selected', s.dataset.value <= rating);
        });
      });
    });
  </script>
</body>
</html>
