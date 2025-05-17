<?php include_once('includes/navbar.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#003366" />
  <link rel="icon" href="logo.png" type="image/png">
  <title>Online Notes Sharing System | Home</title>
<link rel="manifest" href="manifest.php?manifest">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #fff;
      color: #000;
      position: relative;
    }

    #preloader {
      position: fixed;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      z-index: 9999;
      background-color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .spinner {
      border: 5px solid #f3f3f3;
      border-top: 5px solid #003366;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .hero {
      background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/img/bg.jpg') center/cover no-repeat;
      color: white;
      padding: 100px 20px;
      text-align: center;
    }

    .hero h1 {
      font-size: 3rem;
      margin-bottom: 20px;
    }

    .hero p {
      font-size: 1.2rem;
      margin-bottom: 30px;
    }

    .hero .btn {
      padding: 10px 20px;
      background-color: #FFD700;
      color: black;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .hero .btn:hover {
      background-color: #e6c200;
    }

    .features {
      padding: 60px 20px;
      text-align: center;
      background-color: #fff;
    }

    .features h2 {
      color: #003366;
      margin-bottom: 40px;
      font-size: 2.5rem;
    }

    .feature-boxes {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
    }

    .feature {
      background-color: #f9f9f9;
      padding: 30px;
      border-radius: 10px;
      width: 300px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .feature:hover {
      transform: translateY(-5px);
    }

    .feature i {
      font-size: 2.5rem;
      color: #FFD700;
      margin-bottom: 15px;
    }

    .feature h3 {
      color: #003366;
      margin-bottom: 10px;
      font-size: 1.5rem;
    }

    .feature p {
      color: #555;
      line-height: 1.6;
    }

    /* Download Section Styles */
    .download-section {
      background-color: #003366;
      color: white;
      text-align: center;
      padding: 80px 20px;
      position: relative;
    }

    .download-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .download-section h2 {
      font-size: 2.5rem;
      margin-bottom: 15px;
    }

    .download-section p {
      font-size: 1.2rem;
      margin-bottom: 40px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
    }

    .download-options {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 30px;
    }

    .download-card {
      background: rgba(255, 255, 255, 0.1);
      border-radius: 15px;
      padding: 30px;
      width: 300px;
      transition: all 0.3s ease;
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .download-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .download-icon {
      font-size: 3.5rem;
      margin-bottom: 20px;
      display: inline-block;
    }

    .android-icon {
      color: #3ddc84; /* Android green */
    }

    .playstore-icon {
      color: #4285f4; /* Google blue */
    }

    .download-card h3 {
      font-size: 1.5rem;
      margin-bottom: 15px;
    }

    .download-card p {
      font-size: 1rem;
      margin-bottom: 25px;
      color: rgba(255, 255, 255, 0.8);
    }

    .download-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 12px 25px;
      font-size: 1rem;
      background-color: #FFD700;
      color: #000;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
      text-decoration: none;
      width: 100%;
    }

    .download-btn:hover {
      background-color: #e6c200;
      transform: scale(1.05);
    }

    .btn-icon {
      margin-right: 10px;
      font-size: 1.2rem;
    }

    .badge {
      font-size: 0.8rem;
      background: rgba(0, 0, 0, 0.2);
      padding: 3px 10px;
      border-radius: 20px;
      margin-top: 15px;
      display: inline-block;
    }

    footer {
      background-color: #002244;
      color: white;
      text-align: center;
      padding: 30px;
    }

    footer p {
      margin-bottom: 15px;
    }

    footer a {
      color: #FFD700;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    footer a:hover {
      text-decoration: underline;
    }

    .social-icons {
      margin-top: 20px;
    }

    .social-icons a {
      display: inline-block;
      margin: 0 15px;
      font-size: 1.5rem;
    }

    @media (max-width: 768px) {
      .hero {
        padding: 80px 20px;
      }
      
      .hero h1 {
        font-size: 2.2rem;
      }
      
      .feature-boxes {
        flex-direction: column;
        align-items: center;
      }
      
      .feature {
        width: 100%;
        max-width: 350px;
      }
      
      .download-section {
        padding: 60px 20px;
      }
      
      .download-section h2 {
        font-size: 2rem;
      }

      .download-options {
        flex-direction: column;
        align-items: center;
      }
      
      .download-card {
        width: 100%;
        max-width: 350px;
      }
    }
  </style>
</head>
<!-- Add this to the head or before the closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  let deferredPrompt = null;
  let installPromptShown = false;
  let apkFallbackShown = false;
  const PROMPT_COOLDOWN = 24 * 60 * 60 * 1000; // 24 hours cooldown

  // Check if prompt is in cooldown
  function isPromptInCooldown() {
    const lastPromptTime = localStorage.getItem('lastPromptTime');
    if (!lastPromptTime) return false;
    return (Date.now() - parseInt(lastPromptTime)) < PROMPT_COOLDOWN;
  }

  // Set cooldown timestamp
  function setPromptCooldown() {
    localStorage.setItem('lastPromptTime', Date.now().toString());
  }

  // Listen for PWA install prompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    if (isPromptInCooldown()) {
      console.log('Prompt in cooldown - skipping');
      return;
    }
    
    console.log('PWA install prompt available');
    e.preventDefault();
    deferredPrompt = e;
    installPromptShown = true;
    
    showPWAInstallPrompt();
  });

  // Show PWA install prompt
  function showPWAInstallPrompt() {
    if (window.matchMedia('(display-mode: standalone)').matches) {
      console.log('Already installed - skipping prompt');
      return;
    }

    if (!deferredPrompt || isPromptInCooldown()) {
      console.log('No install prompt available or in cooldown');
      return;
    }

    console.log('Showing install prompt dialog');
    Swal.fire({
      title: 'Install NotesShare App',
      html: `Add NotesShare to your home screen for better experience`,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Install',
      cancelButtonText: 'Not Now',
      confirmButtonColor: '#FFD700',
      cancelButtonColor: '#6c757d',
      background: '#003366',
      color: '#fff'
    }).then((result) => {
      if (result.isConfirmed) {
        console.log('User accepted install - showing browser prompt');
        deferredPrompt.prompt();
        
        deferredPrompt.userChoice.then((choiceResult) => {
          setPromptCooldown(); // Set cooldown regardless of choice
          if (choiceResult.outcome === 'accepted') {
            console.log('User completed installation');
          } else {
            console.log('User declined installation');
          }
          deferredPrompt = null;
        });
      } else {
        console.log('User postponed installation');
        setPromptCooldown();
      }
    });
  }

  // Show prompt after page loads if not shown automatically
  window.addEventListener('load', () => {
    if (isPromptInCooldown()) {
      console.log('Prompt in cooldown - not showing');
      return;
    }

    setTimeout(() => {
      if (!installPromptShown && !window.matchMedia('(display-mode: standalone)').matches) {
        console.log('Showing delayed install prompt');
        showPWAInstallPrompt();
      }
    }, 5000); // 5 second delay
  });

  // Rest of your existing code...
  // (keep all your other functions like showDelayedAPKFallback, showAPKDownloadOption, etc.)
</script>
<body>
  <div id="preloader">
    <div class="spinner"></div>
  </div>

  <section class="hero">
    <h1>Online Learning Platform</h1>
    <p>Share and access academic notes anytime, anywhere.</p>
    <a href="download.php" class="btn">Access Notes</a>
  </section>

  <section class="features">
    <h2>Why Choose Us</h2>
    <div class="feature-boxes">
      <div class="feature">
        <i class="fas fa-laptop-code"></i>
        <h3>Interactive Content</h3>
        <p>Access well-organized notes across multiple disciplines with our intuitive platform designed for optimal learning.</p>
      </div>
      <div class="feature">
        <i class="fas fa-user-tie"></i>
        <h3>Expert Contributors</h3>
        <p>Notes curated and shared by top students and educators from prestigious institutions.</p>
      </div>
      <div class="feature">
        <i class="fas fa-infinity"></i>
        <h3>Lifetime Access</h3>
        <p>Download and revisit notes anytime without restrictions. Your learning journey never expires.</p>
      </div>
    </div>
  </section>

  <!-- Redesigned Download Section -->
  <section class="download-section">
    <div class="download-container">
      <h2>Get Our Mobile App</h2>
      <p>Access your notes anytime, anywhere with our mobile application</p>
      
      <div class="download-options">
        <!-- APK Download Card -->
        <div class="download-card">
          <i class="download-icon android-icon bi bi-android"></i>
          <h3>Direct Download</h3>
          <p>Get the latest APK directly for quick installation</p>
          <button onclick="confirmDownload()" class="download-btn">
            <i class="btn-icon bi bi-download"></i> Download APK
          </button>
          <div class="badge">Version 1.2.0 • 3.0 MB</div>
          
        </div>
        
        <!-- Play Store Card -->
        <div class="download-card">
          <i class="download-icon playstore-icon bi bi-google-play"></i>
          <h3>Google Play Store</h3>
          <p>Install from Play Store for automatic updates</p>
          <a href="https://play.google.com/store/apps/details?id=notesshare" 
             target="_blank" 
             class="download-btn">
            <i class="btn-icon bi bi-box-arrow-up-right"></i> Get on Play Store
          </a>
          <div class="badge">4.8 ★ Rating</div>
        </div>
      </div>
    </div>
  </section>

  <footer>
    <p>&copy; <?php echo date("Y"); ?> <strong style="color: #FFD700;">NotesShare</strong>. All rights reserved.</p>
    <p>Developed by <a href="https://charleceegraphix.great-site.net" target="_blank">Charle Cee Graphix</a></p>
    <div class="social-icons">
      <a href="https://wa.me/+265882595892" target="_blank" title="WhatsApp">
        <i class="fab fa-whatsapp"></i>
      </a>
      <a href="https://www.facebook.com/charleceegraphix" target="_blank" title="Facebook">
        <i class="fab fa-facebook"></i>
      </a>
      <a href="https://www.linkedin.com/in/developer" target="_blank" title="LinkedIn">
        <i class="fab fa-linkedin"></i>
      </a>
    </div>
  </footer>

  <script>
    // Preloader
    window.addEventListener('load', function() {
      setTimeout(function() {
        document.getElementById('preloader').style.opacity = '0';
        setTimeout(function() {
          document.getElementById('preloader').style.display = 'none';
        }, 500);
      }, 500);
    });

    // Download confirmation
    async function confirmDownload() {
      try {
        const response = await fetch('includes/NotesShare.apk', { method: 'HEAD' });
        if (!response.ok) throw new Error("APK not found");
        const fileSize = response.headers.get('Content-Length');
        const sizeInMB = (parseInt(fileSize) / (1024 * 1024)).toFixed(2);

        const { isConfirmed } = await Swal.fire({
          title: 'Download NotesShare App?',
          html: `The APK file is approximately <strong>${sizeInMB} MB</strong>. Continue?`,
          icon: 'info',
          showCancelButton: true,
          confirmButtonText: 'Yes, download',
          cancelButtonText: 'Cancel',
          confirmButtonColor: '#FFD700',
          cancelButtonColor: '#d33',
          background: '#003366',
          color: '#fff'
        });

        if (isConfirmed) {
          // Create temporary link to trigger download
          const link = document.createElement('a');
          link.href = 'includes/NotesShare.apk';
          link.download = 'NotesShare.apk';
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          
          // Track download if analytics available
          if (typeof gtag !== 'undefined') {
            gtag('event', 'download', {
              'event_category': 'engagement',
              'event_label': 'android_app_download'
            });
          }
        }
      } catch (error) {
        console.error('Download error:', error);
        Swal.fire({
          title: 'Oops!',
          text: 'The APK file is not available right now. Please try again later.',
          icon: 'error',
          background: '#003366',
          color: '#fff'
        });
      }
    }

     </script>
</body>

</html>