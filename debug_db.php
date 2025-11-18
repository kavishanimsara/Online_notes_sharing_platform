<?php
require_once 'config/db.php';

echo "Checking notes table structure:\n";
$result = $conn->query("DESCRIBE notes");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nChecking existing notes:\n";
$result = $conn->query("SELECT id, title, category_id, status FROM notes LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Title: " . $row['title'] . ", Category: " . $row['category_id'] . ", Status: " . ($row['status'] ?? 'NULL') . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nChecking categories:\n";
$result = $conn->query("SELECT id, name FROM categories LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>