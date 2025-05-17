<?php
session_start();
error_reporting(0);
include('config.php');
include_once('log-activity.php');

if (strlen($_SESSION['ocasuid']) == 0) {
    header('location:logout.php');
    exit;
}

// Get user type from database
$userType = '';
$userQuery = $dbh->prepare("SELECT usertype FROM registered_users WHERE ID = :ocasuid");
$userQuery->bindParam(':ocasuid', $_SESSION['ocasuid'], PDO::PARAM_STR);
$userQuery->execute();
$userData = $userQuery->fetch(PDO::FETCH_ASSOC);
if ($userData) {
    $userType = $userData['usertype'];
}

if (isset($_POST['submit'])) {
    $ocasuid = $_SESSION['ocasuid'];
    $subject = $_POST['subject'];
    $notestitle = $_POST['notestitle'];
    $notesdesc = $_POST['notesdesc'];
    $notestype = $_POST['notestype'] ?? 'other'; // Default to 'other' if not selected
    $file1 = $_FILES["file1"]["name"];
    $extension1 = strtolower(substr($file1, strrpos($file1, '.')));
    $allowed_extensions = array(".docx", ".zip", ".doc", ".pptx", ".pdf");

    if (!in_array($extension1, $allowed_extensions)) {
        $alert = [
            'icon' => 'error',
            'title' => 'Invalid File Format',
            'text' => 'Only docs /zip/ docx / pptx / pdf allowed.',
            'confirmButtonColor' => '#003366'
        ];
    } else {
        $newFilename = md5($file1) . $extension1;
        $targetFilePath = "notes/" . $newFilename;
        
        // Check if file already exists
        if (file_exists($targetFilePath)) {
             $status ='failure';
            logActivity(
                        $_SESSION['ocasuid'],
                        'Attempted to upload note that is already exist',
                        "Approved notes named: $file1",
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REQUEST_METHOD'],
                        $_SERVER['REQUEST_URI'],
                        "note:$notestitle",
                        json_encode(['uploaded_by' => $_SESSION['ocasuid']]),
                        $status
                    );
            $alert = [
                'icon' => 'error',
                'title' => 'File Exists',
                'text' => 'A file with this name already exists. Please try again.',
                'confirmButtonColor' => '#003366'
            ];
        } else {
            if (move_uploaded_file($_FILES["file1"]["tmp_name"], $targetFilePath)) {
                // Set status based on user type
                $status = ($userType === 'admin') ? 'Approved' : 'Pending';
                
                // Check if notes with same title already exists for this user
                $checkQuery = $dbh->prepare("SELECT COUNT(*) FROM tblnotes WHERE UserID = :ocasuid AND NotesTitle = :notestitle");
                $checkQuery->bindParam(':ocasuid', $ocasuid, PDO::PARAM_STR);
                $checkQuery->bindParam(':notestitle', $notestitle, PDO::PARAM_STR);
                $checkQuery->execute();
                $noteExists = $checkQuery->fetchColumn();
                
                if ($noteExists > 0) {
                    // Delete the uploaded file since we won't be using it
                    unlink($targetFilePath);
                $status ='failure';
            logActivity(
                        $_SESSION['ocasuid'],
                        'Attempted to upload note that is already exist and file deleted',
                        "Approved notes named: $file1",
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REQUEST_METHOD'],
                        $_SERVER['REQUEST_URI'],
                        "note:$notestitle",
                        json_encode(['uploaded_by' => $_SESSION['ocasuid']]),
                        $status
                    );
                    $alert = [
                        'icon' => 'error',
                        'title' => 'Duplicate Note',
                        'text' => 'A note with this title already exists for your account.',
                        'confirmButtonColor' => '#003366'
                    ];
                } else {
                    $sql = "INSERT INTO tblnotes(UserID, Subject, NotesTitle, NotesDecription, File1, NotesType, Status) 
                            VALUES(:ocasuid, :subject, :notestitle, :notesdesc, :file1, :notestype, :status)";
                    $query = $dbh->prepare($sql);
                    $query->bindParam(':ocasuid', $ocasuid, PDO::PARAM_STR);
                    $query->bindParam(':subject', $subject, PDO::PARAM_STR);
                    $query->bindParam(':notestitle', $notestitle, PDO::PARAM_STR);
                    $query->bindParam(':notesdesc', $notesdesc, PDO::PARAM_STR);
                    $query->bindParam(':file1', $newFilename, PDO::PARAM_STR);
                    $query->bindParam(':notestype', $notestype, PDO::PARAM_STR);
                    $query->bindParam(':status', $status, PDO::PARAM_STR);

                    if ($query->execute()) {
                        $status ='success';
            logActivity(
                        $_SESSION['ocasuid'],
                        'Uploaded note successfully',
                        "Uploaded notes named: $file1",
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REQUEST_METHOD'],
                        $_SERVER['REQUEST_URI'],
                        "note:$notestitle",
                        json_encode(['uploaded_by' => $_SESSION['ocasuid']]),
                        $status
                    );
                        $alert = [
                            'icon' => 'success',
                            'title' => 'Success',
                            'text' => 'Notes added successfully. ' . ($status === 'Pending' ? 'Waiting for admin approval.' : ''),
                            'confirmButtonColor' => '#003366',
                            'redirect' => 'add-notes.php'
                        ];
                    } else {
                        // Delete the uploaded file if database insertion fails
                        unlink($targetFilePath);
                        
                        $alert = [
                            'icon' => 'error',
                            'title' => 'Database Error',
                            'text' => 'Failed to insert record.',
                            'confirmButtonColor' => '#003366'
                        ];
                    }
                }
            } else {
                $alert = [
                    'icon' => 'error',
                    'title' => 'Upload Error',
                    'text' => 'File upload failed.',
                    'confirmButtonColor' => '#003366'
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Notes</title>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .note-header {
            background-color: #003366;
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .form-container {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 2rem;
        }
        .form-control, .form-select {
            border: 1px solid #003366;
        }
        .form-control:focus, .form-select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25);
        }
        .btn-custom {
            background-color: #FFD700;
            color: #003366;
            border: none;
            font-weight: 600;
        }
        .btn-custom:hover {
            background-color: #e6c200;
            color: #003366;
        }
        .btn-back {
            background-color: #003366;
            color: white;
            font-weight: 600;
        }
        .btn-back:hover {
            background-color: #002244;
            color: white;
        }
    </style>
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include_once('includes/sidebar.php'); ?>
    <div class="content">
        <?php include_once('includes/header2.php'); ?>

        <div class="container-fluid pt-4 px-4">
            <div class="note-header text-center">
                <h4 class="fw-bold text-white">📝 Add New Notes</h4>
            </div>
            <div class="form-container">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="notestitle" class="form-label">Notes Title</label>
                        <input type="text" class="form-control" id="notestitle" name="notestitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="notesdesc" class="form-label">Notes Description</label>
                        <textarea class="form-control" id="notesdesc" name="notesdesc" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="notestype" class="form-label">Notes Type</label>
                        <select class="form-select" id="notestype" name="notestype" required>
                            <option value="book">Book</option>
                            <option value="notes">Notes</option>
                            <option value="papers">Past Papers</option>
                            <option value="other" selected>Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="file1" class="form-label">Upload File</label>
                        <input type="file" class="form-control" id="file1" name="file1" required>
                        <div class="form-text">Allowed formats: .docx, .zip, .doc, .pptx, .pdf max size of a file is 20MB</div>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="submit" name="submit" class="btn btn-custom px-4">
                            <i class="fas fa-upload me-2"></i>Upload Notes
                        </button>
                        <a href="dashboard.php" class="btn btn-back px-4">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php include_once('includes/footer.php'); ?>
    </div>
    <?php include_once('includes/back-totop.php'); ?>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<?php if (isset($alert)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?= $alert['icon'] ?>',
            title: '<?= $alert['title'] ?>',
            text: '<?= $alert['text'] ?>',
            confirmButtonColor: '<?= $alert['confirmButtonColor'] ?>'
        }).then((result) => {
            <?php if (isset($alert['redirect'])): ?>
            if (result.isConfirmed || result.isDismissed) {
                window.location.href = '<?= $alert['redirect'] ?>';
            }
            <?php endif; ?>
        });
    });
</script>
<?php endif; ?>
</body>
</html>