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

// Backup creation functionality
if (isset($_POST['create_backup'])) {
    try {
        // Get all tables
        $tables = $dbh->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $backupSQL = "-- Database Backup\n";
        $backupSQL .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            // Table structure
            $backupSQL .= "--\n-- Table structure for table `$table`\n--\n";
            $createTable = $dbh->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            $backupSQL .= $createTable['Create Table'] . ";\n\n";
            
            // Table data
            $backupSQL .= "--\n-- Dumping data for table `$table`\n--\n";
            $rows = $dbh->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                $columns = array_keys($rows[0]);
                $backupSQL .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES \n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = array_map(function($value) use ($dbh) {
                        if ($value === null) return 'NULL';
                        return $dbh->quote($value);
                    }, $row);
                    $values[] = "(" . implode(", ", $rowValues) . ")";
                }
                $backupSQL .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        // Save backup to file
        $backupDir = 'backups/';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupFilePath = $backupDir . $backupFileName;
        
        if (file_put_contents($backupFilePath, $backupSQL)) {
            $message = "Backup created successfully!";
            $msgClass = "alert-success";
            
            // Log the backup creation
            logActivity(
                $_SESSION['ocasuid'],
                'backup_created',
                "Database backup created: $backupFileName",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'],
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REQUEST_URI'],
                "backup:$backupFileName",
                json_encode(['size' => filesize($backupFilePath)])
            );
        } else {
            $message = "Error saving backup file.";
            $msgClass = "alert-danger";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $msgClass = "alert-danger";
    }
}

// Backup restoration functionality
if (isset($_GET['restore'])) {
    $backupFile = filter_var($_GET['restore'], FILTER_SANITIZE_STRING);
    $backupPath = 'backups/' . $backupFile;
    
    if (file_exists($backupPath)) {
        try {
            // Read backup file
            $sql = file_get_contents($backupPath);
            
            // Execute queries
            $dbh->exec($sql);
            
            $message = "Backup restored successfully!";
            $msgClass = "alert-success";
            
            // Log the restoration
            logActivity(
                $_SESSION['ocasuid'],
                'backup_restored',
                "Database backup restored: $backupFile",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'],
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REQUEST_URI'],
                "backup:$backupFile",
                json_encode(['restored_by' => $_SESSION['ocasuid']])
            );
        } catch (PDOException $e) {
            $message = "Error restoring backup: " . $e->getMessage();
            $msgClass = "alert-danger";
        }
    } else {
        $message = "Backup file not found.";
        $msgClass = "alert-danger";
    }
}

// Backup deletion functionality
if (isset($_GET['delete'])) {
    $backupFile = filter_var($_GET['delete'], FILTER_SANITIZE_STRING);
    $backupPath = 'backups/' . $backupFile;
    
    if (file_exists($backupPath)) {
        if (unlink($backupPath)) {
            $message = "Backup deleted successfully!";
            $msgClass = "alert-success";
            
            // Log the deletion
            logActivity(
                $_SESSION['ocasuid'],
                'backup_deleted',
                "Database backup deleted: $backupFile",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'],
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REQUEST_URI'],
                "backup:$backupFile",
                json_encode(['deleted_by' => $_SESSION['ocasuid']])
            );
        } else {
            $message = "Error deleting backup file.";
            $msgClass = "alert-danger";
        }
    } else {
        $message = "Backup file not found.";
        $msgClass = "alert-danger";
    }
}

// Get list of existing backups
$backupDir = 'backups/';
$backups = [];
if (file_exists($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'path' => $backupDir . $file,
                'size' => filesize($backupDir . $file),
                'modified' => filemtime($backupDir . $file)
            ];
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
    <title>Database Backups</title>
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
        .table thead th {
            background-color: #003366;
            color: #fff;
            position: sticky;
            top: 0;
        }
        .table tbody tr:hover {
            background-color: rgba(255, 215, 0, 0.2);
        }
        .btn-custom {
            background-color: #003366;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color: #002244;
            color: white;
        }
        .btn-gold {
            background-color: #FFD700;
            color: #003366;
            border: none;
        }
        .btn-gold:hover {
            background-color: #e6c200;
            color: #003366;
        }
        .backup-header {
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
        .action-section {
            background-color: #FFD700;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .file-size {
            font-family: monospace;
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
            <div class="backup-header text-center">
                <h4 class="fw-bold text-white">💾 Database Backup Management</h4>
            </div>
            
            <!-- Create Backup Section -->
            <div class="action-section mb-4">
                <form method="post" action="backup.php" class="row g-3">
                    <div class="col-md-12">
                        <h5 style="color: #003366; font-weight: 600;">Create New Backup</h5>
                        <p>Generate a complete backup of the database including all tables and data.</p>
                    </div>
                    <div class="col-md-12 text-end">
                        <button type="submit" name="create_backup" class="btn btn-gold">
                            <i class="bi bi-database-add me-1"></i> Create Backup Now
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Backups Section -->
            <div class="bg-white rounded shadow p-4">
                <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                    <i class="bi bi-archive me-2"></i>Existing Backups
                </h5>
                
                <?php if (!empty($backups)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Backup File</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $index => $backup): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlentities($backup['name']); ?></td>
                                    <td class="file-size"><?php echo formatSizeUnits($backup['size']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', $backup['modified']); ?></td>
                                    <td>
                                        <a href="backups/<?php echo htmlentities($backup['name']); ?>" 
                                           class="btn btn-sm btn-custom me-1" download>
                                            <i class="bi bi-download me-1"></i> Download
                                        </a>
                                        <a href="javascript:void(0);" 
                                           onclick="confirmRestore('<?php echo htmlentities($backup['name']); ?>')" 
                                           class="btn btn-sm btn-success me-1">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                        </a>
                                        <a href="javascript:void(0);" 
                                           onclick="confirmDelete('<?php echo htmlentities($backup['name']); ?>')" 
                                           class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        No backup files found. Create your first backup using the button above.
                    </div>
                <?php endif; ?>
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
    function confirmRestore(backupFile) {
        Swal.fire({
            title: 'Restore Backup?',
            html: `Are you sure you want to restore <b>${backupFile}</b>?<br><br>
                  <span class="text-danger">This will overwrite your current database!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#003366',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, restore it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'backup.php?restore=' + backupFile;
            }
        });
    }
    
    function confirmDelete(backupFile) {
        Swal.fire({
            title: 'Delete Backup?',
            text: `Are you sure you want to permanently delete ${backupFile}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#003366',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'backup.php?delete=' + backupFile;
            }
        });
    }
</script>
</body>
</html>
<?php
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}
?>