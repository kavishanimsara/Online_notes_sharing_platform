<?php
$conn = new mysqli("localhost", "root", "", "notes_sharing");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✅ Database connected successfully!";
?>
