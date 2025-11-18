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
    $admin_id = $_SESSION['admin_id'];
    
    // Prevent admin from banning themselves
    if ($user_id == $admin_id) {
        header('Location: users.php?error=self_action');
        exit();
    }
    
    if ($action === 'ban') {
        $ban_reason = $_GET['reason'] ?? 'Violation of platform rules';
        
        // Check which columns exist
        $check_banned_at = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_at'");
        $check_banned_by = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_by'");
        $check_ban_reason = $conn->query("SHOW COLUMNS FROM users LIKE 'ban_reason'");
        
        if ($check_banned_at->num_rows > 0 && $check_banned_by->num_rows > 0 && $check_ban_reason->num_rows > 0) {
            // Full version with all tracking columns
            $stmt = $conn->prepare("UPDATE users SET is_active = 0, banned_at = NOW(), banned_by = ?, ban_reason = ? WHERE id = ?");
            $stmt->bind_param("isi", $admin_id, $ban_reason, $user_id);
        } else {
            // Basic version without tracking columns
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute();
        
        // Destroy all user sessions
        require_once '../includes/session_manager.php';
        $sessions_destroyed = destroyUserSessions($user_id);
        
    } elseif ($action === 'unban') {
        // Check which columns exist
        $check_banned_at = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_at'");
        $check_banned_by = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_by'");
        $check_ban_reason = $conn->query("SHOW COLUMNS FROM users LIKE 'ban_reason'");
        
        if ($check_banned_at->num_rows > 0 && $check_banned_by->num_rows > 0 && $check_ban_reason->num_rows > 0) {
            // Full version with all tracking columns
            $stmt = $conn->prepare("UPDATE users SET is_active = 1, banned_at = NULL, banned_by = NULL, ban_reason = NULL WHERE id = ?");
            $stmt->bind_param("i", $user_id);
        } else {
            // Basic version without tracking columns
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute();
        
    } elseif ($action === 'delete') {
        // Only super admin can delete users
        if ($_SESSION['admin_level'] === 'super_admin') {
            // First delete user's notes
            $conn->query("DELETE FROM notes WHERE user_id = $user_id");
            // Then delete the user
            $conn->query("DELETE FROM users WHERE id = $user_id");
        }
    } elseif ($action === 'change_role') {
        $new_role = $_GET['role'] ?? 'user';
        if (in_array($new_role, ['user', 'admin', 'super_admin'])) {
            // Only super admin can make other admins
            if ($new_role === 'admin' || $new_role === 'super_admin') {
                if ($_SESSION['admin_level'] === 'super_admin') {
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_role, $user_id);
                    $stmt->execute();
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->bind_param("si", $new_role, $user_id);
                $stmt->execute();
            }
        }
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
                        // Check if banned_by column exists
                        $check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_by'");
                        $has_banned_by = $check_columns->num_rows > 0;
                        
                        if ($has_banned_by) {
                            $users = $conn->query("
                                SELECT u.*, 
                                (SELECT COUNT(*) FROM notes WHERE user_id = u.id) as note_count,
                                a.username as banned_by_username
                                FROM users u 
                                LEFT JOIN users a ON u.banned_by = a.id
                                ORDER BY u.created_at DESC
                            ");
                        } else {
                            $users = $conn->query("
                                SELECT u.*, 
                                (SELECT COUNT(*) FROM notes WHERE user_id = u.id) as note_count
                                FROM users u 
                                ORDER BY u.created_at DESC
                            ");
                        }
                        
                        // Show success/error messages
                        if (isset($_GET['error'])) {
                            if ($_GET['error'] === 'self_action') {
                                echo '<div class="alert alert-warning">You cannot perform this action on yourself.</div>';
                            }
                        }
                        if (isset($_GET['success'])) {
                            echo '<div class="alert alert-success">Action completed successfully!</div>';
                        }
                        ?>
                        
                        <?php if ($users->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Notes</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                            <th>Ban Info</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <tr class="<?php echo !$user['is_active'] ? 'table-danger' : ''; ?>">
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                                        <span class="badge bg-info">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo match($user['role']) {
                                                            'super_admin' => 'danger',
                                                            'admin' => 'warning',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
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
                                                    <?php if (!$user['is_active']): ?>
                                                        <small>
                                                            <?php if (isset($user['banned_at']) && $user['banned_at']): ?>
                                                                <div>Banned: <?php echo date('M j, Y', strtotime($user['banned_at'])); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (isset($user['banned_by_username']) && $user['banned_by_username']): ?>
                                                                <div>By: <?php echo htmlspecialchars($user['banned_by_username']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (isset($user['ban_reason']) && $user['ban_reason']): ?>
                                                                <div class="text-muted">Reason: <?php echo htmlspecialchars(substr($user['ban_reason'], 0, 30)); ?>...</div>
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm dropdown">
                                                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-gear"></i> Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if ($user['is_active']): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-warning" href="#" 
                                                                       onclick="showBanModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                                        <i class="bi bi-ban"></i> Ban User
                                                                    </a>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="users.php?action=unban&id=<?php echo $user['id']; ?>">
                                                                        <i class="bi bi-check-circle"></i> Unban User
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($_SESSION['admin_level'] === 'super_admin' && $user['id'] != $_SESSION['admin_id']): ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li class="dropdown-header">Change Role</li>
                                                                <li>
                                                                    <a class="dropdown-item" href="users.php?action=change_role&id=<?php echo $user['id']; ?>&role=user">
                                                                        <i class="bi bi-person"></i> Set as User
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="users.php?action=change_role&id=<?php echo $user['id']; ?>&role=admin">
                                                                        <i class="bi bi-person-badge"></i> Set as Admin
                                                                    </a>
                                                                </li>
                                                                <?php if ($user['role'] !== 'super_admin'): ?>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="users.php?action=change_role&id=<?php echo $user['id']; ?>&role=super_admin">
                                                                        <i class="bi bi-shield-check"></i> Set as Super Admin
                                                                    </a>
                                                                </li>
                                                                <?php endif; ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="users.php?action=delete&id=<?php echo $user['id']; ?>"
                                                                       onclick="return confirm('⚠️ WARNING: This will permanently delete the user and ALL their notes. This action cannot be undone! Are you absolutely sure?')">
                                                                        <i class="bi bi-trash"></i> Delete User
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
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

    <!-- Ban User Modal -->
    <div class="modal fade" id="banUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Ban User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET" action="users.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ban">
                        <input type="hidden" name="id" id="banUserId">
                        <div class="mb-3">
                            <label class="form-label">User to ban:</label>
                            <div class="form-control-plaintext fw-bold" id="banUsername"></div>
                        </div>
                        <div class="mb-3">
                            <label for="banReason" class="form-label">Ban Reason:</label>
                            <textarea name="reason" id="banReason" class="form-control" rows="3" 
                                      placeholder="Enter the reason for banning this user..." required></textarea>
                            <small class="form-text text-muted">This reason will be shown to the user when they try to log in.</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Warning:</strong> This user will not be able to log in or access the platform while banned.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-ban"></i> Ban User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showBanModal(userId, username) {
            document.getElementById('banUserId').value = userId;
            document.getElementById('banUsername').textContent = username;
            document.getElementById('banReason').value = '';
            new bootstrap.Modal(document.getElementById('banUserModal')).show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>