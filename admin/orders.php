<?php
// admin/orders.php - Orders Management
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Orders';

// Handle status update
if (isset($_POST['update_status'])) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        $success = "Order status updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Fetch orders
try {
    $pdo = connectDB();
    
    // Build query
    $sql = 'SELECT o.*, COUNT(oi.id) as item_count 
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE 1=1';
    $params = [];
    
    if ($status) {
        $sql .= ' AND o.order_status = ?';
        $params[] = $status;
    }
    
    if ($search) {
        $sql .= ' AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_email LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($dateFrom) {
        $sql .= ' AND DATE(o.created_at) >= ?';
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= ' AND DATE(o.created_at) <= ?';
        $params[] = $dateTo;
    }
    
    $sql .= ' GROUP BY o.id';
    
    // Count total
    $countSql = "SELECT COUNT(*) FROM ($sql) as count_table";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    $totalPages = ceil($totalOrders / $perPage);
    
    // Fetch orders
    $sql .= ' ORDER BY o.created_at DESC LIMIT ? OFFSET ?';
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $statsStmt = $pdo->query('SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN order_status = "pending" THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN order_status = "processing" THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN order_status = "completed" THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN order_status = "cancelled" THEN 1 ELSE 0 END) as cancelled
        FROM orders');
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Orders fetch error: " . $e->getMessage());
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - SP Gadgets Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
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

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Same as products.php */
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card.all {
            border-left: 3px solid var(--accent);
        }

        .stat-card.pending {
            border-left: 3px solid var(--warning);
        }

        .stat-card.processing {
            border-left: 3px solid var(--info);
        }

        .stat-card.completed {
            border-left: 3px solid var(--success);
        }

        .stat-card.cancelled {
            border-left: 3px solid var(--error);
        }

        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 500;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .toolbar-left {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select, .date-input {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            outline: none;
            transition: background 0.2s;
        }

        .filter-select:hover, .date-input:hover {
            background: var(--bg-hover);
        }

        .filter-select:focus, .date-input:focus {
            border-color: var(--accent);
        }

        .date-input {
            color-scheme: dark;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        /* Card */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
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
            padding: 16px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
            font-weight: 500;
        }

        td {
            padding: 16px;
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
            background: rgba(62, 166, 255, 0.15);
            color: var(--info);
        }

        /* Order Details */
        .order-number {
            font-weight: 500;
            color: var(--accent);
        }

        .customer-info h4 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .customer-info p {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Status Dropdown */
        .status-select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            outline: none;
        }

        .status-select:focus {
            border-color: var(--accent);
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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-state h3 {
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }

        .pagination a:hover {
            background: var(--bg-hover);
        }

        .pagination .active {
            background: var(--accent);
            border-color: var(--accent);
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Add these new styles for mobile responsiveness */

/* Mobile Table Card Layout */
@media (max-width: 968px) {
    .table-wrapper {
        overflow: visible;
    }

    table, thead, tbody, th, td, tr {
        display: block;
    }

    thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    tbody tr {
        margin-bottom: 16px;
        background: var(--bg-tertiary);
        border-radius: 12px;
        padding: 16px;
        border: 1px solid var(--border);
    }

    tbody tr:hover {
        background: var(--bg-hover);
    }

    td {
        border: none;
        position: relative;
        padding: 12px 12px 12px 45%;
        text-align: right;
        min-height: 45px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    td:before {
        content: attr(data-label);
        position: absolute;
        left: 16px;
        width: 40%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: 500;
        color: var(--text-secondary);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: left;
    }

    td:first-child {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    td:last-child {
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }

    .customer-info {
        text-align: right;
    }

    .customer-info h4 {
        font-size: 13px;
    }

    .customer-info p {
        font-size: 11px;
    }

    .order-number {
        font-size: 16px;
    }

    .status-select {
        width: 100%;
        max-width: 150px;
    }
}

/* Update existing responsive styles */
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

    .search-box {
        display: none;
    }

    .toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }

    .toolbar-left {
        width: 100%;
        flex-direction: column;
    }

    .filter-select, .date-input {
        width: 100%;
    }

    .stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .stat-card {
        padding: 16px;
    }

    .stat-value {
        font-size: 24px;
    }

    .top-bar {
        padding: 12px 16px;
    }

    .page-title {
        font-size: 18px;
    }

    .pagination {
        flex-wrap: wrap;
        padding: 16px;
        gap: 6px;
    }

    .pagination a,
    .pagination span {
        padding: 6px 10px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 14px;
    }

    .stat-label {
        font-size: 11px;
    }

    .stat-value {
        font-size: 22px;
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
                    <h1 class="page-title">Orders</h1>
                </div>
                <div class="top-bar-right">
                    <form class="search-box" method="GET" action="">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>">
                    </form>
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

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <a href="orders.php" class="stat-card all" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">All Orders</div>
                        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></div>
                    </a>
                    <a href="orders.php?status=pending" class="stat-card pending" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Pending</div>
                        <div class="stat-value"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                    </a>
                    <a href="orders.php?status=processing" class="stat-card processing" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Processing</div>
                        <div class="stat-value"><?php echo number_format($stats['processing'] ?? 0); ?></div>
                    </a>
                    <a href="orders.php?status=completed" class="stat-card completed" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Completed</div>
                        <div class="stat-value"><?php echo number_format($stats['completed'] ?? 0); ?></div>
                    </a>
                    <a href="orders.php?status=cancelled" class="stat-card cancelled" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Cancelled</div>
                        <div class="stat-value"><?php echo number_format($stats['cancelled'] ?? 0); ?></div>
                    </a>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="toolbar-left">
                        <form method="GET" style="display: contents;">
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>

                            <input type="date" name="date_from" class="date-input" value="<?php echo htmlspecialchars($dateFrom); ?>" onchange="this.form.submit()" placeholder="From">
                            <input type="date" name="date_to" class="date-input" value="<?php echo htmlspecialchars($dateTo); ?>" onchange="this.form.submit()" placeholder="To">

                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                        </form>

                        <?php if ($search || $status || $dateFrom || $dateTo): ?>
                            <a href="orders.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i>
                                Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-download"></i>
                        Export
                    </button>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-inbox"></i>
                                                <h3>No orders found</h3>
                                                <p>Try adjusting your filters</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr onclick="window.location.href='order-details.php?id=<?php echo $order['id']; ?>'">
                                            <td data-label="Order">
                                                <span class="order-number">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                            </td>
                                            <td data-label="Customer">
                                                <div class="customer-info">
                                                    <h4><?php echo htmlspecialchars($order['customer_name']); ?></h4>
                                                    <p><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                                </div>
                                            </td>
                                            <td data-label="Date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td data-label="Items"><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></td>
                                            <td data-label="Total"><strong>₦<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                            <td data-label="Status">
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
                                            </td>
                                            <td data-label="Actions" onclick="event.stopPropagation()">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="completed" <?php echo $order['order_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php else: ?>
                                <span class="disabled">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="disabled">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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