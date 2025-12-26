<?php
// admin/reviews.php - Reviews Management
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Reviews';

// Handle review actions
if (isset($_POST['action'])) {
    try {
        $pdo = connectDB();
        
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare('UPDATE reviews SET status = "approved" WHERE id = ?');
            $stmt->execute([$_POST['review_id']]);
            $success = "Review approved successfully!";
        } elseif ($_POST['action'] === 'reject') {
            $stmt = $pdo->prepare('UPDATE reviews SET status = "rejected" WHERE id = ?');
            $stmt->execute([$_POST['review_id']]);
            $success = "Review rejected successfully!";
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
            $stmt->execute([$_POST['review_id']]);
            $success = "Review deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch reviews
try {
    $pdo = connectDB();
    
    // Build query
    $sql = 'SELECT r.*, 
            u.first_name, u.last_name, u.email,
            p.name as product_name, p.image as product_image
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN products p ON r.product_id = p.id
            WHERE 1=1';
    $params = [];
    
    if ($status) {
        $sql .= ' AND r.status = ?';
        $params[] = $status;
    }
    
    if ($rating) {
        $sql .= ' AND r.rating = ?';
        $params[] = $rating;
    }
    
    if ($search) {
        $sql .= ' AND (r.review_text LIKE ? OR p.name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Count total
    $countSql = str_replace('r.*, u.first_name, u.last_name, u.email, p.name as product_name, p.image as product_image', 'COUNT(*)', $sql);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalReviews = $countStmt->fetchColumn();
    $totalPages = ceil($totalReviews / $perPage);
    
    // Fetch reviews
    $sql .= ' ORDER BY r.created_at DESC LIMIT ? OFFSET ?';
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $statsStmt = $pdo->query('SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
        AVG(rating) as avg_rating
        FROM reviews');
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Reviews fetch error: " . $e->getMessage());
    $reviews = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - SP Gadgets Admin</title>
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

        .stat-card.pending {
            border-left: 3px solid var(--warning);
        }

        .stat-card.approved {
            border-left: 3px solid var(--success);
        }

        .stat-card.rejected {
            border-left: 3px solid var(--error);
        }

        .stat-card.rating {
            border-left: 3px solid var(--star);
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

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #0d8549;
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

        /* Review Cards */
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .review-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: background 0.2s;
        }

        .review-card:hover {
            background: var(--bg-hover);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .review-product {
            display: flex;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--bg-tertiary);
            flex-shrink: 0;
        }

        .product-info h4 {
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .product-info p {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .review-rating {
            display: flex;
            gap: 4px;
            margin-top: 4px;
        }

        .star {
            color: var(--star);
            font-size: 14px;
        }

        .star.empty {
            color: var(--text-tertiary);
        }

        .review-status {
            display: flex;
            gap: 8px;
            align-items: center;
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

        /* Review Content */
        .review-content {
            margin-bottom: 16px;
        }

        .review-text {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .review-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .review-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Review Actions */
        .review-actions {
            display: flex;
            gap: 8px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
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

            .review-header {
                flex-direction: column;
            }

            .review-status {
                width: 100%;
                justify-content: space-between;
            }

            .review-actions {
                flex-direction: column;
            }

            .review-actions .btn {
                width: 100%;
                justify-content: center;
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
                <a href="reviews.php" class="nav-item active">
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
                    <h1 class="page-title">Reviews</h1>
                </div>
                <div class="top-bar-right">
                    <form class="search-box" method="GET" action="">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search reviews..." value="<?php echo htmlspecialchars($search); ?>">
                    </form>
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
                    <a href="reviews.php" class="stat-card" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Total Reviews</div>
                        <div class="stat-value"><?php echo number_format($stats['total'] ?? 0); ?></div>
                    </a>
                    <a href="reviews.php?status=pending" class="stat-card pending" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Pending</div>
                        <div class="stat-value"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                    </a>
                    <a href="reviews.php?status=approved" class="stat-card approved" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Approved</div>
                        <div class="stat-value"><?php echo number_format($stats['approved'] ?? 0); ?></div>
                    </a>
                    <a href="reviews.php?status=rejected" class="stat-card rejected" style="text-decoration: none; color: inherit;">
                        <div class="stat-label">Rejected</div>
                        <div class="stat-value"><?php echo number_format($stats['rejected'] ?? 0); ?></div>
                    </a>
                    <div class="stat-card rating">
                        <div class="stat-label">Average Rating</div>
                        <div class="stat-value">
                            <i class="fas fa-star" style="color: var(--star);"></i>
                            <?php echo number_format($stats['avg_rating'] ?? 0, 1); ?>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="toolbar-left">
                        <form method="GET" style="display: contents;">
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>

                            <select name="rating" class="filter-select" onchange="this.form.submit()">
                                <option value="0">All Ratings</option>
                                <option value="5" <?php echo $rating === 5 ? 'selected' : ''; ?>>5 Stars</option>
                                <option value="4" <?php echo $rating === 4 ? 'selected' : ''; ?>>4 Stars</option>
                                <option value="3" <?php echo $rating === 3 ? 'selected' : ''; ?>>3 Stars</option>
                                <option value="2" <?php echo $rating === 2 ? 'selected' : ''; ?>>2 Stars</option>
                                <option value="1" <?php echo $rating === 1 ? 'selected' : ''; ?>>1 Star</option>
                            </select>

                            <?php if ($search): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <?php endif; ?>
                        </form>

                        <?php if ($search || $status || $rating): ?>
                            <a href="reviews.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i>
                                Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reviews List -->
                <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <i class="fas fa-star"></i>
                        <h3>No reviews found</h3>
                        <p>Try adjusting your filters</p>
                    </div>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-product">
                                        <img src="<?php echo htmlspecialchars($review['product_image'] ?: '../assets/products/placeholder.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                             class="product-thumb"
                                             onerror="this.src='../assets/products/placeholder.jpg'">
                                        <div class="product-info">
                                            <h4><?php echo htmlspecialchars($review['product_name']); ?></h4>
                                            <p>by <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></p>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'star' : 'star empty'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-status">
                                        <?php
                                        $statusClass = 'warning';
                                        if ($review['status'] == 'approved') $statusClass = 'success';
                                        if ($review['status'] == 'rejected') $statusClass = 'error';
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst($review['status']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="review-content">
                                    <div class="review-text">
                                        <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                    </div>
                                    <div class="review-meta">
                                        <div class="review-meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                        <div class="review-meta-item">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($review['email']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="review-actions">
                                    <?php if ($review['status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i>
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($review['status'] !== 'rejected'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-times"></i>
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button onclick="confirmDelete(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars(addslashes($review['product_name'])); ?>')" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>

                                    <a href="../pages/product-details.php?id=<?php echo $review['product_id']; ?>" class="btn btn-secondary btn-sm" target="_blank">
                                        <i class="fas fa-external-link-alt"></i>
                                        View Product
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $rating ? '&rating=' . $rating : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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
                                    <a href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $rating ? '&rating=' . $rating : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $rating ? '&rating=' . $rating : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="disabled">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Delete Review</h3>
                <button class="modal-close" onclick="closeDeleteModal()">×</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this review for "<strong id="productName"></strong>"?</p>
                <p style="color: var(--text-secondary); font-size: 14px; margin-top: 12px;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="review_id" id="deleteReviewId">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete Review
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        function confirmDelete(reviewId, productName) {
            document.getElementById('productName').textContent = productName;
            document.getElementById('deleteReviewId').value = reviewId;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
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

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeDeleteModal();
            }
        });

        
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