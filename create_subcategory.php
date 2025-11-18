<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Create Subcategory - NotesShare';
$error = '';
$success = '';

// Get main categories
$categories = [];
$category_stmt = $conn->prepare("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
$category_stmt->execute();
$category_result = $category_stmt->get_result();
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}
$category_stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parent_id = intval($_POST['parent_id']);
    
    if (empty($name) || empty($parent_id)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if status column exists
        $check_status = $conn->query("SHOW COLUMNS FROM categories LIKE 'status'");
        $has_status = $check_status->num_rows > 0;
        
        if ($has_status) {
            // Insert with pending status
            $stmt = $conn->prepare("INSERT INTO categories (name, description, parent_id, status, created_by, created_at) VALUES (?, ?, ?, 'pending', ?, NOW())");
            $stmt->bind_param("ssii", $name, $description, $parent_id, $_SESSION['user_id']);
        } else {
            // Insert without status (backward compatibility)
            $stmt = $conn->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $description, $parent_id);
        }
        
        if ($stmt->execute()) {
            $success = 'Subcategory submitted successfully! It will be visible after admin approval.';
            $_POST = array(); // Clear form
        } else {
            $error = 'Failed to create subcategory. Please try again.';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-folder-plus"></i> Create Subcategory</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="create_subcategory.php">
                        <div class="form-group mb-3">
                            <label for="parent_id" class="form-label">Main Category <span class="text-danger">*</span></label>
                            <select id="parent_id" name="parent_id" required class="form-select">
                                <option value="">Select Main Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                        <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Subcategory Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" required class="form-control" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                   maxlength="255" placeholder="Enter subcategory name">
                        </div>

                        <div class="form-group mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" 
                                      placeholder="Describe this subcategory..."
                                      maxlength="500"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <small class="form-text text-muted">Optional: Provide a brief description (Max 500 characters)</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-folder-plus"></i> Create Subcategory
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Guidelines -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Guidelines</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li><i class="bi bi-check-circle text-success"></i> Subcategories must be created under existing main categories</li>
                        <li><i class="bi bi-check-circle text-success"></i> All subcategories require admin approval before appearing</li>
                        <li><i class="bi bi-check-circle text-success"></i> Use clear, descriptive names</li>
                        <li><i class="bi bi-check-circle text-success"></i> Avoid duplicate subcategories</li>
                        <li><i class="bi bi-check-circle text-success"></i> You'll be notified when your subcategory is approved</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>