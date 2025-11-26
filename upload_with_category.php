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
        // Only set a generic error if no file was selected and no specific error occurred
        if ($upload_error !== UPLOAD_ERR_NO_FILE || $_SERVER['REQUEST_METHOD'] == 'POST') {
             $error = $error_messages[$upload_error] ?? 'Please select a file to upload';
        }
    }
}

include 'includes/header.php';
?>

<!-- Interactive Background -->
<div class="interactive-bg">
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Upload Card -->
            <div class="upload-box1">
                <div class="card-header bg-primary text-white position-relative overflow-hidden">
                    <div class="position-absolute top-0 end-0 w-100 h-100 opacity-10">
                        <i class="bi bi-cloud-upload-fill display-1"></i>
                    </div>
                    <h4 class="mb-1"><i class="bi bi-cloud-upload me-2" ></i> Upload New Note</h4>
                    <p class="mb-0 small opacity-75">Share your knowledge with the community</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-error d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="bi bi-check-circle-fill flex-shrink-0 me-2"></i>
                            <div><?php echo htmlspecialchars($success); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="upload_with_category.php" enctype="multipart/form-data" id="uploadForm" class="upload-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="title" class="form-label">Note Title <span class="text-danger">*</span></label>
                                    <input type="text" id="title" name="title" required class="form-control" 
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                           maxlength="255" placeholder="Enter a descriptive title">
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
                                </select>
                                <a href="create_subcategory.php" class="btn btn-outline-secondary" target="_blank" title="Create a new subcategory">
                                    <i class="bi bi-plus-circle"></i> Create New
                                </a>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" 
                                      placeholder="Describe your note, its contents, and key topics..."
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
                            <div class="file-upload-area">
                                <input type="file" id="note_file" name="note_file" required class="form-control" 
                                       accept=".pdf,.doc,.docx,.txt,.ppt,.pptx,.zip"
                                       onchange="validateFile()">
                                <div class="file-upload-info mt-2">
                                    <small class="form-text text-muted">Allowed: PDF, DOC, DOCX, TXT, PPT, PPTX, ZIP (Max: 10MB)</small>
                                    <div id="fileError" class="text-danger small mt-1"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <span id="buttonText"><i class="bi bi-cloud-upload me-2"></i> Upload Note</span>
                                <span id="spinner" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Uploading...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
                <br>
            </div>
        <div>
        </div>
        <br>
            
            <!-- Guidelines Card -->
            <div class="card mt-4">
                <div class=" bg-light circle me-2">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> <br> Upload Guidelines</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> File size limit: 10MB</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Allowed formats: PDF, DOC, DOCX, TXT, PPT, PPTX, ZIP</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Provide clear and descriptive titles</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Select appropriate categories</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Notes will be reviewed before publishing</li>
                                <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i> Share high-quality, educational content</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced Upload Page Styles */
.upload-box1 {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    border: 1px solid var(--glass-border);
    transition: all 0.3s ease;
    overflow: hidden;
    margin-left: 10%;
    margin-right: 10%;
}

.upload-box1:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
}

.upload-box1 .card-header {
    background: var(--gradient-primary) !important;
    border: none;
    padding: px;
    position:relative;
}

.upload-box1 .card-header h4 {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.upload-box1 .card-body {
    padding: 40px;
}

.upload-form .form-group {
    margin-bottom: 25px;
}

.upload-form label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 8px;
    display: block;
}

.upload-form .form-control,
.upload-form .form-select {
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(5px);
}

.upload-form .form-control:focus,
.upload-form .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.upload-form textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.file-upload-area {
    position: relative;
}

.file-upload-area .form-control[type="file"] {
    padding: 15px;
    background: rgba(248, 249, 250, 0.8);
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-upload-area .form-control[type="file"]:hover {
    background: rgba(233, 236, 239, 0.8);
    transform: translateY(-2px);
}

.file-upload-info {
    padding: 10px;
    background: rgba(248, 249, 250, 0.5);
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}

/* Enhanced Guidelines Card */
.card.mt-4 {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--glass-border);
    transition: transform 0.3s ease;
}

.card.mt-4:hover {
    transform: translateY(-3px);
}

.card.mt-4 .card-header {
    background: rgba(255, 255, 255, 0.9) !important;
    border-bottom: 1px solid var(--glass-border);
    padding: 20px 25px;
}

.card.mt-4 .card-header h5 {
    color: var(--dark-color);
    font-weight: 600;
}

.card.mt-4 .card-body {
    padding: 25px;
}

/* Enhanced Alerts */
.alert {
    border-radius: 12px;
    border: none;
    padding: 16px 20px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.alert:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.alert-error {
    background: rgba(231, 76, 60, 0.1);
    color: #C0392B;
    border-left: 4px solid #E74C3C;
}

.alert-success {
    background: rgba(80, 200, 120, 0.1);
    color: #0E6655;
    border-left: 4px solid var(--secondary-color);
}

/* Enhanced Buttons */
.btn-primary {
    background: var(--gradient-primary);
    border: none;
    border-radius: 12px;
    padding: 15px 30px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn-primary:hover::before {
    left: 100%;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.btn-outline-secondary {
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    transform: translateY(-2px);
}

/* Form validation states */
.form-control.is-valid {
    border-color: var(--secondary-color);
    box-shadow: 0 0 0 3px rgba(80, 200, 120, 0.1);
}

.form-control.is-invalid {
    border-color: var(--danger-color);
    box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .upload-box .card-body {
        padding: 30px 20px;
    }
    
    .upload-box .card-header {
        padding: 25px 20px;
    }
    
    .upload-box .card-header h4 {
        font-size: 1.5rem;
    }
    
    .card.mt-4 .card-body {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .upload-box .card-body {
        padding: 25px 15px;
    }
    
    .upload-box .card-header {
        padding: 20px 15px;
    }
    
    .upload-box .card-header h4 {
        font-size: 1.3rem;
    }
}
</style>

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
                        // Restore previous selection if available
                        const isSelected = '<?php echo isset($_POST['subcategory_id']) ? $_POST['subcategory_id'] : ''; ?>' == subcat.id;
                        subcategorySelect.innerHTML += `<option value="${subcat.id}" ${isSelected ? 'selected' : ''}>${subcat.name}</option>`;
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
    
    // Clear previous error message and enable button by default
    fileError.textContent = '';
    
    if (file) {
        const allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'zip'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        const maxSize = 10 * 1024 * 1024; // 10MB
        let isValid = true;
        
        if (!allowedExtensions.includes(fileExtension)) {
            fileError.textContent = 'Invalid file type. Please select a PDF, DOC, DOCX, TXT, PPT, PPTX, or ZIP file.';
            isValid = false;
        } else if (file.size > maxSize) {
            fileError.textContent = 'File size exceeds 10MB limit. Please choose a smaller file.';
            isValid = false;
        }
        
        submitBtn.disabled = !isValid;
        if (isValid) {
            // Give visual feedback that file is OK
            fileInput.classList.remove('is-invalid');
            fileInput.classList.add('is-valid');
        } else {
            fileInput.classList.remove('is-valid');
            fileInput.classList.add('is-invalid');
        }
    } else {
        fileInput.classList.remove('is-valid');
        fileInput.classList.remove('is-invalid');
        submitBtn.disabled = false;
    }
}

// Form submission handling to show loading spinner
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    
    // Perform final check before submitting
    validateFile();
    if (submitBtn.disabled) {
        e.preventDefault();
        return;
    }
    
    // Show spinner
    submitBtn.disabled = true;
    document.getElementById('buttonText').classList.add('d-none');
    document.getElementById('spinner').classList.remove('d-none');
});

// Initialize subcategories on page load if category was previously selected
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('category_id').value) {
        document.getElementById('category_id').dispatchEvent(new Event('change'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>