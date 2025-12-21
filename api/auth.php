<?php
// api/auth.php - User Authentication Functions

require_once('config.php');

// Configuration
define('SESSION_TIMEOUT', 7200); // 2 hours
define('PASSWORD_MIN_LENGTH', 8);

/**
 * Check if user is logged in
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_token']);
}

/**
 * Get current logged-in user
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT id, email, first_name, last_name, phone, is_verified FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Register new user
 */
function registerUser($email, $password, $firstName, $lastName, $phone = null) {
    try {
        $pdo = connectDB();
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        // Validate password
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
        }
        
        // Check if email exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        
        // Create user
        $stmt = $pdo->prepare('INSERT INTO users (email, password, first_name, last_name, phone, verification_token) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $phone, $verificationToken]);
        
        $userId = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'user_id' => $userId,
            'verification_token' => $verificationToken
        ];
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare('SELECT id, email, password, first_name, last_name, is_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Create session
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_token'] = $sessionToken;
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['last_activity'] = time();
        
        // Store session in database
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        $stmt = $pdo->prepare('INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        // Update last login
        $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $stmt->execute([$user['id']]);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    if (isset($_SESSION['user_token'])) {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE session_token = ?');
            $stmt->execute([$_SESSION['user_token']]);
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
    
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

/**
 * Request password reset
 */
function requestPasswordReset($email) {
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Don't reveal if email exists or not (security)
            return ['success' => true, 'message' => 'If email exists, reset link has been sent'];
        }
        
        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Save token
        $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?');
        $stmt->execute([$resetToken, $expiry, $user['id']]);
        
        return [
            'success' => true,
            'message' => 'Reset link sent',
            'reset_token' => $resetToken
        ];
        
    } catch (Exception $e) {
        error_log("Password reset request error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to process request'];
    }
}

/**
 * Reset password with token
 */
function resetPassword($token, $newPassword) {
    try {
        $pdo = connectDB();
        
        // Validate password
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
        }
        
        // Find user with valid token
        $stmt = $pdo->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        // Update password and clear token
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?');
        $stmt->execute([$hashedPassword, $user['id']]);
        
        return ['success' => true, 'message' => 'Password reset successful'];
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Password reset failed'];
    }
}

/**
 * Update user profile
 */
function updateUserProfile($userId, $data) {
    try {
        $pdo = connectDB();
        
        $updates = [];
        $params = [];
        
        if (isset($data['first_name'])) {
            $updates[] = 'first_name = ?';
            $params[] = $data['first_name'];
        }
        
        if (isset($data['last_name'])) {
            $updates[] = 'last_name = ?';
            $params[] = $data['last_name'];
        }
        
        if (isset($data['phone'])) {
            $updates[] = 'phone = ?';
            $params[] = $data['phone'];
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No data to update'];
        }
        
        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return ['success' => true, 'message' => 'Profile updated successfully'];
        
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Profile update failed'];
    }
}

/**
 * Change password
 */
function changePassword($userId, $currentPassword, $newPassword) {
    try {
        $pdo = connectDB();
        
        // Verify current password
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $userId]);
        
        return ['success' => true, 'message' => 'Password changed successfully'];
        
    } catch (Exception $e) {
        error_log("Change password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Password change failed'];
    }
}

/**
 * Get user's orders
 */
function getUserOrders($userId, $limit = 50) {
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare('
            SELECT o.*, COUNT(oi.id) as item_count 
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.user_id = ? 
            GROUP BY o.id 
            ORDER BY o.created_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get orders error: " . $e->getMessage());
        return [];
    }
}

/**
 * Save user address
 */
function saveUserAddress($userId, $addressData) {
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare('INSERT INTO user_addresses (user_id, address_type, full_address, city, state, country, postal_code, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $userId,
            $addressData['address_type'] ?? 'shipping',
            $addressData['full_address'],
            $addressData['city'] ?? null,
            $addressData['state'] ?? null,
            $addressData['country'] ?? 'Nigeria',
            $addressData['postal_code'] ?? null,
            $addressData['is_default'] ?? 0
        ]);
        
        return ['success' => true, 'message' => 'Address saved successfully'];
        
    } catch (Exception $e) {
        error_log("Save address error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to save address'];
    }
}

/**
 * Get user addresses
 */
function getUserAddresses($userId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get addresses error: " . $e->getMessage());
        return [];
    }
}

/**
 * Link guest order to user account
 */
function linkOrderToUser($orderId, $userId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE orders SET user_id = ? WHERE id = ?');
        $stmt->execute([$userId, $orderId]);
        return true;
        
    } catch (Exception $e) {
        error_log("Link order error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isUserLoggedIn()) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            logoutUser();
            return false;
        }
        $_SESSION['last_activity'] = time();
    }
    return true;
}
?>
