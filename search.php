<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Advanced Search';

// Get search parameters
$query = $_GET['q'] ?? '';
$tag = $_GET['tag'] ?? '';
$uploader = $_GET['uploader'] ?? '';
$sort = $_GET['sort'] ?? 'recent';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = NOTES_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Build SQL query
$where_conditions = ["n.status = 'active'"];
$params = [];
$types = '';

if (!empty($query)) {
    $where_conditions[] = "(n.title LIKE ? OR n.description LIKE ? OR n.ai_summary LIKE ?)";
    $search_term = "%$query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($tag)) {
    $where_conditions[] = "EXISTS (
        SELECT 1 FROM note_tags nt 
        JOIN tags t ON nt.tag_id = t.id 
        WHERE nt.note_id = n.id AND t.slug = ?
    )";
    $params[] = $tag;
    $types .= 's';
}

if (!empty($uploader)) {
    $where_conditions[] = "u.username LIKE ?";
    $params[] = "%$uploader%";
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Sorting
$order_by = match($sort) {
    'popular' => 'n.downloads DESC',
    'liked' => 'n.likes_count DESC',
    'viewed' => 'n.views DESC',
    default => 'n.created_at DESC'
};

// Count total results
$count_sql = "SELECT COUNT(*) as total FROM notes n JOIN users u ON n.user_id = u.id WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_results = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get results
$sql = "SELECT n.*, u.username, 
        (SELECT GROUP_CONCAT(t.name SEPARATOR ', ') 
         FROM note_tags nt 
         JOIN tags t ON nt.tag_id = t.id 
         WHERE nt.note_id = n.id) as tags
        FROM notes n 
        JOIN users u ON n.user_id = u.id 
        WHERE $where_clause 
        ORDER BY $order_by 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$results = $stmt->get_result();
$stmt->close();

// Get popular tags
$popular_tags = $conn->query("SELECT * FROM tags ORDER BY usage_count DESC LIMIT 20");

// Calculate pagination
$total_pages = ceil($total_results / $per_page);

include 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="container my-5">
    <h2 class="mb-4"><i class="fas fa-search"></i> Advanced Search</h2>

    <!-- Search Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="search.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search Keywords</label>
                        <input type="text" name="q" class="form-control" placeholder="Enter keywords..." value="<?php echo htmlspecialchars($query); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tag</label>
                        <select name="tag" class="form-select">
                            <option value="">All Tags</option>
                            <?php while ($t = $popular_tags->fetch_assoc()): ?>
                                <option value="<?php echo $t['slug']; ?>" <?php echo $tag === $t['slug'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Uploader</label>
                        <input type="text" name="uploader" class="form-control" placeholder="Username..." value="<?php echo htmlspecialchars($uploader); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select">
                            <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Recent</option>
                            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Downloads</option>
                            <option value="liked" <?php echo $sort === 'liked' ? 'selected' : ''; ?>>Likes</option>
                            <option value="viewed" <?php echo $sort === 'viewed' ? 'selected' : ''; ?>>Views</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="search.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Found <?php echo $total_results; ?> result(s)</h5>
    </div>

    <?php if ($results->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($note = $results->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="view_note.php?id=<?php echo $note['id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($note['title']); ?>
                                </a>
                            </h5>
                            
                            <?php if ($note['tags']): ?>
                                <div class="mb-2">
                                    <?php foreach (explode(', ', $note['tags']) as $t): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($t); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <p class="card-text text-muted small">
                                <?php echo htmlspecialchars(substr($note['description'] ?: $note['ai_summary'] ?: '', 0, 100)); ?>...
                            </p>

                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($note['username']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($note['created_at'])); ?></span>
                            </div>

                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="fas fa-download"></i> <?php echo $note['downloads']; ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $note['likes_count']; ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo $note['views']; ?></span>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="view_note.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="download.php?id=<?php echo $note['id']; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No results found. Try different search criteria.
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>