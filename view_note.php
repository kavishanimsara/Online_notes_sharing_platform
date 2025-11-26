<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'View Note - Notes Sharing Platform';
$note = null;
$comments = [];
$comments_count = 0;

// Initialize variables to prevent undefined variable warnings
$has_views = false;
$has_likes_table = false;
$has_likes_count = false;
$has_comments_table = false;

if (isset($_GET['id'])) {
    $note_id = intval($_GET['id']);
    
    // Check if likes system exists
    $check_likes_table = $conn->query("SHOW TABLES LIKE 'note_likes'");
    $has_likes_table = $check_likes_table->num_rows > 0;
    
    $check_likes_count = $conn->query("SHOW COLUMNS FROM notes LIKE 'likes_count'");
    $has_likes_count = $check_likes_count->num_rows > 0;
    
    // Check if comments table exists
    $check_comments_table = $conn->query("SHOW TABLES LIKE 'comments'");
    $has_comments_table = $check_comments_table->num_rows > 0;
    
    // Check if views column exists
    $check_views = $conn->query("SHOW COLUMNS FROM notes LIKE 'views'");
    $has_views = $check_views->num_rows > 0;
    
    // Build query based on available columns
    $sql = "SELECT n.*, u.username";
    if ($has_likes_count) {
        $sql .= ", n.likes_count";
    }
    if ($has_views) {
        $sql .= ", n.views";
    }
    $sql .= " FROM notes n JOIN users u ON n.user_id = u.id WHERE n.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $note = $result->fetch_assoc();
        
        // Get comments if table exists
        if ($has_comments_table) {
            $comments_stmt = $conn->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.note_id = ? ORDER BY c.created_at DESC LIMIT 3");
            $comments_stmt->bind_param("i", $note_id);
            $comments_stmt->execute();
            $comments_result = $comments_stmt->get_result();
            
            while ($row = $comments_result->fetch_assoc()) {
                $comments[] = $row;
            }
            $comments_stmt->close();
            
            // Get total comments count
            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM comments WHERE note_id = ?");
            $count_stmt->bind_param("i", $note_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $comments_count = $count_result->fetch_assoc()['count'];
            $count_stmt->close();
        }
        
        // Increment views count if column exists
        if ($has_views) {
            $update_views = $conn->prepare("UPDATE notes SET views = views + 1 WHERE id = ?");
            $update_views->bind_param("i", $note_id);
            $update_views->execute();
            $update_views->close();
        }
    }
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="container">
    <?php if ($note): ?>
        <div class="note-detail">
            <div class="note-detail-header">
                <h1><?php echo htmlspecialchars($note['title']); ?></h1>
                <div class="note-detail-meta">
                    <span>📅 <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                    <span>👤 <?php echo htmlspecialchars($note['username']); ?></span>
                    <span>📥 <?php echo $note['downloads']; ?> downloads</span>
                    <?php if ($has_views && isset($note['views'])): ?>
                    <span>👁️ <?php echo $note['views']; ?> views</span>
                    <?php endif; ?>
                    <?php if ($has_comments_table): ?>
                    <span>💬 <?php echo $comments_count; ?> comments</span>
                    <?php endif; ?>
                    <span>📁 <?php echo number_format($note['file_size'] / 1024, 2); ?> KB</span>
                </div>

                <div class="note-actions-large">
                    <?php if (isLoggedIn() && $has_likes_table && $has_likes_count): ?>
                    <button class="btn btn-outline-danger btn-large me-2" 
                            data-note-id="<?php echo $note['id']; ?>"
                            onclick="toggleLike(<?php echo $note['id']; ?>)">
                        <i class="bi bi-heart-fill"></i> 
                        <span class="like-count"><?php echo $note['likes_count'] ?? 0; ?></span> Likes
                    </button>
                    <?php endif; ?>
                    <a href="download.php?id=<?php echo $note['id']; ?>" class="btn btn-primary btn-large">
                        📥 Download Note
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-large">
                        ← Back to Notes
                    </a>
                    <a href="comments.php?note_id=<?php echo $note['id']; ?>" class="btn btn-secondary btn-large">
                         💬 Share your thoughts (<?php echo $comments_count; ?>)
                    </a>
                </div>
            </div>
        </div>

        <?php if ($has_comments_table && !empty($comments)): ?>
        <div class="btn btn-secondary btn-large">
            <h3>Recent Comments</h3>
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
            
            <?php if ($comments_count > 3): ?>
                <div class="text-center">
                    <a href="comments.php?note_id=<?php echo $note['id']; ?>" class="btn btn-outline-primary">
                        View all <?php echo $comments_count; ?> comments
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="error-container">
            <h2>Note Not Found</h2>
            <p>The note you're looking for doesn't exist or has been removed.</p>
            <a href="index.php" class="btn btn-primary">Back to Home</a>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleLike(noteId) {
    if (!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
        alert('Please login to like notes');
        return;
    }
    
    fetch('api/like_note.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'note_id=' + noteId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update like button
            const likeButton = document.querySelector(`[data-note-id="${noteId}"]`);
            const likeCount = likeButton.querySelector('.like-count');
            const icon = likeButton.querySelector('i');
            
            // Update count
            likeCount.textContent = data.like_count;
            
            // Update button style based on action
            if (data.action === 'liked') {
                likeButton.classList.remove('btn-outline-danger');
                likeButton.classList.add('btn-danger');
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
            } else {
                likeButton.classList.remove('btn-danger');
                likeButton.classList.add('btn-outline-danger');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            }
            
            // Show feedback
            showNotification(data.message, data.action === 'liked' ? 'success' : 'info');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>