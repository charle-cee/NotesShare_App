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
            $checkSql = "SELECT id FROM registered_users WHERE id = :rid LIMIT 1";
            $checkQuery = $dbh->prepare($checkSql);
            $checkQuery->bindParam(':rid', $rid, PDO::PARAM_INT);
            $checkQuery->execute();

            if ($checkQuery->rowCount() > 0) {
                $deleteSql = "DELETE FROM registered_users WHERE id = :rid";
                $deleteQuery = $dbh->prepare($deleteSql);
                $deleteQuery->bindParam(':rid', $rid, PDO::PARAM_INT);

                if ($deleteQuery->execute()) {
                    $message = "User deleted successfully!";
                    $msgClass = "alert-success";

                    logActivity(
                        $_SESSION['ocasuid'],
                        'user_deletion',
                        "Deleted user account with ID: $rid",
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REQUEST_METHOD'],
                        $_SERVER['REQUEST_URI'],
                        "user:$rid",
                        json_encode(['deleted_by' => $_SESSION['ocasuid']])
                    );
                } else {
                    $message = "Error deleting user. Please try again.";
                    $msgClass = "alert-danger";

                    logActivity(
                        $_SESSION['ocasuid'],
                        'user_deletion_failed',
                        "Failed to delete user ID: $rid",
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT'],
                        $_SERVER['REQUEST_METHOD'],
                        $_SERVER['REQUEST_URI'],
                        "user:$rid",
                        json_encode(['error' => 'Database delete failed'])
                    );
                }
            } else {
                $message = "User not found";
                $msgClass = "alert-warning";

                logActivity(
                    $_SESSION['ocasuid'],
                    'user_not_found',
                    "Attempted to delete non-existent user ID: $rid",
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
                "Database error during user deletion",
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

            error_log("Database error in user deletion: " . $e->getMessage());
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
                <h4 class="fw-bold text-white">📚 Manage Users</h4>
            </div>
            <div class="bg-white rounded shadow p-4">
                <div class="table-responsive table-striped">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Creation Date</th>
                                <th>Verification Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $ocasuid = $_SESSION['ocasuid'];
                        $sql = "SELECT * FROM registered_users ORDER BY id DESC";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        $cnt = 1;
                        if ($query->rowCount() > 0) {
                            foreach ($results as $row) {
                        ?>
                        <tr>
                            <td><?php echo !empty($cnt) ? htmlentities($cnt) : 'N/A'; ?></td>
<td><?php echo !empty($row->name) ? htmlentities($row->name) : 'N/A'; ?></td>
<td><?php echo !empty($row->email) ? htmlentities($row->email) : 'N/A'; ?></td>
<td><?php echo !empty($row->otp_sent) ? htmlentities($row->otp_sent) : 'N/A'; ?></td>

                            <td>
                                <?php
                                $status = $row->verified;
                                if ($status == 'yes') {
                                    echo '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> Verified</span>';
                                } elseif ($status == 'no') {
                                    echo '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> Not Verified</span>';
                                } else {
                                    echo '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending</span>';
                                }
                                ?>
                            </td>
<td style="display: flex; gap: 8px;">
    <a href="javascript:void(0);" 
       onclick="confirmSendEmail('<?php echo htmlentities($row->verified); ?>', '<?php echo addslashes(htmlentities($row->name)); ?>', '<?php echo addslashes(htmlentities($row->email)); ?>')" 
       style="background-color: #FFD700; color: #003366; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center;">
       <i class="fas fa-envelope" style="margin-right: 5px;"></i> Send Email
    </a>
    <a href="javascript:void(0);" 
       onclick="deleteNote(<?php echo htmlentities($row->id); ?>);" 
       style="background-color: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold; display: inline-flex; align-items: center;">
       <i class="fas fa-trash-alt" style="margin-right: 5px;"></i> Delete
    </a>
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
    function confirmSendEmail(verified, userName, userEmail) {
    Swal.fire({
        title: 'Confirm Email',
        html: `Are you sure you want to send email to <strong>${userName}</strong> (${userEmail})?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#003366',
        cancelButtonColor: '#dc3545',
        confirmButtonText: '<span style="color: #FFD700">Yes, send email</span>',
        cancelButtonText: 'Cancel',
        background: '#ffffff',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch('send-mail.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=sendMail&verified=${encodeURIComponent(verified)}&name=${encodeURIComponent(userName)}&email=${encodeURIComponent(userEmail)}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Success!',
                text: 'Email has been sent successfully.',
                icon: 'success',
                confirmButtonColor: '#003366',
                background: '#ffffff'
            });
        }
    });
}


function deleteNote(userId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the user account.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to the same page with delid query param
                window.location.href = `manage-users.php?delid=${userId}`;
            }
        });
    }
</script>
</body>
</html>

