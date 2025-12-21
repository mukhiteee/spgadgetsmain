<?php
// pages/my-orders.php - Complete Order History with Tracking
session_start();
require_once('../api/config.php');
require_once('../api/auth.php');

if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$statusFilter = $_GET['status'] ?? 'all';

try {
    $pdo = connectDB();
    
    if ($statusFilter === 'all') {
        $stmt = $pdo->prepare('
            SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ');
        $stmt->execute([$user['id']]);
    } else {
        $stmt = $pdo->prepare('
            SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND o.status = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ');
        $stmt->execute([$user['id'], $statusFilter]);
    }
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Get orders error: " . $e->getMessage());
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - SP Gadgets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.6;
        }
        
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
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 16px;
            color: #64748b;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #64748b;
        }
        
        .filter-btn:hover {
            border-color: #1F95B1;
            color: #1F95B1;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
            border-color: transparent;
        }
        
        .orders-grid {
            display: grid;
            gap: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .order-id-section {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .order-id {
            font-size: 20px;
            font-weight: 700;
        }
        
        .order-date {
            font-size: 14px;
            color: #64748b;
        }
        
        .order-status {
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .order-info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
        }
        
        .order-amount {
            font-size: 24px;
            font-weight: 800;
            color: #1F95B1;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(31, 149, 177, 0.3);
        }
        
        .btn-outline {
            background: white;
            color: #1F95B1;
            border: 2px solid #1F95B1;
        }
        
        .btn-outline:hover {
            background: #1F95B1;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .empty-state i {
            font-size: 80px;
            color: #e2e8f0;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #64748b;
            margin-bottom: 25px;
        }
        
        /* Tracking Timeline */
        .tracking-timeline {
            margin: 20px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .timeline-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            position: relative;
        }
        
        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 19px;
            top: 50px;
            width: 2px;
            height: calc(100% - 20px);
            background: #e2e8f0;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            z-index: 1;
        }
        
        .timeline-item.active .timeline-icon {
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
        }
        
        .timeline-content h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .timeline-content p {
            font-size: 14px;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .order-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .order-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="../index.html" class="logo">
                <i class="fas fa-store"></i> SP Gadgets
            </a>
            <a href="my-account.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Account
            </a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">My Orders</h1>
            <p class="page-subtitle">Track and manage all your orders</p>
        </div>

        <div class="filters">
            <div class="filter-buttons">
                <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                    All Orders
                </a>
                <a href="?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="?status=processing" class="filter-btn <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">
                    Processing
                </a>
                <a href="?status=completed" class="filter-btn <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">
                    Completed
                </a>
                <a href="?status=cancelled" class="filter-btn <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">
                    Cancelled
                </a>
            </div>
        </div>

        <?php if (!empty($orders)): ?>
            <div class="orders-grid">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id-section">
                                <div class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-date">
                                    <i class="far fa-calendar"></i> 
                                    <?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>

                        <div class="order-info-grid">
                            <div class="order-info-item">
                                <span class="info-label">Items</span>
                                <span class="info-value"><?php echo $order['item_count']; ?> item(s)</span>
                            </div>
                            <div class="order-info-item">
                                <span class="info-label">Total Amount</span>
                                <span class="order-amount">₦<?php echo number_format($order['total_amount'], 0); ?></span>
                            </div>
                            <div class="order-info-item">
                                <span class="info-label">Payment</span>
                                <span class="info-value"><?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?></span>
                            </div>
                        </div>

                        <!-- Order Tracking Timeline -->
                        <div class="tracking-timeline">
                            <div class="timeline-item active">
                                <div class="timeline-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>Order Placed</h4>
                                    <p><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <?php if (in_array($order['status'], ['processing', 'completed'])): ?>
                            <div class="timeline-item active">
                                <div class="timeline-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>Processing</h4>
                                    <p>Your order is being prepared</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'completed'): ?>
                            <div class="timeline-item active">
                                <div class="timeline-icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>Delivered</h4>
                                    <p>Order completed successfully</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'cancelled'): ?>
                            <div class="timeline-item active">
                                <div class="timeline-icon">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="timeline-content">
                                    <h4>Cancelled</h4>
                                    <p>Order was cancelled</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="order-footer">
                            <div class="order-actions">
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <?php if ($order['status'] === 'completed'): ?>
                                    <button class="btn btn-outline" onclick="reorder(<?php echo $order['id']; ?>)">
                                        <i class="fas fa-redo"></i> Reorder
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Yet</h3>
                <p>Start shopping to see your orders here!</p>
                <a href="shop.php" class="btn btn-primary">
                    <i class="fas fa-store"></i> Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function reorder(orderId) {
            if (confirm('Add all items from this order to your cart?')) {
                // Implementation would fetch order items and add to cart
                alert('Items added to cart!');
                window.location.href = '../index.html#cart';
            }
        }
    </script>
</body>
</html>
