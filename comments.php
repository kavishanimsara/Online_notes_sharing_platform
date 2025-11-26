<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$note_id = isset($_GET['note_id']) ? intval($_GET['note_id']) : 0;
$note = null;

// Get note details
if ($note_id > 0) {
    $stmt = $conn->prepare("SELECT n.*, u.username FROM notes n JOIN users u ON n.user_id = u.id WHERE n.id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $note = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isLoggedIn()) {
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    if (!empty($comment) && $note_id > 0) {
        $stmt = $conn->prepare("INSERT INTO comments (note_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $note_id, $user_id, $comment);
        $stmt->execute();
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: comments.php?note_id=$note_id&success=1");
        exit();
    }
}

// Get comments for this note
$comments = [];
if ($note_id > 0) {
    $stmt = $conn->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.note_id = ? ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Comments - Notes Sharing Platform';
include 'includes/header.php';
?>

<div class="container">
    <?php if ($note): ?>
        <div class="note-detail mb-4">
            <div class="note-detail-header">
                <h2><?php echo htmlspecialchars($note['title']); ?></h2>
                <div class="note-detail-meta">
                    <span>👤 <?php echo htmlspecialchars($note['username']); ?></span>
                    <span>📅 <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <div class="comments-section">
            <h3>Comments (<?php echo count($comments); ?>)</h3>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Comment posted successfully!</div>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <div class="comment-form mb-4">
                    <h4>Share your thoughts</h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <textarea name="comment" class="form-control" rows="4" placeholder="Write your comment here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Post Comment</button>
                        <a href="view_note.php?id=<?php echo $note_id; ?>" class="btn btn-secondary">Back to Note</a>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Please <a href="login.php">login</a> to post comments.
                </div>
            <?php endif; ?>

            <div class="comments-list">
                <?php if (empty($comments)): ?>
                    <p class="text-muted">No comments yet. Be the first to share your thoughts!</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item border-bottom pb-3 mb-3">
                            <div class="comment-header">
                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?>
                                </small>
                            </div>
                            <div class="comment-content mt-2">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="error-container">
            <h2>Note Not Found</h2>
            <p>The note you're looking for doesn't exist or has been removed.</p>
            <a href="index.php" class="btn btn-primary">Back to Home</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>