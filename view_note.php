<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'View Note - Notes Sharing Platform';
$note = null;

if (isset($_GET['id'])) {
    $note_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT n.*, u.username FROM notes n JOIN users u ON n.user_id = u.id WHERE n.id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $note = $result->fetch_assoc();
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
                    <span>📁 <?php echo number_format($note['file_size'] / 1024, 2); ?> KB</span>
                </div>
            </div>

            <div class="note-detail-body">
                <div class="note-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($note['description'])); ?></p>
                </div>

                <div class="note-file-info">
                    <h3>File Information</h3>
                    <p><strong>Original Filename:</strong> <?php echo htmlspecialchars($note['file_name']); ?></p>
                    <p><strong>File Size:</strong> <?php echo number_format($note['file_size'] / 1024, 2); ?> KB</p>
                </div>

                <div class="note-actions-large">
                    <a href="download.php?id=<?php echo $note['id']; ?>" class="btn btn-primary btn-large">
                        📥 Download Note
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-large">
                        ← Back to Notes
                    </a>
                </div>
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