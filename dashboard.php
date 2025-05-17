<?php
session_start();
error_reporting(0);
include('config.php');

if (strlen($_SESSION['ocasuid'] == 0)) {
    header('location:logout.php');
} else {
    $uid = $_SESSION['ocasuid'];

    // Fetch user info (name and usertype)
    $sql = "SELECT name, usertype FROM registered_users WHERE id = :uid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':uid', $uid, PDO::PARAM_STR);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_OBJ);

        // Total uploaded notes for the user
$sql0 = "SELECT COUNT(*) AS totnotes FROM tblnotes WHERE UserID = :uid";
$stmt0 = $dbh->prepare($sql0);
$stmt0->bindParam(':uid', $uid, PDO::PARAM_STR); // or PDO::PARAM_INT if UserID is an integer
$stmt0->execute();
$row0 = $stmt0->fetch(PDO::FETCH_OBJ);
$totnotes = $row0 ? $row0->totnotes : 0;

    

    // Total uploaded files for the user
    $sql2 = "SELECT 
                COUNT(IF(File1 != '', 1, NULL)) AS file1
            FROM tblnotes WHERE UserID = :uid";
    $query2 = $dbh->prepare($sql2);
    $query2->bindParam(':uid', $uid, PDO::PARAM_STR);
    $query2->execute();
    $files = $query2->fetch(PDO::FETCH_OBJ);
    $totalfiles = $files->file1;

    // Monthly upload chart data
    $chartData = array_fill(0, 12, 0);
    $sql = "SELECT MONTH(CreationDate) AS month, COUNT(*) AS count 
            FROM tblnotes 
            WHERE UserID = :uid 
            GROUP BY MONTH(CreationDate)";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $chartData[(int)$row->month - 1] = (int)$row->count;
    }
    $chartDataJSON = json_encode($chartData);

    // Admin statistics (only if user is admin)
    $pendingNotes = 0;
    $totalSubjects = 0;
    if ($user->usertype === 'admin') {
        // Total uploaded notes for the user
        $sql1 = "SELECT COUNT(*) AS totalnotes FROM tblnotes";
        $stmt1 = $dbh->prepare($sql1);
        $stmt1->execute();
        $row1 = $stmt1->fetch(PDO::FETCH_OBJ);
        $totalnotes = $row1->totalnotes;
        
        // Total number of users
        $sql = "SELECT COUNT(*) AS totalusers FROM registered_users";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $totalusers = $row->totalusers;

        // Total number of subjects
        $sql = "SELECT COUNT(DISTINCT Subject) AS totalSubjects FROM tblnotes";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $totalSubjects = $row->totalSubjects;

        // Pending approval notes count
        $sql = "SELECT COUNT(*) AS pending FROM tblnotes WHERE Status = 'Pending'";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        $pendingNotes = $row->pending;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include_once('includes/sidebar.php');?>

    <div class="content">
        <?php include_once('includes/header2.php');?>

        <!-- Welcome Message -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-white rounded shadow p-4 text-center">
                <h2 class="text-dark fw-bold">Welcome back, <span class="text-warning"><?php echo $user->name; ?></span> 👋</h2>
                <p class="text-muted">Here’s a quick overview of your notes and files.</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <!-- Notes and Subject Cards for Regular Users -->
                <div class="col-sm-6 col-xl-4">
                    <div class="rounded shadow d-flex align-items-center justify-content-between p-4" style="background-color: #003366; color: white;">
                        <i class="fa fa-book fa-3x text-warning"></i>
                        <div class="ms-3 text-end">
                            <p class="mb-1 fw-bold">Uploaded Subject Notes</p>
                            <h3 class="mb-0 text-warning"><?php echo htmlentities($totnotes); ?></h3>
                            <a href="add-notes.php" class="btn btn-sm btn-outline-light mt-2">Add Note</a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="rounded shadow d-flex align-items-center justify-content-between p-4" style="background-color: #003366; color: white;">
                        <i class="fa fa-file-alt fa-3x text-warning"></i>
                        <div class="ms-3 text-end">
                            <p class="mb-1 fw-bold">Uploaded Notes Files</p>
                            <h3 class="mb-0 text-warning"><?php echo htmlentities($totalfiles); ?></h3>
                            <a href="manage-notes.php" class="btn btn-sm btn-outline-light mt-2">Manage Notes</a>
                        </div>
                    </div>
                </div>

                <?php if ($user->usertype === 'admin') { ?>
                <!-- Admin Statistics Cards -->
                <div class="col-sm-6 col-xl-4">
                    <div class="rounded shadow d-flex align-items-center justify-content-between p-4" style="background-color: #003366; color: white;">
                        <i class="fa fa-book fa-3x text-warning"></i>
                        <div class="ms-3 text-end">
                            <p class="mb-1 fw-bold">Total Notes</p>
                            <h3 class="mb-0 text-warning"><?php echo htmlentities($totalnotes); ?></h3>
                        </div>
                    </div>
                </div>
                

                <div class="col-sm-6 col-xl-4">
                    <div class="rounded shadow d-flex align-items-center justify-content-between p-4" style="background-color: #003366; color: white;">
                        <i class="fa fa-users fa-3x text-warning"></i>
                        <div class="ms-3 text-end">
                            <p class="mb-1 fw-bold">Total Users</p>
                            <h3 class="mb-0 text-warning"><?php echo htmlentities($totalusers); ?></h3>
                            <a href="manage-users.php" class="btn btn-sm btn-outline-light mt-2">Manage Users</a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="rounded shadow d-flex align-items-center justify-content-between p-4" style="background-color: #003366; color: white;">
                        <i class="fa fa-book-open fa-3x text-warning"></i>
                        <div class="ms-3 text-end">
                            <p class="mb-1 fw-bold">Total Subjects</p>
                            <h3 class="mb-0 text-warning"><?php echo htmlentities($totalSubjects); ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="rounded shadow d-flex align-items-center justify-content-between p-4" style="background-color: #003366; color: white;">
                        <i class="fa fa-check-circle fa-3x text-warning"></i>
                        <div class="ms-3 text-end">
                            <p class="mb-1 fw-bold">Notes Pending Approval</p>
                            <h3 class="mb-0 text-warning"><?php echo htmlentities($pendingNotes); ?></h3>
                            <a href="approve-notes.php" class="btn btn-sm btn-outline-light mt-2">Review Notes</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Upload Activity Chart -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-white rounded shadow p-4">
                <h4 class="mb-4">Upload Activity</h4>
                <canvas id="notesChart" width="400" height="150"></canvas>
            </div>
        </div>

        <?php include_once('includes/footer2.php');?>
    </div>
    <?php include_once('includes/back-totop.php');?>
</div>

<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/chart/chart.min.js"></script>
<script>
    const chartData = <?php echo $chartDataJSON; ?>;
    const ctx = document.getElementById('notesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                     'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Files Uploaded',
                data: chartData,
                backgroundColor: '#FFD700',
                borderColor: '#003366',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="lib/tempusdominus/js/moment.min.js"></script>
<script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
<script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
<?php } ?>
