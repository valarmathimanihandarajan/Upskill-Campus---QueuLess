<!DOCTYPE html>
<html>
<head>
  <title>Now Serving</title>
  <style>
    body { background: #0d0d0d; color: white; font-family: Arial; text-align: center; padding-top: 100px; }
    .token-box { font-size: 3em; animation: pulse 1s infinite; }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
  </style>
</head>
<body>
  <div class="token-box" id="tokenDisplay">Loading...</div>
  <script>
    function fetchToken() {
      fetch('../current_token.txt')
        .then(res => res.text())
        .then(data => {
          document.getElementById("tokenDisplay").innerText = "Now Serving: " + data;
        });
    }
    fetchToken();
    setInterval(fetchToken, 5000); // auto-refresh every 5 sec
  </script>
</body>
</html>
