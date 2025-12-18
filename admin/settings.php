<?php
// admin/settings.php - Admin Settings
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Settings';

$error = '';
$success = '';

$currentAdmin = getCurrentAdmin();

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all password fields';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < ADMIN_PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . ADMIN_PASSWORD_MIN_LENGTH . ' characters';
    } else {
        try {
            $pdo = connectDB();
            
            // Verify current password
            $stmt = $pdo->prepare('SELECT password FROM admin_users WHERE id = ?');
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $admin['password'])) {
                $error = 'Current password is incorrect';
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
                $stmt->execute([$hashedPassword, $_SESSION['admin_id']]);
                
                logAdminActivity($_SESSION['admin_id'], 'update', 'profile', $_SESSION['admin_id'], 'Changed password');
                
                $success = 'Password changed successfully';
            }
            
        } catch (Exception $e) {
            $error = 'Failed to change password';
        }
    }
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($fullName) || empty($email)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare('UPDATE admin_users SET full_name = ?, email = ? WHERE id = ?');
            $stmt->execute([$fullName, $email, $_SESSION['admin_id']]);
            
            logAdminActivity($_SESSION['admin_id'], 'update', 'profile', $_SESSION['admin_id'], 'Updated profile');
            
            $success = 'Profile updated successfully';
            $currentAdmin = getCurrentAdmin();
            
        } catch (Exception $e) {
            $error = 'Failed to update profile';
        }
    }
}

include('includes/header.php');
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Profile Settings -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user"></i> Profile Settings</h3>
        </div>

        <?php if ($error): ?>
            <div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Username</label>
                <input type="text" value="<?php echo htmlspecialchars($currentAdmin['username']); ?>" disabled
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; background: #f8f9fa;">
                <small style="color: #666;">Username cannot be changed</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Full Name</label>
                <input type="text" name="full_name" required
                       value="<?php echo htmlspecialchars($currentAdmin['full_name']); ?>"
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Email</label>
                <input type="email" name="email" required
                       value="<?php echo htmlspecialchars($currentAdmin['email']); ?>"
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;">
            </div>

            <button type="submit" name="update_profile" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Profile
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-lock"></i> Change Password</h3>
        </div>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Current Password</label>
                <input type="password" name="current_password" required
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">New Password</label>
                <input type="password" name="new_password" required minlength="8"
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;">
                <small style="color: #666;">Minimum 8 characters</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8"
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;">
            </div>

            <button type="submit" name="change_password" class="btn btn-danger">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
    </div>
</div>

<!-- System Info -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> System Information</h3>
    </div>

    <table style="width: 100%;">
        <tr>
            <td style="padding: 12px; background: #f8f9fa; font-weight: 600; width: 200px;">Role</td>
            <td style="padding: 12px;"><?php echo ucfirst($currentAdmin['role']); ?></td>
        </tr>
        <tr>
            <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">PHP Version</td>
            <td style="padding: 12px;"><?php echo PHP_VERSION; ?></td>
        </tr>
        <tr>
            <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">Session Timeout</td>
            <td style="padding: 12px;"><?php echo (ADMIN_SESSION_TIMEOUT / 60); ?> minutes</td>
        </tr>
    </table>
</div>

<?php include('includes/footer.php'); ?>
