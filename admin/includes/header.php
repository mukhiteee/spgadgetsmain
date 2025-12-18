<?php
// admin/includes/header.php - Common Header for All Admin Pages
if (!defined('ADMIN_PAGE')) {
    die('Direct access not permitted');
}

$currentAdmin = getCurrentAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - SP Gadgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1F95B1;
            --primary-dark: #0f172a;
            --sidebar-width: 260px;
            --header-height: 70px;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--primary-dark);
            color: white;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 25px;
        }

        .menu-item.active {
            background: var(--primary);
            color: white;
        }

        .menu-item i {
            width: 20px;
            font-size: 1.1rem;
        }

        /* Top Header */
        .top-header {
            position: fixed;
            left: var(--sidebar-width);
            top: 0;
            right: 0;
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            z-index: 999;
        }

        .header-left h1 {
            font-size: 1.5rem;
            color: var(--primary-dark);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .logout-btn {
            padding: 8px 20px;
            background: var(--danger);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 30px;
            min-height: calc(100vh - var(--header-height));
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            z-index: 1001;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header h3 {
            font-size: 1.3rem;
            color: var(--primary-dark);
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: black; }

        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        /* Badges */
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-primary { background: #cce5ff; color: #004085; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -var(--sidebar-width);
            }

            .sidebar.active {
                left: 0;
            }

            .top-header {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .header-left h1 {
                font-size: 1.2rem;
                margin-left: 50px;
            }

            .admin-info span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle -->
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-store"></i> SP Gadgets</h2>
            <p style="font-size: 0.85rem; opacity: 0.7; margin-top: 5px;">Admin Panel</p>
        </div>

        <nav class="sidebar-menu">
            <a href="index.php" class="menu-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="products.php" class="menu-item <?php echo in_array($currentPage, ['products', 'add-product', 'edit-product']) ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="orders.php" class="menu-item <?php echo in_array($currentPage, ['orders', 'order-details']) ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="reviews.php" class="menu-item <?php echo $currentPage === 'reviews' ? 'active' : ''; ?>">
                <i class="fas fa-star"></i> Reviews
            </a>
            <a href="customers.php" class="menu-item <?php echo $currentPage === 'customers' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
            <a href="settings.php" class="menu-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>
    </aside>

    <!-- Top Header -->
    <header class="top-header">
        <div class="header-left">
            <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
        </div>
        <div class="header-right">
            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($currentAdmin['username'] ?? 'A', 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($currentAdmin['username'] ?? 'Admin'); ?></span>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
