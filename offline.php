<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Offline - NotesShare</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #003366;
      --secondary: #FFD700;
      --text-light: #dcdcdc;
      --shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    body {
      background-color: var(--primary);
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: 'Roboto', sans-serif;
      text-align: center;
      margin: 0;
      padding: 20px;
      flex-direction: column;
      animation: fadeIn 1s ease;
    }

    .offline-container {
      max-width: 600px;
      padding: 30px;
      border-radius: 15px;
      background-color: rgba(0, 0, 0, 0.2);
      box-shadow: var(--shadow);
    }

    h1 {
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--secondary);
      text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
      margin-bottom: 20px;
    }

    p {
      font-size: 1.2rem;
      color: var(--text-light);
      margin-bottom: 30px;
      line-height: 1.6;
    }

    .button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background-color: var(--secondary);
      color: var(--primary);
      padding: 15px 30px;
      font-size: 1rem;
      border-radius: 30px;
      text-decoration: none;
      font-weight: bold;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }

    .button:hover {
      background-color: #e6c200;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .button:active {
      transform: translateY(1px);
    }

    .icon {
      font-size: 4rem;
      margin-bottom: 20px;
      color: var(--secondary);
      animation: pulse 2s infinite;
    }

    .footer {
      position: fixed;
      bottom: 20px;
      color: var(--secondary);
      font-size: 0.9rem;
    }

    .footer a {
      color: var(--secondary);
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .footer a:hover {
      text-decoration: underline;
      opacity: 0.8;
    }

    .connection-status {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 8px 15px;
      background-color: rgba(0, 0, 0, 0.5);
      border-radius: 20px;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 2rem;
      }
      
      p {
        font-size: 1rem;
      }
      
      .button {
        padding: 12px 25px;
        font-size: 0.9rem;
      }
      
      .icon {
        font-size: 3rem;
      }
      
      .offline-container {
        padding: 20px;
      }
    }
  </style>
</head>
<body>

  <div class="connection-status">
    <i class="fas fa-wifi-slash"></i>
    <span>Offline</span>
  </div>

  <div class="offline-container">
    <div class="icon">
      <i class="fas fa-cloud-slash"></i>
    </div>
    <h1>You're Offline</h1>
    <p>We can't connect to the internet right now. Please check your network connection and try again. Some features may not be available while offline.</p>
    
    <div class="button-group" style="display: flex; gap: 15px; justify-content: center;">
      <a href="index.php" class="button">
        <i class="fas fa-home"></i> Return Home
      </a>
      <button onclick="window.location.reload()" class="button">
        <i class="fas fa-sync-alt"></i> Try Again
      </button>
    </div>
  </div>

  <div class="footer">
    <p>&copy; <?= date('Y') ?> NotesShare | <a href="https://charleceegraphix.great-site.net" target="_blank">Powered by Charle Cee Graphix</a></p>
  </div>

  <script>
    // Check for connection status periodically
    function checkConnection() {
      if (navigator.onLine) {
        window.location.reload();
      }
    }
    
    // Check every 5 seconds
    setInterval(checkConnection, 5000);
    
    // Also check when connection status changes
    window.addEventListener('online', () => {
      window.location.reload();
    });
  </script>

</body>
</html>