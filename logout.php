<?php
session_start();
require_once 'config/db.php';
require_once 'includes/session_manager.php';

// Clean up session from database if user is logged in
if (isset($_SESSION['user_id'])) {
    $session_id = session_id();
    $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $stmt->close();
}

session_unset();
session_destroy();
header('Location: login.php');
exit();
?>