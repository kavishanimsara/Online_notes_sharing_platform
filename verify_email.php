<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Verify Email - NotesShare Pro';
$error = '';
$success = '';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['email'])) {
    header('Location: register.php');
    exit();
}

$email = $_GET['email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $verification_code = trim($_POST['verification_code']);
    $password = $_POST['password'];
    
    if (empty($verification_code) || empty($password)) {
        $error = 'Please enter verification code and password';
    } else {
        // Check verification code
        $stmt = $conn->prepare("SELECT id, username, verification_expires FROM users WHERE email = ? AND verification_code = ?");
        $stmt->bind_param("ss", $email, $verification_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if code is expired
            if (strtotime($user['verification_expires']) < time()) {
                $error = 'Verification code has expired. Please register again.';
            } else {
                // Update user as verified
                $update_stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                
                if ($update_stmt->execute()) {
                    // Auto-login after verification
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'user';
                    
                    $success = 'Email verified successfully! Redirecting to your dashboard...';
                    header("Refresh: 3; url=dashboard.php");
                } else {
                    $error = 'Verification failed. Please try again.';
                }
                $update_stmt->close();
            }
        } else {
            $error = 'Invalid verification code';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-box">
            <h2><i class="bi bi-envelope-check"></i> Verify Your Email</h2>
            <p class="text-center">We sent a 6-digit verification code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
            
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

            <form method="POST" action="verify_email.php?email=<?php echo urlencode($email); ?>" class="auth-form">
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" required 
                           class="form-control text-center" maxlength="6" pattern="[0-9]{6}"
                           placeholder="Enter 6-digit code" style="font-size: 1.5rem; letter-spacing: 10px;">
                    <small class="form-text text-muted">Check your email for the 6-digit code</small>
                </div>

                <div class="form-group">
                    <label for="password">Your Password</label>
                    <input type="password" id="password" name="password" required class="form-control"
                           placeholder="Enter your password to confirm">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="bi bi-check-circle"></i> Verify Email
                </button>
            </form>

            <div class="text-center mt-4">
                <p>Didn't receive the code? <a href="resend_verification.php?email=<?php echo urlencode($email); ?>">Resend Code</a></p>
                <p>Wrong email? <a href="register.php">Register Again</a></p>
            </div>
        </div>
    </div>
</div>

<style>
.auth-box {
    max-width: 450px;
}
.form-control.text-center {
    font-weight: bold;
}
</style>

<?php include 'includes/footer.php'; ?>