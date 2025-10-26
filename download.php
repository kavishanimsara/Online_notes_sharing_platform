<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isset($_GET['id'])) {
    $note_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $note = $result->fetch_assoc();
        $file_path = $note['file_path'];
        
        if (file_exists($file_path)) {
            // Update download count
            $update_stmt = $conn->prepare("UPDATE notes SET downloads = downloads + 1 WHERE id = ?");
            $update_stmt->bind_param("i", $note_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Set headers for file download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($note['file_name']) . '"');
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            // Clear output buffer
            ob_clean();
            flush();
            
            // Read and output file
            readfile($file_path);
            exit();
        } else {
            echo "File not found on server.";
        }
    } else {
        echo "Note not found.";
    }
    $stmt->close();
} else {
    header('Location: index.php');
    exit();
}
?>