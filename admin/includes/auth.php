<?php
function isAdminLoggedIn() {
    // Check if admin session variables are set and not empty
    if (isset($_SESSION['admin_id']) && 
        isset($_SESSION['admin_username']) && 
        isset($_SESSION['admin_level']) &&
        !empty($_SESSION['admin_id']) &&
        !empty($_SESSION['admin_level'])) {
        return true;
    }
    return false;
}

function getAdminPermissions($conn) {
    if (!isAdminLoggedIn()) return [];
    
    $admin_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT permissions FROM admins WHERE user_id = ? AND is_active = 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        return json_decode($admin['permissions'], true) ?? [];
    }
    
    return [];
}

function hasPermission($conn, $permission) {
    if (!isAdminLoggedIn()) return false;
    
    $admin_level = $_SESSION['admin_level'];
    $permissions = getAdminPermissions($conn);
    
    // Super admin has all permissions
    if ($admin_level === 'super_admin') {
        return true;
    }
    
    return in_array($permission, $permissions);
}

function requirePermission($conn, $permission) {
    if (!hasPermission($conn, $permission)) {
        header('Location: dashboard.php');
        exit();
    }
}

function logAdminActivity($conn, $action, $target_type = null, $target_id = null, $details = null) {
    if (!isAdminLoggedIn()) return;
    
    $admin_id = $_SESSION['admin_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issiiss", $admin_id, $action, $target_type, $target_id, $details, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

function getDefaultPermissions($level) {
    $permissions = [
        'super_admin' => [
            'manage_users', 'manage_notes', 'manage_categories', 'manage_admins',
            'view_reports', 'system_settings', 'approve_notes', 'reject_notes',
            'edit_notes', 'delete_notes', 'ban_users', 'view_activities'
        ],
        'admin' => [
            'manage_users', 'manage_notes', 'manage_categories', 'view_reports',
            'approve_notes', 'reject_notes', 'edit_notes', 'delete_notes',
            'view_activities'
        ],
        'moderator' => [
            'manage_notes', 'approve_notes', 'reject_notes', 'view_activities'
        ]
    ];
    
    return $permissions[$level] ?? [];
}
?>