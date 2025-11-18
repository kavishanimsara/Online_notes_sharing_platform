<?php
// Check which tables/columns exist for feature availability
$check_status_column = $conn->query("SHOW COLUMNS FROM notes LIKE 'status'");
$has_status_column = $check_status_column->num_rows > 0;

$check_categories_table = $conn->query("SHOW TABLES LIKE 'categories'");
$has_categories_table = $check_categories_table->num_rows > 0;

$check_admins_table = $conn->query("SHOW TABLES LIKE 'admins'");  
$has_admins_table = $check_admins_table->num_rows > 0;

$check_activity_table = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
$has_activity_table = $check_activity_table->num_rows > 0;

// Get pending counts
if ($has_status_column) {
    $pending_count = $conn->query("SELECT COUNT(*) as count FROM notes WHERE status = 'pending'")->fetch_assoc()['count'];
} else {
    $pending_count = 0;
}

if ($has_admins_table) {
    $pending_requests = $conn->query("SELECT COUNT(*) as count FROM admin_verification WHERE status = 'pending'")->fetch_assoc()['count'];
} else {
    $pending_requests = 0;
}
?>
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
        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        
        <!-- Notes Approval -->
        <a href="notes_approval.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notes_approval.php' ? 'active' : ''; ?> <?php echo !$has_status_column ? 'feature-disabled' : ''; ?>">
            <i class="bi bi-file-check"></i> Notes Approval
            <?php if ($has_status_column && $pending_count > 0): ?>
                <span class="badge bg-danger float-end"><?php echo $pending_count; ?></span>
            <?php elseif (!$has_status_column): ?>
                <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
            <?php endif; ?>
        </a>
        
        <!-- Users Management -->
        <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Users Management
        </a>
        
        <!-- Categories -->
        <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?> <?php echo !$has_categories_table ? 'feature-disabled' : ''; ?>">
            <i class="bi bi-tags"></i> Categories
            <?php if (!$has_categories_table): ?>
                <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
            <?php endif; ?>
        </a>
        
        <!-- Admin Management -->
        <a href="admins.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?> <?php echo !$has_admins_table ? 'feature-disabled' : ''; ?>">
            <i class="bi bi-person-gear"></i> Admin Management
            <?php if ($has_admins_table && $pending_requests > 0): ?>
                <span class="badge bg-warning float-end"><?php echo $pending_requests; ?></span>
            <?php elseif (!$has_admins_table): ?>
                <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
            <?php endif; ?>
        </a>
        
        <!-- Reports -->
        <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="bi bi-graph-up"></i> Reports
        </a>
        
        <!-- Activity Log -->
        <a href="activities.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'activities.php' ? 'active' : ''; ?> <?php echo !$has_activity_table ? 'feature-disabled' : ''; ?>">
            <i class="bi bi-activity"></i> Activity Log
            <?php if (!$has_activity_table): ?>
                <small class="float-end text-warning"><i class="bi bi-exclamation-triangle"></i></small>
            <?php endif; ?>
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