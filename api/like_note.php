<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Only allow logged-in users
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to like notes']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_id'])) {
    $note_id = intval($_POST['note_id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if note exists
    $note_check = $conn->prepare("SELECT id FROM notes WHERE id = ?");
    $note_check->bind_param("i", $note_id);
    $note_check->execute();
    if ($note_check->get_result()->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Note not found']);
        exit();
    }
    
    // Check if user already liked this note
    $like_check = $conn->prepare("SELECT id FROM note_likes WHERE note_id = ? AND user_id = ?");
    $like_check->bind_param("ii", $note_id, $user_id);
    $like_check->execute();
    $already_liked = $like_check->get_result()->num_rows > 0;
    
    if ($already_liked) {
        // Unlike the note
        $delete_like = $conn->prepare("DELETE FROM note_likes WHERE note_id = ? AND user_id = ?");
        $delete_like->bind_param("ii", $note_id, $user_id);
        $delete_like->execute();
        
        $action = 'unliked';
        $message = 'Note unliked';
    } else {
        // Like the note
        $insert_like = $conn->prepare("INSERT INTO note_likes (note_id, user_id) VALUES (?, ?)");
        $insert_like->bind_param("ii", $note_id, $user_id);
        $insert_like->execute();
        
        $action = 'liked';
        $message = 'Note liked';
    }
    
    // Get updated like count
    $count_query = $conn->prepare("SELECT COUNT(*) as count FROM note_likes WHERE note_id = ?");
    $count_query->bind_param("i", $note_id);
    $count_query->execute();
    $like_count = $count_query->get_result()->fetch_assoc()['count'];
    
    // Update likes_count in notes table if column exists
    $check_column = $conn->query("SHOW COLUMNS FROM notes LIKE 'likes_count'");
    if ($check_column->num_rows > 0) {
        $update_count = $conn->prepare("UPDATE notes SET likes_count = ? WHERE id = ?");
        $update_count->bind_param("ii", $like_count, $note_id);
        $update_count->execute();
    }
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'message' => $message,
        'like_count' => $like_count
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>