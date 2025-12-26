<?php
// admin/index.php - Modern Admin Dashboard
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Dashboard';

// Fetch dashboard statistics
try {
    $pdo = connectDB();
    
    // Total products
    $stmt = $pdo->query('SELECT COUNT(*) FROM products');
    $totalProducts = $stmt->fetchColumn();
    
    // Total orders
    $stmt = $pdo->query('SELECT COUNT(*) FROM orders');
    $totalOrders = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $pdo->query('SELECT SUM(total_amount) FROM orders WHERE order_status != "cancelled"');
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Pending orders
    $stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE order_status = "pending"');
    $pendingOrders = $stmt->fetchColumn();
    
    // Low stock products (less than 10)
    $stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity < 10 AND stock_quantity > 0');
    $lowStockCount = $stmt->fetchColumn();
    
    // Out of stock products
    $stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity = 0');
    $outOfStockCount = $stmt->fetchColumn();
    
    // Recent orders
    $stmt = $pdo->query('SELECT o.*, COUNT(oi.id) as item_count 
                         FROM orders o 
                         LEFT JOIN order_items oi ON o.id = oi.order_id 
                         GROUP BY o.id 
                         ORDER BY o.created_at DESC 
                         LIMIT 5');
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low stock products
    $stmt = $pdo->query('SELECT * FROM products WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 5');
    $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Today's stats
    $stmt = $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()');
    $todayOrders = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT SUM(total_amount) FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != "cancelled"');
    $todayRevenue = $stmt->fetchColumn() ?: 0;
    
    // Get monthly revenue (last 6 months)
    $stmt = $pdo->query('SELECT 
                            DATE_FORMAT(created_at, "%b") as month,
                            SUM(total_amount) as revenue
                         FROM orders 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         AND order_status != "cancelled"
                         GROUP BY DATE_FORMAT(created_at, "%Y-%m")
                         ORDER BY created_at ASC');
    $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SP Gadgets Admin</title>
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
            --sidebar-width: 240px;
        }

        /* =====================================================
   SP GADGETS ADMIN - INDEX.PHP THEME UPDATE
   ADD THIS TO YOUR EXISTING <style> TAG
   ===================================================== */

/* ==================== THEME VARIABLES ==================== */

/* Add data-theme attribute to control themes */
:root[data-theme="dark"] {
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
    --sidebar-width: 240px;
}

:root[data-theme="light"] {
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --bg-tertiary: #e9ecef;
    --bg-hover: #dee2e6;
    --text-primary: #212529;
    --text-secondary: #6c757d;
    --text-tertiary: #adb5bd;
    --accent: #1F95B1;
    --accent-hover: #5CB9A4;
    --border: #dee2e6;
    --success: #0f9d58;
    --warning: #f9ab00;
    --error: #dd2c00;
    --sidebar-width: 240px;
}

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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

        .page-title {
            font-size: 20px;
            font-weight: 500;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 8px 16px;
            min-width: 300px;
        }

        .search-box input {
            background: none;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-size: 14px;
            width: 100%;
            margin-left: 8px;
        }

        .search-box i {
            color: var(--text-tertiary);
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
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.revenue {
            background: rgba(31, 149, 177, 0.15);
            color: var(--accent);
        }

        .stat-icon.orders {
            background: rgba(15, 157, 88, 0.15);
            color: var(--success);
        }

        .stat-icon.products {
            background: rgba(249, 171, 0, 0.15);
            color: var(--warning);
        }

        .stat-icon.pending {
            background: rgba(221, 44, 0, 0.15);
            color: var(--error);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .stat-change {
            font-size: 13px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-change.positive {
            color: var(--success);
        }

        .stat-change.negative {
            color: var(--error);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        /* Cards */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 16px;
            font-weight: 500;
        }

        .card-action {
            color: var(--accent);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .card-action:hover {
            color: var(--accent-hover);
        }

        .card-body {
            padding: 20px;
        }

        /* Table */
        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            border-bottom: 1px solid var(--border);
        }

        th {
            text-align: left;
            padding: 12px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
            font-weight: 500;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        tbody tr {
            transition: background 0.2s;
            cursor: pointer;
        }

        tbody tr:hover {
            background: var(--bg-hover);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
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
            background: rgba(31, 149, 177, 0.15);
            color: var(--accent);
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 18px;
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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .quick-action-btn {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .quick-action-btn:hover {
            background: var(--bg-hover);
            transform: translateY(-2px);
        }

        .quick-action-btn i {
            font-size: 32px;
            color: var(--accent);
        }

        .quick-action-btn span {
            font-size: 14px;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .mobile-menu-btn {
                display: none;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-box {
                display: none;
            }

            .mobile-menu-btn {
                display: flex;
            }
        }

        .mobile-menu-btn {
            
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
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                    <?php if ($pendingOrders > 0): ?>
                        <span class="badge badge-error" style="margin-left: auto;"><?php echo $pendingOrders; ?></span>
                    <?php endif; ?>
                </a>
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a href="customers.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>

                <div class="nav-section-title">Insights</div>
                <a href="analytics.php" class="nav-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
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
                    <h1 class="page-title">Dashboard</h1>
                </div>
                <div class="top-bar-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                    <button class="icon-btn theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                        <i class="fas fa-moon" id="themeIcon"></i>
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
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-label">Total Revenue</div>
                                <div class="stat-value">₦<?php echo number_format($totalRevenue, 2); ?></div>
                                <div class="stat-change positive">
                                    <i class="fas fa-arrow-up"></i>
                                    ₦<?php echo number_format($todayRevenue, 2); ?> today
                                </div>
                            </div>
                            <div class="stat-icon revenue">
                                <i class="fas fa-naira-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-label">Total Orders</div>
                                <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                                <div class="stat-change">
                                    <i class="fas fa-clock"></i>
                                    <?php echo $todayOrders; ?> today
                                </div>
                            </div>
                            <div class="stat-icon orders">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-label">Products</div>
                                <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
                                <div class="stat-change <?php echo $lowStockCount > 0 ? 'negative' : ''; ?>">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo $lowStockCount; ?> low stock
                                </div>
                            </div>
                            <div class="stat-icon products">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div>
                                <div class="stat-label">Pending Orders</div>
                                <div class="stat-value"><?php echo number_format($pendingOrders); ?></div>
                                <div class="stat-change">
                                    <i class="fas fa-tasks"></i>
                                    Needs attention
                                </div>
                            </div>
                            <div class="stat-icon pending">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <!-- Recent Orders -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Orders</h3>
                            <a href="orders.php" class="card-action">View all</a>
                        </div>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentOrders)): ?>
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <i class="fas fa-inbox"></i>
                                                    <p>No orders yet</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr onclick="window.location.href='order-details.php?id=<?php echo $order['id']; ?>'">
                                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td>₦<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = 'info';
                                                    if ($order['order_status'] == 'completed') $statusClass = 'success';
                                                    if ($order['order_status'] == 'pending') $statusClass = 'warning';
                                                    if ($order['order_status'] == 'cancelled') $statusClass = 'error';
                                                    ?>
                                                    <span class="badge badge-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($order['order_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
                                Low Stock Alert
                            </h3>
                            <a href="products.php" class="card-action">View all</a>
                        </div>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($lowStockProducts)): ?>
                                        <tr>
                                            <td colspan="3">
                                                <div class="empty-state">
                                                    <i class="fas fa-check-circle"></i>
                                                    <p>All products well stocked</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($lowStockProducts as $product): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                                    <small style="color: var(--text-tertiary);"><?php echo htmlspecialchars($product['brand']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $product['stock_quantity'] == 0 ? 'badge-error' : 'badge-warning'; ?>">
                                                        <?php echo $product['stock_quantity']; ?> units
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="add-product.php" class="quick-action-btn">
                                <i class="fas fa-plus-circle"></i>
                                <span>Add Product</span>
                            </a>
                            <a href="orders.php?status=pending" class="quick-action-btn">
                                <i class="fas fa-clock"></i>
                                <span>Pending Orders</span>
                            </a>
                            <a href="reviews.php" class="quick-action-btn">
                                <i class="fas fa-star"></i>
                                <span>Reviews</span>
                            </a>
                            <a href="customers.php" class="quick-action-btn">
                                <i class="fas fa-users"></i>
                                <span>Customers</span>
                            </a>
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

        /* =====================================================
   ADD THIS JAVASCRIPT TO YOUR <script> TAG
   (At the bottom of index.php, around line ~740)
   ===================================================== */

// ==================== THEME TOGGLE FUNCTIONS ====================

// Load theme on page load
document.addEventListener('DOMContentLoaded', function() {
    loadTheme();
});

// Toggle between dark and light theme
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('admin-theme', newTheme);
    updateThemeIcon(newTheme);
}

// Update theme icon based on current theme
function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (icon) {
        if (theme === 'dark') {
            icon.className = 'fas fa-moon';
        } else {
            icon.className = 'fas fa-sun';
        }
    }
}

// Load saved theme from localStorage
function loadTheme() {
    const savedTheme = localStorage.getItem('admin-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}
    </script>
</body>
</html>