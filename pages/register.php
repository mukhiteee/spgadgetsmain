<?php
session_start();
require_once('../api/config.php');
require_once('../api/auth.php');

if (isUserLoggedIn()) {
    header('Location: my-account.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    if (!$terms) {
        $error = 'Please accept the terms and conditions';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } else {
        $result = registerUser($firstName, $lastName, $email, $phone, $password);
        
        if ($result['success']) {
            $success = 'Account created! Redirecting to login...';
            header('refresh:2;url=login.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SP Gadgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1F95B1 0%, #5CB9A4 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .register-container { width: 100%; max-width: 500px; animation: slideUp 0.6s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .register-card { background: white; border-radius: 20px; padding: 50px 40px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
        .logo-section { text-align: center; margin-bottom: 35px; }
        .logo { width: 70px; height: 70px; background: linear-gradient(135deg, #1F95B1, #5CB9A4); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 32px; margin-bottom: 20px; }
        .logo-section h1 { font-size: 28px; font-weight: 700; margin-bottom: 8px; color: #0f172a; }
        .logo-section p { color: #64748b; font-size: 15px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 600; margin-bottom: 10px; color: #0f172a; font-size: 14px; }
        .input-wrapper { position: relative; }
        .input-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 18px; }
        .form-input { width: 100%; padding: 14px 18px 14px 50px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; transition: all 0.3s; }
        .form-input:focus { outline: none; border-color: #1F95B1; box-shadow: 0 0 0 4px rgba(31, 149, 177, 0.1); }
        .password-toggle { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; font-size: 18px; }
        .password-strength { margin-top: 8px; height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; }
        .strength-bar { height: 100%; width: 0%; transition: all 0.3s; }
        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }
        .terms-wrapper { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 25px; }
        .terms-wrapper input { width: 18px; height: 18px; cursor: pointer; margin-top: 2px; }
        .terms-wrapper label { font-size: 14px; color: #64748b; cursor: pointer; }
        .terms-wrapper a { color: #1F95B1; text-decoration: none; font-weight: 600; }
        .register-btn { width: 100%; padding: 16px; background: linear-gradient(135deg, #1F95B1, #5CB9A4); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 30px rgba(31, 149, 177, 0.3); }
        .register-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 40px rgba(31, 149, 177, 0.4); }
        .login-link { text-align: center; margin-top: 25px; font-size: 15px; color: #64748b; }
        .login-link a { color: #1F95B1; text-decoration: none; font-weight: 700; }
        .error-message { background: #fee2e2; color: #991b1b; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .success-message { background: #d1fae5; color: #065f46; padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .back-home { text-align: center; margin-top: 25px; }
        .back-home a { color: white; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; }
        @media (max-width: 580px) { .form-row { grid-template-columns: 1fr; } .register-card { padding: 35px 25px; } }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="logo-section">
                <div class="logo"><i class="fas fa-store"></i></div>
                <h1>Create Account</h1>
                <p>Join SP Gadgets today</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm()">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="first_name" class="form-input" placeholder="First name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="last_name" class="form-input" placeholder="Last name" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" class="form-input" placeholder="your@email.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="phone" class="form-input" placeholder="+234" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" class="form-input" placeholder="Min. 8 characters" required minlength="8" oninput="checkPasswordStrength()">
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'icon1')">
                            <i class="fas fa-eye" id="icon1"></i>
                        </button>
                    </div>
                    <div class="password-strength"><div class="strength-bar" id="strengthBar"></div></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder="Re-enter password" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'icon2')">
                            <i class="fas fa-eye" id="icon2"></i>
                        </button>
                    </div>
                </div>

                <div class="terms-wrapper">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a></label>
                </div>

                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>

        <div class="back-home">
            <a href="../index.html"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            
            strengthBar.className = 'strength-bar';
            
            if (password.length >= 12 && /[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                strengthBar.classList.add('strength-strong');
            } else if (password.length >= 8 && /[A-Z]/.test(password)) {
                strengthBar.classList.add('strength-medium');
            } else if (password.length >= 8) {
                strengthBar.classList.add('strength-weak');
            }
        }

        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>
