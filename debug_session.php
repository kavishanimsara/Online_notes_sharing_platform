<?php
session_start();
require_once 'config/db.php';

echo "=== Session Debug Info ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data: " . print_r($_SESSION, true) . "\n";

echo "\n=== Database Connection ===\n";
echo "DB Connection: " . ($conn && !$conn->connect_error ? "Connected" : "Not connected") . "\n";

echo "\n=== User Sessions Table ===\n";
$check_table = $conn->query("SHOW TABLES LIKE 'user_sessions'");
if ($check_table && $check_table->num_rows > 0) {
    echo "Table exists\n";
    
    // Show table structure
    $result = $conn->query("DESCRIBE user_sessions");
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    // Show current sessions
    echo "\nCurrent sessions in database:\n";
    $result = $conn->query("SELECT * FROM user_sessions");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "  User ID: " . $row['user_id'] . ", Session ID: " . $row['session_id'] . ", IP: " . $row['ip_address'] . "\n";
        }
    } else {
        echo "  No sessions found\n";
    }
} else {
    echo "Table does not exist\n";
}

echo "\n=== Session Validation Test ===\n";
if (isset($_SESSION['user_id'])) {
    require_once 'includes/session_manager.php';
    $isValid = isCurrentSessionValid();
    echo "Current session valid: " . ($isValid ? "YES" : "NO") . "\n";
} else {
    echo "No user_id in session\n";
}
?>