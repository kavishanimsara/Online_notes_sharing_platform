<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Home - Notes Sharing Platform';

// Fetch all notes
$search = $_GET['search'] ?? '';
$sql = "SELECT n.*, u.username FROM notes n 
        JOIN users u ON n.user_id = u.id";

if ($search) {
    $search = $conn->real_escape_string($search);
    $sql .= " WHERE n.title LIKE '%$search%' OR n.description LIKE '%$search%'";
}

$sql .= " ORDER BY n.created_at DESC";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="container">
    <section class="hero">
        <h1>Welcome to NotesShare</h1>
        <p>Share your study notes and access thousands of resources</p>
        <?php if (!isLoggedIn()): ?>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">Get Started</a>
                <a href="login.php" class="btn btn-secondary">Login</a>
            </div>
        <?php else: ?>
            <a href="upload.php" class="btn btn-primary">Upload Your Note</a>
        <?php endif; ?>
    </section>

    <section class="search-section">
        <form method="GET" action="index.php" class="search-form">
            <input type="text" name="search" placeholder="Search notes..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
            <button type="submit" class="btn btn-search">Search</button>
        </form>
    </section>

    <section class="notes-section">
        <h2>Available Notes</h2>
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="notes-grid">
                <?php while ($note = $result->fetch_assoc()): ?>
                    <div class="note-card">
                        <div class="note-icon">📄</div>
                        <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                        <p class="note-description"><?php echo htmlspecialchars(substr($note['description'], 0, 100)) . (strlen($note['description']) > 100 ? '...' : ''); ?></p>
                        <div class="note-meta">
                            <span class="note-author">By: <?php echo htmlspecialchars($note['username']); ?></span>
                            <span class="note-downloads">📥 <?php echo $note['downloads']; ?></span>
                        </div>
                        <div class="note-actions">
                            <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                            <a href="download.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-secondary">Download</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No notes found. Be the first to share!</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'includes/footer.php'; ?>