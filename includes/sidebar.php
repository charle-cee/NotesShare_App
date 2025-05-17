<?php 
$currentPage = basename($_SERVER['PHP_SELF']); 
$isAdmin = false;
$uid = $_SESSION['ocasuid'];
$sql = "SELECT * FROM registered_users WHERE id=:uid";
$query = $dbh->prepare($sql);
$query->bindParam(':uid', $uid, PDO::PARAM_STR);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);

if ($query->rowCount() > 0) {
    foreach ($results as $row) {
        $isAdmin = ($row->usertype == 'admin');
        $userName = htmlentities($row->name);
        $userEmail = htmlentities($row->email);
        $userType = htmlentities($row->usertype);
    }
}
?><!-- Sidebar -->
<div class="sidebar pe-4 pb-4 d-flex flex-column" style="background-color: #003366; min-height: 100vh; box-shadow: 2px 0 10px rgba(0,0,0,0.1); transition: all 0.3s;" id="sidebar">
    <nav class="navbar navbar-dark flex-column align-items-start">
        <!-- Brand -->
        <a href="dashboard.php" class="navbar-brand mx-4 mb-3 d-flex align-items-center">
            <img src="includes/logo.png" alt="Logo" style="height: 35px;" class="me-2">
            <h4 class="text-warning fw-bold mb-0">ONSS</h4>
        </a>

        <!-- User Info -->
        <div class="d-flex align-items-center ms-4 mb-4">
            <div class="position-relative user-profile-img">
                <img src="images/user.jpg" class="rounded-circle border border-white shadow" style="width: 48px; height: 48px;" alt="User">
                <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
            </div>
            <div class="ms-3">
                <h6 class="text-warning mb-0"><?= $userName ?></h6>
                <small class="text-white"><?= $userEmail ?></small><br>
                <span class="badge bg-warning text-dark"><?= ucfirst($userType) ?></span>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="navbar-nav w-100 px-3">

            <a href="dashboard.php" class="nav-item nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : 'text-warning' ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                <?= ($currentPage == 'dashboard.php') ? '<span class="active-indicator"></span>' : '' ?>
            </a>

            <a href="download.php" class="nav-item nav-link <?= ($currentPage == 'download.php') ? 'active' : 'text-warning' ?>">
                <i class="fas fa-file-download me-2"></i>Access Notes
                <?= ($currentPage == 'download.php') ? '<span class="active-indicator"></span>' : '' ?>
            </a>

            <!-- My Notes Dropdown -->
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle <?= (in_array($currentPage, ['add-notes.php', 'manage-notes.php'])) ? 'active' : 'text-warning' ?>" data-bs-toggle="dropdown">
                   <i class="fas fa-book me-2"></i>My Notes
                </a>
                <div class="dropdown-menu shadow-sm">
                    <a href="add-notes.php" class="dropdown-item <?= ($currentPage == 'add-notes.php') ? 'active' : '' ?>">
                        <i class="fas fa-plus-circle me-2 text-warning"></i>Add Notes
                    </a>
                    <a href="manage-notes.php" class="dropdown-item <?= ($currentPage == 'manage-notes.php') ? 'active' : '' ?>">
                        <i class="fas fa-tasks me-2 text-warning"></i>Manage Notes
                    </a>
                </div>
            </div>

            <!-- Admin Tools -->
            <?php if ($isAdmin): ?>
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle <?= (in_array($currentPage, ['manage-users.php', 'system-logs.php', 'backup.php', 'analytics.php'])) ? 'active' : 'text-warning' ?>" data-bs-toggle="dropdown">
                    <i class="fas fa-user-shield me-2"></i>Admin Tools
                </a>
                <div class="dropdown-menu shadow-sm">
                                    <a href="approve-notes.php" class="dropdown-item <?= ($currentPage == 'approve-notes.php') ? 'active' : '' ?>">
                        <i class="fas fa-book me-2 text-warning"></i>Approve Notes                    </a>
                    <a href="manage-users.php" class="dropdown-item <?= ($currentPage == 'manage-users.php') ? 'active' : '' ?>">
                        <i class="fas fa-users-cog me-2 text-warning"></i>Manage Users
                    </a>
                    <a href="system-logs.php" class="dropdown-item <?= ($currentPage == 'system-logs.php') ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-list me-2 text-warning"></i>System Logs
                    </a>
                    <a href="backup.php" class="dropdown-item <?= ($currentPage == 'backup.php') ? 'active' : '' ?>">
                        <i class="fas fa-database me-2 text-warning"></i>Database Backup
                    </a>
                    <a href="analytics.php" class="dropdown-item <?= ($currentPage == 'analytics.php') ? 'active' : '' ?>">
                        <i class="fas fa-chart-line me-2 text-warning"></i>Usage Analytics
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Settings -->
            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle <?= (in_array($currentPage, ['profile.php', 'setting.php', 'notifications.php'])) ? 'active' : 'text-warning' ?>" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-2"></i>Profile
                </a>
                <div class="dropdown-menu shadow-sm">
                    <a href="profile.php" class="dropdown-item <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                        <i class="fas fa-user me-2 text-warning"></i>My Profile
                    </a>
                    <a href="setting.php" class="dropdown-item <?= ($currentPage == 'setting.php') ? 'active' : '' ?>">
                        <i class="fas fa-key me-2 text-warning"></i>Change Password
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-4">
                <a href="add-notes.php" class="btn btn-warning btn-sm w-100 mb-2">
                    <i class="fas fa-plus me-1"></i> Quick Note
                </a>
                <?php if ($isAdmin): ?>
                <a href="manage-users.php" class="btn btn-outline-light btn-sm w-100">
                    <i class="fas fa-user-cog me-1"></i> Manage Users
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</div>

<!-- Styles -->
<style>
    .nav-link {
        padding: 12px 15px;
        border-radius: 5px;
        margin: 4px 0;
        transition: all 0.3s ease;
        position: relative;
    }

    .nav-link:hover, .nav-link.active {
        background-color: rgba(255, 215, 0, 0.2);
        color: #fff !important;
    }

    .active-indicator {
        position: absolute;
        right: 0;
        top: 20%;
        width: 4px;
        height: 60%;
        background-color: #FFD700;
        border-radius: 3px 0 0 3px;
    }

    .dropdown-menu {
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        min-width: 220px;
    }

    .dropdown-item {
        transition: background 0.3s, color 0.3s;
        font-weight: 500;
        color: #003366;
    }

    .dropdown-item:hover, .dropdown-item.active {
        background-color: #FFE680;
        color: #003366 !important;
    }

    .user-profile-img:hover {
        transform: scale(1.05);
        transition: transform 0.3s ease;
    }
</style>