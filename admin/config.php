<?php
// admin/config.php - Admin Panel Configuration & Authentication

session_start();

// Include main database config
require_once('../api/config.php');

// Admin configuration
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour
define('ADMIN_PASSWORD_MIN_LENGTH', 8);

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_token']);
}

// Require admin login (redirect to login if not authenticated)
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: /sp_gadgets_main/admin/login.php');
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > ADMIN_SESSION_TIMEOUT)) {
        adminLogout();
        header('Location: /sp_gadgets_main/admin/login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

// Get current admin info
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT id, username, email, full_name, role FROM admin_users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

// Admin login
function adminLogin($username, $password) {
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare('SELECT id, username, email, password, full_name, role, is_active FROM admin_users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        if (!$admin['is_active']) {
            return ['success' => false, 'message' => 'Account is deactivated'];
        }
        
        if (!password_verify($password, $admin['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Create session
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_token'] = $sessionToken;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['last_activity'] = time();
        
        // Store session in database
        $expiresAt = date('Y-m-d H:i:s', time() + ADMIN_SESSION_TIMEOUT);
        $stmt = $pdo->prepare('INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $admin['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        // Update last login
        $stmt = $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?');
        $stmt->execute([$admin['id']]);
        
        // Log activity
        logAdminActivity($admin['id'], 'login', null, null, 'Admin logged in');
        
        return ['success' => true, 'admin' => $admin];
        
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed. Please try again.'];
    }
}

// Admin logout
function adminLogout() {
    if (isset($_SESSION['admin_token'])) {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare('DELETE FROM admin_sessions WHERE session_token = ?');
            $stmt->execute([$_SESSION['admin_token']]);
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
    
    session_destroy();
}

// Log admin activity
function logAdminActivity($adminId, $action, $entityType = null, $entityId = null, $description = null) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('INSERT INTO admin_activity_log (admin_id, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $adminId,
            $action,
            $entityType,
            $entityId,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

// Format currency
function formatCurrency($amount) {
    return '₦' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

// Get order status badge HTML
function getOrderStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'processing' => '<span class="badge badge-info">Processing</span>',
        'shipped' => '<span class="badge badge-primary">Shipped</span>',
        'delivered' => '<span class="badge badge-success">Delivered</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

// Check admin permission
function hasPermission($requiredRole) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    $roleHierarchy = [
        'super_admin' => 3,
        'admin' => 2,
        'moderator' => 1
    ];
    
    $currentRole = $_SESSION['admin_role'] ?? 'moderator';
    
    return ($roleHierarchy[$currentRole] ?? 0) >= ($roleHierarchy[$requiredRole] ?? 999);
}

// Sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
