<?php
// admin/order-details.php - Order Details View
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
        $stmt->execute([$_POST['status'], $orderId]);
        $success = "Order status updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Fetch order details
try {
    $pdo = connectDB();
    
    // Get order
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare('SELECT oi.*, p.name, p.image, p.category 
                           FROM order_items oi 
                           LEFT JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?');
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get customer info
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$order['user_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Order details error: " . $e->getMessage());
    header('Location: orders.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?> - SP Gadgets Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0f0f0f;
            --bg-secondary: #212121;
            --bg-tertiary: #282828;
            --bg-hover: #3f3f3f;
            --text-primary: #ffffff;
            --text-secondary: #aaaaaa;
            --text-tertiary: #717171;
            --accent: #1F95B1;
            --accent-hover: #5CB9A4;
            --border: #3f3f3f;
            --success: #0f9d58;
            --warning: #f9ab00;
            --error: #dd2c00;
            --info: #3ea6ff;
            --sidebar-width: 240px;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Same as before */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid var(--border);
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--bg-hover);
            border-radius: 4px;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .sidebar-logo img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .sidebar-logo-text {
            font-size: 18px;
            font-weight: 500;
        }

        .sidebar-nav {
            padding: 12px 0;
        }

        .nav-section-title {
            padding: 8px 20px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-tertiary);
            font-weight: 500;
            margin-top: 16px;
        }

        .nav-section-title:first-child {
            margin-top: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 10px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
            font-weight: 400;
        }

        .nav-item:hover {
            background: var(--bg-hover);
        }

        .nav-item.active {
            background: var(--bg-hover);
            border-left: 3px solid var(--accent);
            padding-left: 17px;
        }

        .nav-item i {
            width: 24px;
            font-size: 20px;
            color: var(--text-primary);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
            font-size: 14px;
        }

        .back-btn:hover {
            color: var(--accent);
        }

        .page-title {
            font-size: 20px;
            font-weight: 500;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: background 0.2s;
        }

        .icon-btn:hover {
            background: var(--bg-hover);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }

        /* Content Area */
        .content-area {
            padding: 24px;
            max-width: 1400px;
        }

        /* Order Header */
        .order-header {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }

        .order-header-left h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .order-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .order-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-header-right {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: flex-end;
        }

        /* Status Badge */
        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: rgba(15, 157, 88, 0.15);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(249, 171, 0, 0.15);
            color: var(--warning);
        }

        .badge-error {
            background: rgba(221, 44, 0, 0.15);
            color: var(--error);
        }

        .badge-info {
            background: rgba(62, 166, 255, 0.15);
            color: var(--info);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 24px;
        }

        /* Card */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body {
            padding: 20px;
        }

        /* Order Items */
        .order-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--bg-tertiary);
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .item-meta {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 14px;
            font-weight: 500;
        }

        /* Order Summary */
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 14px;
        }

        .summary-row.total {
            border-top: 2px solid var(--border);
            margin-top: 12px;
            padding-top: 16px;
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
        }

        /* Info Section */
        .info-section {
            margin-bottom: 20px;
        }

        .info-section:last-child {
            margin-bottom: 0;
        }

        .info-section h4 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
            margin-bottom: 12px;
        }

        .info-section p {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 4px;
        }

        .info-section strong {
            color: var(--text-primary);
        }

        /* Status Form */
        .status-form {
            display: flex;
            gap: 12px;
        }

        .status-select {
            flex: 1;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            outline: none;
        }

        .status-select:focus {
            border-color: var(--accent);
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--bg-tertiary);
        }

        /* Alert */
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(15, 157, 88, 0.15);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(221, 44, 0, 0.15);
            border: 1px solid var(--error);
            color: var(--error);
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -26px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--accent);
            border: 3px solid var(--bg-secondary);
        }

        .timeline-content {
            font-size: 14px;
        }

        .timeline-content strong {
            display: block;
            margin-bottom: 4px;
        }

        .timeline-content span {
            color: var(--text-secondary);
            font-size: 12px;
        }

        /* Print Styles */
        @media print {
            .sidebar,
            .top-bar,
            .btn,
            .status-form {
                display: none !important;
            }

            .main-content {
                margin-left: 0;
            }

            body {
                background: white;
                color: black;
            }

            .card {
                border: 1px solid #ddd;
                break-inside: avoid;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-area {
                padding: 16px;
            }

            .order-header {
                padding: 20px;
                flex-direction: column;
            }

            .order-header-left h1 {
                font-size: 20px;
            }

            .order-header-right {
                width: 100%;
                align-items: stretch;
            }

            .order-meta {
                flex-direction: column;
                gap: 8px;
            }

            .card-body {
                padding: 16px;
            }

            .order-item {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: 200px;
            }

            .status-form {
                flex-direction: column;
            }

            .top-bar {
                padding: 12px 16px;
            }

            .page-title {
                font-size: 16px;
            }

            .back-btn span {
                display: none;
            }
        }

        .mobile-menu-btn {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="../assets/icon.png" alt="SP Gadgets">
                    <span class="sidebar-logo-text">SP Gadgets</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Main</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-item active">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a href="customers.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>

                <div class="nav-section-title">Content</div>
                <a href="reviews.php" class="nav-item">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <a href="categories.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>

                <div class="nav-section-title">Settings</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="orders.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Orders</span>
                    </a>
                </div>
                <div class="top-bar-right">
                    <button class="icon-btn" onclick="window.print()">
                        <i class="fas fa-print"></i>
                    </button>
                    <button class="icon-btn">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Order Header -->
                <div class="order-header">
                    <div class="order-header-left">
                        <h1>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h1>
                        <div class="order-meta">
                            <div class="order-meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('M d, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-meta-item">
                                <i class="fas fa-credit-card"></i>
                                <span><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="order-header-right">
                        <?php
                        $statusClass = 'info';
                        if ($order['order_status'] == 'completed') $statusClass = 'success';
                        if ($order['order_status'] == 'pending') $statusClass = 'warning';
                        if ($order['order_status'] == 'cancelled') $statusClass = 'error';
                        if ($order['order_status'] == 'processing') $statusClass = 'info';
                        ?>
                        <span class="badge badge-<?php echo $statusClass; ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                        
                        <form method="POST" class="status-form">
                            <select name="status" class="status-select">
                                <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="completed" <?php echo $order['order_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary">
                                Update
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Left Column -->
                    <div>
                        <!-- Order Items -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-box"></i>
                                    Order Items (<?php echo count($orderItems); ?>)
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="order-item">
                                        <img src="<?php echo htmlspecialchars($item['image'] ?: '../assets/products/placeholder.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="item-image"
                                             onerror="this.src='../assets/products/placeholder.jpg'">
                                        <div class="item-details">
                                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="item-meta">
                                                Category: <?php echo htmlspecialchars(ucfirst($item['category'])); ?> • 
                                                Quantity: <?php echo $item['quantity']; ?>
                                            </div>
                                            <div class="item-price">
                                                ₦<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?> = 
                                                <strong>₦<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Order Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-history"></i>
                                    Order Timeline
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <strong>Order Placed</strong>
                                            <span><?php echo date('M d, Y g:i A', strtotime($order['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($order['order_status'] != 'pending'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <strong>Status: <?php echo ucfirst($order['order_status']); ?></strong>
                                            <span><?php echo date('M d, Y g:i A', strtotime($order['updated_at'])); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <!-- Order Summary -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-receipt"></i>
                                    Order Summary
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <strong>₦<?php echo number_format($order['subtotal'], 2); ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span>Shipping:</span>
                                    <strong>₦<?php echo number_format($order['shipping_cost'], 2); ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span>Tax (7.5%):</span>
                                    <strong>₦<?php echo number_format($order['tax'], 2); ?></strong>
                                </div>
                                <div class="summary-row total">
                                    <span>Total:</span>
                                    <span>₦<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user"></i>
                                    Customer Information
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="info-section">
                                    <h4>Contact</h4>
                                    <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                                    <p><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                    <p><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Shipping Address
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="info-section">
                                    <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                    <p><?php echo htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_state']); ?></p>
                                </div>

                                <?php if ($order['order_notes']): ?>
                                <div class="info-section">
                                    <h4>Order Notes</h4>
                                    <p><?php echo nl2br(htmlspecialchars($order['order_notes'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    </script>
</body>
</html>