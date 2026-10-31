<?php
require_once '../config/db.php';
session_start();

$pageTitle = 'Admin Login - NotesShare Pro';
$error = '';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Include auth functions AFTER session_start()
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        // Check if user exists and is an admin
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.password, u.full_name, u.email, 
                   a.admin_level, a.is_active as admin_active, a.permissions
            FROM users u 
            INNER JOIN admins a ON u.id = a.user_id 
            WHERE u.username = ? AND a.is_active = 1
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Set admin session
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_level'] = $user['admin_level'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_permissions'] = json_decode($user['permissions'], true) ?? [];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Log login activity - NOW this function is available
                logAdminActivity($conn, 'admin_login', 'admin', $user['id'], 'Admin logged in successfully');
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Admin account not found or inactive';
        }
        $stmt->close();
    }
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
        .admin-login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 400px;
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
        <div class="admin-login-card">
            <div class="admin-header">
                <h2 class="mb-0">
                    <i class="bi bi-shield-lock"></i> Admin Login
                </h2>
                <p class="mb-0">NotesShare Pro Control Panel</p>
            </div>
            
            <div class="admin-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 py-3">
                        <i class="bi bi-box-arrow-in-right"></i> Login to Admin Panel
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="../index.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Back to Main Site
                    </a>
                    <br>
                    <a href="register.php" class="text-decoration-none small">
                        <i class="bi bi-person-plus"></i> Request Admin Access
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>