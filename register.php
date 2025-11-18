<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Register - Notes Sharing Platform';
$error = '';
$success = '';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize all form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $age = !empty($_POST['age']) ? intval($_POST['age']) : NULL;
    $gender = !empty($_POST['gender']) ? $_POST['gender'] : 'prefer_not_to_say';
    $institution_type = !empty($_POST['institution_type']) ? $_POST['institution_type'] : NULL;
    $institution_name = !empty($_POST['institution_name']) ? trim($_POST['institution_name']) : NULL;
    $grade_level = !empty($_POST['grade_level']) ? trim($_POST['grade_level']) : NULL;
    $bio = !empty($_POST['bio']) ? trim($_POST['bio']) : NULL;

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif ($age && $age < 13) {
        $error = 'You must be at least 13 years old to register';
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            // Insert new user with enhanced fields
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare the SQL statement with all fields - COUNT THE PLACEHOLDERS CAREFULLY
            // We have 14 placeholders (?) and 14 variables to bind
            $stmt = $conn->prepare("INSERT INTO users (
                username, email, password, full_name, age, gender,
                institution_type, institution_name, grade_level,
                bio, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if ($stmt) {
                // Bind parameters - make sure types match the database schema
                // s = string, i = integer, s = string, s = string, i = integer, s = string, s = string, s = string, s = string, s = string, s = string, s = string, s = string, s = string
                // That's 14 's' for 14 string parameters (age is integer)
                $stmt->bind_param("ssssisssss",
                    $username, $email, $hashed_password, $full_name, $age,
                    $gender, $institution_type, $institution_name,
                    $grade_level, $bio
                );

                if ($stmt->execute()) {
                    $success = 'Registration successful! You can now login.';
                    // Clear form data
                    $_POST = array();
                } else {
                    $error = 'Registration failed: ' . $stmt->error;
                }
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box enhanced-register">
            <h2><i class="bi bi-person-plus-fill"></i> Create Your Account</h2>
            <p class="auth-subtitle">Join our community of learners and educators</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
                    <p>You will be redirected to login page in 5 seconds...</p>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 5000);
                </script>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="register.php" class="auth-form">
                <div class="form-section">
                    <h4>Account Information</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="required">*</span></label>
                                <input type="text" id="full_name" name="full_name" required class="form-control" 
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" required class="form-control" 
                                       pattern="[a-zA-Z0-9_]{3,20}"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <small class="form-text">3-20 characters, letters, numbers, underscore only</small>
                            </div>
                        </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required class="form-control" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <input type="password" id="password" name="password" required class="form-control" minlength="6">
                                <small class="form-text">Minimum 6 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Personal Information</h4>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" class="form-control" min="13" max="100"
                                       value="<?php echo isset($_POST['age']) ? $_POST['age'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="form-section">
                    <h4>Education Information</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="institution_type">Institution Type</label>
                                <select id="institution_type" name="institution_type" class="form-control">
                                    <option value="">Select...</option>
                                    <option value="school" <?php echo (isset($_POST['institution_type']) && $_POST['institution_type'] == 'school') ? 'selected' : ''; ?>>School</option>
                                    <option value="college" <?php echo (isset($_POST['institution_type']) && $_POST['institution_type'] == 'college') ? 'selected' : ''; ?>>College</option>
                                    <option value="university" <?php echo (isset($_POST['institution_type']) && $_POST['institution_type'] == 'university') ? 'selected' : ''; ?>>University</option>
                                    <option value="other" <?php echo (isset($_POST['institution_type']) && $_POST['institution_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="grade_level">Grade/Year Level</label>
                                <input type="text" id="grade_level" name="grade_level" class="form-control" 
                                       value="<?php echo isset($_POST['grade_level']) ? htmlspecialchars($_POST['grade_level']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="institution_name">Institution Name</label>
                        <input type="text" id="institution_name" name="institution_name" class="form-control" 
                               value="<?php echo isset($_POST['institution_name']) ? htmlspecialchars($_POST['institution_name']) : ''; ?>">
                </div>

                <div class="form-section">
                    <div class="form-group">
                        <label for="bio">Bio (Optional)</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3" 
                                  placeholder="Tell us about yourself..."><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                            <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="bi bi-person-check"></i> Create Account
                </button>
            </form>
            <?php endif; ?>

            <p class="auth-link">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</div>

<style>
.enhanced-register {
    max-width: 800px;
    margin: 0 auto;
}
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}
.form-section h4 {
    color: #667eea;
    margin-bottom: 1rem;
    font-weight: 600;
}
.required {
    color: #dc3545;
}
.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}
.auth-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 1.5rem;
}
</style>

<script>
// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    
    if (password !== confirm) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Calculate age from date of birth
document.getElementById('date_of_birth').addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
    document.getElementById('age').value = age;
});
</script>

<?php include 'includes/footer.php'; ?>