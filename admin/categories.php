<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in - FIXED
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Manage Categories';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header('Location: categories.php');
        exit();
    }
}

// Handle category actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    $action = $_GET['action'];
    $admin_id = $_SESSION['admin_id'];
    
    if ($action === 'delete') {
        $conn->query("DELETE FROM categories WHERE id = $category_id");
    } elseif ($action === 'approve') {
        $conn->query("UPDATE categories SET status = 'approved', approved_by = $admin_id, approved_at = NOW() WHERE id = $category_id");
    } elseif ($action === 'reject') {
        $reject_reason = $_GET['reason'] ?? 'Does not meet guidelines';
        $conn->query("UPDATE categories SET status = 'rejected', approved_by = $admin_id, approved_at = NOW(), rejection_reason = '$reject_reason' WHERE id = $category_id");
    }
    
    // Redirect to avoid resubmission
    header('Location: categories.php');
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
                    <a href="categories.php" class="nav-link active">
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
                        <i class="bi bi-tags"></i> Categories Management
                    </h2>
                    <div class="btn-group">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-circle"></i> Add Category
                        </button>
                        <a href="create_subcategory.php" class="btn btn-outline-primary">
                            <i class="bi bi-folder-plus"></i> Create Subcategory
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Categories List</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Check if status column exists
                        $check_status = $conn->query("SHOW COLUMNS FROM categories LIKE 'status'");
                        $has_status = $check_status->num_rows > 0;
                        
                        if ($has_status) {
                            $categories = $conn->query("
                                SELECT c.*, 
                                (SELECT COUNT(*) FROM notes WHERE category_id = c.id) as note_count,
                                u.username as created_by_username,
                                a.username as approved_by_username
                                FROM categories c 
                                LEFT JOIN users u ON c.created_by = u.id
                                LEFT JOIN users a ON c.approved_by = a.id
                                ORDER BY c.parent_id ASC, c.name ASC
                            ");
                        } else {
                            $categories = $conn->query("
                                SELECT c.*, 
                                (SELECT COUNT(*) FROM notes WHERE category_id = c.id) as note_count
                                FROM categories c 
                                ORDER BY c.name ASC
                            ");
                        }
                        ?>
                        
                        <?php if ($categories->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Category Name</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Notes Count</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $main_categories = [];
                                        $subcategories = [];
                                        
                                        // Separate main categories and subcategories
                                        while ($category = $categories->fetch_assoc()) {
                                            if ($category['parent_id'] == null) {
                                                $main_categories[] = $category;
                                            } else {
                                                $subcategories[] = $category;
                                            }
                                        }
                                        
                                        // Display main categories
                                        foreach ($main_categories as $category): ?>
                                            <tr class="table-primary">
                                                <td><?php echo $category['id']; ?></td>
                                                <td>
                                                    <strong><i class="bi bi-folder-fill"></i> <?php echo htmlspecialchars($category['name']); ?></strong>
                                                </td>
                                                <td><span class="badge bg-info">Main Category</span></td>
                                                <td>
                                                    <?php if ($has_status): ?>
                                                        <?php if ($category['status'] === 'approved'): ?>
                                                            <span class="badge bg-success">Approved</span>
                                                        <?php elseif ($category['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning">Pending</span>
                                                        <?php elseif ($category['status'] === 'rejected'): ?>
                                                            <span class="badge bg-danger">Rejected</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $category['note_count']; ?> notes</span>
                                                </td>
                                                <td><?php echo htmlspecialchars($category['created_by_username'] ?? 'System'); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($has_status && $category['status'] === 'pending'): ?>
                                                            <a href="categories.php?action=approve&id=<?php echo $category['id']; ?>" 
                                                               class="btn btn-outline-success btn-sm" title="Approve">
                                                                <i class="bi bi-check-lg"></i>
                                                            </a>
                                                            <button class="btn btn-outline-danger btn-sm" 
                                                                    onclick="showRejectModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')" title="Reject">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" 
                                                           class="btn btn-outline-danger btn-sm"
                                                           onclick="return confirm('Are you sure you want to delete this category?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            // Display subcategories under this main category
                                            <?php foreach ($subcategories as $subcat): ?>
                                                <?php if ($subcat['parent_id'] == $category['id']): ?>
                                                    <tr>
                                                        <td><?php echo $subcat['id']; ?></td>
                                                        <td>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;<i class="bi bi-folder2-open"></i> <?php echo htmlspecialchars($subcat['name']); ?>
                                                        </td>
                                                        <td><span class="badge bg-secondary">Subcategory</span></td>
                                                        <td>
                                                            <?php if ($has_status): ?>
                                                                <?php if ($subcat['status'] === 'approved'): ?>
                                                                    <span class="badge bg-success">Approved</span>
                                                                <?php elseif ($subcat['status'] === 'pending'): ?>
                                                                    <span class="badge bg-warning">Pending</span>
                                                                <?php elseif ($subcat['status'] === 'rejected'): ?>
                                                                    <span class="badge bg-danger">Rejected</span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $subcat['note_count']; ?> notes</span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($subcat['created_by_username'] ?? 'System'); ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <?php if ($has_status && $subcat['status'] === 'pending'): ?>
                                                                    <a href="categories.php?action=approve&id=<?php echo $subcat['id']; ?>" 
                                                                       class="btn btn-outline-success btn-sm" title="Approve">
                                                                        <i class="bi bi-check-lg"></i>
                                                                    </a>
                                                                    <button class="btn btn-outline-danger btn-sm" 
                                                                            onclick="showRejectModal(<?php echo $subcat['id']; ?>, '<?php echo htmlspecialchars($subcat['name']); ?>')" title="Reject">
                                                                        <i class="bi bi-x-lg"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <a href="categories.php?action=delete&id=<?php echo $subcat['id']; ?>" 
                                                                   class="btn btn-outline-danger btn-sm"
                                                                   onclick="return confirm('Are you sure you want to delete this subcategory?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-tags display-1 text-muted"></i>
                                <h4 class="text-muted">No categories found</h4>
                                <p class="text-muted">Add your first category using the button above.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="categories.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Category Modal -->
    <div class="modal fade" id="rejectCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Reject Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="GET" action="categories.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" id="rejectCategoryId">
                        <div class="mb-3">
                            <label class="form-label">Category to reject:</label>
                            <div class="form-control-plaintext fw-bold" id="rejectCategoryName"></div>
                        </div>
                        <div class="mb-3">
                            <label for="rejectReason" class="form-label">Rejection Reason:</label>
                            <textarea name="reason" id="rejectReason" class="form-control" rows="4" 
                                      placeholder="Enter the reason for rejecting this category..." required></textarea>
                            <small class="form-text text-muted">This reason will be stored for reference.</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Note:</strong> This category will be rejected and won't appear on the main site.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-lg"></i> Reject Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showRejectModal(categoryId, categoryName) {
            document.getElementById('rejectCategoryId').value = categoryId;
            document.getElementById('rejectCategoryName').textContent = categoryName;
            document.getElementById('rejectReason').value = '';
            new bootstrap.Modal(document.getElementById('rejectCategoryModal')).show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>