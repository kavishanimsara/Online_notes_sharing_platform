<?php
require_once 'config/db.php';

echo "<h2>Categories Table Structure</h2>";
$result = $conn->query("DESCRIBE categories");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td><td>{$row['Extra']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Triggers on categories table</h2>";
$result = $conn->query("SHOW TRIGGERS LIKE 'categories'");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr><th>Trigger</th><th>Event</th><th>Table</th><th>Statement</th><th>Timing</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Trigger']}</td><td>{$row['Event']}</td><td>{$row['Table']}</td><td>{$row['Statement']}</td><td>{$row['Timing']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "No triggers found on categories table";
}

$conn->close();
?>