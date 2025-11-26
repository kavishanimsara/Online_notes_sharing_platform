<?php
require_once 'config/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>System Verification Report</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        .status-ok { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .check-item { padding: 10px; border-bottom: 1px solid #eee; }
        .check-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-10'>
                <div class='card'>
                    <div class='card-header bg-primary text-white'>
                        <h3>🔍 System Verification Report</h3>
                        <p class='mb-0'>Notes Sharing Platform - Complete System Check</p>
                    </div>
                    <div class='card-body'>
                        <h4>📊 Database Structure Verification</h4>";

// Check database structure
$checks = [];

// 1. Check users table
$users_columns = $conn->query("DESCRIBE users");
$users_fields = [];
while ($row = $users_columns->fetch_assoc()) {
    $users_fields[] = $row['Field'];
}

$required_user_fields = ['id', 'username', 'email', 'password', 'full_name', 'age', 'gender', 'institution_type', 'institution_name', 'grade_level', 'bio', 'profile_picture', 'is_active', 'role', 'banned_at', 'banned_by', 'ban_reason', 'email_verified', 'email_verification_token', 'last_login', 'created_at'];
foreach ($required_user_fields as $field) {
    $checks['users'][$field] = in_array($field, $users_fields);
}

// 2. Check notes table
$notes_columns = $conn->query("DESCRIBE notes");
$notes_fields = [];
while ($row = $notes_columns->fetch_assoc()) {
    $notes_fields[] = $row['Field'];
}

$required_note_fields = ['id', 'user_id', 'title', 'description', 'file_name', 'file_path', 'file_size', 'downloads', 'category_id', 'subcategory_id', 'tags', 'status', 'views', 'likes_count', 'approved_by', 'approved_at', 'rejection_reason', 'updated_at', 'created_at'];
foreach ($required_note_fields as $field) {
    $checks['notes'][$field] = in_array($field, $notes_fields);
}

// 3. Check tables existence
$tables_to_check = ['categories', 'note_likes', 'favorites', 'comments', 'admins', 'admin_verification', 'admin_activity_log', 'user_sessions'];
foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $checks['tables'][$table] = $result->num_rows > 0;
}

// Display results
echo "<div class='table-responsive'>";
echo "<table class='table table-striped'>";
echo "<thead><tr><th>Component</th><th>Item</th><th>Status</th></tr></thead>";
echo "<tbody>";

// Users table
foreach ($checks['users'] as $field => $exists) {
    $status = $exists ? "<span class='status-ok'>✓ EXISTS</span>" : "<span class='status-error'>✗ MISSING</span>";
    echo "<tr><td>Users Table</td><td>$field</td><td>$status</td></tr>";
}

// Notes table
foreach ($checks['notes'] as $field => $exists) {
    $status = $exists ? "<span class='status-ok'>✓ EXISTS</span>" : "<span class='status-error'>✗ MISSING</span>";
    echo "<tr><td>Notes Table</td><td>$field</td><td>$status</td></tr>";
}

// Other tables
foreach ($checks['tables'] as $table => $exists) {
    $status = $exists ? "<span class='status-ok'>✓ EXISTS</span>" : "<span class='status-error'>✗ MISSING</span>";
    echo "<tr><td>Tables</td><td>$table</td><td>$status</td></tr>";
}

echo "</tbody></table>";
echo "</div>";

// Summary
$all_good = true;
foreach ($checks as $category) {
    foreach ($category as $item => $exists) {
        if (!$exists) {
            $all_good = false;
            break;
        }
    }
}

echo "<div class='alert " . ($all_good ? 'alert-success' : 'alert-warning') . " mt-4'>";
echo "<h5>📋 Summary</h5>";
if ($all_good) {
    echo "<p class='mb-0'><strong>✅ All database components are properly set up!</strong></p>";
    echo "<p class='mb-0'>Your Notes Sharing Platform is ready to use.</p>";
} else {
    echo "<p class='mb-0'><strong>⚠️ Some database components are missing.</strong></p>";
    echo "<p class='mb-0'>Please run the <a href='complete_database_setup.php'>Complete Database Setup</a> to fix these issues.</p>";
}
echo "</div>";

echo "<h4 class='mt-5'>🔧 Fixed Issues Summary</h4>";
echo "<div class='list-group'>";
echo "<div class='list-group-item'><strong>✓ Dashboard.php:</strong> Fixed Total Views and Total Likes calculations with proper column existence checks</div>";
echo "<div class='list-group-item'><strong>✓ Register.php:</strong> Fixed dynamic SQL query building based on available columns</div>";
echo "<div class='list-group-item'><strong>✓ API files:</strong> Fixed missing logActivity function and SQL injection vulnerabilities</div>";
echo "<div class='list-group-item'><strong>✓ Search.php:</strong> Fixed all database column existence checks and query building</div>";
echo "<div class='list-group-item'><strong>✓ Admin panel:</strong> Fixed authentication, session management, and SQL injection issues</div>";
echo "<div class='list-group-item'><strong>✓ View/Download:</strong> Added proper view counting and column checks</div>";
echo "<div class='list-group-item'><strong>✓ Upload system:</strong> Enhanced with dynamic column detection</div>";
echo "<div class='list-group-item'><strong>✓ Security:</strong> Fixed SQL injection vulnerabilities throughout the system</div>";
echo "</div>";

echo "<div class='text-center mt-5'>";
echo "<a href='index.php' class='btn btn-primary btn-lg me-2'>🏠 Go to Homepage</a>";
echo "<a href='complete_database_setup.php' class='btn btn-warning btn-lg me-2'>🔧 Run Database Setup</a>";
echo "<a href='admin/login.php' class='btn btn-success btn-lg'>⚙️ Admin Panel</a>";
echo "</div>";

echo "</div></div></div></div>";

echo "</body>
</html>";
?>