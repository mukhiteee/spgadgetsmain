<?php
// admin/customers.php - Customer Management
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Customers';

// Handle customer actions
if (isset($_POST['action'])) {
    try {
        $pdo = connectDB();
        
        if ($_POST['action'] === 'block') {
            $stmt = $pdo->prepare('UPDATE users SET status = "blocked" WHERE id = ?');
            $stmt->execute([$_POST['customer_id']]);
            $success = "Customer blocked successfully!";
        } elseif ($_POST['action'] === 'unblock') {
            $stmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?');
            $stmt->execute([$_POST['customer_id']]);
            $success = "Customer unblocked successfully!";
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$_POST['customer_id']]);
            $success = "Customer deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Fetch customers
try {
    $pdo = connectDB();
    
    // Build query
    $sql = 'SELECT u.*, 
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            WHERE 1=1';
    $params = [];
    
    if ($search) {
        $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($status) {
        $sql .= ' AND u.status = ?';
        $params[] = $status;
    }
    
    $sql .= ' GROUP BY u.id';
    
    // Apply sorting
    switch ($sortBy) {
        case 'name':
            $sql .= ' ORDER BY u.first_name ASC';
            break;
        case 'orders':
            $sql .= ' ORDER BY total_orders DESC';
            break;
        case 'spent':
            $sql .= ' ORDER BY total_spent DESC';
            break;
        case 'oldest':
            $sql .= ' ORDER BY u.created_at ASC';
            break;
        case 'newest':
        default:
            $sql .= ' ORDER BY u.created_at DESC';
            break;
    }
    
    // Count total
    $countSql = "SELECT COUNT(*) FROM ($sql) as count_table";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCustomers = $countStmt->fetchColumn();
    $totalPages = ceil($totalCustomers / $perPage);
    
    // Fetch customers with pagination
    $sql .= ' LIMIT ? OFFSET ?';
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $statsStmt = $pdo->query('SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = "blocked" THEN 1 ELSE 0 END) as blocked,
        SUM(CASE WHEN DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
        FROM users');
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Customers fetch error: " . $e->getMessage());
    $customers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - SP Gadgets Admin</title>
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
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

        .filter-select {
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

        .filter-select:hover {
            background: var(--bg-hover);
        }

        .filter-select:focus {
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

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #b71c1c;
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

        /* Customer Info */
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 16px;
            flex-shrink: 0;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-details h4 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .customer-details p {
            font-size: 12px;
            color: var(--text-secondary);
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

        .badge-error {
            background: rgba(221, 44, 0, 0.15);
            color: var(--error);
        }

        /* Actions Dropdown */
        .actions-dropdown {
            position: relative;
        }

        .actions-btn {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .actions-menu {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 4px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            min-width: 150px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: none;
            z-index: 10;
        }

        .actions-dropdown.active .actions-menu {
            display: block;
        }

        .actions-menu button {
            width: 100%;
            padding: 10px 16px;
            border: none;
            background: none;
            color: var(--text-primary);
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .actions-menu button:hover {
            background: var(--bg-hover);
        }

        .actions-menu button:first-child {
            border-radius: 8px 8px 0 0;
        }

        .actions-menu button:last-child {
            border-radius: 0 0 8px 8px;
        }

        .actions-menu button.danger {
            color: var(--error);
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

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            border: 1px solid var(--border);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 500;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .modal-close:hover {
            background: var(--bg-hover);
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Mobile Responsive */
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
                justify-content: flex-end;
            }

            .customer-details {
                text-align: right;
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

            .search-box {
                display: none;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-left {
                width: 100%;
                flex-direction: column;
            }

            .filter-select {
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
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a href="customers.php" class="nav-item active">
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
                    <h1 class="page-title">Customers</h1>
                </div>
                <div class="top-bar-right">
                    <form class="search-box" method="GET" action="">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
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
                    <div class="stat-card">
                        <div class="stat-label">Total Customers</div>
                        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Active Customers</div>
                        <div class="stat-value"><?php echo number_format($stats['active'] ?? 0); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Blocked Customers</div>
                        <div class="stat-value"><?php echo number_format($stats['blocked'] ?? 0); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">New This Month</div>
                        <div class="stat-value"><?php echo number_format($stats['new_this_month'] ?? 0); ?></div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="toolbar-left">
                        <form method="GET" style="display: contents;">
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                            </select>

                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="orders" <?php echo $sortBy === 'orders' ? 'selected' : ''; ?>>Most Orders</option>
                                <option value="spent" <?php echo $sortBy === 'spent' ? 'selected' : ''; ?>>Highest Spent</option>
                            </select>

                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                        </form>

                        <?php if ($search || $status): ?>
                            <a href="customers.php" class="btn btn-secondary btn-sm">
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

                <!-- Customers Table -->
                <div class="card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Joined</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-users"></i>
                                                <h3>No customers found</h3>
                                                <p>Try adjusting your filters</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td data-label="Customer">
                                                <div class="customer-info">
                                                    <div class="customer-avatar">
                                                        <?php echo strtoupper(substr($customer['first_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="customer-details">
                                                        <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                                                        <p><?php echo htmlspecialchars($customer['email']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Phone"><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                                            <td data-label="Joined"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                            <td data-label="Orders"><strong><?php echo number_format($customer['total_orders']); ?></strong></td>
                                            <td data-label="Total Spent"><strong>₦<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                            <td data-label="Status">
                                                <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : 'error'; ?>">
                                                    <?php echo ucfirst($customer['status']); ?>
                                                </span>
                                            </td>
                                            <td data-label="Actions" onclick="event.stopPropagation()">
                                                <div class="actions-dropdown" onclick="toggleActions(this, event)">
                                                    <button class="actions-btn">
                                                        Actions
                                                        <i class="fas fa-chevron-down"></i>
                                                    </button>
                                                    <div class="actions-menu">
                                                        <button onclick="viewCustomer(<?php echo $customer['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                            View Details
                                                        </button>
                                                        <?php if ($customer['status'] === 'active'): ?>
                                                            <button onclick="confirmAction(<?php echo $customer['id']; ?>, 'block', '<?php echo htmlspecialchars(addslashes($customer['first_name'] . ' ' . $customer['last_name'])); ?>')">
                                                                <i class="fas fa-ban"></i>
                                                                Block Customer
                                                            </button>
                                                        <?php else: ?>
                                                            <button onclick="confirmAction(<?php echo $customer['id']; ?>, 'unblock', '<?php echo htmlspecialchars(addslashes($customer['first_name'] . ' ' . $customer['last_name'])); ?>')">
                                                                <i class="fas fa-check"></i>
                                                                Unblock Customer
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="danger" onclick="confirmAction(<?php echo $customer['id']; ?>, 'delete', '<?php echo htmlspecialchars(addslashes($customer['first_name'] . ' ' . $customer['last_name'])); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                            Delete Customer
                                                        </button>
                                                    </div>
                                                </div>
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
                                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $sortBy !== 'newest' ? '&sort=' . urlencode($sortBy) : ''; ?>">
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
                                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $sortBy !== 'newest' ? '&sort=' . urlencode($sortBy) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $sortBy !== 'newest' ? '&sort=' . urlencode($sortBy) : ''; ?>">
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

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Confirm Action</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="customer_id" id="customerId">
                    <input type="hidden" name="action" id="actionType">
                    <button type="submit" class="btn btn-danger" id="confirmBtn">
                        Confirm
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        function toggleActions(element, event) {
            event.stopPropagation();
            
            // Close all other dropdowns
            document.querySelectorAll('.actions-dropdown').forEach(dropdown => {
                if (dropdown !== element) {
                    dropdown.classList.remove('active');
                }
            });
            
            element.classList.toggle('active');
        }

        function viewCustomer(customerId) {
            window.location.href = `customer-details.php?id=${customerId}`;
        }

        function confirmAction(customerId, action, customerName) {
            const modal = document.getElementById('confirmModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('confirmBtn');
            
            document.getElementById('customerId').value = customerId;
            document.getElementById('actionType').value = action;
            
            if (action === 'block') {
                modalTitle.textContent = 'Block Customer';
                modalMessage.textContent = `Are you sure you want to block "${customerName}"? They will not be able to place orders.`;
                confirmBtn.textContent = 'Block Customer';
                confirmBtn.className = 'btn btn-danger';
            } else if (action === 'unblock') {
                modalTitle.textContent = 'Unblock Customer';
                modalMessage.textContent = `Are you sure you want to unblock "${customerName}"?`;
                confirmBtn.textContent = 'Unblock Customer';
                confirmBtn.className = 'btn btn-primary';
            } else if (action === 'delete') {
                modalTitle.textContent = 'Delete Customer';
                modalMessage.textContent = `Are you sure you want to delete "${customerName}"? This action cannot be undone.`;
                confirmBtn.textContent = 'Delete Customer';
                confirmBtn.className = 'btn btn-danger';
            }
            
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.actions-dropdown')) {
                document.querySelectorAll('.actions-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Close modal when clicking outside
        document.getElementById('confirmModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
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