<?php
require_once '../config/db.php';
session_start();

$pageTitle = 'Admin Registration - NotesShare Pro';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $reason = trim($_POST['reason']);
    
    if (empty($email) || empty($full_name) || empty($reason)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        // Check if email already has a pending request
        $stmt = $conn->prepare("SELECT id FROM admin_verification WHERE email = ? AND status = 'pending'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'You already have a pending admin request';
        } else {
            // Check if user exists in users table
            $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $user_stmt->bind_param("s", $email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                $error = 'No user found with this email. Please register as a user first.';
            } else {
                // Generate verification code
                $verification_code = sprintf("%06d", mt_rand(1, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Insert verification request
                $stmt = $conn->prepare("INSERT INTO admin_verification (email, verification_code, full_name, phone_number, reason, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $email, $verification_code, $full_name, $phone_number, $reason, $expires_at);
                
                if ($stmt->execute()) {
                    // Send verification email (implement your email function)
                    if (sendAdminVerificationEmail($email, $full_name, $verification_code)) {
                        $success = 'Admin request submitted successfully! Check your email for verification code.';
                    } else {
                        $success = 'Admin request submitted! Verification code: ' . $verification_code;
                    }
                } else {
                    $error = 'Failed to submit request. Please try again.';
                }
            }
            $user_stmt->close();
        }
        $stmt->close();
    }
}

function sendAdminVerificationEmail($email, $name, $code) {
    $subject = "Admin Verification Code - NotesShare Pro";
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
                <h2>Admin Access Request</h2>
            </div>
            <div class='content'>
                <p>Hello $name,</p>
                <p>You have requested admin access to NotesShare Pro. Use the following verification code to complete your registration:</p>
                <div class='code'>$code</div>
                <p>This code will expire in 24 hours.</p>
                <p>If you didn't request admin access, please ignore this email.</p>
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .admin-register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            margin: 0 auto;
        }
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .admin-body {
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-register-card">
            <div class="admin-header">
                <h2 class="mb-0">
                    <i class="bi bi-shield-lock"></i> Request Admin Access
                </h2>
                <p class="mb-0">Submit your request to become an admin</p>
            </div>
            
            <div class="admin-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                        <p class="mb-0 mt-2">
                            <a href="verify_request.php?email=<?php echo urlencode($email); ?>" class="btn btn-sm btn-success">
                                Complete Registration
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <small class="text-muted">Must be registered as a user first</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone_number" class="form-control" 
                               value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Why do you want to become an admin? <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required 
                                  placeholder="Explain why you should be granted admin access..."><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 py-3">
                        <i class="bi bi-send"></i> Submit Request
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="login.php" class="text-decoration-none">
                        <i class="bi bi-box-arrow-in-right"></i> Already an admin? Login here
                    </a>
                    <br>
                    <a href="../index.php" class="text-decoration-none small">
                        <i class="bi bi-arrow-left"></i> Back to Main Site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>