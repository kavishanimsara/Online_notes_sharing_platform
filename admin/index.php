<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();

// Check if user is admin
$user_id = getUserId();
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$pageTitle = 'Admin Dashboard';

// Get statistics
$stats = [];

// Total users
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Total notes
$stats['total_notes'] = $conn->query("SELECT COUNT(*) as count FROM notes")->fetch_assoc()['count'];

// Pending approvals
$stats['pending_notes'] = $conn->query("SELECT COUNT(*) as count FROM notes WHERE status = 'pending'")->fetch_assoc()['count'];

// Total downloads
$stats['total_downloads'] = $conn->query("SELECT COALESCE(SUM(downloads), 0) as total FROM notes")->fetch_assoc()['total'];

// Pending reports
$stats['pending_reports'] = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch_assoc()['count'];

// Total comments
$stats['total_comments'] = $conn->query("SELECT COUNT(*) as count FROM comments")->fetch_assoc()['count'];

// Recent users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Recent notes
$recent_notes = $conn->query("SELECT n.*, u.username FROM notes n JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 5");

// Pending reports
$pending_reports = $conn->query("SELECT r.*, u.username as reporter FROM reports r JOIN users u ON r.reporter_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 5");

include '../includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.admin-sidebar {
    min-height: calc(100vh - 70px);
    background: #2C3E50;
}
.admin-sidebar .nav-link {
    color: #ECF0F1;
    padding: 12px 20px;
    border-left: 3px solid transparent;
}
.admin-sidebar .nav-link:hover,
.admin-sidebar .nav-link.active {
    background: #34495E;
    border-left-color: #3498DB;
    color: white;
}
.stat-card {
    transition: transform 0.3s;
}
.stat-card:hover {
    transform: translateY(-5px);
}
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0 admin-sidebar">
            <div class="py-3">
                <h5 class="text-white text-center mb-4">
                    <i class="fas fa-shield-alt"></i> Admin Panel
                </h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="notes.php">
                            <i class="fas fa-file-alt"></i> Manage Notes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="comments.php">
                            <i class="fas fa-comments"></i> Manage Comments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-flag"></i> Reports 
                            <?php if ($stats['pending_reports'] > 0): ?>
                                <span class="badge bg-danger"><?php echo $stats['pending_reports']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-arrow-left"></i> Back to Site
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 px-md-4">
            <div class="py-4">
                <h2 class="mb-4">Admin Dashboard</h2>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-xl-3">
                        <div class="card stat-card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Users</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['total_users']; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-primary bg-opacity-75 border-0">
                                <a href="users.php" class="text-white text-decoration-none small">
                                    View all <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card stat-card border-0 shadow-sm bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total Notes</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['total_notes']; ?></h2>
                                    </div>
                                    <i class="fas fa-file-alt fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-success bg-opacity-75 border-0">
                                <a href="notes.php" class="text-white text-decoration-none small">
                                    Manage <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card stat-card border-0 shadow-sm bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Pending Approvals</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['pending_notes']; ?></h2>
                                    </div>
                                    <i class="fas fa-clock fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-warning bg-opacity-75 border-0">
                                <a href="notes.php?status=pending" class="text-white text-decoration-none small">
                                    Review <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card stat-card border-0 shadow-sm bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Pending Reports</h6>
                                        <h2 class="mt-2 mb-0"><?php echo $stats['pending_reports']; ?></h2>
                                    </div>
                                    <i class="fas fa-flag fa-3x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-danger bg-opacity-75 border-0">
                                <a href="reports.php" class="text-white text-decoration-none small">
                                    Review <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Recent Users -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-user-plus"></i> Recent Users</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Joined</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($user = $recent_users->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <a href="users.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                                                            View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Notes -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-file-upload"></i> Recent Notes</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Title</th>
                                                <th>Uploader</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($note = $recent_notes->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(substr($note['title'], 0, 30)); ?></td>
                                                    <td><?php echo htmlspecialchars($note['username']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $note['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                            <?php echo $note['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="notes.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-primary">
                                                            View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Reports -->
                <?php if ($stats['pending_reports'] > 0): ?>
                    <div class="row g-4 mt-1">
                        <div class="col-12">
                            <div class="card shadow-sm border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Pending Reports</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Reporter</th>
                                                    <th>Type</th>
                                                    <th>Reason</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($report = $pending_reports->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($report['reporter']); ?></td>
                                                        <td><span class="badge bg-secondary"><?php echo $report['reported_type']; ?></span></td>
                                                        <td><?php echo htmlspecialchars(substr($report['reason'], 0, 50)); ?>...</td>
                                                        <td><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                                                        <td>
                                                            <a href="reports.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-danger">
                                                                Review
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>