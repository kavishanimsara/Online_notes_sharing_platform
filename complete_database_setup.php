<?php
require_once 'config/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Complete Database Setup</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .log-output { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 5px; 
            padding: 15px; 
            max-height: 500px; 
            overflow-y: auto; 
            font-family: 'Courier New', monospace; 
            font-size: 14px;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-10'>
                <div class='card'>
                    <div class='card-header bg-primary text-white'>
                        <h3>Complete Database Setup for Notes Sharing Platform</h3>
                    </div>
                    <div class='card-body'>
                        <div class='log-output'>";

if (isset($_POST['setup'])) {
    
    // Function to log output
    function logMessage($message, $type = 'info') {
        $class = $type;
        $icon = '';
        switch($type) {
            case 'success': $icon = '✓'; break;
            case 'error': $icon = '✗'; break;
            case 'warning': $icon = '⚠'; break;
            case 'info': $icon = 'ℹ'; break;
        }
        echo "<div class='$class'>$icon $message</div>";
        flush();
        ob_flush();
    }
    
    logMessage("Starting complete database setup...", "info");
    
    // 1. Update users table with missing columns
    logMessage("Updating users table...", "info");
    
    $user_columns = [
        'full_name' => "ALTER TABLE users ADD COLUMN full_name VARCHAR(255) NULL DEFAULT NULL COMMENT 'User full name'",
        'age' => "ALTER TABLE users ADD COLUMN age INT NULL DEFAULT NULL COMMENT 'User age'",
        'gender' => "ALTER TABLE users ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT 'prefer_not_to_say'",
        'institution_type' => "ALTER TABLE users ADD COLUMN institution_type ENUM('school', 'college', 'university', 'other') NULL DEFAULT NULL",
        'institution_name' => "ALTER TABLE users ADD COLUMN institution_name VARCHAR(255) NULL DEFAULT NULL COMMENT 'Institution name'",
        'grade_level' => "ALTER TABLE users ADD COLUMN grade_level VARCHAR(100) NULL DEFAULT NULL COMMENT 'Grade/Year level'",
        'bio' => "ALTER TABLE users ADD COLUMN bio TEXT NULL DEFAULT NULL COMMENT 'User biography'",
        'profile_picture' => "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL COMMENT 'Profile picture path'",
        'is_active' => "ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 NOT NULL COMMENT '1=active, 0=banned'",
        'role' => "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'super_admin') DEFAULT 'user' NOT NULL",
        'banned_at' => "ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When user was banned'",
        'banned_by' => "ALTER TABLE users ADD COLUMN banned_by INT NULL DEFAULT NULL COMMENT 'Admin ID who banned the user'",
        'ban_reason' => "ALTER TABLE users ADD COLUMN ban_reason TEXT NULL DEFAULT NULL COMMENT 'Reason for banning'",
        'email_verified' => "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 NOT NULL COMMENT 'Email verification status'",
        'email_verification_token' => "ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(255) NULL DEFAULT NULL COMMENT 'Email verification token'",
        'last_login' => "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL COMMENT 'Last login time'"
    ];
    
    foreach ($user_columns as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
        if ($check->num_rows == 0) {
            if ($conn->query($sql)) {
                logMessage("Added $column column to users table", "success");
            } else {
                logMessage("Error adding $column: " . $conn->error, "error");
            }
        } else {
            logMessage("$column column already exists in users table", "info");
        }
    }
    
    // 2. Update notes table with missing columns
    logMessage("Updating notes table...", "info");
    
    $notes_columns = [
        'category_id' => "ALTER TABLE notes ADD COLUMN category_id INT NULL DEFAULT NULL COMMENT 'Category ID'",
        'subcategory_id' => "ALTER TABLE notes ADD COLUMN subcategory_id INT NULL DEFAULT NULL COMMENT 'Subcategory ID'",
        'tags' => "ALTER TABLE notes ADD COLUMN tags VARCHAR(255) NULL DEFAULT NULL COMMENT 'Comma-separated tags'",
        'status' => "ALTER TABLE notes ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' NOT NULL COMMENT 'Note approval status'",
        'views' => "ALTER TABLE notes ADD COLUMN views INT DEFAULT 0 NOT NULL COMMENT 'Total views count'",
        'likes_count' => "ALTER TABLE notes ADD COLUMN likes_count INT DEFAULT 0 NOT NULL COMMENT 'Total likes count'",
        'approved_by' => "ALTER TABLE notes ADD COLUMN approved_by INT NULL DEFAULT NULL COMMENT 'Admin who approved/rejected'",
        'approved_at' => "ALTER TABLE notes ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When note was approved/rejected'",
        'rejection_reason' => "ALTER TABLE notes ADD COLUMN rejection_reason TEXT NULL DEFAULT NULL COMMENT 'Reason for rejection'",
        'updated_at' => "ALTER TABLE notes ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update time'"
    ];
    
    foreach ($notes_columns as $column => $sql) {
        $check = $conn->query("SHOW COLUMNS FROM notes LIKE '$column'");
        if ($check->num_rows == 0) {
            if ($conn->query($sql)) {
                logMessage("Added $column column to notes table", "success");
            } else {
                logMessage("Error adding $column: " . $conn->error, "error");
            }
        } else {
            logMessage("$column column already exists in notes table", "info");
        }
    }
    
    // 3. Create categories table
    logMessage("Creating categories table...", "info");
    $create_categories = "
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        icon VARCHAR(100) NULL,
        parent_id INT NULL DEFAULT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' NOT NULL,
        created_by INT NULL DEFAULT NULL,
        approved_by INT NULL DEFAULT NULL,
        approved_at TIMESTAMP NULL DEFAULT NULL,
        rejection_reason TEXT NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_parent_id (parent_id),
        INDEX idx_status (status),
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($create_categories)) {
        logMessage("Categories table created successfully", "success");
    } else {
        logMessage("Error creating categories table: " . $conn->error, "error");
    }
    
    // 4. Create note_likes table
    logMessage("Creating note_likes table...", "info");
    $create_note_likes = "
    CREATE TABLE IF NOT EXISTS note_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        note_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (note_id, user_id),
        INDEX idx_note_id (note_id),
        INDEX idx_user_id (user_id),
        FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_note_likes)) {
        logMessage("Note likes table created successfully", "success");
    } else {
        logMessage("Error creating note likes table: " . $conn->error, "error");
    }
    
    // 5. Create favorites table
    logMessage("Creating favorites table...", "info");
    $create_favorites = "
    CREATE TABLE IF NOT EXISTS favorites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        note_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_favorite (user_id, note_id),
        INDEX idx_user_id (user_id),
        INDEX idx_note_id (note_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_favorites)) {
        logMessage("Favorites table created successfully", "success");
    } else {
        logMessage("Error creating favorites table: " . $conn->error, "error");
    }
    
    // 6. Create comments table
    logMessage("Creating comments table...", "info");
    $create_comments = "
    CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        note_id INT NOT NULL,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        parent_id INT NULL DEFAULT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_note_id (note_id),
        INDEX idx_user_id (user_id),
        INDEX idx_parent_id (parent_id),
        INDEX idx_status (status),
        FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_comments)) {
        logMessage("Comments table created successfully", "success");
    } else {
        logMessage("Error creating comments table: " . $conn->error, "error");
    }
    
    // 7. Create admin tables
    logMessage("Creating admin tables...", "info");
    
    $create_admins = "
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        admin_level ENUM('admin', 'super_admin') DEFAULT 'admin' NOT NULL,
        permissions JSON NULL,
        is_active TINYINT(1) DEFAULT 1 NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_admins)) {
        logMessage("Admins table created successfully", "success");
    } else {
        logMessage("Error creating admins table: " . $conn->error, "error");
    }
    
    $create_admin_verification = "
    CREATE TABLE IF NOT EXISTS admin_verification (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        requested_level ENUM('admin', 'super_admin') NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' NOT NULL,
        processed_by INT NULL DEFAULT NULL,
        processed_at TIMESTAMP NULL DEFAULT NULL,
        rejection_reason TEXT NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($create_admin_verification)) {
        logMessage("Admin verification table created successfully", "success");
    } else {
        logMessage("Error creating admin verification table: " . $conn->error, "error");
    }
    
    $create_admin_activity_log = "
    CREATE TABLE IF NOT EXISTS admin_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        target_type VARCHAR(50) NULL,
        target_id INT NULL,
        details TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_id (admin_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_admin_activity_log)) {
        logMessage("Admin activity log table created successfully", "success");
    } else {
        logMessage("Error creating admin activity log table: " . $conn->error, "error");
    }
    
    // 8. Create user_sessions table
    logMessage("Creating user_sessions table...", "info");
    $create_user_sessions = "
    CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        UNIQUE KEY unique_session (session_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_user_sessions)) {
        logMessage("User sessions table created successfully", "success");
    } else {
        logMessage("Error creating user sessions table: " . $conn->error, "error");
    }
    
    // 9. Update existing data
    logMessage("Updating existing data...", "info");
    
    // Update existing notes to have approved status
    $conn->query("UPDATE notes SET status = 'approved' WHERE status IS NULL OR status = ''");
    logMessage("Updated existing notes status", "success");
    
    // Update likes_count from note_likes table
    $conn->query("UPDATE notes n SET likes_count = (SELECT COUNT(*) FROM note_likes nl WHERE nl.note_id = n.id)");
    logMessage("Updated likes_count for existing notes", "success");
    
    // Set default views for existing notes
    $conn->query("UPDATE notes SET views = 0 WHERE views IS NULL");
    logMessage("Set default views for existing notes", "success");
    
    logMessage("Database setup completed successfully!", "success");
    logMessage("You can now use all features of the Notes Sharing Platform.", "info");
    
} else {
    echo "<div class='alert alert-info'>
        <h5>Database Setup Required</h5>
        <p>This script will create all necessary tables and columns for the Notes Sharing Platform.</p>
        <p><strong>This will:</strong></p>
        <ul>
            <li>Add missing columns to users table</li>
            <li>Add missing columns to notes table</li>
            <li>Create categories, likes, favorites, and comments tables</li>
            <li>Create admin management tables</li>
            <li>Create session management table</li>
            <li>Update existing data</li>
        </ul>
        <p class='mb-0'><strong>⚠ Warning:</strong> This will modify your database structure. Make sure you have a backup.</p>
    </div>";
}

echo "        </div>
        <div class='card-footer text-center'>
            <form method='post'>
                <button type='submit' name='setup' class='btn btn-primary btn-lg'>
                    <i class='bi bi-database-fill-gear'></i> Run Complete Database Setup
                </button>
            </form>
            <a href='index.php' class='btn btn-secondary mt-2'>
                <i class='bi bi-house'></i> Back to Home
            </a>
        </div>
    </div>
</div>";

echo "</div>
</body>
</html>";
?>