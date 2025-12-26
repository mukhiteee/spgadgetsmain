<?php
// admin/analytics.php - Advanced Analytics Dashboard
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Analytics';

// Date range filters
$dateRange = isset($_GET['range']) ? $_GET['range'] : '30days';
$startDate = null;
$endDate = date('Y-m-d');

switch ($dateRange) {
    case '7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        break;
    case '6months':
        $startDate = date('Y-m-d', strtotime('-6 months'));
        break;
    case '1year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    case 'custom':
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
}

try {
    $pdo = connectDB();
    
    // === REVENUE ANALYTICS ===
    
    // Total revenue in date range
    $stmt = $pdo->prepare('SELECT 
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        COUNT(*) as total_orders
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status != "cancelled"');
    $stmt->execute([$startDate, $endDate]);
    $revenueStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Previous period comparison
    $prevStart = date('Y-m-d', strtotime($startDate . ' -' . (strtotime($endDate) - strtotime($startDate)) . ' seconds'));
    $prevEnd = date('Y-m-d', strtotime($startDate . ' -1 day'));
    
    $stmt = $pdo->prepare('SELECT 
        SUM(total_amount) as prev_revenue,
        COUNT(*) as prev_orders
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status != "cancelled"');
    $stmt->execute([$prevStart, $prevEnd]);
    $prevStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate growth
    $revenueGrowth = $prevStats['prev_revenue'] > 0 
        ? (($revenueStats['total_revenue'] - $prevStats['prev_revenue']) / $prevStats['prev_revenue']) * 100 
        : 0;
    $ordersGrowth = $prevStats['prev_orders'] > 0 
        ? (($revenueStats['total_orders'] - $prevStats['prev_orders']) / $prevStats['prev_orders']) * 100 
        : 0;
    
    // Daily revenue trend
    $stmt = $pdo->prepare('SELECT 
        DATE(created_at) as date,
        SUM(total_amount) as revenue,
        COUNT(*) as orders
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status != "cancelled"
        GROUP BY DATE(created_at)
        ORDER BY date ASC');
    $stmt->execute([$startDate, $endDate]);
    $dailyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Revenue by payment method
    $stmt = $pdo->prepare('SELECT 
        payment_method,
        SUM(total_amount) as revenue,
        COUNT(*) as orders
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status != "cancelled"
        GROUP BY payment_method
        ORDER BY revenue DESC');
    $stmt->execute([$startDate, $endDate]);
    $revenueByPayment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // === PRODUCT ANALYTICS ===
    
    // Top selling products
    $stmt = $pdo->prepare('SELECT 
        p.id, p.name, p.category, p.price, p.image,
        SUM(oi.quantity) as units_sold,
        SUM(oi.quantity * oi.price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND o.order_status != "cancelled"
        GROUP BY p.id
        ORDER BY revenue DESC
        LIMIT 10');
    $stmt->execute([$startDate, $endDate]);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sales by category
    $stmt = $pdo->prepare('SELECT 
        p.category,
        SUM(oi.quantity) as units_sold,
        SUM(oi.quantity * oi.price) as revenue,
        COUNT(DISTINCT p.id) as product_count
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND o.order_status != "cancelled"
        GROUP BY p.category
        ORDER BY revenue DESC');
    $stmt->execute([$startDate, $endDate]);
    $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Low performing products
    $stmt = $pdo->prepare('SELECT 
        p.id, p.name, p.category, p.price, p.stock_quantity,
        COALESCE(SUM(oi.quantity), 0) as units_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id 
            AND DATE(o.created_at) BETWEEN ? AND ?
            AND o.order_status != "cancelled"
        WHERE p.stock_quantity > 0
        GROUP BY p.id
        HAVING units_sold < 5
        ORDER BY units_sold ASC
        LIMIT 10');
    $stmt->execute([$startDate, $endDate]);
    $lowPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // === CUSTOMER ANALYTICS ===
    
    // Customer stats
    $stmt = $pdo->prepare('SELECT 
        COUNT(DISTINCT user_id) as total_customers,
        COUNT(*) as total_orders,
        AVG(order_count) as avg_orders_per_customer
        FROM (
            SELECT user_id, COUNT(*) as order_count
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND order_status != "cancelled"
            GROUP BY user_id
        ) as customer_orders');
    $stmt->execute([$startDate, $endDate]);
    $customerStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // New vs returning customers
    $stmt = $pdo->prepare('SELECT 
        SUM(CASE WHEN order_count = 1 THEN 1 ELSE 0 END) as new_customers,
        SUM(CASE WHEN order_count > 1 THEN 1 ELSE 0 END) as returning_customers
        FROM (
            SELECT user_id, COUNT(*) as order_count
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND order_status != "cancelled"
            GROUP BY user_id
        ) as customer_orders');
    $stmt->execute([$startDate, $endDate]);
    $customerType = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Top customers
    $stmt = $pdo->prepare('SELECT 
        u.id, u.first_name, u.last_name, u.email,
        COUNT(o.id) as order_count,
        SUM(o.total_amount) as total_spent
        FROM users u
        JOIN orders o ON u.id = o.user_id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND o.order_status != "cancelled"
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 10');
    $stmt->execute([$startDate, $endDate]);
    $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Customer acquisition by day
    $stmt = $pdo->prepare('SELECT 
        DATE(created_at) as date,
        COUNT(*) as new_customers
        FROM users
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC');
    $stmt->execute([$startDate, $endDate]);
    $customerAcquisition = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // === ORDER ANALYTICS ===
    
    // Order status distribution
    $stmt = $pdo->prepare('SELECT 
        order_status,
        COUNT(*) as count,
        SUM(total_amount) as revenue
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY order_status
        ORDER BY count DESC');
    $stmt->execute([$startDate, $endDate]);
    $orderStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Orders by hour of day
    $stmt = $pdo->prepare('SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as order_count
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC');
    $stmt->execute([$startDate, $endDate]);
    $ordersByHour = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Orders by day of week
    $stmt = $pdo->prepare('SELECT 
        DAYNAME(created_at) as day_name,
        DAYOFWEEK(created_at) as day_num,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status != "cancelled"
        GROUP BY DAYNAME(created_at), DAYOFWEEK(created_at)
        ORDER BY day_num ASC');
    $stmt->execute([$startDate, $endDate]);
    $ordersByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Average fulfillment time
    $stmt = $pdo->prepare('SELECT 
        AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_fulfillment_hours
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status = "completed"');
    $stmt->execute([$startDate, $endDate]);
    $fulfillmentStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // === INVENTORY ANALYTICS ===
    
    // Stock status
    $stmt = $pdo->query('SELECT 
        SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
        SUM(CASE WHEN stock_quantity > 0 AND stock_quantity < 10 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN stock_quantity >= 10 THEN 1 ELSE 0 END) as in_stock,
        SUM(stock_quantity) as total_units,
        SUM(stock_quantity * price) as inventory_value
        FROM products');
    $inventoryStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Products by condition
    $stmt = $pdo->query('SELECT 
        `condition`,
        COUNT(*) as product_count,
        SUM(stock_quantity) as total_units
        FROM products
        GROUP BY `condition`');
    $productsByCondition = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // === REVIEW ANALYTICS ===
    
    // Review stats
    $stmt = $pdo->prepare('SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
        FROM reviews
        WHERE DATE(created_at) BETWEEN ? AND ?');
    $stmt->execute([$startDate, $endDate]);
    $reviewStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // === LOCATION ANALYTICS ===
    
    // Orders by city
    $stmt = $pdo->prepare('SELECT 
        shipping_city,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status != "cancelled"
        AND shipping_city IS NOT NULL
        GROUP BY shipping_city
        ORDER BY revenue DESC
        LIMIT 10');
    $stmt->execute([$startDate, $endDate]);
    $ordersByCity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Orders by state
    $stmt = $pdo->prepare('SELECT 
        shipping_state,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND order_status != "cancelled"
        AND shipping_state IS NOT NULL
        GROUP BY shipping_state
        ORDER BY revenue DESC');
    $stmt->execute([$startDate, $endDate]);
    $ordersByState = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - SP Gadgets Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            --purple: #9c27b0;
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

        /* Date Range Filters */
        .filters-bar {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .filter-label {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .filter-btn:hover {
            background: var(--bg-hover);
        }

        .filter-btn.active {
            background: var(--accent);
            border-color: var(--accent);
        }

        .date-input {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            color-scheme: dark;
        }

        .date-input:focus {
            border-color: var(--accent);
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

        .stat-header {
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

        .stat-icon.customers {
            background: rgba(249, 171, 0, 0.15);
            color: var(--warning);
        }

        .stat-icon.products {
            background: rgba(156, 39, 176, 0.15);
            color: var(--purple);
        }

        .stat-icon.reviews {
            background: rgba(62, 166, 255, 0.15);
            color: var(--info);
        }

        .stat-label {
            font-size: 13px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .stat-change {
            font-size: 13px;
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

        /* Cards */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 24px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body {
            padding: 20px;
        }

        /* Charts */
        .chart-container {
            position: relative;
            height: 300px;
        }

        .chart-container.large {
            height: 400px;
        }

        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
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

        tbody tr:hover {
            background: var(--bg-hover);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Product Item */
        .product-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--bg-tertiary);
        }

        .product-details h4 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .product-details p {
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

        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: var(--accent);
            border-radius: 4px;
            transition: width 0.3s;
        }

        /* List Items */
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item-label {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .list-item-value {
            font-size: 16px;
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
            margin-bottom: 12px;
            opacity: 0.3;
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

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .grid-2, .grid-3 {
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

            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                padding: 12px 16px;
            }

            .page-title {
                font-size: 18px;
            }

            .chart-container {
                height: 250px;
            }

            .chart-container.large {
                height: 300px;
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
                    <h1 class="page-title">Analytics</h1>
                </div>
                <div class="top-bar-right">
                    <button class="icon-btn" onclick="window.print()">
                        <i class="fas fa-download"></i>
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
                <!-- Date Range Filters -->
                <div class="filters-bar">
                    <div class="filter-group">
                        <span class="filter-label">Date Range:</span>
                        <a href="?range=7days" class="filter-btn <?php echo $dateRange === '7days' ? 'active' : ''; ?>">
                            Last 7 Days
                        </a>
                        <a href="?range=30days" class="filter-btn <?php echo $dateRange === '30days' ? 'active' : ''; ?>">
                            Last 30 Days
                        </a>
                        <a href="?range=90days" class="filter-btn <?php echo $dateRange === '90days' ? 'active' : ''; ?>">
                            Last 90 Days
                        </a>
                        <a href="?range=6months" class="filter-btn <?php echo $dateRange === '6months' ? 'active' : ''; ?>">
                            Last 6 Months
                        </a>
                        <a href="?range=1year" class="filter-btn <?php echo $dateRange === '1year' ? 'active' : ''; ?>">
                            Last Year
                        </a>
                    </div>

                    <form method="GET" class="filter-group">
                        <input type="hidden" name="range" value="custom">
                        <input type="date" name="start_date" class="date-input" 
                               value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                        <span style="color: var(--text-secondary);">to</span>
                        <input type="date" name="end_date" class="date-input" 
                               value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </form>
                </div>

                <!-- Key Metrics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Total Revenue</div>
                                <div class="stat-value">₦<?php echo number_format($revenueStats['total_revenue'] ?? 0, 2); ?></div>
                                <div class="stat-change <?php echo $revenueGrowth >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $revenueGrowth >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo number_format(abs($revenueGrowth), 1); ?>% vs previous period
                                </div>
                            </div>
                            <div class="stat-icon revenue">
                                <i class="fas fa-naira-sign"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Total Orders</div>
                                <div class="stat-value"><?php echo number_format($revenueStats['total_orders'] ?? 0); ?></div>
                                <div class="stat-change <?php echo $ordersGrowth >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-arrow-<?php echo $ordersGrowth >= 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo number_format(abs($ordersGrowth), 1); ?>% vs previous period
                                </div>
                            </div>
                            <div class="stat-icon orders">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Avg Order Value</div>
                                <div class="stat-value">₦<?php echo number_format($revenueStats['avg_order_value'] ?? 0, 2); ?></div>
                                <div class="stat-change">
                                    <i class="fas fa-info-circle"></i>
                                    Per order average
                                </div>
                            </div>
                            <div class="stat-icon revenue">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Total Customers</div>
                                <div class="stat-value"><?php echo number_format($customerStats['total_customers'] ?? 0); ?></div>
                                <div class="stat-change">
                                    <i class="fas fa-user-plus"></i>
                                    <?php echo number_format($customerAcquisition ? count($customerAcquisition) : 0); ?> new this period
                                </div>
                            </div>
                            <div class="stat-icon customers">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Inventory Value</div>
                                <div class="stat-value">₦<?php echo number_format($inventoryStats['inventory_value'] ?? 0, 2); ?></div>
                                <div class="stat-change">
                                    <i class="fas fa-box"></i>
                                    <?php echo number_format($inventoryStats['total_units'] ?? 0); ?> units in stock
                                </div>
                            </div>
                            <div class="stat-icon products">
                                <i class="fas fa-warehouse"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Average Rating</div>
                                <div class="stat-value">
                                    <i class="fas fa-star" style="color: #ffc107;"></i>
                                    <?php echo number_format($reviewStats['avg_rating'] ?? 0, 1); ?>
                                </div>
                                <div class="stat-change">
                                    <i class="fas fa-comment"></i>
                                    <?php echo number_format($reviewStats['total_reviews'] ?? 0); ?> reviews
                                </div>
                            </div>
                            <div class="stat-icon reviews">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i>
                            Revenue Trend
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container large">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Category Performance & Order Status -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tags"></i>
                                Sales by Category
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tasks"></i>
                                Order Status Distribution
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Analytics -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-friends"></i>
                                New vs Returning Customers
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="customerTypeChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-clock"></i>
                                Orders by Hour of Day
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="hourlyChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders by Day of Week -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-week"></i>
                            Orders by Day of Week
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dayOfWeekChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods & Location Analytics -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-credit-card"></i>
                                Revenue by Payment Method
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($revenueByPayment)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-credit-card"></i>
                                    <p>No payment data available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($revenueByPayment as $payment): ?>
                                    <div class="list-item">
                                        <div>
                                            <div class="list-item-label">
                                                <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                                            </div>
                                            <div style="font-size: 12px; color: var(--text-tertiary);">
                                                <?php echo $payment['orders']; ?> orders
                                            </div>
                                        </div>
                                        <div class="list-item-value">
                                            ₦<?php echo number_format($payment['revenue'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo ($payment['revenue'] / $revenueStats['total_revenue']) * 100; ?>%"></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-map-marker-alt"></i>
                                Top Cities by Revenue
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($ordersByCity)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <p>No location data available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($ordersByCity, 0, 5) as $city): ?>
                                    <div class="list-item">
                                        <div>
                                            <div class="list-item-label"><?php echo htmlspecialchars($city['shipping_city']); ?></div>
                                            <div style="font-size: 12px; color: var(--text-tertiary);">
                                                <?php echo $city['order_count']; ?> orders
                                            </div>
                                        </div>
                                        <div class="list-item-value">
                                            ₦<?php echo number_format($city['revenue'], 2); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Products Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-trophy"></i>
                            Top Selling Products
                        </h3>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-box-open"></i>
                                                <p>No sales data available</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topProducts as $index => $product): ?>
                                        <tr>
                                            <td><strong><?php echo $index + 1; ?></strong></td>
                                            <td>
                                                <div class="product-item">
                                                    <img src="../<?php echo htmlspecialchars($product['image'] ?: 'assets/products/placeholder.jpg'); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         class="product-thumb"
                                                         onerror="this.src='../assets/products/placeholder.jpg'">
                                                    <div class="product-details">
                                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                        <p>₦<?php echo number_format($product['price'], 2); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars(ucfirst($product['category'])); ?></td>
                                            <td><strong><?php echo number_format($product['units_sold']); ?></strong></td>
                                            <td><strong>₦<?php echo number_format($product['revenue'], 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Customers Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-tie"></i>
                            Top Customers
                        </h3>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topCustomers)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-users"></i>
                                                <p>No customer data available</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topCustomers as $index => $customer): ?>
                                        <tr onclick="window.location.href='customer-details.php?id=<?php echo $customer['id']; ?>'">
                                            <td><strong><?php echo $index + 1; ?></strong></td>
                                            <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                            <td><strong><?php echo $customer['order_count']; ?></strong></td>
                                            <td><strong>₦<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Low Performing Products -->
                <?php if (!empty($lowPerformers)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
                            Low Performing Products
                        </h3>
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Units Sold</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowPerformers as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($product['category'])); ?></td>
                                        <td><?php echo $product['stock_quantity']; ?> units</td>
                                        <td>
                                            <span class="badge badge-warning">
                                                <?php echo $product['units_sold']; ?> sold
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;">
                                                <i class="fas fa-edit"></i>
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Review Ratings Distribution -->
                <?php if ($reviewStats['total_reviews'] > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-star"></i>
                            Review Ratings Distribution
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php 
                        $ratingData = [
                            ['stars' => 5, 'count' => $reviewStats['five_star']],
                            ['stars' => 4, 'count' => $reviewStats['four_star']],
                            ['stars' => 3, 'count' => $reviewStats['three_star']],
                            ['stars' => 2, 'count' => $reviewStats['two_star']],
                            ['stars' => 1, 'count' => $reviewStats['one_star']]
                        ];
                        ?>
                        <?php foreach ($ratingData as $rating): ?>
                            <div class="list-item">
                                <div class="list-item-label">
                                    <?php for ($i = 0; $i < $rating['stars']; $i++): ?>
                                        <i class="fas fa-star" style="color: #ffc107; font-size: 12px;"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="list-item-value">
                                    <?php echo $rating['count']; ?> reviews 
                                    (<?php echo $reviewStats['total_reviews'] > 0 ? round(($rating['count'] / $reviewStats['total_reviews']) * 100, 1) : 0; ?>%)
                                </div>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $reviewStats['total_reviews'] > 0 ? ($rating['count'] / $reviewStats['total_reviews']) * 100 : 0; ?>%; background: #ffc107;"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stock Status Overview -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-warehouse"></i>
                            Inventory Status Overview
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="grid-3">
                            <div>
                                <div class="stat-label">In Stock</div>
                                <div class="stat-value" style="color: var(--success);">
                                    <?php echo number_format($inventoryStats['in_stock'] ?? 0); ?>
                                </div>
                                <p style="font-size: 12px; color: var(--text-secondary);">Products available</p>
                            </div>
                            <div>
                                <div class="stat-label">Low Stock</div>
                                <div class="stat-value" style="color: var(--warning);">
                                    <?php echo number_format($inventoryStats['low_stock'] ?? 0); ?>
                                </div>
                                <p style="font-size: 12px; color: var(--text-secondary);">Need restocking</p>
                            </div>
                            <div>
                                <div class="stat-label">Out of Stock</div>
                                <div class="stat-value" style="color: var(--error);">
                                    <?php echo number_format($inventoryStats['out_of_stock'] ?? 0); ?>
                                </div>
                                <p style="font-size: 12px; color: var(--text-secondary);">Unavailable products</p>
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

        // Chart.js Configuration
        Chart.defaults.color = '#aaaaaa';
        Chart.defaults.borderColor = '#3f3f3f';

        // Revenue Trend Chart
        const revenueData = <?php echo json_encode($dailyRevenue); ?>;
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(d => d.date),
                datasets: [{
                    label: 'Revenue (₦)',
                    data: revenueData.map(d => d.revenue),
                    borderColor: '#1F95B1',
                    backgroundColor: 'rgba(31, 149, 177, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Orders',
                    data: revenueData.map(d => d.orders),
                    borderColor: '#0f9d58',
                    backgroundColor: 'rgba(15, 157, 88, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (₦)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Category Sales Chart
        const categoryData = <?php echo json_encode($categoryStats); ?>;
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(c => c.category.charAt(0).toUpperCase() + c.category.slice(1)),
                datasets: [{
                    data: categoryData.map(c => c.revenue),
                    backgroundColor: [
                        '#1F95B1',
                        '#0f9d58',
                        '#f9ab00',
                        '#dd2c00',
                        '#9c27b0',
                        '#3ea6ff'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Order Status Chart
        const statusData = <?php echo json_encode($orderStatus); ?>;
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: statusData.map(s => s.order_status.charAt(0).toUpperCase() + s.order_status.slice(1)),
                datasets: [{
                    data: statusData.map(s => s.count),
                    backgroundColor: [
                        '#f9ab00',
                        '#3ea6ff',
                        '#0f9d58',
                        '#dd2c00',
                        '#9c27b0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Customer Type Chart
        const customerTypeCtx = document.getElementById('customerTypeChart').getContext('2d');
        new Chart(customerTypeCtx, {
            type: 'bar',
            data: {
                labels: ['New Customers', 'Returning Customers'],
                datasets: [{
                    data: [
                        <?php echo $customerType['new_customers'] ?? 0; ?>,
                        <?php echo $customerType['returning_customers'] ?? 0; ?>
                    ],
                    backgroundColor: ['#1F95B1', '#0f9d58']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Hourly Orders Chart
        const hourlyData = <?php echo json_encode($ordersByHour); ?>;
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        
        // Create array with all 24 hours
        const allHours = Array.from({length: 24}, (_, i) => i);
        const hourlyOrders = allHours.map(hour => {
            const found = hourlyData.find(h => parseInt(h.hour) === hour);
            return found ? found.order_count : 0;
        });
        
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: allHours.map(h => `${h}:00`),
                datasets: [{
                    label: 'Orders',
                    data: hourlyOrders,
                    backgroundColor: '#1F95B1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Day of Week Chart
        const dayData = <?php echo json_encode($ordersByDay); ?>;
        const dayCtx = document.getElementById('dayOfWeekChart').getContext('2d');
        new Chart(dayCtx, {
            type: 'bar',
            data: {
                labels: dayData.map(d => d.day_name),
                datasets: [{
                    label: 'Orders',
                    data: dayData.map(d => d.order_count),
                    backgroundColor: '#1F95B1',
                    yAxisID: 'y'
                }, {
                    label: 'Revenue (₦)',
                    data: dayData.map(d => d.revenue),
                    backgroundColor: '#0f9d58',
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Orders'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Revenue (₦)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>