<?php
session_start();
error_reporting(0);
include('config.php');
if (strlen($_SESSION['ocasuid']) == 0) {
    header('location:logout.php');
} else {
    if (isset($_POST['submit'])) {
        $subject = $_POST['subject'];
        $notestitle = $_POST['notestitle'];
        $notesdesc = $_POST['notesdesc'];
        $eid = $_GET['editid'];

        $sql = "UPDATE tblnotes SET Subject=:subject, NotesTitle=:notestitle, NotesDecription=:notesdesc WHERE ID=:eid";
        $query = $dbh->prepare($sql);

        $query->bindParam(':subject', $subject, PDO::PARAM_STR);
        $query->bindParam(':notestitle', $notestitle, PDO::PARAM_STR);
        $query->bindParam(':notesdesc', $notesdesc, PDO::PARAM_STR);
        $query->bindParam(':eid', $eid, PDO::PARAM_STR);

        if ($query->execute()) {
            echo "<script>
                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Notes has been updated successfully!',
                        confirmButtonColor: '#FFD700',
                        color: '#003366',
                        background: 'white'
                    }).then(() => {
                        window.location.href = 'manage-notes.php';
                    });
                }, 100);
            </script>";
        } else {
            echo "<script>
                setTimeout(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong. Try again.',
                        confirmButtonColor: '#FFD700',
                        color: '#003366',
                        background: 'white'
                    });
                }, 100);
            </script>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Notes</title>
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        label {
            font-weight: 600;
            color: #003366;
        }
        .form-control:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }
        .btn-primary {
            background-color: #FFD700;
            color: #003366;
            border: none;
        }
        .btn-primary:hover {
            background-color: #e6c200;
            color: black;
        }
    </style>
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include_once('includes/sidebar.php'); ?>
    <div class="content">
        <?php include_once('includes/header2.php'); ?>
        <div class="container-fluid pt-4 px-4">
            <div class="row g-4">
                <div class="col-sm-12 col-xl-8 mx-auto">
                    <div class="bg-white rounded shadow p-4">
                        <h4 class="mb-4 fw-bold text-center" style="color: #003366;">✏️ Update Notes</h4>
                        <form method="post">
                            <?php
                            $eid = $_GET['editid'];
                            $sql = "SELECT * FROM tblnotes WHERE ID=:eid";
                            $query = $dbh->prepare($sql);
                            $query->bindParam(':eid', $eid, PDO::PARAM_STR);
                            $query->execute();
                            $results = $query->fetchAll(PDO::FETCH_OBJ);
                            if ($query->rowCount() > 0) {
                                foreach ($results as $row) {
                            ?>
                            <div class="mb-3">
                                <label>Subject</label>
                                <input type="text" name="subject" class="form-control" value="<?php echo htmlentities($row->Subject); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Notes Title</label>
                                <input type="text" name="notestitle" class="form-control" value="<?php echo htmlentities($row->NotesTitle); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Notes Description</label>
                                <textarea name="notesdesc" class="form-control" required><?php echo htmlentities($row->NotesDecription); ?></textarea>
                            </div>
                            <?php for ($i = 1; $i <= 4; $i++) {
                                $fileKey = "File$i";
                                $folder = "folder$i";
                            ?>
                                <div class="mb-3">
                                    <label>View File<?php echo $i; ?></label><br>
                                    <?php if ($row->$fileKey == "") { ?>
                                        <strong style="color: red">File is not available</strong>
                                    <?php } else { ?>
                                        <a href="<?php echo $folder . '/' . $row->$fileKey; ?>" target="_blank"><strong style="color: red">View</strong></a> |
                                        <a href="changefile<?php echo $i; ?>.php?editid=<?php echo $row->ID; ?>" target="_blank"><strong style="color: red">Edit</strong></a>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                            <div class="text-center">
                                <button type="submit" name="submit" class="btn btn-primary px-4 py-2">Update</button>
                            </div>
                            <?php }} ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include_once('includes/footer.php'); ?>
    </div>
    <?php include_once('includes/back-totop.php'); ?>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
<?php } ?>