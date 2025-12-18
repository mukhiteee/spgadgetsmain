<?php
// admin/index.php - Admin Dashboard
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
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

include('includes/header.php');
?>

<!-- Dashboard Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <!-- Total Revenue -->
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">Total Revenue</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo formatCurrency($totalRevenue); ?></h2>
                <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">All time</p>
            </div>
            <div style="font-size: 3rem; opacity: 0.3;">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div style="display: flex; justify-space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">Total Orders</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo number_format($totalOrders); ?></h2>
                <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">
                    <?php echo $todayOrders; ?> today
                </p>
            </div>
            <div style="font-size: 3rem; opacity: 0.3;">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
    </div>

    <!-- Total Products -->
    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">Total Products</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo number_format($totalProducts); ?></h2>
                <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">
                    <?php echo $lowStockCount; ?> low stock
                </p>
            </div>
            <div style="font-size: 3rem; opacity: 0.3;">
                <i class="fas fa-box"></i>
            </div>
        </div>
    </div>

    <!-- Pending Orders -->
    <div class="card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">Pending Orders</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo number_format($pendingOrders); ?></h2>
                <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">Needs attention</p>
            </div>
            <div style="font-size: 3rem; opacity: 0.3;">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Orders</h3>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px; color: #999;">
                                No orders yet
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr onclick="window.location.href='order-details.php?id=<?php echo $order['id']; ?>'" style="cursor: pointer;">
                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><?php echo getOrderStatusBadge($order['order_status']); ?></td>
                                <td><?php echo formatDate($order['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="orders.php" class="btn btn-primary">View All Orders</a>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="card">
        <div class="card-header">
            <h3>
                <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> 
                Low Stock Alert
            </h3>
        </div>
        <div class="table-responsive">
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
                            <td colspan="3" style="text-align: center; padding: 30px; color: #999;">
                                <i class="fas fa-check-circle" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                                <p>All products are well stocked!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                    <small style="color: #999;"><?php echo htmlspecialchars($product['brand']); ?></small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $product['stock_quantity'] == 0 ? 'badge-danger' : 'badge-warning'; ?>">
                                        <?php echo $product['stock_quantity']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="font-size: 0.85rem; padding: 6px 12px;">
                                        <i class="fas fa-edit"></i> Update
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="products.php" class="btn btn-primary">View All Products</a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="add-product.php" class="btn btn-success" style="padding: 15px; text-align: center;">
            <i class="fas fa-plus-circle"></i><br>
            <span style="font-size: 0.9rem;">Add New Product</span>
        </a>
        <a href="orders.php?status=pending" class="btn btn-warning" style="padding: 15px; text-align: center;">
            <i class="fas fa-clock"></i><br>
            <span style="font-size: 0.9rem;">Process Pending Orders</span>
        </a>
        <a href="reviews.php" class="btn btn-primary" style="padding: 15px; text-align: center;">
            <i class="fas fa-star"></i><br>
            <span style="font-size: 0.9rem;">Moderate Reviews</span>
        </a>
        <a href="customers.php" class="btn btn-primary" style="padding: 15px; text-align: center;">
            <i class="fas fa-users"></i><br>
            <span style="font-size: 0.9rem;">View Customers</span>
        </a>
    </div>
</div>

<?php include('includes/footer.php'); ?>
