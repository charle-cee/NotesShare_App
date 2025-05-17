<?php
$uid = $_SESSION['ocasuid'];
$sql = "SELECT * from registered_users where ID=:uid";
$query = $dbh->prepare($sql);
$query->bindParam(':uid', $uid, PDO::PARAM_STR);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$userName = '';
if ($query->rowCount() > 0) {
    foreach ($results as $row) {
        $userName = htmlentities($row->name);
    }
}
?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="includes/logo.png" type="image/png">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

<style>
    .sidebar-toggler:hover,
    .navbar .nav-link:hover {
        color: gold !important;
    }

    .dropdown-menu a:hover {
        background-color: #003366;
        color: gold !important;
    }

    .dropdown-menu {
        transition: all 0.3s ease-in-out;
    }
    
    /* Mobile toggle button styles */
    #sidebarToggleBtn {
        cursor: pointer;
        font-size: 1.25rem;
    }
    
    /* Overlay styles */
    #sidebarOverlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.5);
        z-index: 1040;
        display: none;
    }
</style>

<!-- Navbar Start -->
<nav class="navbar navbar-expand navbar-dark sticky-top px-4 py-0 shadow-sm" style="background-color: #003366;">
    <!-- Sidebar toggle button -->
    <a href="#" class="sidebar-toggler flex-shrink-0 text-warning" id="sidebarToggleBtn">
        <i class="fa fa-bars"></i>
    </a>

    <!-- Right side user nav -->
    <div class="navbar-nav align-items-center ms-auto">
        <div class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                <img class="rounded-circle me-2 border border-white" src="images/user.jpg" alt="User" style="width: 40px; height: 40px;">
                <span class="d-none d-lg-inline-flex text-white fw-semibold"><?= $userName ?></span>
            </a>

            <!-- Dropdown menu -->
            <div class="dropdown-menu dropdown-menu-end bg-white border-0 shadow-lg mt-2 rounded-3">
                <a href="profile.php" class="dropdown-item text-dark">My Profile</a>
                <a href="setting.php" class="dropdown-item text-dark">Settings</a>
                <a href="logout.php" class="dropdown-item text-danger">Log Out</a>
            </div>
        </div>
    </div>
</nav>
<!-- Navbar End -->

<!-- Sidebar Overlay -->
<div id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('mainSidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('sidebar-mobile-active');
            overlay.style.display = sidebar.classList.contains('sidebar-mobile-active') ? 'block' : 'none';
        });
    }
    
    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('sidebar-mobile-active');
            overlay.style.display = 'none';
        });
    }
});
</script>