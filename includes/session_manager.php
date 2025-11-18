<?php
require_once 'config/db.php';

// Create sessions table if it doesn't exist
$create_sessions_table = "
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
    UNIQUE KEY unique_session (session_id)
)";

$result = $conn->query($create_sessions_table);
if (!$result) {
    echo "Error creating sessions table: " . $conn->error . "\n";
} else {
    echo "✓ Sessions table created successfully\n";
}

// Verify table was actually created
$check_table = $conn->query("SHOW TABLES LIKE 'user_sessions'");
if ($check_table->num_rows == 0) {
    echo "⚠️ Warning: Sessions table may not have been created properly\n";
}

// Function to track user session
function trackUserSession($user_id) {
    global $conn;
    
    // Check if connection exists
    if (!$conn || $conn->connect_error) {
        return; // No database connection
    }
    
    // Check if user_sessions table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'user_sessions'");
    if (!$check_table || $check_table->num_rows == 0) {
        return; // Table doesn't exist, skip session tracking
    }
    
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    try {
        // Clean up old sessions for this user
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Insert new session
        $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $session_id, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        // Silently fail - session tracking is not critical
        error_log("Session tracking error: " . $e->getMessage());
    }
}

// Function to destroy all user sessions
function destroyUserSessions($user_id) {
    global $conn;
    
    // Check if connection and table exist
    if (!$conn || $conn->connect_error) {
        return 0;
    }
    
    $check_table = $conn->query("SHOW TABLES LIKE 'user_sessions'");
    if (!$check_table || $check_table->num_rows == 0) {
        return 0; // Table doesn't exist
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected;
        }
    } catch (Exception $e) {
        error_log("Destroy sessions error: " . $e->getMessage());
        return 0;
    }
    
    return 0;
}

// Function to check if current session is valid
function isCurrentSessionValid() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return true; // No user logged in
    }
    
    // Check if connection and table exist
    if (!$conn || $conn->connect_error) {
        return true; // Assume valid if no DB connection
    }
    
    $check_table = $conn->query("SHOW TABLES LIKE 'user_sessions'");
    if (!$check_table || $check_table->num_rows == 0) {
        return true; // Table doesn't exist, assume valid
    }
    
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_sessions WHERE user_id = ? AND session_id = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $session_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] > 0;
        }
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        return true; // Assume valid on error
    }
    
    return true;
}

echo "Session management system initialized!\n";
?>