
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once('config.php');

if (empty($_SESSION['ocasuid'])) {
    header('Location: logout.php');
    exit();
}

$message = '';
$msgClass = '';

if (isset($_GET['delid'])) {
    $rid = filter_var($_GET['delid'], FILTER_VALIDATE_INT);

    if ($rid === false || $rid <= 0) {
        $message = "Invalid user ID";
        $msgClass = "alert-danger";
    } else {
        try {
            $checkSql = "SELECT ID FROM tblnotes WHERE ID = :rid LIMIT 1";
            $checkQuery = $dbh->prepare($checkSql);
            $checkQuery->bindParam(':rid', $rid, PDO::PARAM_INT);
            $checkQuery->execute();

            if ($checkQuery->rowCount() > 0) {
                $deleteSql = "DELETE FROM tblnotes WHERE ID = :rid";
                $deleteQuery = $dbh->prepare($deleteSql);
                $deleteQuery->bindParam(':rid', $rid, PDO::PARAM_INT);

                if ($deleteQuery->execute()) {
                    $message = "notes deleted successfully!";
                    $msgClass = "alert-success";

                    logActivity(
                        $_SESSION['ocasuid'],
                        'notes_deletion',
                        "Deleted notes with ID: $rid",
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REQUEST_METHOD'],
                        $_SERVER['REQUEST_URI'],
                        "notes:$rid",
                        json_encode(['deleted_by' => $_SESSION['ocasuid']])
                    );
                } else {
                    $message = "Error deleting note. Please try again.";
                    $msgClass = "alert-danger";

                    logActivity(
                        $_SESSION['ocasuid'],
                        'notes_deletion_failed',
                        "Failed to delete note ID: $rid",
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REQUEST_METHOD'],
                        $_SERVER['REQUEST_URI'],
                        "notes:$rid",
                        json_encode(['error' => 'Database delete failed'])
                    );
                }
            } else {
                $message = "Notes not found";
                $msgClass = "alert-warning";

                logActivity(
                    $_SESSION['ocasuid'],
                    'user_not_found',
                    "Attempted to delete non-existent note ID: $rid",
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT'],
                    $_SERVER['REQUEST_METHOD'],
                    $_SERVER['REQUEST_URI'],
                    null,
                    json_encode(['attempted_id' => $rid])
                );
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $msgClass = "alert-danger";

            logActivity(
                $_SESSION['ocasuid'],
                'system_error',
                "Database error during notesr deletion",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'],
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REQUEST_URI'],
                null,
                json_encode([
                    'error' => $e->getMessage(),
                    'attempted_id' => $rid,
                    'trace' => $e->getTraceAsString()
                ])
            );

            error_log("Database error in notes deletion: " . $e->getMessage());
        }
    }
}

function logActivity(
    $userId,
    $actionType,
    $actionDescription,
    $ipAddress,
    $userAgent,
    $requestMethod,
    $requestUri,
    $affectedResource = null,
    $metadata = null,
    $status = 'success'
) {
    global $dbh;

    try {
        $sql = "INSERT INTO activity_log 
                (user_id, action_type, action_description, ip_address, user_agent, 
                 request_method, request_uri, affected_resource, metadata, status, created_at) 
                VALUES 
                (:user_id, :action_type, :action_desc, :ip, :ua, 
                 :method, :uri, :affected, :metadata, :status, NOW())";

        $query = $dbh->prepare($sql);
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':action_type', $actionType, PDO::PARAM_STR);
        $query->bindParam(':action_desc', $actionDescription, PDO::PARAM_STR);
        $query->bindParam(':ip', $ipAddress, PDO::PARAM_STR);
        $query->bindParam(':ua', $userAgent, PDO::PARAM_STR);
        $query->bindParam(':method', $requestMethod, PDO::PARAM_STR);
        $query->bindParam(':uri', $requestUri, PDO::PARAM_STR);
        $query->bindParam(':affected', $affectedResource, PDO::PARAM_STR);
        $query->bindParam(':metadata', $metadata, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();
    } catch (PDOException $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Notes</title>
    <meta  charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->
    <style>
        .table thead th {
            background-color: #003366;
            color: #fff;
        }
        .table tbody tr:hover {
            background-color: #f0f0f0;
        }
        .btn-custom {
            background-color: #FFD700;
            color: #003366;
            border: none;
        }
        .btn-custom:hover {
            background-color: #e6c200;
            color: black;
        }
        .note-header {
            background-color: #003366;
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .alert {
            margin-top: 20px;
        }
        .badge i {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include_once('includes/sidebar.php'); ?>
    <div class="content">
        <?php include_once('includes/header2.php'); ?>

        <!-- Displaying the success/error message -->
        <?php if (isset($message)): ?>
            <div class="alert <?php echo $msgClass; ?> text-center">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="container-fluid pt-4 px-4">
            <div class="note-header text-center">
                <h4 class="fw-bold text-white">📚 Manage Notes</h4>
            </div>
            <div class="bg-white rounded shadow p-4">
                <div class="table-responsive table-striped">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Notes Title</th>
                                <th>Creation Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $ocasuid = $_SESSION['ocasuid'];
                        $sql = "SELECT * FROM tblnotes WHERE UserID = :ocasuid ORDER BY CreationDate DESC";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':ocasuid', $ocasuid, PDO::PARAM_STR);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        $cnt = 1;
                        if ($query->rowCount() > 0) {
                            foreach ($results as $row) {
                        ?>
                        <tr>
                            <td><?php echo htmlentities($cnt); ?></td>
                            <td><?php echo htmlentities($row->Subject); ?></td>
                            <td><?php echo htmlentities($row->NotesTitle); ?></td>
                            <td><?php echo htmlentities($row->CreationDate); ?></td>
                            <td>
                                <?php
                                $status = $row->Status;
                                if ($status == 'Approved') {
                                    echo '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Approved</span>';
                                } elseif ($status == 'Rejected') {
                                    echo '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> Rejected</span>';
                                } else {
                                    echo '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="edit-notes.php?editid=<?php echo htmlentities($row->ID); ?>" class="btn btn-sm btn-custom">Edit</a>
                                <a href="javascript:void(0);" onclick="deleteNote(<?php echo htmlentities($row->ID); ?>);" class="btn btn-sm btn-danger">Delete</a>
                            </td>
                        </tr>
                        <?php $cnt++; }} else { ?>
                        <tr>
                            <td colspan="6" class="text-center">No notes found.</td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php include_once('includes/footer.php'); ?>
    </div>
    <?php include_once('includes/back-totop.php'); ?>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<script>
    function deleteNote(noteId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to undo this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#003366',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'manage-notes.php?delid=' + noteId;
            }
        });
    }
</script>
</body>
</html>

