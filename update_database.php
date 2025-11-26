<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Database Update</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        if (isset($_POST['update'])) {
                            require_once 'config/db.php';
                            
                            echo "<div class='alert alert-info'><h5>Updating database...</h5>";
                            
                            // Check if views column exists
                            $check_views = $conn->query("SHOW COLUMNS FROM notes LIKE 'views'");
                            if ($check_views->num_rows == 0) {
                                $add_views = "ALTER TABLE notes ADD COLUMN views INT DEFAULT 0 NOT NULL COMMENT 'Total views count'";
                                if ($conn->query($add_views)) {
                                    echo "<p>✓ views column added to notes table</p>";
                                } else {
                                    echo "<p>✗ Error adding views column: " . $conn->error . "</p>";
                                }
                            } else {
                                echo "<p>✓ views column already exists in notes table</p>";
                            }
                            
                            // Check if likes_count column exists
                            $check_likes_count = $conn->query("SHOW COLUMNS FROM notes LIKE 'likes_count'");
                            if ($check_likes_count->num_rows == 0) {
                                $add_likes_count = "ALTER TABLE notes ADD COLUMN likes_count INT DEFAULT 0 NOT NULL COMMENT 'Total likes count'";
                                if ($conn->query($add_likes_count)) {
                                    echo "<p>✓ likes_count column added to notes table</p>";
                                } else {
                                    echo "<p>✗ Error adding likes_count column: " . $conn->error . "</p>";
                                }
                            } else {
                                echo "<p>✓ likes_count column already exists in notes table</p>";
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
                                echo "<p>✗ Error creating likes table: " . $conn->error . "</p>";
                            } else {
                                echo "<p>✓ Likes table exists or created successfully</p>";
                            }
                            
                            echo "<p class='mt-3'><strong>Database update completed!</strong></p>";
                            echo "<a href='dashboard.php' class='btn btn-primary'>Go to Dashboard</a></div>";
                        } else {
                        ?>
                        <p>This script will add the missing database columns needed for the dashboard statistics:</p>
                        <ul>
                            <li><code>views</code> column to notes table</li>
                            <li><code>likes_count</code> column to notes table</li>
                            <li><code>note_likes</code> table for tracking likes</li>
                        </ul>
                        
                        <form method="post">
                            <button type="submit" name="update" class="btn btn-warning">Update Database</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>