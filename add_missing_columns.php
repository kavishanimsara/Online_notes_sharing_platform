<?php
require_once 'config/db.php';

echo "Adding views column to notes table...\n";

// Check if views column already exists
$check_views = $conn->query("SHOW COLUMNS FROM notes LIKE 'views'");
if ($check_views->num_rows == 0) {
    // Add views column
    $add_views = "ALTER TABLE notes ADD COLUMN views INT DEFAULT 0 NOT NULL COMMENT 'Total views count'";
    if ($conn->query($add_views)) {
        echo "✓ views column added to notes table\n";
    } else {
        echo "✗ Error adding views column: " . $conn->error . "\n";
    }
} else {
    echo "✓ views column already exists in notes table\n";
}

// Check if likes_count column exists
$check_likes_count = $conn->query("SHOW COLUMNS FROM notes LIKE 'likes_count'");
if ($check_likes_count->num_rows == 0) {
    // Add likes_count column
    $add_likes_count = "ALTER TABLE notes ADD COLUMN likes_count INT DEFAULT 0 NOT NULL COMMENT 'Total likes count'";
    if ($conn->query($add_likes_count)) {
        echo "✓ likes_count column added to notes table\n";
    } else {
        echo "✗ Error adding likes_count column: " . $conn->error . "\n";
    }
} else {
    echo "✓ likes_count column already exists in notes table\n";
}

// Create note_likes table if it doesn't exist
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
    echo "✓ Likes table exists or created successfully\n";
}

echo "\nDatabase update completed!\n";
?>