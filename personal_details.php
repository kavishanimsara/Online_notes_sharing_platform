<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'My Profile - NotesShare Pro';
$error = '';
$success = '';

$user_id = $_SESSION['user_id'];

// Get user data - only basic fields that exist in your database
$stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Initialize user data with safe defaults
$user_data = [
    'full_name' => '',
    'age' => '',
    'gender' => 'prefer_not_to_say',
    'institution_type' => '',
    'institution_name' => '',
    'grade_level' => '',
    'bio' => '',
    'email_verified' => false
];

// Check if additional columns exist and get their values
$columns_to_check = [
    'full_name', 'age', 'gender',
    'institution_type', 'institution_name', 'grade_level', 'bio', 'email_verified'
];

$existing_columns = [];
foreach ($columns_to_check as $column) {
    $check_stmt = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($check_stmt->num_rows > 0) {
        $existing_columns[] = $column;
    }
}

// If we have additional columns, fetch them
if (!empty($existing_columns)) {
    $columns_sql = implode(', ', $existing_columns);
    $stmt = $conn->prepare("SELECT id, username, email, created_at, $columns_sql FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_with_extras = $result->fetch_assoc();
        foreach ($existing_columns as $column) {
            $user_data[$column] = $user_with_extras[$column] ?? '';
        }
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        if (in_array('profile_picture', $existing_columns)) {
            $file = $_FILES['profile_picture'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 2 * 1024 * 1024; // 2MB
            
            if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_file_size) {
                $upload_dir = 'uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete old profile picture if exists
                    if (!empty($user_data['profile_picture']) && file_exists($user_data['profile_picture'])) {
                        unlink($user_data['profile_picture']);
                    }
                    
                    $updatable_fields[] = "profile_picture = ?";
                    $params[] = $upload_path;
                    $types .= 's';
                    $user_data['profile_picture'] = $upload_path;
                }
            }
        }
    }
    
    // Only update fields that exist in database
    $updatable_fields = $updatable_fields ?? [];
    $params = $params ?? [];
    $types = $types ?? '';
    
    // Basic fields that should exist
    $email = trim($_POST['email']);
    $updatable_fields[] = "email = ?";
    $params[] = $email;
    $types .= 's';
    
    // Check and add optional fields if they exist
    $optional_fields = [
        'full_name' => 's',
        'age' => 'i',
        'gender' => 's',
        'institution_type' => 's',
        'institution_name' => 's',
        'grade_level' => 's',
        'bio' => 's'
    ];
    
    foreach ($optional_fields as $field => $type) {
        if (in_array($field, $existing_columns)) {
            $value = trim($_POST[$field]);
            $updatable_fields[] = "$field = ?";
            $params[] = $value;
            $types .= $type;
            
            // Update user_data for display
            $user_data[$field] = $value;
        }
    }
    
    // Add user_id for WHERE clause
    $params[] = $user_id;
    $types .= 'i';
    
    if (!empty($updatable_fields)) {
        $sql = "UPDATE users SET " . implode(', ', $updatable_fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            // Update session email if changed
            if ($email !== $user['email']) {
                $_SESSION['email'] = $email;
            }
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
        $stmt->close();
    } else {
        $error = 'No fields to update.';
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <!-- Profile Sidebar -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <?php if (in_array('profile_picture', $existing_columns) && !empty($user_data['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" 
                                 alt="Profile Picture" class="rounded-circle" 
                                 style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #667eea;">
                        <?php else: ?>
                            <i class="bi bi-person-circle" style="font-size: 4rem; color: #667eea;"></i>
                        <?php endif; ?>
                    </div>
                    <h5><?php echo htmlspecialchars($user_data['full_name'] ?: $user['username']); ?></h5>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <div class="d-grid gap-2">
                        <?php if (in_array('show_email', $existing_columns) || in_array('show_phone', $existing_columns)): ?>
                        <a href="privacy_settings.php" class="btn btn-outline-primary">
                            <i class="bi bi-shield-lock"></i> Privacy Settings
                        </a>
                        <?php endif; ?>
                        <a href="change_password.php" class="btn btn-outline-secondary">
                            <i class="bi bi-key"></i> Change Password
                        </a>
                        <?php if (in_array('profile_picture', $existing_columns)): ?>
                        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#uploadPictureModal">
                            <i class="bi bi-camera"></i> Change Picture
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <div class="col-md-8">
            <!-- Profile Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-person-gear"></i> Personal Details</h4>
                    <?php if (empty($existing_columns) || count($existing_columns) <= 3): ?>
                    <small class="opacity-75">Basic profile information</small>
                    <?php endif; ?>
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

                    <form method="POST" action="personal_details.php">
                        <h5 class="mb-3 text-primary">Account Information</h5>
                        <div class="row">
                            <?php if (in_array('full_name', $existing_columns)): ?>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user_data['full_name']); ?>">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="<?php echo in_array('full_name', $existing_columns) ? 'col-md-6' : 'col-12'; ?>">
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <?php if ($user_data['email_verified']): ?>
                                        <small class="text-success"><i class="bi bi-check-circle-fill"></i> Email verified</small>
                                    <?php else: ?>
                                        <small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Email not verified</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (in_array('age', $existing_columns) || in_array('date_of_birth', $existing_columns) || in_array('gender', $existing_columns)): ?>
                        <h5 class="mb-3 mt-4 text-primary">Personal Information</h5>
                        <div class="row">
                            <?php if (in_array('age', $existing_columns)): ?>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" id="age" name="age" class="form-control" min="13" max="100"
                                           value="<?php echo $user_data['age']; ?>">
                                </div>
                            </div>
                            <?php endif; ?>

                            
                            <?php if (in_array('gender', $existing_columns)): ?>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select id="gender" name="gender" class="form-select">
                                        <option value="prefer_not_to_say">Prefer not to say</option>
                                        <option value="male" <?php echo $user_data['gender'] == 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo $user_data['gender'] == 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo $user_data['gender'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('institution_type', $existing_columns) || in_array('institution_name', $existing_columns) || in_array('grade_level', $existing_columns)): ?>
                        <h5 class="mb-3 mt-4 text-primary">Education Information</h5>
                        <div class="row">
                            <?php if (in_array('institution_type', $existing_columns)): ?>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="institution_type" class="form-label">Institution Type</label>
                                    <select id="institution_type" name="institution_type" class="form-select">
                                        <option value="">Select...</option>
                                        <option value="school" <?php echo $user_data['institution_type'] == 'school' ? 'selected' : ''; ?>>School</option>
                                        <option value="college" <?php echo $user_data['institution_type'] == 'college' ? 'selected' : ''; ?>>College</option>
                                        <option value="university" <?php echo $user_data['institution_type'] == 'university' ? 'selected' : ''; ?>>University</option>
                                        <option value="other" <?php echo $user_data['institution_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (in_array('grade_level', $existing_columns)): ?>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="grade_level" class="form-label">Grade/Year Level</label>
                                    <input type="text" id="grade_level" name="grade_level" class="form-control" 
                                           value="<?php echo htmlspecialchars($user_data['grade_level']); ?>">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('institution_name', $existing_columns)): ?>
                        <div class="form-group mb-3">
                            <label for="institution_name" class="form-label">Institution Name</label>
                            <input type="text" id="institution_name" name="institution_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_data['institution_name']); ?>">
                        </div>
                        <?php endif; ?>
                        <?php if (in_array('bio', $existing_columns)): ?>
                        <div class="form-group mb-4">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea id="bio" name="bio" class="form-control" rows="4" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
                        </div>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Update Profile
                            </button>
                        </div>
                    </form>

                    <?php if (empty($existing_columns) || count($existing_columns) <= 3): ?>
                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle"></i> Limited Profile Features</h6>
                        <p class="mb-0">Your profile is currently using basic features. Contact the administrator to enable advanced profile features like personal information, education details, and privacy settings.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Upload Modal -->
<?php if (in_array('profile_picture', $existing_columns)): ?>
<div class="modal fade" id="uploadPictureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Update Profile Picture</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="personal_details.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Choose Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control" 
                               accept="image/*" onchange="previewImage(event)">
                        <small class="form-text text-muted">Allowed: JPG, PNG, GIF (Max: 2MB)</small>
                    </div>
                    <div class="mb-3 text-center">
                        <img id="imagePreview" src="#" alt="Preview" class="rounded-circle" 
                             style="width: 100px; height: 100px; object-fit: cover; display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-camera"></i> Upload Picture
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}
</script>
<?php endif; ?>

<script>
// Calculate age from date of birth if both fields exist
<?php if (in_array('date_of_birth', $existing_columns) && in_array('age', $existing_columns)): ?>
document.getElementById('date_of_birth').addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
    document.getElementById('age').value = age;
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>