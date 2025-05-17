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

// Add filtering functionality
$filter_action = isset($_GET['action']) ? filter_var($_GET['action'], FILTER_SANITIZE_STRING) : '';
$filter_user = isset($_GET['user']) ? filter_var($_GET['user'], FILTER_VALIDATE_INT) : '';
$filter_date_from = isset($_GET['date_from']) ? filter_var($_GET['date_from'], FILTER_SANITIZE_STRING) : '';
$filter_date_to = isset($_GET['date_to']) ? filter_var($_GET['date_to'], FILTER_SANITIZE_STRING) : '';
$filter_status = isset($_GET['status']) ? filter_var($_GET['status'], FILTER_SANITIZE_STRING) : '';

// Build the base query
$sql = "SELECT al.*, u.name 
        FROM activity_log al
        LEFT JOIN registered_users u ON al.user_id = u.ID
        WHERE 1=1";

$params = [];

// Add filters to the query
if (!empty($filter_action)) {
    $sql .= " AND al.action_type LIKE :action";
    $params[':action'] = "%$filter_action%";
}

if (!empty($filter_user) && $filter_user > 0) {
    $sql .= " AND al.user_id = :user_id";
    $params[':user_id'] = $filter_user;
}

if (!empty($filter_date_from)) {
    $sql .= " AND DATE(al.created_at) >= :date_from";
    $params[':date_from'] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $sql .= " AND DATE(al.created_at) <= :date_to";
    $params[':date_to'] = $filter_date_to;
}

if (!empty($filter_status)) {
    $sql .= " AND al.status = :status";
    $params[':status'] = $filter_status;
}

// Add sorting
$sort = isset($_GET['sort']) ? filter_var($_GET['sort'], FILTER_SANITIZE_STRING) : 'created_at';
$order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
$sql .= " ORDER BY $sort $order";

// Prepare and execute the query
try {
    $query = $dbh->prepare($sql);
    foreach ($params as $key => $value) {
        $query->bindValue($key, $value);
    }
    $query->execute();
    $logs = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
    $msgClass = "alert-danger";
    error_log("Error fetching logs: " . $e->getMessage());
}

// Get distinct action types for filter dropdown
try {
    $actionTypesQuery = $dbh->query("SELECT DISTINCT action_type FROM activity_log ORDER BY action_type");
    $actionTypes = $actionTypesQuery->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $actionTypes = [];
    error_log("Error fetching action types: " . $e->getMessage());
}

// Get users for filter dropdown
try {
    $usersQuery = $dbh->query("SELECT id, name FROM registered_users ORDER BY name ASC");
    $users = $usersQuery->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $users = [];
    error_log("Error fetching users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>System Logs</title>
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
            background-color: #FFD700;
        }
        .btn-custom {
            background-color: #003366;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color:#FFD700;
            color: black;
        }
        .log-header {
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
        .filter-section {
            background-color: #FFD700;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .log-details {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .log-details:hover {
            white-space: normal;
            overflow: visible;
            position: absolute;
            z-index: 1000;
            background-color: white;
            border: 1px solid #ddd;
            padding: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .sortable {
            cursor: pointer;
        }
        .sortable:hover {
            text-decoration: underline;
        }
        .success-badge {
            background-color: green;
        }
        .error-badge {
            background-color: red;
        }
        .warning-badge {
            background-color: #FFD700;
            color: #003366;
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
            <div class="log-header text-center">
                <h4 class="fw-bold text-white">📝 System Activity Logs</h4>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section mb-4">
                <form method="get" action="system-logs.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="action" class="form-label">Action Type</label>
                        <select class="form-select" id="action" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actionTypes as $type): ?>
                                <option value="<?php echo htmlentities($type); ?>" <?php echo $filter_action === $type ? 'selected' : ''; ?>>
                                    <?php echo htmlentities($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="user" class="form-label">User</label>
                        <select class="form-select" id="user" name="user">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlentities($user->id); ?>" <?php echo $filter_user == $user->id ? 'selected' : ''; ?>>
                                    <?php echo htmlentities($user->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>Success</option>
                            <option value="error" <?php echo $filter_status === 'error' ? 'selected' : ''; ?>>Error</option>
                            <option value="warning" <?php echo $filter_status === 'warning' ? 'selected' : ''; ?>>Warning</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlentities($filter_date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlentities($filter_date_to); ?>">
                    </div>
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-custom me-2">Apply Filters</button>
                        <a href="system-logs.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded shadow p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th class="sortable" onclick="sortTable('id')"># 
                                    <?php if ($sort === 'id'): ?>
                                        <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </th>
                                <th class="sortable" onclick="sortTable('created_at')">Timestamp 
                                    <?php if ($sort === 'created_at'): ?>
                                        <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </th>
                                <th>User</th>
                                <th class="sortable" onclick="sortTable('action_type')">Action Type 
                                    <?php if ($sort === 'action_type'): ?>
                                        <i class="bi bi-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlentities($log->id); ?></td>
                                <td><?php echo htmlentities(date('Y-m-d H:i:s', strtotime($log->created_at))); ?></td>
                                <td>
                                    <?php if ($log->user_id): ?>
                                        <?php echo htmlentities($log->FirstName . ' ' . $log->LastName); ?>
                                        <br><small class="text-muted">ID: <?php echo htmlentities($log->user_id); ?></small>
                                    <?php else: ?>
                                        System
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlentities($log->action_type); ?></td>
                                <td class="log-details" title="<?php echo htmlentities($log->action_description); ?>">
                                    <?php echo htmlentities($log->action_description); ?>
                                </td>
                                <td><?php echo htmlentities($log->ip_address); ?></td>
                                <td>
                                    <?php if ($log->status === 'success'): ?>
                                        <span class="badge success-badge"><i class="bi bi-check-circle-fill me-1"></i> Success</span>
                                    <?php elseif ($log->status === 'error'): ?>
                                        <span class="badge error-badge"><i class="bi bi-x-circle-fill me-1"></i> Error</span>
                                    <?php else: ?>
                                        <span class="badge warning-badge"><i class="bi bi-exclamation-triangle-fill me-1"></i> Warning</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                   <button 
    style="background-color: #FFD700; color: #003366; border: none; padding: 0.25rem 0.5rem; font-size: 0.875rem; border-radius: 0.25rem; cursor: pointer; transition: background-color 0.3s ease;"
    onmouseover="this.style.backgroundColor='#e6c200'"
    onmouseout="this.style.backgroundColor='#FFD700'"
    onclick="showLogDetails(<?php echo htmlentities(json_encode($log)); ?>)"
>
    <i class="bi bi-eye-fill" style="margin-right: 0.25rem;"></i> View
</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No logs found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php include_once('includes/footer.php'); ?>
    </div>
    <?php include_once('includes/back-totop.php'); ?>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border: 2px solid #003366;">
            <div class="modal-header" style="background-color: #003366; color: white; border-bottom: 2px solid #FFD700;">
                <h5 class="modal-title" id="logDetailsModalLabel" style="font-weight: 600;">Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background-color: #f8f9fa;">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 style="color: #003366; font-weight: 600; border-bottom: 1px solid #FFD700; padding-bottom: 5px;">Basic Information</h6>
                        <table class="table table-sm">
                            <tr style="background-color: rgba(0, 51, 102, 0.05);">
                                <th width="40%" style="color: #003366;">Log ID:</th>
                                <td id="detail-id" style="font-weight: 500;"></td>
                            </tr>
                            <tr>
                                <th style="color: #003366;">Timestamp:</th>
                                <td id="detail-timestamp" style="font-weight: 500;"></td>
                            </tr>
                            <tr style="background-color: rgba(0, 51, 102, 0.05);">
                                <th style="color: #003366;">User:</th>
                                <td id="detail-user" style="font-weight: 500;"></td>
                            </tr>
                            <tr>
                                <th style="color: #003366;">Action Type:</th>
                                <td id="detail-action-type" style="font-weight: 500;"></td>
                            </tr>
                            <tr style="background-color: rgba(0, 51, 102, 0.05);">
                                <th style="color: #003366;">Status:</th>
                                <td id="detail-status" style="font-weight: 500;"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 style="color: #003366; font-weight: 600; border-bottom: 1px solid #FFD700; padding-bottom: 5px;">Technical Information</h6>
                        <table class="table table-sm">
                            <tr style="background-color: rgba(0, 51, 102, 0.05);">
                                <th width="40%" style="color: #003366;">IP Address:</th>
                                <td id="detail-ip" style="font-weight: 500;"></td>
                            </tr>
                            <tr>
                                <th style="color: #003366;">User Agent:</th>
                                <td id="detail-user-agent" style="font-weight: 500;"></td>
                            </tr>
                            <tr style="background-color: rgba(0, 51, 102, 0.05);">
                                <th style="color: #003366;">Request Method:</th>
                                <td id="detail-method" style="font-weight: 500;"></td>
                            </tr>
                            <tr>
                                <th style="color: #003366;">Request URI:</th>
                                <td id="detail-uri" style="font-weight: 500;"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h6 style="color: #003366; font-weight: 600; border-bottom: 1px solid #FFD700; padding-bottom: 5px; margin-top: 15px;">Description</h6>
                        <div class="p-3 rounded mb-3" id="detail-description" style="background-color: white; border: 1px solid #dee2e6;"></div>
                        
                        <h6 style="color: #003366; font-weight: 600; border-bottom: 1px solid #FFD700; padding-bottom: 5px;">Affected Resource</h6>
                        <div class="p-3 rounded mb-3" id="detail-affected-resource" style="background-color: white; border: 1px solid #dee2e6;"></div>
                        
                        <h6 style="color: #003366; font-weight: 600; border-bottom: 1px solid #FFD700; padding-bottom: 5px;">Metadata</h6>
                        <pre class="p-3 rounded" id="detail-metadata" style="background-color: white; border: 1px solid #dee2e6; max-height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #003366; border-top: 2px solid #FFD700;">
                <button type="button" class="btn" data-bs-dismiss="modal" 
                        style="background-color: #FFD700; color: #003366; font-weight: 600; border: none; padding: 6px 12px;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<script>
    function sortTable(column) {
        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get('sort');
        const currentOrder = url.searchParams.get('order');
        
        let newOrder = 'DESC';
        if (currentSort === column && currentOrder === 'DESC') {
            newOrder = 'ASC';
        }
        
        url.searchParams.set('sort', column);
        url.searchParams.set('order', newOrder);
        window.location.href = url.toString();
    }
    
    function showLogDetails(log) {
        // Format the timestamp
        const timestamp = new Date(log.created_at).toLocaleString();
        
        // Set basic information
        $('#detail-id').text(log.id);
        $('#detail-timestamp').text(timestamp);
        $('#detail-user').text(log.user_id ? (log.FirstName + ' ' + log.LastName + ' (ID: ' + log.user_id + ')') : 'System');
        $('#detail-action-type').text(log.action_type);
        
        // Set status with badge
        let statusBadge = '';
        if (log.status === 'success') {
            statusBadge = '<span class="badge success-badge"><i class="bi bi-check-circle-fill me-1"></i> Success</span>';
        } else if (log.status === 'error') {
            statusBadge = '<span class="badge error-badge"><i class="bi bi-x-circle-fill me-1"></i> Error</span>';
        } else {
            statusBadge = '<span class="badge warning-badge"><i class="bi bi-exclamation-triangle-fill me-1"></i> Warning</span>';
        }
        $('#detail-status').html(statusBadge);
        
        // Set technical information
        $('#detail-ip').text(log.ip_address);
        $('#detail-user-agent').text(log.user_agent);
        $('#detail-method').text(log.request_method);
        $('#detail-uri').text(log.request_uri);
        
        // Set description and affected resource
        $('#detail-description').text(log.action_description);
        $('#detail-affected-resource').text(log.affected_resource || 'N/A');
        
        // Format and display metadata
        try {
            const metadata = JSON.parse(log.metadata);
            $('#detail-metadata').text(JSON.stringify(metadata, null, 2));
        } catch (e) {
            $('#detail-metadata').text(log.metadata || 'N/A');
        }
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
        modal.show();
    }
</script>
</body>
</html>