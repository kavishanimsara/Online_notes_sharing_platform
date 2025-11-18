<?php
require_once 'config/db.php';

header('Content-Type: application/json');

$category_id = intval($_GET['category_id'] ?? 0);

if ($category_id > 0) {
    // Check if status column exists
    $check_status = $conn->query("SHOW COLUMNS FROM categories LIKE 'status'");
    $has_status = $check_status->num_rows > 0;
    
    if ($has_status) {
        $stmt = $conn->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND status = 'approved' ORDER BY name");
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM categories WHERE parent_id = ? ORDER BY name");
    }
    
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subcategories = [];
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = $row;
    }
    
    echo json_encode($subcategories);
} else {
    echo json_encode([]);
}
?>