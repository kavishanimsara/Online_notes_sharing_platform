<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle = 'Upload Note - Notes Sharing Platform';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    if (empty($title) || !isset($_FILES['note_file'])) {
        $error = 'Please fill in all required fields';
    } else {
        $file = $_FILES['note_file'];
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
        $max_size = 10 * 1024 * 1024; // 10MB

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload failed';
        } elseif (!in_array($file['type'], $allowed_types)) {
            $error = 'Invalid file type. Only PDF, DOC, DOCX, TXT, PPT, PPTX allowed';
        } elseif ($file['size'] > $max_size) {
            $error = 'File size must be less than 10MB';
        } else {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $unique_filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $user_id = getUserId();
                $file_name = $file['name'];
                $file_size = $file['size'];

                $stmt = $conn->prepare("INSERT INTO notes (user_id, title, description, file_name, file_path, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssi", $user_id, $title, $description, $file_name, $file_path, $file_size);

                if ($stmt->execute()) {
                    $success = 'Note uploaded successfully!';
                    $_POST = array(); // Clear form
                } else {
                    $error = 'Failed to save note information';
                    unlink($file_path); // Delete uploaded file
                }
                $stmt->close();
            } else {
                $error = 'Failed to upload file';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="upload-container">
        <div class="upload-box">
            <h2>Upload Your Note</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="upload.php" enctype="multipart/form-data" class="upload-form">
                <div class="form-group">
                    <label for="title">Note Title *</label>
                    <input type="text" id="title" name="title" required class="form-control" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" class="form-control"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="note_file">Upload File * (PDF, DOC, DOCX, TXT, PPT, PPTX - Max 10MB)</label>
                    <input type="file" id="note_file" name="note_file" required class="form-control" accept=".pdf,.doc,.docx,.txt,.ppt,.pptx">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Upload Note</button>
            </form>

            <p class="upload-info">Share your knowledge with the community!</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>