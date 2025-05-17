<?php
// Serve the Web App Manifest
if (isset($_GET['manifest'])) {
    header('Content-Type: application/manifest+json');
    echo json_encode([
        "name" => "NotesShare",
        "short_name" => "NotesShare",
        "description" => "Platform for sharing educational materials",
        "start_url" => "/welcome.php",
        "scope" => "/",
        "display" => "standalone",
        "orientation" => "portrait",
        "background_color" => "#ffffff",
        "theme_color" => "#003366",
        "icons" => [
            [
                "src" => "logo.png",
                "sizes" => "192x192",
                "type" => "image/png",
                "purpose" => "any maskable"
            ],
            [
                "src" => "logo.png",
                "sizes" => "512x512",
                "type" => "image/png",
                "purpose" => "any maskable"
            ]
        ],
        "categories" => ["education", "productivity"]
    ]);
    exit;
}

// Serve the Service Worker
if (isset($_GET['sw'])) {
    header('Content-Type: application/javascript');
    ?>
const CACHE_NAME = 'notesshare-v1';
const ASSETS = [
  '/',
  '/index.php',
  '/manifest.php?manifest',
  '/logo.png',
  '/offline.php',
  '/css/style.css'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }));
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        return response || fetch(event.request)
          .catch(() => {
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/offline.php');
            }
          });
      })
  );
});
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>NotesShare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#003366">
  <meta name="description" content="Free educational resources for Malawian students - MANEB past papers, notes, textbooks">
  
  <!-- PWA Meta Tags -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="NotesShare">
  <link rel="manifest" href="manifest.php?manifest">
  <link rel="icon" href="logo.png">
  <link rel="apple-touch-icon" href="logo.png">
  
  <!-- Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
  
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    /* Splash Screen Styles */
    #splash-screen {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #003366, #ffd700);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      z-index: 1000;
      transition: opacity 0.5s ease;
    }
    
    .splash-logo {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: contain;
      margin-bottom: 20px;
      animation: pulse 1.5s infinite;
    }
    
    .splash-title {
      color: white;
      font-size: 2rem;
      font-weight: bold;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
      margin-bottom: 10px;
    }
    
    .splash-loading {
      color: white;
      font-size: 1rem;
      margin-top: 20px;
    }
    
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }

    body {
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #003366, #ffd700);
      color: #003366;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      opacity: 0;
      transition: opacity 0.5s ease;
    }

    body.loaded {
      opacity: 1;
    }

    /* Header with logo left and title right */
    .app-header {
      background: linear-gradient(to right, #002244, #003366);
      color: #fff;
      padding: 1rem 2rem;
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.3);
      display: flex;
      align-items: center;
    }

    .header-container {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .app-header img {
      height: 50px;
      width: 50px;
      border-radius: 50%;
      box-shadow: 0 3px 6px rgba(255, 255, 255, 0.6);
      object-fit: contain;
    }

    .app-title {
      font-size: 1.8rem;
      font-weight: bold;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }

    /* Main content */
    .content {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      text-align: center;
    }

    .notes-icon {
      font-size: 5rem;
      color: #003366;
      margin-bottom: 1rem;
      text-shadow: 1px 1px 2px #ffffff;
    }

    .description {
      font-size: 1.2rem;
      color: #222;
      line-height: 1.6;
      max-width: 600px;
      margin-bottom: 30px;
    }

    .features-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      margin: 30px 0;
      max-width: 800px;
    }

    .feature-card {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 10px;
      padding: 20px;
      width: 100%;
      max-width: 350px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      text-align: left;
    }

    .feature-icon {
      font-size: 2rem;
      color: #003366;
      margin-bottom: 10px;
    }

    .feature-title {
      color: #003366;
      font-weight: bold;
      margin-bottom: 10px;
      font-size: 1.2rem;
    }

    .feature-desc {
      color: #333;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .btn {
      background-color: #ffd700;
      color: #003366;
      padding: 15px 35px;
      border-radius: 30px;
      font-weight: bold;
      text-decoration: none;
      font-size: 1.2rem;
      box-shadow: 0 6px 10px rgba(0, 0, 0, 0.25);
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      margin: 10px;
    }

    .btn:hover {
      background-color: #e6c200;
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
    }

    /* Footer */
    .app-footer {
      background: #003366;
      color: white;
      text-align: center;
      padding: 0.8rem;
      font-size: 0.95rem;
    }

    .app-footer a {
      color: #ffd700;
      text-decoration: none;
      font-weight: bold;
    }

    /* Install banners */
    .install-banner {
      background-color: rgba(0, 51, 102, 0.9);
      color: white;
      padding: 15px;
      border-radius: 10px;
      margin-top: 20px;
      display: none;
      flex-direction: column;
      align-items: center;
      max-width: 400px;
      position: relative;
    }

    .install-btn {
      background-color: #ffd700;
      color: #003366;
      border: none;
      padding: 10px 20px;
      border-radius: 20px;
      font-weight: bold;
      margin-top: 10px;
      cursor: pointer;
    }

    /* iOS specific instructions */
    .ios-instructions {
      display: none;
      background-color: rgba(0, 51, 102, 0.9);
      color: white;
      padding: 25px 15px 15px;
      border-radius: 10px;
      margin-top: 20px;
      max-width: 400px;
      text-align: left;
      position: relative;
    }
    
    .ios-instructions h3 {
      margin-bottom: 10px;
      color: #FFD700;
    }
    
    .ios-instructions ol {
      padding-left: 20px;
      margin: 10px 0;
    }
    
    .ios-instructions li {
      margin-bottom: 8px;
      line-height: 1.5;
    }
    
    .ios-close-btn {
      background: none;
      border: none;
      color: #FFD700;
      position: absolute;
      top: 5px;
      right: 5px;
      font-size: 1.5rem;
      cursor: pointer;
      line-height: 1;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .app-header {
        padding: 1rem;
      }
      
      .app-title {
        font-size: 1.5rem;
      }
      
      .app-header img {
        height: 40px;
        width: 40px;
      }

      .btn {
        padding: 12px 25px;
        font-size: 1rem;
      }
      
      .description {
        font-size: 1rem;
      }
      
      .feature-card {
        padding: 15px;
      }
    }
    
    @media (max-width: 480px) {
      .app-title {
        font-size: 1.3rem;
      }
      
      .app-header img {
        height: 35px;
        width: 35px;
      }
      
      .notes-icon {
        font-size: 4rem;
      }
      
      .logo-container {
        gap: 10px;
      }
      
      .splash-logo {
        width: 100px;
        height: 100px;
      }
      
      .splash-title {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Splash Screen -->
  <div id="splash-screen">
    <img src="logo.png" class="splash-logo" alt="NotesShare Logo">
    <div class="splash-title">NotesShare</div>
    <div class="splash-loading">Loading educational resources...</div>
  </div>

  <!-- Main App Content -->
  <header class="app-header">
    <div class="header-container">
      <div class="logo-container">
        <img src="logo.png" alt="NotesShare Logo">
        <h1 class="app-title">NotesShare</h1>
      </div>
    </div>
  </header>

  <main class="content">
    <div class="notes-icon">📚</div>
    <p class="description">
      Your free educational hub for MANEB past papers, study notes, textbooks, and learning resources.
      Share knowledge with peers and access materials for all subjects and levels.
    </p>
    
    <a href="index.php" class="btn">Get Started!</a>
    
    <!-- PWA Install Banner (Android/Desktop) -->
    <div class="install-banner" id="installBanner">
      <p>Install NotesShare App for a better experience</p>
      <button id="installButton" class="install-btn">Install Now</button>
      <p style="font-size:0.8rem; margin-top:8px;">(Works on Android/Chrome/Edge)</p>
    </div>
    
    <!-- iOS Install Instructions -->
    <div class="ios-instructions" id="iosInstructions">
      <button class="ios-close-btn" id="iosCloseBtn">&times;</button>
      <h3>How to Install on iPhone/iPad/Mac:</h3>
      <ol>
        <li>Tap the <strong>Share</strong> button <span style="display:inline-block">(<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M18 16.08C17.24 16.08 16.56 16.38 16.04 16.85L8.91 12.7C8.96 12.47 9 12.24 9 12C9 11.76 8.96 11.53 8.91 11.3L15.96 7.19C16.5 7.69 17.21 8 18 8C19.66 8 21 6.66 21 5C21 3.34 19.66 2 18 2C16.34 2 15 3.34 15 5C15 5.24 15.04 5.47 15.09 5.7L8.04 9.81C7.5 9.31 6.79 9 6 9C4.34 9 3 10.34 3 12C3 13.66 4.34 15 6 15C6.79 15 7.5 14.69 8.04 14.19L15.16 18.35C15.11 18.56 15.08 18.78 15.08 19C15.08 20.61 16.39 21.92 18 21.92C19.61 21.92 20.92 20.61 20.92 19C20.92 17.39 19.61 16.08 18 16.08Z" fill="white"/>
</svg>)</span> in Safari</li>
        <li>Select <strong>"Add to Home Screen"</strong> (iOS) or <strong>"Add to Dock"</strong> (Mac)</li>
        <li>Tap <strong>Add</strong> to complete installation</li>
      </ol>
      <p style="font-size:0.8rem; margin-top:10px; color:#ffd700;">Note: Safari on Mac requires macOS Big Sur or later</p>
    </div>
    
    <div class="features-container">
      <div class="feature-card">
        <div class="feature-icon">📝</div>
        <div class="feature-title">Comprehensive Notes</div>
        <div class="feature-desc">
          Access well-organized notes for all subjects including Mathematics, Sciences, 
          Humanities and Languages. Contribute your own notes to help others.
        </div>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">📘</div>
        <div class="feature-title">MANEB Past Papers</div>
        <div class="feature-desc">
          Complete collection of Malawi National Examinations Board past papers with 
          solutions. Perfect for exam preparation from MSCE to A-levels.
        </div>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <div class="feature-title">Textbook Resources</div>
        <div class="feature-desc">
          Find recommended textbooks and reference materials. Search by subject, 
          author or publication year.
        </div>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">👥</div>
        <div class="feature-title">Collaborative Learning</div>
        <div class="feature-desc">
          Join study groups, ask questions, and get help from teachers and top 
          students across Malawi.
        </div>
      </div>
    </div>
  </main>

  <footer class="app-footer">
    © <script>document.write(new Date().getFullYear())</script> NotesShare | 
    Powered by <a href="https://charleceegraphix.great-site.net" target="_blank" rel="noopener">Charle Cee Graphix</a>
  </footer>

  <script>
    // Splash screen timeout
    setTimeout(function() {
      document.getElementById('splash-screen').style.opacity = '0';
      document.body.classList.add('loaded');
      setTimeout(function() {
        document.getElementById('splash-screen').style.display = 'none';
      }, 500);
    }, 2000); // 2 second splash screen

    // Enhanced device detection
    function isIOS() {
      return /iPad|iPhone|iPod/.test(navigator.userAgent) || 
             (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    }

    function isSafari() {
      return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    }

    function isMobile() {
      return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    // Register Service Worker (for non-Safari browsers)
    if ('serviceWorker' in navigator && !isSafari()) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
          .then(registration => {
            console.log('ServiceWorker registered');
          })
          .catch(err => {
            console.log('ServiceWorker registration failed: ', err);
          });
      });
    }

    // PWA Installation Logic
    let deferredPrompt;
    const installButton = document.getElementById('installButton');
    const installBanner = document.getElementById('installBanner');
    const iosInstructions = document.getElementById('iosInstructions');
    const iosCloseBtn = document.getElementById('iosCloseBtn');

    // Show appropriate install prompt
    function showInstallOption() {
      // Already installed - hide all prompts
      if (window.matchMedia('(display-mode: standalone)').matches) {
        installBanner.style.display = 'none';
        iosInstructions.style.display = 'none';
        return;
      }
      
      // iOS/Mac Safari
      if (isIOS() && isSafari()) {
        installBanner.style.display = 'none';
        iosInstructions.style.display = 'block';
      } 
      // Android/Desktop Chrome/Edge
      else {
        installBanner.style.display = 'flex';
        iosInstructions.style.display = 'none';
      }
    }

    // Handle beforeinstallprompt event (Android/Desktop)
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      showInstallOption();
      
      installButton.addEventListener('click', async () => {
        installBanner.style.display = 'none';
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User ${outcome} the install prompt`);
        deferredPrompt = null;
      });
    });

    // If beforeinstallprompt doesn't fire (some desktop cases)
    window.addEventListener('load', () => {
      // Check if we're on a capable browser but the event didn't fire
      const isChromium = !!window.chrome;
      const isEdgeChromium = navigator.userAgent.includes('Edg');
      
      if ((isChromium || isEdgeChromium) && !isMobile() && !deferredPrompt) {
        showInstallOption();
      }
    });

    // Close iOS instructions
    iosCloseBtn.addEventListener('click', () => {
      iosInstructions.style.display = 'none';
    });

    // Initial check
    showInstallOption();

    // Track install events
    window.addEventListener('appinstalled', () => {
      console.log('PWA was installed');
      // You can add analytics here
    });
  </script>
</body>
</html>