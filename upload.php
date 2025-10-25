<?php include 'includes/header.php'; ?>
<div class="form-container">
  <h2 class="text-center mb-4">Upload Notes</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Title</label>
      <input type="text" class="form-control" name="title" required>
    </div>
    <div class="mb-3">
      <label>Description</label>
      <textarea class="form-control" name="description" required></textarea>
    </div>
    <div class="mb-3">
      <label>Upload File (PDF/DOC)</label>
      <input type="file" class="form-control" name="file" accept=".pdf,.doc,.docx" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Upload</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
