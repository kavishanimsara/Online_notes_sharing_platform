<?php
require_once '../config/db.php';
session_start();

$pageTitle = 'Verify Admin Request - NotesShare Pro';
$error = '';
$success = '';
$show_resend_option = false;

if (!isset($_GET['email'])) {
    header('Location: register.php');
    exit();
}

$email = $_GET['email'];

// Check if request exists and get status
$check_request = $conn->prepare("SELECT * FROM admin_verification WHERE email = ? AND status = 'pending'");
$check_request->bind_param("s", $email);
$check_request->execute();
$request_result = $check_request->get_result();

if ($request_result->num_rows === 0) {
    $error = 'No pending admin request found for this email.';
    $show_resend_option = true;
} else {
    $request_data = $request_result->fetch_assoc();
    
    // Check if code is expired
    if (strtotime($request_data['expires_at']) < time()) {
        $error = 'Verification code has expired. Please request a new one.';
        $show_resend_option = true;
        
        // Optionally mark as expired in database
        $update_expired = $conn->prepare("UPDATE admin_verification SET status = 'expired' WHERE id = ?");
        $update_expired->bind_param("i", $request_data['id']);
        $update_expired->execute();
        $update_expired->close();
    }
}
$check_request->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle resend verification code
    if (isset($_POST['resend_code'])) {
        // Generate new verification code
        $new_verification_code = sprintf("%06d", mt_rand(1, 999999));
        $new_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update with new code
        $update_stmt = $conn->prepare("UPDATE admin_verification SET verification_code = ?, expires_at = ? WHERE email = ? AND status = 'pending'");
        $update_stmt->bind_param("sss", $new_verification_code, $new_expires_at, $email);
        
        if ($update_stmt->execute()) {
            // Send new verification email (implement your email function)
            if (sendAdminVerificationEmail($email, $request_data['full_name'], $new_verification_code)) {
                $success = 'New verification code sent! Check your email.';
                $error = '';
                $show_resend_option = false;
            } else {
                $success = 'New verification code: ' . $new_verification_code;
                $error = '';
                $show_resend_option = false;
            }
        } else {
            $error = 'Failed to generate new verification code. Please try again.';
        }
        $update_stmt->close();
    }
    // Handle verification (existing code)
    else {
        $verification_code = trim($_POST['verification_code']);
        $admin_level = $_POST['admin_level'];
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($verification_code) || empty($username) || empty($password)) {
            $error = 'Please fill in all required fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            // Verify code with expiry check
            $stmt = $conn->prepare("SELECT * FROM admin_verification WHERE email = ? AND verification_code = ? AND status = 'pending' AND expires_at > NOW()");
            $stmt->bind_param("ss", $email, $verification_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $request = $result->fetch_assoc();
                
                // Check if username exists
                $user_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $user_check->bind_param("s", $username);
                $user_check->execute();
                
                if ($user_check->get_result()->num_rows > 0) {
                    $error = 'Username already exists';
                } else {
                    // Get user ID from email
                    $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $user_stmt->bind_param("s", $email);
                    $user_stmt->execute();
                    $user_result = $user_stmt->get_result();
                    
                    if ($user_result->num_rows > 0) {
                        $user = $user_result->fetch_assoc();
                        $user_id = $user['id'];
                        
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Update user with admin details
                        $update_user = $conn->prepare("UPDATE users SET username = ?, password = ?, full_name = ? WHERE id = ?");
                        $update_user->bind_param("sssi", $username, $hashed_password, $request['full_name'], $user_id);
                        
                        if ($update_user->execute()) {
                            // Create admin record
                            $permissions = json_encode(getDefaultPermissions($admin_level));
                            $created_by = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
                            
                            $admin_stmt = $conn->prepare("INSERT INTO admins (user_id, admin_level, permissions, created_by) VALUES (?, ?, ?, ?)");
                            $admin_stmt->bind_param("issi", $user_id, $admin_level, $permissions, $created_by);
                            
                            if ($admin_stmt->execute()) {
                                // Update verification request
                                $update_verification = $conn->prepare("UPDATE admin_verification SET status = 'approved', verified_at = NOW() WHERE id = ?");
                                $update_verification->bind_param("i", $request['id']);
                                $update_verification->execute();
                                
                                $success = 'Admin account created successfully! You can now login.';
                                
                                // Log activity if created by existing admin
                                if ($created_by) {
                                    logAdminActivity($conn, 'admin_created', 'admin', $user_id, "Created $admin_level: $username");
                                }
                                
                                echo "<script>setTimeout(function() { window.location.href = 'login.php'; }, 3000);</script>";
                            } else {
                                $error = 'Failed to create admin account';
                            }
                            $admin_stmt->close();
                        } else {
                            $error = 'Failed to update user account';
                        }
                        $update_user->close();
                    } else {
                        $error = 'User not found';
                    }
                    $user_stmt->close();
                }
                $user_check->close();
            } else {
                $error = 'Invalid or expired verification code';
                $show_resend_option = true;
            }
            $stmt->close();
        }
    }
}

// Function to send verification email (you need to implement this)
function sendAdminVerificationEmail($email, $name, $code) {
    $subject = "New Verification Code - NotesShare Pro Admin";
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f9f9f9; }
            .code { font-size: 32px; font-weight: bold; color: #dc3545; text-align: center; padding: 20px; background: white; border-radius: 10px; margin: 20px 0; letter-spacing: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Admin Verification Code</h2>
            </div>
            <div class='content'>
                <p>Hello $name,</p>
                <p>Here is your new verification code for admin registration:</p>
                <div class='code'>$code</div>
                <p>This code will expire in 24 hours.</p>
                <p>If you didn't request a new code, please ignore this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: NotesShare Pro <noreply@noteshare.com>" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

// Function to get default permissions (you need to implement this)
function getDefaultPermissions($level) {
    $permissions = [
        'super_admin' => ['manage_users', 'manage_notes', 'manage_categories', 'manage_admins', 'view_reports', 'system_settings'],
        'admin' => ['manage_users', 'manage_notes', 'manage_categories', 'view_reports'],
        'moderator' => ['manage_notes', 'view_reports']
    ];
    
    return $permissions[$level] ?? [];
}

// Function to log activity (you need to implement this)
function logAdminActivity($conn, $action, $target_type, $target_id, $details) {
    // Your logging implementation here
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
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-shield-check"></i> Complete Admin Registration
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                                
                                <?php if ($show_resend_option): ?>
                                    <div class="mt-3">
                                        <form method="POST" action="verify_request.php?email=<?php echo urlencode($email); ?>">
                                            <button type="submit" name="resend_code" class="btn btn-warning btn-sm">
                                                <i class="bi bi-arrow-clockwise"></i> Resend Verification Code
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$error || ($error && !$show_resend_option)): ?>
                        <form method="POST" action="verify_request.php?email=<?php echo urlencode($email); ?>">
                            <div class="mb-3">
                                <label class="form-label">Verification Code</label>
                                <input type="text" name="verification_code" class="form-control text-center" 
                                       required maxlength="6" pattern="[0-9]{6}" 
                                       style="font-size: 1.5rem; letter-spacing: 10px;"
                                       placeholder="000000"
                                       value="<?php echo isset($_POST['verification_code']) ? htmlspecialchars($_POST['verification_code']) : ''; ?>">
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Check your email for the 6-digit code
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Admin Level</label>
                                <select name="admin_level" class="form-select" required>
                                    <option value="moderator">Moderator</option>
                                    <option value="admin">Admin</option>
                                    <?php if (isset($_SESSION['admin_level']) && $_SESSION['admin_level'] == 'super_admin'): ?>
                                    <option value="super_admin">Super Admin</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required 
                                       pattern="[a-zA-Z0-9_]{3,20}"
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="8">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-3">
                                <i class="bi bi-check-circle"></i> Complete Registration
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="register.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to Request Form
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>