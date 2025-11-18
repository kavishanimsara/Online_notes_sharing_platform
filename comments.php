<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Add Comment - Notes Sharing Platform';
$error = '';
$success = '';

requireLogin();

if (!isset($_GET['note_id'])) {
    header('Location: index.php');
    exit();
}

$note_id = intval($_GET['note_id']);

// Verify note exists
$stmt = $conn->prepare("SELECT id, title FROM notes WHERE id = ?");
$stmt->bind_param("i", $note_id);
$stmt->execute();
$result = $stmt->get_result();
$note = $result->fetch_assoc();
$stmt->close();

if (!$note) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $comment = trim($_POST['comment']);
    $rating = intval($_POST['rating']);
    $user_id = getUserId();

    if (empty($comment)) {
        $error = 'Please write a comment';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a valid rating (1-5)';
    } else {
        // Check if user already commented on this note
        $stmt = $conn->prepare("SELECT id FROM comments WHERE note_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $note_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'You have already reviewed this note';
        } else {
            // Insert new comment
            $stmt = $conn->prepare("INSERT INTO comments (note_id, user_id, comment, rating) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $note_id, $user_id, $comment, $rating);
            
            if ($stmt->execute()) {
                $success = 'Thank you for your review!';
                $_POST = array(); // Clear form
            } else {
                $error = 'Failed to submit review. Please try again.';
            }
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="comment-container">
        <div class="comment-box">
            <h2>Add Your Review</h2>
            <p class="note-info">You're reviewing: <strong><?php echo htmlspecialchars($note['title']); ?></strong></p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="comments.php?note_id=<?php echo $note_id; ?>" class="comment-form">
                <div class="form-group">
                    <label for="rating">Rating</label>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="star-label">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ($i == 5 ? 'checked' : ''); ?>>
                                <span class="star">⭐</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comment">Your Review *</label>
                    <textarea id="comment" name="comment" rows="6" required class="form-control" placeholder="How helpful was this note? What did you like about it?"><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                    <a href="view_note.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>