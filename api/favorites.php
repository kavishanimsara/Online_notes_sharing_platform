<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

$user_id = getUserId();
$action = $_POST['action'] ?? '';
$note_id = intval($_POST['note_id'] ?? 0);

if (empty($action) || $note_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Verify note exists
$stmt = $conn->prepare("SELECT id FROM notes WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $note_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Note not found']);
    exit();
}
$stmt->close();

if ($action === 'toggle') {
    // Check if already favorited
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND note_id = ?");
    $stmt->bind_param("ii", $user_id, $note_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        // Remove favorite
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND note_id = ?");
        $stmt->bind_param("ii", $user_id, $note_id);
        $stmt->execute();
        $stmt->close();

        // Decrement likes count
        $conn->query("UPDATE notes SET likes_count = likes_count - 1 WHERE id = $note_id");

        logActivity($user_id, 'unfavorite_note', "Note ID: $note_id");

        $message = 'Removed from favorites';
        $is_favorited = false;
    } else {
        // Add favorite
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, note_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $note_id);
        $stmt->execute();
        $stmt->close();

        // Increment likes count
        $conn->query("UPDATE notes SET likes_count = likes_count + 1 WHERE id = $note_id");

        logActivity($user_id, 'favorite_note', "Note ID: $note_id");

        $message = 'Added to favorites';
        $is_favorited = true;
    }

    // Get updated count
    $result = $conn->query("SELECT likes_count FROM notes WHERE id = $note_id");
    $likes_count = $result->fetch_assoc()['likes_count'];

    echo json_encode([
        'success' => true,
        'message' => $message,
        'is_favorited' => $is_favorited,
        'likes_count' => $likes_count
    ]);
} elseif ($action === 'check') {
    // Check if user has favorited
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND note_id = ?");
    $stmt->bind_param("ii", $user_id, $note_id);
    $stmt->execute();
    $is_favorited = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    // Get likes count
    $result = $conn->query("SELECT likes_count FROM notes WHERE id = $note_id");
    $likes_count = $result->fetch_assoc()['likes_count'];

    echo json_encode([
        'success' => true,
        'is_favorited' => $is_favorited,
        'likes_count' => $likes_count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>