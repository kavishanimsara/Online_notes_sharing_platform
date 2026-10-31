<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Admin Management';

// Check if admins table exists
$check_table = $conn->query("SHOW TABLES LIKE 'admins'");
$has_admins_table = $check_table->num_rows > 0;
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
                    <a href="admins.php" class="nav-link active">
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
                    <a href="activities.php" class="nav-link">
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
                        <i class="bi bi-person-gear"></i> Admin Management
                    </h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (!$has_admins_table): ?>
                    <div class="alert alert-warning">
                        <h5><i class="bi bi-exclamation-triangle"></i> Setup Required</h5>
                        <p>The admin management system requires database setup.</p>
                        <a href="../database_setup.php" class="btn btn-warning">Run Database Setup</a>
                    </div>
                <?php else: ?>
                    <!-- Admins List -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Administrators List</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $admins = $conn->query("
                                SELECT a.*, u.username, u.email, u.full_name,
                                       creator.username as created_by_name
                                FROM admins a
                                INNER JOIN users u ON a.user_id = u.id
                                LEFT JOIN users creator ON a.created_by = creator.id
                                ORDER BY a.admin_level DESC, a.created_at DESC
                            ");
                            ?>
                            
                            <?php if ($admins->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Admin Level</th>
                                                <th>Status</th>
                                                <th>Created By</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($admin = $admins->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($admin['username']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $badge_class = [
                                                            'super_admin' => 'bg-danger',
                                                            'admin' => 'bg-primary', 
                                                            'moderator' => 'bg-info'
                                                        ][$admin['admin_level']] ?? 'bg-secondary';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>">
                                                            <?php echo ucfirst($admin['admin_level']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($admin['is_active']): ?>
                                                            <span class="badge bg-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars($admin['created_by_name'] ?? 'System'); ?></small>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php echo $admin['last_login'] ? date('M j, Y', strtotime($admin['last_login'])) : 'Never'; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if ($admin['admin_level'] !== 'super_admin'): ?>
                                                                <?php if ($admin['is_active']): ?>
                                                                    <a href="?action=deactivate&id=<?php echo $admin['id']; ?>" 
                                                                       class="btn btn-outline-warning btn-sm"
                                                                       onclick="return confirm('Deactivate this admin?')">
                                                                        Deactivate
                                                                    </a>
                                                                <?php else: ?>
                                                                    <a href="?action=activate&id=<?php echo $admin['id']; ?>" 
                                                                       class="btn btn-outline-success btn-sm"
                                                                       onclick="return confirm('Activate this admin?')">
                                                                        Activate
                                                                    </a>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Protected</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-person-gear display-1 text-muted"></i>
                                    <h4 class="text-muted">No administrators found</h4>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pending Requests -->
                    <?php
                    $pending_requests = $conn->query("
                        SELECT * FROM admin_verification 
                        WHERE status = 'pending' 
                        ORDER BY created_at DESC
                    ");
                    ?>
                    
                    <?php if ($pending_requests->num_rows > 0): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Pending Admin Requests</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Full Name</th>
                                            <th>Reason</th>
                                            <th>Requested</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($request = $pending_requests->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                                <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($request['reason'], 0, 100) . '...'); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($request['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="verify_request.php?email=<?php echo urlencode($request['email']); ?>" 
                                                           class="btn btn-outline-success btn-sm">
                                                            Approve
                                                        </a>
                                                        <a href="?action=reject&id=<?php echo $request['id']; ?>" 
                                                           class="btn btn-outline-danger btn-sm"
                                                           onclick="return confirm('Reject this request?')">
                                                            Reject
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>