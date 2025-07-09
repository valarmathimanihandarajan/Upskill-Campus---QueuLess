<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>QueuLess – Smart Queue System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: linear-gradient(135deg, #e0f7fa, #fce4ec);
      --card: rgba(255, 255, 255, 0.8);
      --text: #333;
      --btn-bg: #2a2a72;
      --btn-hover: #1a1a52;
    }

    body.dark {
      --bg: linear-gradient(135deg, #121212, #1f1f1f);
      --card: rgba(40, 40, 40, 0.8);
      --text: #eee;
      --btn-bg: #90caf9;
      --btn-hover: #64b5f6;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Montserrat', sans-serif;
      background: var(--bg);
      color: var(--text);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: 0.3s ease;
    }

    .container {
      background: var(--card);
      padding: 50px;
      border-radius: 25px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      text-align: center;
      max-width: 600px;
      width: 90%;
      animation: slideIn 1s ease-out;
    }

    h1 {
      font-size: 36px;
      margin-bottom: 10px;
      color: var(--btn-bg);
      animation: bounceIn 1.2s ease-in-out;
    }

    .menu {
      margin-top: 30px;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .btn {
      background: var(--btn-bg);
      color: white;
      padding: 15px 25px;
      border: none;
      border-radius: 12px;
      font-size: 18px;
      text-decoration: none;
      transition: transform 0.3s, background 0.3s;
    }

    .btn:hover {
      background: var(--btn-hover);
      transform: scale(1.05);
    }

    .clock {
      position: absolute;
      top: 15px;
      right: 20px;
      font-size: 16px;
      font-weight: bold;
      color: var(--btn-bg);
    }

    .toggle {
      position: absolute;
      top: 15px;
      left: 20px;
    }

    .toggle button {
      background: var(--btn-bg);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 14px;
      cursor: pointer;
    }

    .tip {
      margin-top: 30px;
      font-style: italic;
      font-size: 15px;
      color: #777;
    }

    @keyframes slideIn {
      from { transform: translateY(30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes bounceIn {
      0%   { transform: scale(0.9); opacity: 0; }
      60%  { transform: scale(1.1); opacity: 1; }
      100% { transform: scale(1); }
    }
   

  </style>

</head>
<body>
  <div class="toggle">
    <button onclick="toggleTheme()">🌗 Theme</button>
  </div>
  <div class="clock" id="clock">🕒</div>

  <div class="container">
    <h1>Welcome to QueuLess</h1>
    <p style="font-size: 1.2em;">Avoid queues. Book tokens. Save time.</p>

    <div class="menu">
      <a href="user/register.php" class="btn">🎓 User Register</a>
      <a href="user/login.php" class="btn">🔐 User Login</a>
      <a href="staff/login.php" class="btn">🛠️ Staff Login</a>
    </div>

    <div class="tip" id="tipBox">💡 Tip loading...</div>
  </div>

  <script>
    // Theme Toggle
    function toggleTheme() {
      document.body.classList.toggle("dark");
      localStorage.setItem("theme", document.body.classList.contains("dark") ? "dark" : "light");
    }
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
    }

    // Clock
    function updateClock() {
      const now = new Date();
      document.getElementById("clock").innerText = "🕒 " + now.toLocaleTimeString();
      setTimeout(updateClock, 1000);
    }
    updateClock();

    // Tips
    const tips = [
      "Did you know? Tokens are auto-sorted by arrival time.",
      "Staff can set estimated time from dashboard.",
      "Refresh your dashboard to see real-time updates.",
      "Use dark mode for eye comfort at night.",
      "No data? Try checking connection or contact admin."
    ];
    document.getElementById("tipBox").innerText = "💡 " + tips[Math.floor(Math.random() * tips.length)];
  </script>
</body>
</html>
