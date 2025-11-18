<?php
require_once 'config/db.php';

echo "Adding is_active field to users table...\n";

// Check if is_active column already exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
if ($check_column->num_rows == 0) {
    // Add is_active column
    $alter_sql = "ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 NOT NULL COMMENT '1=active, 0=banned'";
    if ($conn->query($alter_sql)) {
        echo "✓ is_active column added successfully\n";
    } else {
        echo "✗ Error adding is_active column: " . $conn->error . "\n";
    }
} else {
    echo "✓ is_active column already exists\n";
}

// Check if role column exists
$check_role = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($check_role->num_rows == 0) {
    // Add role column
    $alter_role_sql = "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin', 'super_admin') DEFAULT 'user' NOT NULL";
    if ($conn->query($alter_role_sql)) {
        echo "✓ role column added successfully\n";
    } else {
        echo "✗ Error adding role column: " . $conn->error . "\n";
    }
} else {
    echo "✓ role column already exists\n";
}

// Check if banned_at column exists
$check_banned_at = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_at'");
if ($check_banned_at->num_rows == 0) {
    // Add banned_at column
    $alter_banned_sql = "ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When user was banned'";
    if ($conn->query($alter_banned_sql)) {
        echo "✓ banned_at column added successfully\n";
    } else {
        echo "✗ Error adding banned_at column: " . $conn->error . "\n";
    }
} else {
    echo "✓ banned_at column already exists\n";
}

// Check if banned_by column exists
$check_banned_by = $conn->query("SHOW COLUMNS FROM users LIKE 'banned_by'");
if ($check_banned_by->num_rows == 0) {
    // Add banned_by column
    $alter_banned_by_sql = "ALTER TABLE users ADD COLUMN banned_by INT NULL DEFAULT NULL COMMENT 'Admin ID who banned the user'";
    if ($conn->query($alter_banned_by_sql)) {
        echo "✓ banned_by column added successfully\n";
    } else {
        echo "✗ Error adding banned_by column: " . $conn->error . "\n";
    }
} else {
    echo "✓ banned_by column already exists\n";
}

// Check if ban_reason column exists
$check_ban_reason = $conn->query("SHOW COLUMNS FROM users LIKE 'ban_reason'");
if ($check_ban_reason->num_rows == 0) {
    // Add ban_reason column
    $alter_ban_reason_sql = "ALTER TABLE users ADD COLUMN ban_reason TEXT NULL DEFAULT NULL COMMENT 'Reason for banning'";
    if ($conn->query($alter_ban_reason_sql)) {
        echo "✓ ban_reason column added successfully\n";
    } else {
        echo "✗ Error adding ban_reason column: " . $conn->error . "\n";
    }
} else {
    echo "✓ ban_reason column already exists\n";
}

// Check if notes table needs status column
echo "\nChecking notes table...\n";
$check_notes_status = $conn->query("SHOW COLUMNS FROM notes LIKE 'status'");
if ($check_notes_status->num_rows == 0) {
    $notes_status_sql = "ALTER TABLE notes ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' NOT NULL COMMENT 'Note approval status'";
    if ($conn->query($notes_status_sql)) {
        echo "✓ status column added to notes table\n";
    } else {
        echo "✗ Error adding status column to notes: " . $conn->error . "\n";
    }
} else {
    echo "✓ status column already exists in notes table\n";
}

// Check if notes table needs approval columns
$approval_columns = ['approved_by', 'approved_at', 'rejection_reason'];
foreach ($approval_columns as $column) {
    $check_column = $conn->query("SHOW COLUMNS FROM notes LIKE '$column'");
    if ($check_column->num_rows == 0) {
        if ($column === 'approved_by') {
            $sql = "ALTER TABLE notes ADD COLUMN approved_by INT NULL DEFAULT NULL COMMENT 'Admin who approved/rejected'";
        } elseif ($column === 'approved_at') {
            $sql = "ALTER TABLE notes ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When note was approved/rejected'";
        } elseif ($column === 'rejection_reason') {
            $sql = "ALTER TABLE notes ADD COLUMN rejection_reason TEXT NULL DEFAULT NULL COMMENT 'Reason for rejection'";
        }
        
        if ($conn->query($sql)) {
            echo "✓ $column column added to notes table\n";
        } else {
            echo "✗ Error adding $column column to notes: " . $conn->error . "\n";
        }
    } else {
        echo "✓ $column column already exists in notes table\n";
    }
}

// Update existing notes to have 'approved' status if they don't have status set
$update_existing = $conn->query("UPDATE notes SET status = 'approved' WHERE status IS NULL OR status = ''");
if ($update_existing) {
    echo "✓ Existing notes set to approved status\n";
}

// Check if categories table needs subcategory approval columns
echo "\nChecking categories table...\n";
$category_columns = ['status', 'created_by', 'approved_by', 'approved_at', 'rejection_reason'];
foreach ($category_columns as $column) {
    $check_column = $conn->query("SHOW COLUMNS FROM categories LIKE '$column'");
    if ($check_column->num_rows == 0) {
        if ($column === 'status') {
            $sql = "ALTER TABLE categories ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' NOT NULL COMMENT 'Category approval status'";
        } elseif ($column === 'created_by') {
            $sql = "ALTER TABLE categories ADD COLUMN created_by INT NULL DEFAULT NULL COMMENT 'User who created category'";
        } elseif ($column === 'approved_by') {
            $sql = "ALTER TABLE categories ADD COLUMN approved_by INT NULL DEFAULT NULL COMMENT 'Admin who approved/rejected'";
        } elseif ($column === 'approved_at') {
            $sql = "ALTER TABLE categories ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When category was approved/rejected'";
        } elseif ($column === 'rejection_reason') {
            $sql = "ALTER TABLE categories ADD COLUMN rejection_reason TEXT NULL DEFAULT NULL COMMENT 'Reason for rejection'";
        }
        
        if ($conn->query($sql)) {
            echo "✓ $column column added to categories table\n";
        } else {
            echo "✗ Error adding $column column to categories: " . $conn->error . "\n";
        }
    } else {
        echo "✓ $column column already exists in categories table\n";
    }
}

// Check if users table needs additional profile fields
echo "\nChecking users table for profile fields...\n";
$profile_columns = ['full_name', 'phone', 'bio', 'profile_picture'];
foreach ($profile_columns as $column) {
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($check_column->num_rows == 0) {
        if ($column === 'full_name') {
            $sql = "ALTER TABLE users ADD COLUMN full_name VARCHAR(255) NULL DEFAULT NULL COMMENT 'User full name'";
        } elseif ($column === 'phone') {
            $sql = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL DEFAULT NULL COMMENT 'User phone number'";
        } elseif ($column === 'bio') {
            $sql = "ALTER TABLE users ADD COLUMN bio TEXT NULL DEFAULT NULL COMMENT 'User biography'";
        } elseif ($column === 'profile_picture') {
            $sql = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL DEFAULT NULL COMMENT 'Profile picture path'";
        }
        
        if ($conn->query($sql)) {
            echo "✓ $column column added to users table\n";
        } else {
            echo "✗ Error adding $column column to users: " . $conn->error . "\n";
        }
    } else {
        echo "✓ $column column already exists in users table\n";
    }
}

// Create likes table
echo "\nCreating likes table...\n";
$create_likes_table = "
CREATE TABLE IF NOT EXISTS note_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (note_id, user_id),
    INDEX idx_note_id (note_id),
    INDEX idx_user_id (user_id)
)";

if (!$conn->query($create_likes_table)) {
    echo "✗ Error creating likes table: " . $conn->error . "\n";
} else {
    echo "✓ Likes table created successfully\n";
}

// Add likes_count column to notes table
echo "\nAdding likes_count to notes table...\n";
$check_likes_count = $conn->query("SHOW COLUMNS FROM notes LIKE 'likes_count'");
if ($check_likes_count->num_rows == 0) {
    $add_likes_count = "ALTER TABLE notes ADD COLUMN likes_count INT DEFAULT 0 NOT NULL COMMENT 'Total likes count'";
    if ($conn->query($add_likes_count)) {
        echo "✓ likes_count column added to notes table\n";
        
        // Update existing notes with current like counts
        $conn->query("UPDATE notes n SET likes_count = (SELECT COUNT(*) FROM note_likes nl WHERE nl.note_id = n.id)");
        echo "✓ Existing notes updated with like counts\n";
    } else {
        echo "✗ Error adding likes_count column: " . $conn->error . "\n";
    }
} else {
    echo "✓ likes_count column already exists in notes table\n";
}

echo "\nDatabase update completed!\n";
?>