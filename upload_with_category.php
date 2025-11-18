<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Upload Note - NotesShare Pro';
$error = '';
$success = '';

// Get categories for dropdown
$categories = [];
$subcategories = [];
$category_stmt = $conn->prepare("SELECT id, name, icon FROM categories WHERE parent_id IS NULL ORDER BY name");
$category_stmt->execute();
$category_result = $category_stmt->get_result();
while ($row = $category_result->fetch_assoc()) {
    $categories[] = $row;
}
$category_stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $subcategory_id = !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : NULL;
    $tags = trim($_POST['tags']);
    
    // File upload handling
    if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['note_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'zip'];
        $max_file_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $error = 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions);
        } elseif ($file_size > $max_file_size) {
            $error = 'File too large. Maximum size: 10MB';
        } elseif (empty($title) || empty($category_id)) {
            $error = 'Please fill in all required fields';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/notes/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $error = 'Failed to create upload directory. Please check permissions.';
                }
            }
            
            // Check if directory is writable
            if (!is_writable($upload_dir)) {
                $error = 'Upload directory is not writable. Please check permissions.';
            } else {
                // Generate unique filename
                $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Insert into database
                    $user_id = $_SESSION['user_id'];
                    
                    // Check if user has role and set appropriate status
                    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
                    $status = ($user_role == 'admin' || $user_role == 'super_admin') ? 'approved' : 'pending';
                    
                    // First, let's check the table structure
                    $table_check = $conn->query("DESCRIBE notes");
                    $columns = [];
                    while ($row = $table_check->fetch_assoc()) {
                        $columns[] = $row['Field'];
                    }
                    
                    // Prepare the SQL based on available columns
                    if (in_array('tags', $columns) && in_array('subcategory_id', $columns)) {
                        // Full version with tags and subcategory
                        $stmt = $conn->prepare("INSERT INTO notes (user_id, title, description, file_path, file_name, file_size, category_id, subcategory_id, tags, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("issssiisss", $user_id, $title, $description, $upload_path, $file_name, $file_size, $category_id, $subcategory_id, $tags, $status);
                    } elseif (in_array('tags', $columns)) {
                        // Version with tags but no subcategory
                        $stmt = $conn->prepare("INSERT INTO notes (user_id, title, description, file_path, file_name, file_size, category_id, tags, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("issssiss", $user_id, $title, $description, $upload_path, $file_name, $file_size, $category_id, $tags, $status);
                    } elseif (in_array('subcategory_id', $columns)) {
                        // Version with subcategory but no tags
                        $stmt = $conn->prepare("INSERT INTO notes (user_id, title, description, file_path, file_name, file_size, category_id, subcategory_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("issssiiss", $user_id, $title, $description, $upload_path, $file_name, $file_size, $category_id, $subcategory_id, $status);
                    } else {
                        // Basic version without tags and subcategory
                        $stmt = $conn->prepare("INSERT INTO notes (user_id, title, description, file_path, file_name, file_size, category_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("issssiss", $user_id, $title, $description, $upload_path, $file_name, $file_size, $category_id, $status);
                    }
                    
                    if ($stmt->execute()) {
                        $success = $status == 'approved' ? 
                            'Note uploaded successfully and published!' : 
                            'Note uploaded successfully! Waiting for admin approval.';
                        
                        // Redirect to home page after successful upload
                        header('Location: index.php?upload=success');
                        exit();
                    } else {
                        $error = 'Failed to save note details. Please try again. Error: ' . $stmt->error;
                        // Delete uploaded file
                        if (file_exists($upload_path)) {
                            unlink($upload_path);
                        }
                    }
                    $stmt->close();
                } else {
                    $error = 'Failed to upload file. Please try again.';
                    // Debug information
                    error_log("Upload failed. Tmp: $file_tmp, Target: $upload_path");
                }
            }
        }
    } else {
        $upload_error = $_FILES['note_file']['error'] ?? 'Unknown error';
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit',
            UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
        ];
        $error = $error_messages[$upload_error] ?? 'Please select a file to upload';
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-cloud-upload"></i> Upload New Note</h4>
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

                    <form method="POST" action="upload_with_category.php" enctype="multipart/form-data" id="uploadForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="title" class="form-label">Note Title <span class="text-danger">*</span></label>
                                    <input type="text" id="title" name="title" required class="form-control" 
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                           maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select id="category_id" name="category_id" required class="form-select">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php if (!empty($category['icon'])): ?>
                                                    <i class="bi <?php echo $category['icon']; ?>"></i> 
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="subcategory_id" class="form-label">Subcategory (Optional)</label>
                            <div class="input-group">
                                <select id="subcategory_id" name="subcategory_id" class="form-select">
                                    <option value="">Select Subcategory</option>
                                    <!-- Subcategories will be loaded via AJAX -->
                                </select>
                                <a href="create_subcategory.php" class="btn btn-outline-secondary" target="_blank">
                                    <i class="bi bi-plus-circle"></i> Create
                                </a>
                            </div>
                            <small class="form-text text-muted">Don't see a subcategory? Create a new one!</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" 
                                      placeholder="Describe your note..."
                                      maxlength="1000"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <small class="form-text text-muted">Max 1000 characters</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="tags" class="form-label">Tags (Optional)</label>
                            <input type="text" id="tags" name="tags" class="form-control" 
                                   placeholder="e.g., algebra, calculus, math-101"
                                   value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>"
                                   maxlength="255">
                            <small class="form-text text-muted">Separate tags with commas (Max 255 characters)</small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="note_file" class="form-label">Note File <span class="text-danger">*</span></label>
                            <input type="file" id="note_file" name="note_file" required class="form-control" 
                                   accept=".pdf,.doc,.docx,.txt,.ppt,.pptx,.zip"
                                   onchange="validateFile()">
                            <small class="form-text text-muted">Allowed: PDF, DOC, DOCX, TXT, PPT, PPTX, ZIP (Max: 10MB)</small>
                            <div id="fileError" class="text-danger small mt-1"></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="bi bi-cloud-upload"></i> Upload Note
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Upload Guidelines -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Upload Guidelines</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li><i class="bi bi-check-circle text-success"></i> File size limit: 10MB</li>
                        <li><i class="bi bi-check-circle text-success"></i> Allowed formats: PDF, DOC, DOCX, TXT, PPT, PPTX, ZIP</li>
                        <li><i class="bi bi-check-circle text-success"></i> Provide clear and descriptive titles</li>
                        <li><i class="bi bi-check-circle text-success"></i> Select appropriate categories for better organization</li>
                        <li><i class="bi bi-check-circle text-success"></i> Notes will be reviewed before publishing</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load subcategories when category changes
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const subcategorySelect = document.getElementById('subcategory_id');
    
    if (categoryId) {
        // Show loading state
        subcategorySelect.innerHTML = '<option value="">Loading subcategories...</option>';
        subcategorySelect.disabled = true;
        
        fetch('get_subcategories.php?category_id=' + categoryId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                if (data.length > 0) {
                    data.forEach(subcat => {
                        subcategorySelect.innerHTML += `<option value="${subcat.id}">${subcat.name}</option>`;
                    });
                } else {
                    subcategorySelect.innerHTML += '<option value="">No subcategories available</option>';
                }
                subcategorySelect.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                subcategorySelect.innerHTML = '<option value="">Error loading subcategories</option>';
                subcategorySelect.disabled = false;
            });
    } else {
        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
        subcategorySelect.disabled = false;
    }
});

// File validation
function validateFile() {
    const fileInput = document.getElementById('note_file');
    const fileError = document.getElementById('fileError');
    const submitBtn = document.getElementById('submitBtn');
    const file = fileInput.files[0];
    
    if (file) {
        const allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'zip'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!allowedExtensions.includes(fileExtension)) {
            fileError.textContent = 'Invalid file type. Please select a PDF, DOC, DOCX, TXT, PPT, PPTX, or ZIP file.';
            submitBtn.disabled = true;
        } else if (file.size > maxSize) {
            fileError.textContent = 'File size exceeds 10MB limit. Please choose a smaller file.';
            submitBtn.disabled = true;
        } else {
            fileError.textContent = '';
            submitBtn.disabled = false;
        }
    } else {
        fileError.textContent = '';
        submitBtn.disabled = false;
    }
}

// Form submission handling
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
});
</script>

<?php include 'includes/footer.php'; ?>