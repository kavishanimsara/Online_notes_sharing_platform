<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Activity Log';

// Check if activity log table exists
$check_table = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
$has_activity_table = $check_table->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar {
            background: #343a40;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 12px 20px;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: #495057;
            border-left-color: #dc3545;
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="p-4">
                    <h4 class="text-white">
                        <i class="bi bi-shield-lock"></i> Admin Panel
                    </h4>
                    <p class="text-muted small">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
                    <p class="text-warning small">
                        <i class="bi bi-person-badge"></i> 
                        <?php echo ucfirst($_SESSION['admin_level']); ?>
                    </p>
                </div>
                
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a href="users.php" class="nav-link">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a href="admins.php" class="nav-link">
                        <i class="bi bi-person-gear"></i> Admins
                    </a>
                    <a href="categories.php" class="nav-link">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                    <a href="notes_approval.php" class="nav-link">
                        <i class="bi bi-file-check"></i> Notes Approval
                    </a>
                    <a href="reports.php" class="nav-link">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    <a href="activities.php" class="nav-link active">
                        <i class="bi bi-activity"></i> Activity Log
                    </a>
                    
                    <div class="mt-4 pt-3 border-top">
                        <a href="../index.php" class="nav-link">
                            <i class="bi bi-house"></i> Main Site
                        </a>
                        <a href="logout.php" class="nav-link">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ml-sm-auto p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h3">
                        <i class="bi bi-activity"></i> Admin Activity Log
                    </h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (!$has_activity_table): ?>
                    <div class="alert alert-warning">
                        <h5><i class="bi bi-exclamation-triangle"></i> Setup Required</h5>
                        <p>The activity log system requires database setup.</p>
                        <a href="../database_setup.php" class="btn btn-warning">Run Database Setup</a>
                    </div>
                <?php else: ?>
                    <!-- Activity Log -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Recent Activities</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php
                            $activities = $conn->query("
                                SELECT a.*, u.username 
                                FROM admin_activity_log a 
                                LEFT JOIN users u ON a.admin_id = u.id 
                                ORDER BY a.created_at DESC 
                                LIMIT 50
                            ");
                            ?>
                            
                            <?php if ($activities->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($activity = $activities->fetch_assoc()): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <span class="badge bg-secondary">
                                                        <?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?>
                                                    </span>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($activity['details']); ?></p>
                                            <small class="text-muted">
                                                By: <?php echo htmlspecialchars($activity['username']); ?> | 
                                                IP: <?php echo htmlspecialchars($activity['ip_address']); ?>
                                            </small>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-activity display-1 text-muted"></i>
                                    <h4 class="text-muted">No activities found</h4>
                                    <p class="text-muted">Admin activities will appear here once they occur.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>