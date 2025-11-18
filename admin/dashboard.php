<?php
require_once '../config/db.php';
session_start();

// Check if admin is logged in FIRST
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Now include auth functions
require_once 'includes/auth.php';

$pageTitle = 'Admin Dashboard - NotesShare Pro';
$admin_id = $_SESSION['admin_id'];
$admin_level = $_SESSION['admin_level'];
// $permissions = $_SESSION['admin_permissions'] ?? [];
$permissions = $_SESSION['admin_permissions'] ?? [];

// Get statistics - CHECK IF COLUMNS EXIST FIRST
$users_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$notes_count = $conn->query("SELECT COUNT(*) as count FROM notes")->fetch_assoc()['count'];

// Check if likes system exists
$check_likes_table = $conn->query("SHOW TABLES LIKE 'note_likes'");
$has_likes_table = $check_likes_table->num_rows > 0;

if ($has_likes_table) {
    $total_likes = $conn->query("SELECT COUNT(*) as count FROM note_likes")->fetch_assoc()['count'];
} else {
    $total_likes = 0;
}

// Check if status column exists in notes table
$check_status_column = $conn->query("SHOW COLUMNS FROM notes LIKE 'status'");
$has_status_column = $check_status_column->num_rows > 0;

// Check if categories table exists
$check_categories_table = $conn->query("SHOW TABLES LIKE 'categories'");
$has_categories_table = $check_categories_table->num_rows > 0;

// Check if admins table exists  
$check_admins_table = $conn->query("SHOW TABLES LIKE 'admins'");
$has_admins_table = $check_admins_table->num_rows > 0;

// Check if admin_verification table exists
$check_verification_table = $conn->query("SHOW TABLES LIKE 'admin_verification'");
$has_verification_table = $check_verification_table->num_rows > 0;

// Set counts based on what tables/columns exist
if ($has_status_column) {
    $pending_notes = $conn->query("SELECT COUNT(*) as count FROM notes WHERE status = 'pending'")->fetch_assoc()['count'];
} else {
    $pending_notes = 0; // Default value if column doesn't exist
}

if ($has_categories_table) {
    $categories_count = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
} else {
    $categories_count = 0;
}

if ($has_admins_table) {
    $admins_count = $conn->query("SELECT COUNT(*) as count FROM admins WHERE is_active = 1")->fetch_assoc()['count'];
} else {
    $admins_count = 0;
}

if ($has_verification_table) {
    $pending_requests = $conn->query("SELECT COUNT(*) as count FROM admin_verification WHERE status = 'pending'")->fetch_assoc()['count'];
} else {
    $pending_requests = 0;
}

// Check if admin_activity_log table exists
$check_activity_table = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
$has_activity_table = $check_activity_table->num_rows > 0;

// Recent activities - only if table exists
if ($has_activity_table) {
    $recent_activities = $conn->query("
        SELECT a.action, a.details, a.created_at, u.username 
        FROM admin_activity_log a 
        LEFT JOIN users u ON a.admin_id = u.id 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
} else {
    $recent_activities = false;
}

// Recent pending notes - only if status column exists
if ($has_status_column) {
    $recent_pending = $conn->query("
        SELECT n.*, u.username 
        FROM notes n 
        LEFT JOIN users u ON n.user_id = u.id 
        WHERE n.status = 'pending' 
        ORDER BY n.created_at DESC 
        LIMIT 5
    ");
} else {
    $recent_pending = false;
}
?>
<?php include 'includes/header.php'; ?>
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
        .stat-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .bg-pending { background: linear-gradient(45deg, #ffc107, #ff8c00); }
        .bg-users { background: linear-gradient(45deg, #17a2b8, #138496); }
        .bg-notes { background: linear-gradient(45deg, #28a745, #20c997); }
        .bg-categories { background: linear-gradient(45deg, #6f42c1, #e83e8c); }
        .bg-admins { background: linear-gradient(45deg, #dc3545, #c82333); }
        .bg-requests { background: linear-gradient(45deg, #fd7e14, #e55a00); }
        .feature-disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
                        <?php echo ucfirst(str_replace('_', ' ', $admin_level)); ?>
                    </p>
                </div>
                
                <nav class="nav flex-column">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    
                    <?php if (in_array('manage_notes', $permissions)): ?>
                    <a href="notes_approval.php" class="nav-link <?php echo !$has_status_column ? 'feature-disabled' : ''; ?>">
                        <i class="bi bi-file-check"></i> Notes Approval
                        <?php if ($has_status_column && $pending_notes > 0): ?>
                            <span class="badge bg-danger float-end"><?php echo $pending_notes; ?></span>
                        <?php endif; ?>
                        <?php if (!$has_status_column): ?>
                            <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array('manage_users', $permissions)): ?>
                    <a href="users.php" class="nav-link">
                        <i class="bi bi-people"></i> Users Management
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array('manage_categories', $permissions)): ?>
                    <a href="categories.php" class="nav-link <?php echo !$has_categories_table ? 'feature-disabled' : ''; ?>">
                        <i class="bi bi-tags"></i> Categories
                        <?php if (!$has_categories_table): ?>
                            <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array('manage_admins', $permissions)): ?>
                    <a href="admins.php" class="nav-link <?php echo !$has_admins_table ? 'feature-disabled' : ''; ?>">
                        <i class="bi bi-person-gear"></i> Admin Management
                        <?php if ($has_verification_table && $pending_requests > 0): ?>
                            <span class="badge bg-warning float-end"><?php echo $pending_requests; ?></span>
                        <?php endif; ?>
                        <?php if (!$has_admins_table): ?>
                            <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array('view_reports', $permissions)): ?>
                    <a href="reports.php" class="nav-link">
                        <i class="bi bi-graph-up"></i> Reports
                    </a>
                    <?php endif; ?>
                    
                    <?php if (in_array('view_activities', $permissions)): ?>
                    <a href="admin/activities.php" class="nav-link <?php echo !$has_activity_table ? 'feature-disabled' : ''; ?>">
                        <i class="bi bi-activity"></i> Activity Log
                        <?php if (!$has_activity_table): ?>
                            <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
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
                    <h2 class="h3">Dashboard Overview</h2>
                    <span class="text-muted"><?php echo date('F j, Y'); ?></span>
                </div>

                <!-- Database Setup Alert -->
                <?php if (!$has_status_column || !$has_categories_table || !$has_admins_table): ?>
                <div class="alert alert-warning mb-4">
                    <h6><i class="bi bi-exclamation-triangle"></i> Some Features Need Setup</h6>
                    <p class="mb-2">Some admin features require additional database tables/columns. Features with <i class="bi bi-exclamation-triangle text-warning"></i> are currently unavailable.</p>
                    <a href="../database_setup.php" class="btn btn-sm btn-warning">Run Database Setup</a>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card stat-card text-white bg-users">
                            <div class="card-body">
                                <div class="text-center">
                                    <h5 class="card-title">Users</h5>
                                    <h2 class="mb-0"><?php echo $users_count; ?></h2>
                                    <i class="bi bi-people fs-1 opacity-50 mt-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card stat-card text-white bg-notes">
                            <div class="card-body">
                                <div class="text-center">
                                    <h5 class="card-title">Notes</h5>
                                    <h2 class="mb-0"><?php echo $notes_count; ?></h2>
                                    <i class="bi bi-file-text fs-1 opacity-50 mt-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (in_array('manage_notes', $permissions)): ?>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card stat-card text-white bg-pending">
                            <div class="card-body">
                                <div class="text-center">
                                    <h5 class="card-title">Pending</h5>
                                    <h2 class="mb-0"><?php echo $pending_notes; ?></h2>
                                    <i class="bi bi-clock-history fs-1 opacity-50 mt-2"></i>
                                    <?php if (!$has_status_column): ?>
                                        <small class="d-block mt-1">Feature Disabled</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #e91e63, #f06292);">
                            <div class="card-body">
                                <div class="text-center">
                                    <h5 class="card-title">Total Likes</h5>
                                    <h2 class="mb-0"><?php echo $total_likes; ?></h2>
                                    <i class="bi bi-heart-fill fs-1 opacity-50 mt-2"></i>
                                    <?php if (!$has_likes_table): ?>
                                        <small class="d-block mt-1">Feature Disabled</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (in_array('manage_categories', $permissions)): ?>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card stat-card text-white bg-categories">
                            <div class="card-body">
                                <div class="text-center">
                                    <h5 class="card-title">Categories</h5>
                                    <h2 class="mb-0"><?php echo $categories_count; ?></h2>
                                    <i class="bi bi-tags fs-1 opacity-50 mt-2"></i>
                                    <?php if (!$has_categories_table): ?>
                                        <small class="d-block mt-1">No Categories Table</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array('manage_admins', $permissions)): ?>
                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card stat-card text-white bg-admins">
                            <div class="card-body">
                                <div class="text-center">
                                    <h5 class="card-title">Admins</h5>
                                    <h2 class="mb-0"><?php echo $admins_count; ?></h2>
                                    <i class="bi bi-person-gear fs-1 opacity-50 mt-2"></i>
                                    <?php if (!$has_admins_table): ?>
                                        <small class="d-block mt-1">No Admins Table</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 mb-4">
                        <div class="card stat-card text-white bg-requests">
                            <div class="card-body">
                                <div class="text-center">
                                    <h5 class="card-title">Requests</h5>
                                    <h2 class="mb-0"><?php echo $pending_requests; ?></h2>
                                    <i class="bi bi-person-plus fs-1 opacity-50 mt-2"></i>
                                    <?php if (!$has_verification_table): ?>
                                        <small class="d-block mt-1">No Verification Table</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="row">
                    <!-- Recent Pending Notes -->
                    <?php if (in_array('manage_notes', $permissions) && $has_status_column && $recent_pending): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history"></i> Recent Pending Notes
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_pending->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($note = $recent_pending->fetch_assoc()): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($note['title']); ?></h6>
                                                    <small class="text-muted">
                                                        By <?php echo htmlspecialchars($note['username']); ?> • 
                                                        <?php echo date('M j, g:i A', strtotime($note['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <a href="notes_approval.php?action=review&id=<?php echo $note['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">Review</a>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No pending notes</p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-center">
                                <a href="notes_approval.php" class="btn btn-warning btn-sm">
                                    View All Pending Notes
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Activities -->
                    <?php if (in_array('view_activities', $permissions) && $has_activity_table && $recent_activities): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-activity"></i> Recent Activities
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_activities->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></h6>
                                                    <small><?php echo date('g:i A', strtotime($activity['created_at'])); ?></small>
                                                </div>
                                                <p class="mb-1 small"><?php echo htmlspecialchars($activity['details']); ?></p>
                                                <small class="text-muted">By <?php echo htmlspecialchars($activity['username']); ?></small>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No recent activities</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-lightning"></i> Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <?php if (in_array('manage_notes', $permissions)): ?>
                                    <div class="col-md-3 mb-3">
                                        <a href="notes_approval.php" class="btn btn-outline-warning w-100 py-3 <?php echo !$has_status_column ? 'feature-disabled' : ''; ?>">
                                            <i class="bi bi-file-check fs-1 d-block mb-2"></i>
                                            Review Notes
                                            <?php if (!$has_status_column): ?>
                                                <small class="d-block text-warning">Setup Required</small>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('manage_users', $permissions)): ?>
                                    <div class="col-md-3 mb-3">
                                        <a href="users.php" class="btn btn-outline-info w-100 py-3">
                                            <i class="bi bi-people fs-1 d-block mb-2"></i>
                                            Manage Users
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('manage_categories', $permissions)): ?>
                                    <div class="col-md-3 mb-3">
                                        <a href="categories.php" class="btn btn-outline-success w-100 py-3 <?php echo !$has_categories_table ? 'feature-disabled' : ''; ?>">
                                            <i class="bi bi-tags fs-1 d-block mb-2"></i>
                                            Categories
                                            <?php if (!$has_categories_table): ?>
                                                <small class="d-block text-warning">Setup Required</small>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array('manage_admins', $permissions)): ?>
                                    <div class="col-md-3 mb-3">
                                        <a href="admins.php" class="btn btn-outline-danger w-100 py-3 <?php echo !$has_admins_table ? 'feature-disabled' : ''; ?>">
                                            <i class="bi bi-person-gear fs-1 d-block mb-2"></i>
                                            Admins
                                            <?php if (!$has_admins_table): ?>
                                                <small class="d-block text-warning">Setup Required</small>
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include 'includes/footer.php'; ?>
</html>