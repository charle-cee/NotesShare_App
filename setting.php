<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['ocasuid']) == 0) {
    header('location:logout.php');
} else {
    if (isset($_POST['submit'])) {
        $uid = $_SESSION['ocasuid'];
        $cpassword = $_POST['currentpassword'];
        $newpassword = $_POST['newpassword'];

        // Fetch the current hashed password
        $sql = "SELECT password FROM registered_users WHERE id=:uid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($cpassword, $row['password'])) {
            // Hash the new password
            $hashedNewPassword = password_hash($newpassword, PASSWORD_DEFAULT);

            $update = "UPDATE registered_users SET password=:newpassword WHERE id=:uid";
            $stmt = $dbh->prepare($update);
            $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
            $stmt->bindParam(':newpassword', $hashedNewPassword, PDO::PARAM_STR);
            $stmt->execute();

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Your password was successfully changed',
                        confirmButtonColor: '#003366'
                    }).then(() => {
                        window.location.href = 'setting.php';
                    });
                });
            </script>";
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops!',
                        text: 'Your current password is incorrect',
                        confirmButtonColor: '#003366'
                    });
                });
            </script>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Change Password</title>
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .form-label {
            color: #003366 !important;
            font-weight: 600 !important;
        }
        .form-control {
            border: 1px solid #003366 !important;
        }
        .btn-primary {
            background-color: #FFD700 !important;
            border-color: #003366 !important;
            color: black !important;
            font-weight: bold !important;
        }
        .btn-primary:hover {
            background-color: #003366 !important;
            color: #FFD700 !important;
        }
        h6 {
            color: #003366 !important;
            font-weight: 700;
        }
        .bg-light {
            background-color: #ffffff !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
        function checkpass() {
            if (document.changepassword.newpassword.value != document.changepassword.confirmpassword.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Mismatch!',
                    text: 'New Password and Confirm Password do not match',
                    confirmButtonColor: '#003366'
                });
                document.changepassword.confirmpassword.focus();
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <div class="container-fluid position-relative bg-white d-flex p-0">
        <?php include_once('includes/sidebar.php'); ?>

        <!-- Content Start -->
        <div class="content">
            <?php include_once('includes/header2.php'); ?>

            <!-- Form Start -->
            <div class="container-fluid pt-4 px-4">
                <div class="row g-4">
                    <div class="col-sm-12 col-xl-6">
                        <div class="bg-light rounded h-100 p-4">
                            <h6 class="mb-4">Change Password</h6>
                            <form method="post" name="changepassword" onsubmit="return checkpass();">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="currentpassword" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="newpassword" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirmpassword" required>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">Change</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Form End -->

            <?php include_once('includes/footer.php'); ?>
        </div>
        <!-- Content End -->

        <?php include_once('includes/back-totop.php'); ?>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
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
