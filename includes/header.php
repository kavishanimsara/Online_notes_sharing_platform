<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Notes Sharing Platform'; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar">
        <div class="container nav-container">
            <div class="nav-brand">
                <img src="assets/logo.png" alt="Logo" class="logo" onerror="this.style.display='none'">
                <span class="brand-name">📚 NotesShare</span>
            </div>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const navToggle = document.getElementById('navToggle');
                const navMenu = document.getElementById('navMenu');
                
                if (navToggle && navMenu) {
                    navToggle.addEventListener('click', function() {
                        navMenu.classList.toggle('active');
                        navToggle.classList.toggle('active');
                    });
                    
                    // Close menu when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                            navMenu.classList.remove('active');
                            navToggle.classList.remove('active');
                        }
                    });
                    
                    // Close menu on escape key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            navMenu.classList.remove('active');
                            navToggle.classList.remove('active');
                        }
                    });
                }
            });
            </script>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php">🏠 Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="#" class="user-info">👋 Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
                    <li><a href="upload_with_category.php">📤 Upload Note</a></li>
                    <li><a href="dashboard.php">📊 Dashboard</a></li>
                    <li><a href="personal_details.php">👤 My Account</a></li>
                    <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin')): ?>
                    <li><a href="admin/dashboard.php" class="btn-admin">⚙️ Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="btn-logout">🚪 Logout</a></li>
               
                <?php else: ?>
                    <li><a href="login.php">🔑 Login</a></li>
                    <li><a href="register.php" class="btn-register">✨ Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <main class="main-content">