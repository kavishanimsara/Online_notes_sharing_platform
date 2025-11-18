<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Home - Notes Sharing Platform';

// Check if status column exists in notes table
$check_status = $conn->query("SHOW COLUMNS FROM notes LIKE 'status'");
$has_status = $check_status->num_rows > 0;

// Check if likes system exists
$check_likes_table = $conn->query("SHOW TABLES LIKE 'note_likes'");
$has_likes_table = $check_likes_table->num_rows > 0;

$check_likes_count = $conn->query("SHOW COLUMNS FROM notes LIKE 'likes_count'");
$has_likes_count = $check_likes_count->num_rows > 0;

// Fetch all notes with categories
$search = $_GET['search'] ?? '';
$sql = "SELECT n.*, u.username, c.name as category_name, c.id as category_id";

if ($has_likes_count) {
    $sql .= ", n.likes_count";
}

$sql .= " FROM notes n 
        JOIN users u ON n.user_id = u.id
        LEFT JOIN categories c ON n.category_id = c.id";

if ($search) {
    $search = $conn->real_escape_string($search);
    $sql .= " WHERE n.title LIKE '%$search%' OR n.description LIKE '%$search%' OR c.name LIKE '%$search%'";
}

// Add status filter if column exists
if ($has_status) {
    $sql .= ($search ? " AND" : " WHERE") . " (n.status = 'approved' OR n.status IS NULL)";
}

$sql .= " ORDER BY c.name ASC, n.created_at DESC";

try {
    $result = $conn->query($sql);
} catch (Exception $e) {
    // Fallback query without likes if table doesn't exist
    $sql = "SELECT n.*, u.username, c.name as category_name, c.id as category_id 
             FROM notes n 
             JOIN users u ON n.user_id = u.id
             LEFT JOIN categories c ON n.category_id = c.id";
    
    if ($search) {
        $sql .= " WHERE n.title LIKE '%$search%' OR n.description LIKE '%$search%' OR c.name LIKE '%$search%'";
    }
    
    if ($has_status) {
        $sql .= ($search ? " AND" : " WHERE") . " (n.status = 'approved' OR n.status IS NULL)";
    }
    
    $sql .= " ORDER BY c.name ASC, n.created_at DESC";
    $result = $conn->query($sql);
}

// Fetch all categories for display
if ($has_status) {
    $categories_sql = "SELECT DISTINCT c.id, c.name 
                       FROM categories c 
                       INNER JOIN notes n ON c.id = n.category_id 
                       WHERE (n.status = 'approved' OR n.status IS NULL)
                       ORDER BY c.name ASC";
} else {
    $categories_sql = "SELECT DISTINCT c.id, c.name 
                       FROM categories c 
                       INNER JOIN notes n ON c.id = n.category_id 
                       ORDER BY c.name ASC";
}
$categories_result = $conn->query($categories_sql);

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
        <h2>Available Notes by Category</h2>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php 
            // Reset result pointer to beginning
            $result->data_seek(0);
            
            // Group notes by category
            $notes_by_category = [];
            while ($note = $result->fetch_assoc()) {
                $category_id = $note['category_id'] ?? 0;
                $category_name = $note['category_name'] ?? 'Uncategorized';
                if (!isset($notes_by_category[$category_id])) {
                    $notes_by_category[$category_id] = [
                        'name' => $category_name,
                        'notes' => []
                    ];
                }
                $notes_by_category[$category_id]['notes'][] = $note;
            }
            
            // Display notes by category
            foreach ($notes_by_category as $category_id => $category): ?>
                <div class="category-section">
                    <div class="category-header">
                        <h3>
                            <span class="category-icon">📁</span>
                            <?php echo htmlspecialchars($category['name']); ?>
                            <span class="note-count">(<?php echo count($category['notes']); ?> notes)</span>
                        </h3>
                    </div>
                    <div class="notes-grid">
                        <?php foreach ($category['notes'] as $note): ?>
                            <div class="note-card">
                                <div class="note-icon">📄</div>
                                <h4><?php echo htmlspecialchars($note['title']); ?></h4>
                                <p class="note-description"><?php echo htmlspecialchars(substr($note['description'], 0, 100)) . (strlen($note['description']) > 100 ? '...' : ''); ?></p>
                        <div class="note-meta">
                            <span class="note-author">By: <?php echo htmlspecialchars($note['username']); ?></span>
                            <span class="note-downloads">📥 <?php echo $note['downloads']; ?></span>
                        </div>
                        <div class="note-actions">
                            <?php if ($has_likes_table && $has_likes_count): ?>
                            <button class="btn btn-sm btn-outline-danger like-btn" 
                                    data-note-id="<?php echo $note['id']; ?>"
                                    onclick="toggleLike(<?php echo $note['id']; ?>)">
                                <i class="bi bi-heart"></i> 
                                <span class="like-count"><?php echo $note['likes_count'] ?? 0; ?></span>
                            </button>
                            <?php endif; ?>
                            <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                            <a href="download.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-secondary">Download</a>
                        </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No notes found. Be the first to share!</p>
            </div>
        <?php endif; ?>
    </section>
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
            // Update all like buttons for this note
            const likeButtons = document.querySelectorAll(`[data-note-id="${noteId}"]`);
            likeButtons.forEach(button => {
                const likeCount = button.querySelector('.like-count');
                const icon = button.querySelector('i');
                
                // Update count
                likeCount.textContent = data.like_count;
                
                // Update button style based on action
                if (data.action === 'liked') {
                    button.classList.remove('btn-outline-danger');
                    button.classList.add('btn-danger');
                    icon.classList.remove('bi-heart');
                    icon.classList.add('bi-heart-fill');
                } else {
                    button.classList.remove('btn-danger');
                    button.classList.add('btn-outline-danger');
                    icon.classList.remove('bi-heart-fill');
                    icon.classList.add('bi-heart');
                }
            });
            
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