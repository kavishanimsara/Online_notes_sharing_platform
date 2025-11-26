<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'session_manager.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    // Check if user is still active (not banned)
    if (!isUserActive()) {
        session_destroy();
        header('Location: login.php?error=banned');
        exit();
    }
    
// Check if current session is still valid
    if (!isCurrentSessionValid()) {
        session_destroy();
        header('Location: login.php?error=session_expired');
        exit();
    }
}

function isUserActive() {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once 'config/db.php';
    $user_id = $_SESSION['user_id'];
    
    $stmt = $GLOBALS['conn']->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        return $user['is_active'] == 1;
    }
    
    return false;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUsername() {
    return $_SESSION['username'] ?? null;
}

function getUserRole() {
    return $_SESSION['role'] ?? 'user';
}

function isAdmin() {
    $role = getUserRole();
    return $role === 'admin' || $role === 'super_admin';
}

function isSuperAdmin() {
    return getUserRole() === 'super_admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php?error=access_denied');
        exit();
    }
}

function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: admin/dashboard.php?error=access_denied');
        exit();
    }
}

function logActivity($user_id, $action, $details = '') {
    global $conn;
    
    // Check if admin_activity_log table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
    if (!$check_table || $check_table->num_rows == 0) {
        return; // Table doesn't exist, skip logging
    }
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $action, $details, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}
?>