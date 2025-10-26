<?php include 'includes/header.php'; ?>
<h2 class="text-center mb-4">📚 Shared Notes</h2>

<div class="search-bar mb-4 text-center">
  <input type="text" class="form-control w-50 d-inline" placeholder="Search notes by title...">
  <button class="btn btn-primary ms-2">Search</button>
</div>

<div class="row">
  <!-- Sample Note Card -->
  <div class="col-md-4">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h5 class="card-title">Introduction to Data Science</h5>
        <p class="card-text text-muted">Uploaded by: Kavisha</p>
        <p class="small text-secondary">Description: Basic concepts and tools used in data science.</p>
        <a href="view_note.php" class="btn btn-outline-primary btn-sm">View</a>
        <a href="#" class="btn btn-outline-success btn-sm">Download</a>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
