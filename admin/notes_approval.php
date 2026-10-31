<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Notes Approval';

// Check if status column exists
$check_status = $conn->query("SHOW COLUMNS FROM notes LIKE 'status'");
$has_status = $check_status->num_rows > 0;

// Handle note actions
if (isset($_GET['action']) && isset($_GET['id']) && $has_status) {
    $note_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE notes SET status = 'approved', approved_by = {$_SESSION['admin_id']}, approved_at = NOW() WHERE id = $note_id");
    } elseif ($action === 'reject') {
        $conn->query("UPDATE notes SET status = 'rejected', approved_by = {$_SESSION['admin_id']}, approved_at = NOW() WHERE id = $note_id");
    }
    
    // Redirect to avoid form resubmission
    header('Location: notes_approval.php');
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
                    <a href="users.php" class="nav-link">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <a href="admins.php" class="nav-link">
                        <i class="bi bi-person-gear"></i> Admins
                    </a>
                    <a href="categories.php" class="nav-link">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                    <a href="notes_approval.php" class="nav-link active">
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
                        <i class="bi bi-file-check"></i> Notes Approval
                    </h2>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (!$has_status): ?>
                    <div class="alert alert-warning">
                        <h5><i class="bi bi-exclamation-triangle"></i> Setup Required</h5>
                        <p>The notes approval system requires database setup.</p>
                        <a href="../database_setup.php" class="btn btn-warning">Run Database Setup</a>
                    </div>
                <?php else: ?>
                    <!-- Pending Notes -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Pending Notes for Approval</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $pending_notes = $conn->query("
                                SELECT n.*, u.username, u.email, c.name as category_name
                                FROM notes n 
                                LEFT JOIN users u ON n.user_id = u.id 
                                LEFT JOIN categories c ON n.category_id = c.id 
                                WHERE n.status = 'pending' 
                                ORDER BY n.created_at DESC
                            ");
                            ?>
                            
                            <?php if ($pending_notes->num_rows > 0): ?>
                                <div class="row">
                                    <?php while ($note = $pending_notes->fetch_assoc()): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header">
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($note['title']); ?></h5>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text">
                                                        <?php echo htmlspecialchars(substr($note['description'] ?? 'No description', 0, 150)); ?>...
                                                    </p>
                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <strong>By:</strong> <?php echo htmlspecialchars($note['username']); ?><br>
                                                            <strong>Category:</strong> <?php echo htmlspecialchars($note['category_name'] ?? 'Uncategorized'); ?><br>
                                                            <strong>Uploaded:</strong> <?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="btn-group w-100">
                                                        <a href="notes_approval.php?action=approve&id=<?php echo $note['id']; ?>" 
                                                           class="btn btn-success"
                                                           onclick="return confirm('Approve this note?')">
                                                            <i class="bi bi-check-lg"></i> Approve
                                                        </a>
                                                        <a href="notes_approval.php?action=reject&id=<?php echo $note['id']; ?>" 
                                                           class="btn btn-danger"
                                                           onclick="return confirm('Reject this note?')">
                                                            <i class="bi bi-x-lg"></i> Reject
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-check-circle display-1 text-success"></i>
                                    <h3 class="text-success">No Pending Notes</h3>
                                    <p class="text-muted">All notes have been reviewed and approved.</p>
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