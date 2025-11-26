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
    'institution_type', 'institution_name', 'grade_level', 'bio', 'email_verified', 'show_email', 'show_phone'
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
    // Ensure 'id, username, email, created_at' are included in the select if they weren't checked
    $select_columns = array_unique(array_merge(['id', 'username', 'email', 'created_at'], $existing_columns));
    $columns_sql = implode(', ', $select_columns);
    
    $stmt = $conn->prepare("SELECT $columns_sql FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_with_extras = $result->fetch_assoc();
        foreach ($user_with_extras as $key => $value) {
            if (array_key_exists($key, $user_data)) {
                $user_data[$key] = $value;
            }
        }
        // Also update the basic user data in case fields like email were fetched differently
        $user['email'] = $user_with_extras['email'];
        $user['username'] = $user_with_extras['username'];
    }
    $stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Only update fields that exist in database
    $updatable_fields = $updatable_fields ?? [];
    $params = $params ?? [];
    $types = $types ?? '';
    
    // Check if the request is a general profile update
    if (isset($_POST['email'])) {
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
    }

    // Add user_id for WHERE clause if there are fields to update
    if (!empty($updatable_fields) && isset($_POST['email'])) { // Only update if it's the main form post
        $params[] = $user_id;
        $types .= 'i';
        
        $sql = "UPDATE users SET " . implode(', ', $updatable_fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // Handle potential errors with bind_param due to variable length
        if ($stmt->bind_param($types, ...$params)) {
            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
                // Update session email if changed
                if ($email !== $user['email']) {
                    $_SESSION['email'] = $email;
                }
            } else {
                $error = 'Failed to update profile. Please try again. Error: ' . $stmt->error;
            }
        } else {
            $error = 'Internal binding error.';
        }
        $stmt->close();
        
    } elseif (!isset($_POST['email'])) {
        // This case handles a submission that isn't the main form
        // We'll rely on the logic above to catch updates.
    }
}

// Re-fetch data after a successful update
if (!empty($existing_columns)) {
    // Re-fetch all data to display the current state
    $select_columns = array_unique(array_merge(['id', 'username', 'email', 'created_at'], $existing_columns));
    $columns_sql = implode(', ', $select_columns);
    
    $stmt = $conn->prepare("SELECT $columns_sql FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_with_extras = $result->fetch_assoc();
        foreach ($user_with_extras as $key => $value) {
            if (array_key_exists($key, $user_data)) {
                $user_data[$key] = $value;
            }
        }
        $user['email'] = $user_with_extras['email'];
        $user['username'] = $user_with_extras['username'];
    }
    $stmt->close();
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
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3 position-relative d-inline-block">
                        <div class="profile-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                    </div>
                    <h4 class="mt-3 mb-0"><?php echo htmlspecialchars($user_data['full_name'] ?: $user['username']); ?></h4>
                    <p class="text-muted fw-light mb-4">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="user-stats mb-4">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="stat-number text-primary"><?php 
                                        $notes_count = $conn->query("SELECT COUNT(*) FROM notes WHERE user_id = $user_id")->fetch_row()[0];
                                        echo $notes_count;
                                    ?></div>
                                    <div class="stat-label">Notes</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="stat-number text-success"><?php 
                                        $downloads_count = $conn->query("SELECT COALESCE(SUM(downloads), 0) FROM notes WHERE user_id = $user_id")->fetch_row()[0];
                                        echo $downloads_count;
                                    ?></div>
                                    <div class="stat-label">Downloads</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if (in_array('show_email', $existing_columns) || in_array('show_phone', $existing_columns)): ?>
                        <a href="privacy_settings.php" class="btn btn-primary-soft w-100">
                            <i class="bi bi-shield-lock me-2"></i> Privacy Settings
                        </a>
                        <?php endif; ?>
                        <a href="change_password.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-key me-2"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (in_array('bio', $existing_columns) && !empty($user_data['bio'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i> About Me</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted small"><?php echo nl2br(htmlspecialchars($user_data['bio'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-person-gear me-2"></i> Edit Personal Details</h4>
                    <?php if (empty($existing_columns) || count(array_intersect($existing_columns, ['full_name', 'age', 'gender', 'institution_type', 'institution_name', 'grade_level', 'bio'])) === 0): ?>
                    <small class="text-muted">Basic profile information only. Advanced features are disabled.</small>
                    <?php endif; ?>
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

                    <form method="POST" action="personal_details.php" id="profileForm" class="profile-form">
                        <h5 class="mb-3 border-bottom pb-2">Account Information</h5>
                        <div class="row">
                            <?php if (in_array('full_name', $existing_columns)): ?>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="Enter your full name"
                                           value="<?php echo htmlspecialchars($user_data['full_name']); ?>">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="<?php echo in_array('full_name', $existing_columns) ? 'col-md-6' : 'col-12'; ?>">
                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address"
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <?php if (in_array('email_verified', $existing_columns)): ?>
                                        <?php if ($user_data['email_verified']): ?>
                                            <small class="text-success"><i class="bi bi-check-circle-fill"></i> Verified</small>
                                        <?php else: ?>
                                            <small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Not Verified</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if (in_array('age', $existing_columns) || in_array('date_of_birth', $existing_columns) || in_array('gender', $existing_columns)): ?>
                        <h5 class="mb-3 mt-4 border-bottom pb-2">Personal Details</h5>
                        <div class="row">
                            <?php if (in_array('age', $existing_columns)): ?>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" id="age" name="age" class="form-control" placeholder="Enter your age" min="13" max="100"
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
                        <h5 class="mb-3 mt-4 border-bottom pb-2">Education Information</h5>
                        <div class="row">
                            <?php if (in_array('institution_type', $existing_columns)): ?>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="institution_type" class="form-label">Institution Type</label>
                                    <select id="institution_type" name="institution_type" class="form-select">
                                        <option value="">Select institution type...</option>
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
                                    <input type="text" id="grade_level" name="grade_level" class="form-control" placeholder="Enter grade/year level"
                                           value="<?php echo htmlspecialchars($user_data['grade_level']); ?>">
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('institution_name', $existing_columns)): ?>
                        <div class="form-group mb-3">
                            <label for="institution_name" class="form-label">Institution Name</label>
                            <input type="text" id="institution_name" name="institution_name" class="form-control" placeholder="Enter institution name"
                                   value="<?php echo htmlspecialchars($user_data['institution_name']); ?>">
                        </div>
                        <?php endif; ?>
                        <?php if (in_array('bio', $existing_columns)): ?>
                        <div class="form-group mb-4">
                            <label for="bio" class="form-label">Bio / Summary</label>
                            <textarea id="bio" name="bio" class="form-control" rows="4" 
                                      placeholder="Tell us about yourself, your interests, and what you're studying..."><?php echo htmlspecialchars($user_data['bio']); ?></textarea>
                        </div>
                        <?php endif; ?>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <span id="buttonText"><i class="bi bi-check-circle me-2"></i> Update Profile</span>
                                <span id="spinner" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Updating...
                                </span>
                            </button>
                        </div>
                    </form>

                    <?php if (empty($existing_columns) || count(array_intersect($existing_columns, ['full_name', 'age', 'gender', 'institution_type', 'institution_name', 'grade_level', 'bio'])) === 0): ?>
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

<style>


.user-stats {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border-radius: 15px;
    padding: 20px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.stat-item {
    padding: 10px;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--text-color);
    opacity: 0.8;
    font-weight: 500;
}

.profile-form .form-group {
    margin-bottom: 25px;
}

.profile-form label {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 8px;
    display: block;
}

.profile-form .form-control,
.profile-form .form-select {
    border: 2px solid transparent;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(5px);
}

.profile-form .form-control:focus,
.profile-form .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.profile-form textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.card {
    background: var(--glass-bg);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--glass-border);
    transition: all 0.3s ease;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.card-header {
    background: var(--gradient-primary);
    border: none;
    padding: 25px 30px;
    color: white;
}

.card-header h4 {
    font-weight: 600;
    margin-bottom: 5px;
}

.card-body {
    padding: 30px;
}

.card .card-header:not(.bg-primary) {
    background: rgba(255, 255, 255, 0.9) !important;
    border-bottom: 1px solid var(--glass-border);
    color: var(--dark-color);
}

.card .card-header:not(.bg-primary) h6 {
    font-weight: 600;
    color: var(--primary-color);
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

.alert-info {
    background: rgba(74, 144, 226, 0.1);
    color: var(--dark-color);
    border-left: 4px solid var(--primary-color);
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
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 2px solid var(--border-color);
}

.btn-outline-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.btn-primary-soft {
    background: rgba(74, 144, 226, 0.1);
    color: var(--primary-color);
    border: 2px solid rgba(74, 144, 226, 0.2);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-primary-soft:hover {
    background: rgba(74, 144, 226, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(74, 144, 226, 0.2);
}

/* Section Headers */
h5.border-bottom {
    border-bottom: 2px solid var(--primary-color) !important;
    color: var(--dark-color);
    font-weight: 600;
    padding-bottom: 10px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 25px 20px;
    }
    
    .card-header {
        padding: 20px 25px;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
    }
    
    .profile-avatar i {
        font-size: 3rem;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    .card-body {
        padding: 20px 15px;
    }
    
    .card-header {
        padding: 15px 20px;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
    }
    
    .profile-avatar i {
        font-size: 2.5rem;
    }
}
</style>

<script>
// Add a loading state to the main form submission
document.getElementById('profileForm').addEventListener('submit', function() {
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('buttonText').classList.add('d-none');
    document.getElementById('spinner').classList.remove('d-none');
});

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