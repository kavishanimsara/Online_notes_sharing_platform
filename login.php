<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Login - Notes Sharing Platform';
$error = '';
$success = '';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check which columns exist in users table
        $check_columns = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        $has_is_active = $check_columns->num_rows > 0;
        
        $check_role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        $has_role = $check_role->num_rows > 0;
        
        $check_banned_at = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_at'");
        $has_banned_at = $check_banned_at->num_rows > 0;
        
        $check_ban_reason = $conn->query("SHOW COLUMNS FROM users LIKE 'ban_reason'");
        $has_ban_reason = $check_ban_reason->num_rows > 0;
        
        // Build query based on available columns
        $sql = "SELECT id, username, password";
        if ($has_is_active) $sql .= ", is_active";
        if ($has_role) $sql .= ", role";
        if ($has_banned_at) $sql .= ", banned_at";
        if ($has_ban_reason) $sql .= ", ban_reason";
        $sql .= " FROM users WHERE username = ? OR email = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Check if user is banned (only if is_active column exists)
                if ($has_is_active && $user['is_active'] == 0) {
                    $ban_reason = '';
                    if ($has_ban_reason && !empty($user['ban_reason'])) {
                        $ban_reason = 'Reason: ' . htmlspecialchars($user['ban_reason']);
                    }
                    $error = 'Your account has been banned. ' . ($ban_reason ?: 'Please contact administrator.');
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    
                    // Track this session if session manager exists
                    try {
                        if (function_exists('trackUserSession')) {
                            trackUserSession($user['id']);
                        }
                    } catch (Exception $e) {
                        // Session tracking failed, but continue with login
                        error_log("Session tracking failed: " . $e->getMessage());
                    }
                    
                    // Redirect based on role
                    $user_role = $user['role'] ?? 'user';
                    if ($user_role === 'admin' || $user_role === 'super_admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit();
                }
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <h2>Login to Your Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required class="form-control">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-control">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <p class="auth-link">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>