<?php
session_start();
require_once('../api/config.php');
require_once('../api/auth.php');

if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $user['id']
        ]);
        $success = 'Profile updated successfully!';
        $user = getCurrentUser(); // Refresh user data
    } catch (Exception $e) {
        $error = 'Failed to update profile';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if ($_POST['new_password'] === $_POST['confirm_password']) {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([password_hash($_POST['new_password'], PASSWORD_BCRYPT), $user['id']]);
            $success = 'Password changed successfully!';
        } catch (Exception $e) {
            $error = 'Failed to change password';
        }
    } else {
        $error = 'Passwords do not match';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SP Gadgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #0f172a; line-height: 1.6; }
        .header { background: white; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .header-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 700; background: linear-gradient(135deg, #1F95B1, #5CB9A4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .back-btn { padding: 10px 20px; background: #f1f5f9; color: #0f172a; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s; }
        .back-btn:hover { background: #e2e8f0; }
        .container { max-width: 800px; margin: 0 auto; padding: 0 20px; }
        .page-title { font-size: 32px; font-weight: 700; margin-bottom: 30px; }
        .card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .card-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e2e8f0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        input { width: 100%; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.3s; }
        input:focus { outline: none; border-color: #1F95B1; box-shadow: 0 0 0 3px rgba(31, 149, 177, 0.1); }
        .btn { padding: 14px 28px; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s; border: none; }
        .btn-primary { background: linear-gradient(135deg, #1F95B1, #5CB9A4); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(31, 149, 177, 0.3); }
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        @media (max-width: 768px) { .container { padding: 0 15px; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="../index.html" class="logo"><i class="fas fa-store"></i> SP Gadgets</a>
            <a href="my-account.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Account</a>
        </div>
    </div>

    <div class="container">
        <h1 class="page-title">My Profile</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 class="card-title">Personal Information</h2>
            <form method="POST">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title">Change Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" minlength="8" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" minlength="8" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</body>
</html>
