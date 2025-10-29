<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle = 'Dashboard';
$user_id = getUserId();

// Get user statistics
$stats = [];

// Total notes
$result = $conn->query("SELECT COUNT(*) as count FROM notes WHERE user_id = $user_id");
$stats['total_notes'] = $result->fetch_assoc()['count'];

// Total downloads
$result = $conn->query("SELECT COALESCE(SUM(downloads), 0) as total FROM notes WHERE user_id = $user_id");
$stats['total_downloads'] = $result->fetch_assoc()['total'];

// Total views
$result = $conn->query("SELECT COALESCE(SUM(views), 0) as total FROM notes WHERE user_id = $user_id");
$stats['total_views'] = $result->fetch_assoc()['total'];

// Total likes
$result = $conn->query("SELECT COALESCE(SUM(likes_count), 0) as total FROM notes WHERE user_id = $user_id");
$stats['total_likes'] = $result->fetch_assoc()['total'];

// Favorites count
$result = $conn->query("SELECT COUNT(*) as count FROM favorites WHERE user_id = $user_id");
$stats['favorites'] = $result->fetch_assoc()['count'];

// Comments received
$result = $conn->query("SELECT COUNT(c.id) as count FROM comments c JOIN notes n ON c.note_id = n.id WHERE n.user_id = $user_id");
$stats['comments_received'] = $result->fetch_assoc()['count'];

// Recent uploads
$recent_notes = $conn->query("SELECT * FROM notes WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");

// Top performing notes
$top_notes = $conn->query("SELECT * FROM notes WHERE user_id = $user_id ORDER BY (downloads + views + likes_count) DESC LIMIT 5");

// Recent activity
$recent_activity = $conn->query("SELECT * FROM activity_log WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 10");

// Download statistics by month
$download_stats = $conn->query("
    SELECT DATE_FORMAT(downloaded_at, '%Y-%m') as month, COUNT(*) as count 
    FROM downloads d 
    JOIN notes n ON d.note_id = n.id 
    WHERE n.user_id = $user_id 
    GROUP BY month 
    ORDER BY month DESC 
    LIMIT 6
");

include 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-tachometer-alt"></i> My Dashboard</h2>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Notes</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['total_notes']; ?></h2>
                        </div>
                        <i class="fas fa-file-alt fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-primary bg-opacity-75">
                    <a href="upload.php" class="text-white text-decoration-none small">
                        <i class="fas fa-plus"></i> Upload New
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Downloads</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['total_downloads']; ?></h2>
                        </div>
                        <i class="fas fa-download fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-success bg-opacity-75">
                    <span class="small">From all your notes</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Views</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['total_views']; ?></h2>
                        </div>
                        <i class="fas fa-eye fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-info bg-opacity-75">
                    <span class="small">Note impressions</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Likes</h6>
                            <h2 class="mt-2 mb-0"><?php echo $stats['total_likes']; ?></h2>
                        </div>
                        <i class="fas fa-heart fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-danger bg-opacity-75">
                    <span class="small">Community appreciation</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Downloads Chart -->
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Download Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="downloadsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span><i class="fas fa-star text-warning"></i> Favorites</span>
                        <strong><?php echo $stats['favorites']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <span><i class="fas fa-comments text-primary"></i> Comments Received</span>
                        <strong><?php echo $stats['comments_received']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span><i class="fas fa-trophy text-success"></i> Avg Rating</span>
                        <strong>
                            <?php 
                            $avg_rating = $stats['total_notes'] > 0 ? 
                                round(($stats['total_likes'] / $stats['total_notes']), 1) : 0;
                            echo $avg_rating;
                            ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Recent Uploads -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Uploads</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while ($note = $recent_notes->fetch_assoc()): ?>
                            <a href="view_note.php?id=<?php echo $note['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($note['title']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d', strtotime($note['created_at'])); ?></small>
                                </div>
                                <div class="d-flex gap-3 text-muted small">
                                    <span><i class="fas fa-download"></i> <?php echo $note['downloads']; ?></span>
                                    <span><i class="fas fa-heart"></i> <?php echo $note['likes_count']; ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo $note['views']; ?></span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Notes -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-fire text-danger"></i> Top Performing</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php while ($note = $top_notes->fetch_assoc()): ?>
                            <a href="view_note.php?id=<?php echo $note['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($note['title']); ?></h6>
                                    <span class="badge bg-success">
                                        <?php echo $note['downloads'] + $note['views'] + $note['likes_count']; ?> total
                                    </span>
                                </div>
                                <div class="d-flex gap-3 text-muted small">
                                    <span><i class="fas fa-download"></i> <?php echo $note['downloads']; ?></span>
                                    <span><i class="fas fa-heart"></i> <?php echo $note['likes_count']; ?></span>
                                    <span><i class="fas fa-eye"></i> <?php echo $note['views']; ?></span>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Downloads Chart
const ctx = document.getElementById('downloadsChart').getContext('2d');
const downloadData = <?php 
    $months = [];
    $counts = [];
    $download_stats->data_seek(0);
    while ($row = $download_stats->fetch_assoc()) {
        $months[] = $row['month'];
        $counts[] = $row['count'];
    }
    echo json_encode(['months' => array_reverse($months), 'counts' => array_reverse($counts)]);
?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: downloadData.months,
        datasets: [{
            label: 'Downloads',
            data: downloadData.counts,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'includes/footer.php'; ?>