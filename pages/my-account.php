<?php
// pages/my-account.php - User Account Dashboard
session_start();
require_once('../api/config.php');
require_once('../api/auth.php');

// Require login
if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();

// Get user stats
try {
    $pdo = connectDB();
    
    // Get order count
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $orderCount = $stmt->fetchColumn();
    
    // Get total spent
    $stmt = $pdo->prepare('SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND status != "cancelled"');
    $stmt->execute([$user['id']]);
    $totalSpent = $stmt->fetchColumn() ?: 0;
    
    // Get recent orders
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->execute([$user['id']]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Account dashboard error: " . $e->getMessage());
    $orderCount = 0;
    $totalSpent = 0;
    $recentOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - SP Gadgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.6;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: #f1f5f9;
            color: #0f172a;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #e2e8f0;
        }
        
        /* Main Content */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        
        .welcome-banner h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .welcome-banner p {
            font-size: 18px;
            opacity: 0.95;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #1F95B1;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .action-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .action-btn i {
            font-size: 20px;
            color: #1F95B1;
        }
        
        /* Recent Orders */
        .section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .view-all {
            color: #1F95B1;
            text-decoration: none;
            font-weight: 600;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .order-card {
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            border-color: #1F95B1;
            box-shadow: 0 4px 15px rgba(31, 149, 177, 0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .order-id {
            font-weight: 700;
            font-size: 18px;
        }
        
        .order-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .order-details {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #64748b;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        
        .no-orders i {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .welcome-banner {
                padding: 25px;
            }
            
            .welcome-banner h1 {
                font-size: 24px;
            }
            
            .stats-grid,
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-container">
            <a href="../index.html" class="logo">
                <i class="fas fa-store"></i> SP Gadgets
            </a>
            <a href="../index.html" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h1>
            <p>Manage your orders, profile, and wishlist all in one place</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-value"><?php echo $orderCount; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-naira-sign"></i>
                </div>
                <div class="stat-value">₦<?php echo number_format($totalSpent, 0); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-value" id="wishlist-count">0</div>
                <div class="stat-label">Wishlist Items</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="my-orders.php" class="action-btn">
                <i class="fas fa-box"></i>
                <span>My Orders</span>
            </a>
            <a href="my-profile.php" class="action-btn">
                <i class="fas fa-user-edit"></i>
                <span>Edit Profile</span>
            </a>
            <a href="wishlist.php" class="action-btn">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
            </a>
            <a href="shop.php" class="action-btn">
                <i class="fas fa-store"></i>
                <span>Continue Shopping</span>
            </a>
            <a href="logout.php" class="action-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>

        <!-- Recent Orders -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Orders</h2>
                <a href="my-orders.php" class="view-all">View All →</a>
            </div>

            <?php if (!empty($recentOrders)): ?>
                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            <span>₦<?php echo number_format($order['total_amount'], 0); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-bag"></i>
                    <p>No orders yet. Start shopping to see your orders here!</p>
                    <a href="shop.php" style="color: #1F95B1; font-weight: 600; text-decoration: none; margin-top: 15px; display: inline-block;">
                        Browse Products →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fetch wishlist count
        async function loadWishlistCount() {
            try {
                const cart = JSON.parse(localStorage.getItem('wishlist') || '[]');
                document.getElementById('wishlist-count').textContent = cart.length;
            } catch (e) {
                console.error('Error loading wishlist count:', e);
            }
        }
        
        loadWishlistCount();
    </script>
</body>
</html>
