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

<style>
/* Interactive Background */
.interactive-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    z-index: -2;
    overflow: hidden;
}

.floating-shapes {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
}

.shape {
    position: absolute;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    animation: float 20s infinite linear;
}

.shape:nth-child(1) {
    width: 80px;
    height: 80px;
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}

.shape:nth-child(2) {
    width: 120px;
    height: 120px;
    top: 20%;
    right: 10%;
    animation-delay: -5s;
}

.shape:nth-child(3) {
    width: 60px;
    height: 60px;
    bottom: 20%;
    left: 20%;
    animation-delay: -10s;
}

.shape:nth-child(4) {
    width: 100px;
    height: 100px;
    bottom: 10%;
    right: 20%;
    animation-delay: -15s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    25% {
        transform: translateY(-20px) rotate(90deg);
    }
    50% {
        transform: translateY(0px) rotate(180deg);
    }
    75% {
        transform: translateY(20px) rotate(270deg);
    }
}

/* Enhanced Styling */
.container {
    position: relative;
    z-index: 1;
}

.hero {
    text-align: center;
    padding: 80px 20px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    margin-bottom: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.hero h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
}

.hero p {
    font-size: 1.3rem;
    margin-bottom: 30px;
    color: #666;
}

.hero-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.search-section {
    margin-bottom: 50px;
}

.search-form {
    display: flex;
    gap: 15px;
    max-width: 600px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.95);
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.search-input {
    flex: 1;
    padding: 15px 20px;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: translateY(-2px);
}

.notes-section {
    background: rgba(255, 254, 254, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.notes-section h2 {
    text-align: center;
    margin-bottom: 40px;
    color: #2c3e50;
    font-size: 2.5rem;
    font-weight: 600;
}

.category-section {
    background: rgba(150, 150, 150, 0.95);
    margin-bottom: 50px;
}

.category-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 20px 30px;
    border-radius: 15px;
    margin-bottom: 25px;
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.category-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: translateX(-100%);
}

.category-header:hover::before {
    transform: translateX(100%);
    transition: transform 0.6s ease;
}

.category-header h3 {
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 15px;
}

.note-count {
    font-size: 1rem;
    opacity: 0.9;
    font-weight: 400;
}

.notes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
}

.note-card {
    background:  rgba(229, 229, 229, 1);;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
    position: relative;
    overflow: hidden;
}

.note-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.note-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.note-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    text-align: center;
}

.note-card h4 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1.3rem;
    font-weight: 600;
}

.note-description {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
}

.note-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
    font-size: 0.9rem;
    color: #888;
}

.note-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.note-actions .btn {
    flex: 1;
    min-width: 120px;
    transition: all 0.3s ease;
}

.note-actions .btn:hover {
    transform: translateY(-2px);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 15px;
    border: 2px dashed #ddd;
}

.empty-state p {
    font-size: 1.2rem;
    color: #666;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero {
        padding: 40px 20px;
    }
    
    .hero h1 {
        font-size: 2.5rem;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .notes-grid {
        grid-template-columns: 1fr;
    }
    
    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .notes-section {
        padding: 20px;
    }
}
</style>

<!-- Interactive Background -->
<div class="interactive-bg">
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
</div>

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
            <a href="upload_with_category.php" class="btn btn-primary">Upload Your Note</a>
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