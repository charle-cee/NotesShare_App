<?php
session_start();
error_reporting(0);
include('config.php');

if (strlen($_SESSION['ocasuid']) == 0) {
    header('location:logout.php');
} else {
    if (isset($_POST['submit'])) {
        $uid = $_SESSION['ocasuid'];
        $fname = $_POST['name'];

        $sql = "UPDATE registered_users SEt name=:name WHERE id =:uid";
        $query = $dbh->prepare($sql);
        $query->bindParam(':name', $fname, PDO::PARAM_STR);
        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
        $query->execute();

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: 'Your profile has been updated successfully!',
                    confirmButtonColor: '#003366',
                    background: '#fff',
                    color: '#000'
                });
            });
        </script>";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile</title>
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
            <div class="container-fluid pt-4 px-4">
                <div class="row g-4">
                    <div class="col-sm-12 col-xl-6">
                        <div style="background-color: #fff; border: 2px solid #003366; border-radius: 10px; padding: 30px;">
                            <h6 style="color: #003366; font-weight: 700; margin-bottom: 20px;">User Profile</h6>
                            <form method="post">
                                <?php
                                $uid = $_SESSION['ocasuid'];
                                $sql = "SELECT * FROM registered_users WHERE id =:uid";
                                $query = $dbh->prepare($sql);
                                $query->bindParam(':uid', $uid, PDO::PARAM_STR);
                                $query->execute();
                                $results = $query->fetchAll(PDO::FETCH_OBJ);
                                if ($query->rowCount() > 0) {
                                    foreach ($results as $row) {
                                ?>
                                <div style="margin-bottom: 20px;">
                                    <label style="color: #003366; font-weight: 600;">Full Name</label>
                                    <input type="text" class="form-control" name="name" value="<?php echo $row->name; ?>" required style="border: 1px solid #003366; background-color: #f4f6f9; color: black;">
                                </div>
                                <div style="margin-bottom: 20px;">
                                    <label style="color: #003366; font-weight: 600;">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $row->email; ?>" readonly style="border: 1px solid #003366; background-color: #f4f6f9; color: black;">
                                </div>
                                <div style="margin-bottom: 20px;">
                                    <label style="color: #003366; font-weight: 600;">Registration Date</label>
                                    <input type="text" class="form-control" value="<?php echo $row->otp_sent; ?>" readonly style="border: 1px solid #003366; background-color: #f4f6f9; color: black;">
                                </div>
                                <?php }} ?>
                                <div style="text-align: center;">
                                    <button type="submit" name="submit" style="background-color: #FFD700; color: black; font-weight: bold; padding: 10px 20px; border: none; border-radius: 5px;">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once('includes/footer.php');?>
        </div>
        <?php include_once('includes/back-totop.php');?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
