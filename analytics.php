<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once('config.php');

if (empty($_SESSION['ocasuid'])) {
    header('Location: logout.php');
    exit();
}

// Check admin privileges using usertype column
$isAdmin = false;
try {
    $stmt = $dbh->prepare("SELECT usertype FROM registered_users WHERE id = :userid");
    $stmt->bindParam(':userid', $_SESSION['ocasuid'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['usertype'] === 'admin');
} catch (PDOException $e) {
    error_log("Error checking admin status: " . $e->getMessage());
}

if (!$isAdmin) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$msgClass = '';
$timeframe = isset($_GET['timeframe']) ? filter_var($_GET['timeframe'], FILTER_SANITIZE_STRING) : '7days';
$chartType = isset($_GET['chart']) ? filter_var($_GET['chart'], FILTER_SANITIZE_STRING) : 'activity';

// Calculate date ranges based on timeframe
$now = new DateTime();
$dateRanges = [
    '24hours' => ['start' => (clone $now)->modify('-24 hours'), 'end' => $now, 'interval' => 'hour'],
    '7days' => ['start' => (clone $now)->modify('-7 days'), 'end' => $now, 'interval' => 'day'],
    '30days' => ['start' => (clone $now)->modify('-30 days'), 'end' => $now, 'interval' => 'day'],
    '90days' => ['start' => (clone $now)->modify('-90 days'), 'end' => $now, 'interval' => 'week'],
    '12months' => ['start' => (clone $now)->modify('-12 months'), 'end' => $now, 'interval' => 'month']
];

$currentRange = $dateRanges[$timeframe] ?? $dateRanges['7days'];

// Function to fetch comprehensive analytics data
function fetchAnalyticsData($dbh, $startDate, $endDate, $interval) {
    $data = [];
    
    try {
        // 1. User Activity Over Time
        $query = "SELECT 
                    DATE_FORMAT(al.created_at, '%Y-%m-%d') AS date,
                    COUNT(*) AS activity_count
                  FROM activity_log al
                  WHERE al.created_at BETWEEN :start_date AND :end_date
                  GROUP BY DATE_FORMAT(al.created_at, '%Y-%m-%d')
                  ORDER BY date";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['activity_over_time'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Most Active Users
        $query = "SELECT 
                    u.id, u.name, u.email, u.usertype,
                    COUNT(al.id) AS activity_count
                  FROM activity_log al
                  JOIN registered_users u ON al.user_id = u.id
                  WHERE al.created_at BETWEEN :start_date AND :end_date
                  GROUP BY al.user_id
                  ORDER BY activity_count DESC
                  LIMIT 10";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['active_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Most Common Actions
        $query = "SELECT 
                    action_type,
                    COUNT(*) AS count
                  FROM activity_log
                  WHERE created_at BETWEEN :start_date AND :end_date
                  GROUP BY action_type
                  ORDER BY count DESC
                  LIMIT 10";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['common_actions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. System Status (success/error rates)
        $query = "SELECT 
                    status,
                    COUNT(*) AS count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM activity_log WHERE created_at BETWEEN :start_date AND :end_date), 2) AS percentage
                  FROM activity_log
                  WHERE created_at BETWEEN :start_date AND :end_date
                  GROUP BY status";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['status_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Device/Browser Breakdown
        $query = "SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Mobile%' THEN 'Mobile'
                        WHEN user_agent LIKE '%Tablet%' THEN 'Tablet'
                        ELSE 'Desktop'
                    END AS device_type,
                    COUNT(*) AS count
                  FROM activity_log
                  WHERE created_at BETWEEN :start_date AND :end_date
                  GROUP BY device_type";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['device_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. User Registration Trends
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m-%d') AS date,
                    COUNT(*) AS registration_count,
                    SUM(CASE WHEN usertype = 'admin' THEN 1 ELSE 0 END) AS admin_registrations,
                    SUM(CASE WHEN verified = 'yes' THEN 1 ELSE 0 END) AS verified_users
                  FROM registered_users
                  WHERE created_at BETWEEN :start_date AND :end_date
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
                  ORDER BY date";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['registration_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 7. Content Statistics (Notes, Comments, Likes)
        $query = "SELECT 
                    COUNT(*) AS total_notes,
                    SUM(CASE WHEN Status = 'Approved' THEN 1 ELSE 0 END) AS approved_notes,
                    SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) AS pending_notes,
                    SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_notes,
                    SUM(Likes) AS total_likes,
                    AVG(Likes) AS avg_likes_per_note
                  FROM tblnotes
                  WHERE CreationDate BETWEEN :start_date AND :end_date";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['content_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // 8. Most Active Content Contributors
        $query = "SELECT 
                    u.id, u.name, u.email,
                    COUNT(n.ID) AS notes_count,
                    SUM(n.Likes) AS total_likes,
                    AVG(n.Likes) AS avg_likes
                  FROM tblnotes n
                  JOIN registered_users u ON n.UserID = u.id
                  WHERE n.CreationDate BETWEEN :start_date AND :end_date
                  GROUP BY n.UserID
                  ORDER BY notes_count DESC
                  LIMIT 10";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['content_contributors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 9. Comment Activity
        $query = "SELECT 
                    u.id, u.name, u.email,
                    COUNT(c.ID) AS comments_count
                  FROM tblcomments c
                  JOIN registered_users u ON c.UserID = u.id
                  WHERE c.CommentDate BETWEEN :start_date AND :end_date
                  GROUP BY c.UserID
                  ORDER BY comments_count DESC
                  LIMIT 10";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['comment_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 10. Most Popular Notes
        $query = "SELECT 
                    n.ID, n.NotesTitle, n.Subject, n.NotesType,
                    n.Likes, COUNT(c.ID) AS comments_count,
                    u.name AS author_name
                  FROM tblnotes n
                  LEFT JOIN tblcomments c ON n.ID = c.NoteID
                  JOIN registered_users u ON n.UserID = u.id
                  WHERE n.CreationDate BETWEEN :start_date AND :end_date
                  GROUP BY n.ID
                  ORDER BY n.Likes DESC
                  LIMIT 10";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['popular_notes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 11. Content Type Distribution
        $query = "SELECT 
                    NotesType,
                    COUNT(*) AS count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tblnotes WHERE CreationDate BETWEEN :start_date AND :end_date), 2) AS percentage
                  FROM tblnotes
                  WHERE CreationDate BETWEEN :start_date AND :end_date
                  GROUP BY NotesType";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['content_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 12. Subject Distribution
        $query = "SELECT 
                    Subject,
                    COUNT(*) AS count
                  FROM tblnotes
                  WHERE CreationDate BETWEEN :start_date AND :end_date
                  GROUP BY Subject
                  ORDER BY count DESC
                  LIMIT 10";
        
        $stmt = $dbh->prepare($query);
        $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'));
        $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'));
        $stmt->execute();
        $data['subject_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error fetching analytics data: " . $e->getMessage());
    }
    
    return $data;
}

$analyticsData = fetchAnalyticsData($dbh, $currentRange['start'], $currentRange['end'], $currentRange['interval']);

// Prepare data for charts
$chartData = [
    'labels' => [],
    'datasets' => [
        [
            'label' => 'System Activity',
            'data' => [],
            'backgroundColor' => 'rgba(0, 51, 102, 0.7)',
            'borderColor' => 'rgba(0, 51, 102, 1)',
            'borderWidth' => 1
        ],
        [
            'label' => 'User Registrations',
            'data' => [],
            'backgroundColor' => 'rgba(255, 215, 0, 0.7)',
            'borderColor' => 'rgba(255, 215, 0, 1)',
            'borderWidth' => 1
        ]
    ]
];

foreach ($analyticsData['activity_over_time'] as $entry) {
    $chartData['labels'][] = $entry['date'];
    $chartData['datasets'][0]['data'][] = $entry['activity_count'];
}

foreach ($analyticsData['registration_trends'] as $entry) {
    $chartData['datasets'][1]['data'][] = $entry['registration_count'];
}

$pieChartData = [
    'labels' => [],
    'datasets' => [
        [
            'data' => [],
            'backgroundColor' => [
                'rgba(0, 51, 102, 0.7)',
                'rgba(255, 215, 0, 0.7)',
                'rgba(220, 53, 69, 0.7)',
                'rgba(40, 167, 69, 0.7)',
                'rgba(23, 162, 184, 0.7)',
                'rgba(111, 66, 193, 0.7)',
                'rgba(253, 126, 20, 0.7)'
            ]
        ]
    ]
];

foreach ($analyticsData['content_types'] as $entry) {
    $pieChartData['labels'][] = ucfirst($entry['NotesType']);
    $pieChartData['datasets'][0]['data'][] = $entry['count'];
}

$contentContributorsData = [
    'labels' => [],
    'datasets' => [
        [
            'label' => 'Notes Uploaded',
            'data' => [],
            'backgroundColor' => 'rgba(0, 51, 102, 0.7)'
        ],
        [
            'label' => 'Avg Likes per Note',
            'data' => [],
            'backgroundColor' => 'rgba(255, 215, 0, 0.7)'
        ]
    ]
];

foreach ($analyticsData['content_contributors'] as $contributor) {
    $contentContributorsData['labels'][] = $contributor['name'];
    $contentContributorsData['datasets'][0]['data'][] = $contributor['notes_count'];
    $contentContributorsData['datasets'][1]['data'][] = $contributor['avg_likes'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>System Analytics</title>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table thead th {
            background-color: #003366;
            color: #fff;
            position: sticky;
            top: 0;
        }
        .table tbody tr:hover {
            background-color: rgba(255, 215, 0, 0.1);
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
        .analytics-header {
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
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-left: 4px solid #003366;
        }
        .stat-card h5 {
            color: #003366;
            font-weight: 600;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #003366;
        }
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .progress {
            height: 10px;
            margin-top: 10px;
        }
        .progress-bar {
            background-color: #003366;
        }
        .nav-pills .nav-link.active {
            background-color: #003366;
        }
        .nav-pills .nav-link {
            color: #003366;
        }
        .badge-admin {
            background-color: #003366;
            color: white;
        }
        .badge-user {
            background-color: #6c757d;
            color: white;
        }
        .subject-badge {
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
            <div class="analytics-header text-center">
                <h4 class="fw-bold text-white">📊 System Analytics Dashboard</h4>
                <p class="mb-0">Comprehensive insights into system usage and activity</p>
            </div>
            
            <!-- Timeframe Filter Section -->
            <div class="filter-section mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <h5 style="color: #003366; font-weight: 600;">Select Timeframe</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <a href="analytics.php?timeframe=24hours" class="btn btn-sm <?php echo $timeframe === '24hours' ? 'btn-custom' : 'btn-outline-custom'; ?>">24 Hours</a>
                            <a href="analytics.php?timeframe=7days" class="btn btn-sm <?php echo $timeframe === '7days' ? 'btn-custom' : 'btn-outline-custom'; ?>">7 Days</a>
                            <a href="analytics.php?timeframe=30days" class="btn btn-sm <?php echo $timeframe === '30days' ? 'btn-custom' : 'btn-outline-custom'; ?>">30 Days</a>
                            <a href="analytics.php?timeframe=90days" class="btn btn-sm <?php echo $timeframe === '90days' ? 'btn-custom' : 'btn-outline-custom'; ?>">90 Days</a>
                            <a href="analytics.php?timeframe=12months" class="btn btn-sm <?php echo $timeframe === '12months' ? 'btn-custom' : 'btn-outline-custom'; ?>">12 Months</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Total Activities</h5>
                        <?php 
                        $totalActivities = array_sum(array_column($analyticsData['activity_over_time'], 'activity_count'));
                        ?>
                        <div class="stat-value"><?php echo number_format($totalActivities); ?></div>
                        <div class="stat-label">Across <?php echo count($analyticsData['activity_over_time']); ?> days</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Active Users</h5>
                        <div class="stat-value"><?php echo count($analyticsData['active_users']); ?></div>
                        <div class="stat-label">Top user: <?php echo $analyticsData['active_users'][0]['name'] ?? 'N/A'; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Content Uploads</h5>
                        <div class="stat-value"><?php echo number_format($analyticsData['content_stats']['total_notes'] ?? 0); ?></div>
                        <div class="stat-label"><?php echo number_format($analyticsData['content_stats']['total_likes'] ?? 0); ?> total likes</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Success Rate</h5>
                        <?php 
                        $successRate = 0;
                        foreach ($analyticsData['status_stats'] as $stat) {
                            if ($stat['status'] === 'success') {
                                $successRate = $stat['percentage'];
                                break;
                            }
                        }
                        ?>
                        <div class="stat-value"><?php echo $successRate; ?>%</div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $successRate; ?>%" 
                                 aria-valuenow="<?php echo $successRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart Navigation -->
            <ul class="nav nav-pills mb-4" id="chartTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $chartType === 'activity' ? 'active' : ''; ?>" 
                            id="activity-tab" data-bs-toggle="pill" data-bs-target="#activity-chart" 
                            type="button" role="tab" aria-controls="activity-chart" aria-selected="true">
                        Activity & Registrations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $chartType === 'content' ? 'active' : ''; ?>" 
                            id="content-tab" data-bs-toggle="pill" data-bs-target="#content-chart" 
                            type="button" role="tab" aria-controls="content-chart" aria-selected="false">
                        Content Types
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $chartType === 'contributors' ? 'active' : ''; ?>" 
                            id="contributors-tab" data-bs-toggle="pill" data-bs-target="#contributors-chart" 
                            type="button" role="tab" aria-controls="contributors-chart" aria-selected="false">
                        Top Contributors
                    </button>
                </li>
            </ul>

            <!-- Charts -->
            <div class="tab-content" id="chartTabsContent">
                <div class="tab-pane fade <?php echo $chartType === 'activity' ? 'show active' : ''; ?>" 
                     id="activity-chart" role="tabpanel" aria-labelledby="activity-tab">
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo $chartType === 'content' ? 'show active' : ''; ?>" 
                     id="content-chart" role="tabpanel" aria-labelledby="content-tab">
                    <div class="chart-container">
                        <canvas id="contentChart"></canvas>
                    </div>
                </div>
                <div class="tab-pane fade <?php echo $chartType === 'contributors' ? 'show active' : ''; ?>" 
                     id="contributors-chart" role="tabpanel" aria-labelledby="contributors-tab">
                    <div class="chart-container">
                        <canvas id="contributorsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data Tables -->
            <div class="row">
                <div class="col-md-6">
                    <div class="bg-white rounded shadow p-4 mb-4">
                        <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                            <i class="bi bi-people-fill me-2"></i>Most Active Users
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Type</th>
                                        <th>Activities</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analyticsData['active_users'] as $index => $user): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php echo htmlentities($user['name']); ?>
                                            <br><small class="text-muted"><?php echo htmlentities($user['email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user['usertype'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                                <?php echo ucfirst($user['usertype']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($user['activity_count']); ?></td>
                                        <td>
                                            <?php 
                                            $percentage = round(($user['activity_count'] / $totalActivities) * 100, 1);
                                            echo $percentage; ?>%
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                     aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-white rounded shadow p-4">
                        <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                            <i class="bi bi-activity me-2"></i>System Status
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analyticsData['status_stats'] as $stat): ?>
                                    <tr>
                                        <td>
                                            <?php if ($stat['status'] === 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php elseif ($stat['status'] === 'error'): ?>
                                                <span class="badge bg-danger">Error</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Warning</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($stat['count']); ?></td>
                                        <td><?php echo $stat['percentage']; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Statistics -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="bg-white rounded shadow p-4 mb-4">
                        <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                            <i class="bi bi-file-earmark-text me-2"></i>Content Statistics
                        </h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h5>Total Notes</h5>
                                    <div class="stat-value"><?php echo number_format($analyticsData['content_stats']['total_notes'] ?? 0); ?></div>
                                    <div class="stat-label">All content types</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h5>Approved</h5>
                                    <div class="stat-value"><?php echo number_format($analyticsData['content_stats']['approved_notes'] ?? 0); ?></div>
                                    <div class="stat-label">Available to users</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h5>Pending</h5>
                                    <div class="stat-value"><?php echo number_format($analyticsData['content_stats']['pending_notes'] ?? 0); ?></div>
                                    <div class="stat-label">Awaiting approval</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h5>Rejected</h5>
                                    <div class="stat-value"><?php echo number_format($analyticsData['content_stats']['rejected_notes'] ?? 0); ?></div>
                                    <div class="stat-label">Not approved</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Content -->
            <div class="row">
                <div class="col-md-6">
                    <div class="bg-white rounded shadow p-4 mb-4">
                        <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                            <i class="bi bi-star-fill me-2"></i>Most Popular Notes
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Subject</th>
                                        <th>Type</th>
                                        <th>Likes</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analyticsData['popular_notes'] as $index => $note): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlentities($note['NotesTitle']); ?></td>
                                        <td>
                                            <span class="badge subject-badge"><?php echo htmlentities($note['Subject']); ?></span>
                                        </td>
                                        <td><?php echo ucfirst($note['NotesType']); ?></td>
                                        <td><?php echo number_format($note['Likes']); ?></td>
                                        <td><?php echo number_format($note['comments_count']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-white rounded shadow p-4">
                        <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                            <i class="bi bi-upload me-2"></i>Top Content Contributors
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Notes</th>
                                        <th>Avg Likes</th>
                                        <th>Total Likes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analyticsData['content_contributors'] as $index => $contributor): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php echo htmlentities($contributor['name']); ?>
                                            <br><small class="text-muted"><?php echo htmlentities($contributor['email']); ?></small>
                                        </td>
                                        <td><?php echo number_format($contributor['notes_count']); ?></td>
                                        <td><?php echo number_format($contributor['avg_likes'], 1); ?></td>
                                        <td><?php echo number_format($contributor['total_likes']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Continue from previous code -->

<script>
    // Activity Chart (Combined Line Chart)
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    const activityChart = new Chart(activityCtx, {
        type: 'line',
        data: <?php echo json_encode($chartData); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'System Activity & User Registrations',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#003366'
                },
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Count',
                        color: '#003366'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Date',
                        color: '#003366'
                    }
                }
            }
        }
    });

    // Content Type Distribution (Pie Chart)
    const contentCtx = document.getElementById('contentChart').getContext('2d');
    const contentChart = new Chart(contentCtx, {
        type: 'pie',
        data: <?php echo json_encode($pieChartData); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Content Type Distribution',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#003366'
                },
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: '#003366',
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Top Contributors (Bar Chart)
    const contributorsCtx = document.getElementById('contributorsChart').getContext('2d');
    const contributorsChart = new Chart(contributorsCtx, {
        type: 'bar',
        data: <?php echo json_encode($contentContributorsData); ?>,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Top Content Contributors',
                    font: {
                        size: 16,
                        weight: 'bold'
                    },
                    color: '#003366'
                },
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Count',
                        color: '#003366'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Contributors',
                        color: '#003366'
                    }
                }
            }
        }
    });

    // Activate the correct tab based on URL parameter
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const chartParam = urlParams.get('chart');
        
        if (chartParam === 'content') {
            $('#content-tab').tab('show');
        } else if (chartParam === 'contributors') {
            $('#contributors-tab').tab('show');
        } else {
            $('#activity-tab').tab('show');
        }
        
        // Update URL when tabs are clicked
        $('button[data-bs-toggle="pill"]').on('click', function() {
            const tabId = $(this).attr('id');
            let chartType = 'activity';
            
            if (tabId === 'content-tab') {
                chartType = 'content';
            } else if (tabId === 'contributors-tab') {
                chartType = 'contributors';
            }
            
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('chart', chartType);
            window.history.pushState({}, '', newUrl);
        });
    });

    // Window resize handler to redraw charts
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            activityChart.resize();
            contentChart.resize();
            contributorsChart.resize();
        }, 200);
    });
</script>

<!-- Additional Statistics Section -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="bg-white rounded shadow p-4 mb-4">
            <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                <i class="bi bi-phone me-2"></i>Device Usage
            </h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Device Type</th>
                            <th>Sessions</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalDevices = array_sum(array_column($analyticsData['device_stats'], 'count'));
                        foreach ($analyticsData['device_stats'] as $device): 
                            $percentage = ($device['count'] / $totalDevices) * 100;
                        ?>
                        <tr>
                            <td><?php echo htmlentities($device['device_type']); ?></td>
                            <td><?php echo number_format($device['count']); ?></td>
                            <td>
                                <?php echo number_format($percentage, 1); ?>%
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%"
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="bg-white rounded shadow p-4">
            <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                <i class="bi bi-book me-2"></i>Subject Distribution
            </h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Notes</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalSubjects = array_sum(array_column($analyticsData['subject_distribution'], 'count'));
                        foreach ($analyticsData['subject_distribution'] as $subject): 
                            $percentage = ($subject['count'] / $totalSubjects) * 100;
                        ?>
                        <tr>
                            <td>
                                <span class="badge subject-badge">
                                    <?php echo htmlentities($subject['Subject']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($subject['count']); ?></td>
                            <td>
                                <?php echo number_format($percentage, 1); ?>%
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%"
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Comment Activity Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="bg-white rounded shadow p-4">
            <h5 style="color: #003366; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid #FFD700; padding-bottom: 0.5rem;">
                <i class="bi bi-chat-dots me-2"></i>Top Commenters
            </h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Comments</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalComments = array_sum(array_column($analyticsData['comment_activity'], 'comments_count'));
                        foreach ($analyticsData['comment_activity'] as $index => $commenter): 
                            $percentage = ($commenter['comments_count'] / $totalComments) * 100;
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php echo htmlentities($commenter['name']); ?>
                                <br><small class="text-muted"><?php echo htmlentities($commenter['email']); ?></small>
                            </td>
                            <td><?php echo number_format($commenter['comments_count']); ?></td>
                            <td>
                                <?php echo number_format($percentage, 1); ?>%
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%"
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>