<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in - FIXED
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Manage Users';

// Handle user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'ban') {
        $conn->query("UPDATE users SET is_active = 0 WHERE id = $user_id");
    } elseif ($action === 'unban') {
        $conn->query("UPDATE users SET is_active = 1 WHERE id = $user_id");
    }
    
    // Redirect to avoid form resubmission
    header('Location: users.php');
    exit();
}
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
                    <a href="users.php" class="nav-link active">
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
                        <i class="bi bi-people"></i> Users Management
                    </h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Users List</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $users = $conn->query("
                            SELECT u.*, 
                            (SELECT COUNT(*) FROM notes WHERE user_id = u.id) as note_count
                            FROM users u 
                            WHERE u.role = 'user'
                            ORDER BY u.created_at DESC
                        ");
                        ?>
                        
                        <?php if ($users->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Full Name</th>
                                            <th>Notes</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $user['note_count']; ?> notes</span>
                                                </td>
                                                <td>
                                                    <?php if ($user['is_active']): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Banned</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($user['is_active']): ?>
                                                            <a href="users.php?action=ban&id=<?php echo $user['id']; ?>" 
                                                               class="btn btn-outline-warning btn-sm"
                                                               onclick="return confirm('Are you sure you want to ban this user?')">
                                                                <i class="bi bi-person-x"></i> Ban
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="users.php?action=unban&id=<?php echo $user['id']; ?>" 
                                                               class="btn btn-outline-success btn-sm"
                                                               onclick="return confirm('Are you sure you want to unban this user?')">
                                                                <i class="bi bi-person-check"></i> Unban
                                                            </a>
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
                                <i class="bi bi-people display-1 text-muted"></i>
                                <h4 class="text-muted">No users found</h4>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>