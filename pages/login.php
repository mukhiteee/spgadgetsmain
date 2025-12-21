<?php
session_start();
require_once('../api/config.php');
require_once('../api/auth.php');

// Redirect if already logged in
if (isUserLoggedIn()) {
    header('Location: my-account.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = loginUser($email, $password);
    
    if ($result['success']) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['user_email'] = $result['user']['email'];
        header('Location: my-account.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SP Gadgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1F95B1 0%, #5CB9A4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            margin-bottom: 20px;
        }
        
        .logo-section h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #0f172a;
        }
        
        .logo-section p {
            color: #64748b;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #0f172a;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
        }
        
        .form-input {
            width: 100%;
            padding: 16px 18px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #1F95B1;
            box-shadow: 0 0 0 4px rgba(31, 149, 177, 0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-wrapper input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-wrapper label {
            font-size: 14px;
            color: #64748b;
            cursor: pointer;
        }
        
        .forgot-password {
            color: #1F95B1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: #5CB9A4;
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(31, 149, 177, 0.3);
        }
        
        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(31, 149, 177, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(-1px);
        }
        
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e2e8f0;
        }
        
        .divider span {
            position: relative;
            background: white;
            padding: 0 15px;
            color: #64748b;
            font-size: 14px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 15px;
            color: #64748b;
        }
        
        .register-link a {
            color: #1F95B1;
            text-decoration: none;
            font-weight: 700;
            transition: color 0.3s;
        }
        
        .register-link a:hover {
            color: #5CB9A4;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .back-home {
            text-align: center;
            margin-top: 25px;
        }
        
        .back-home a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-home a:hover {
            opacity: 0.8;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 35px 25px;
            }
            
            .logo-section h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-store"></i>
                </div>
                <h1>Welcome Back!</h1>
                <p>Login to continue shopping</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="divider">
                <span>or</span>
            </div>

            <div class="register-link">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </div>

        <div class="back-home">
            <a href="../index.html">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
