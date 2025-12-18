<?php
// admin/orders.php - Order Management
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Orders Management';

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['status'];
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        
        logAdminActivity($_SESSION['admin_id'], 'update', 'order', $orderId, "Changed status to: $newStatus");
        
        header('Location: orders.php?success=' . urlencode('Order status updated'));
        exit;
    } catch (Exception $e) {
        $error = 'Failed to update status';
    }
}

// Fetch orders
try {
    $pdo = connectDB();
    
    $statusFilter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $sql = 'SELECT o.*, COUNT(oi.id) as item_count 
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE 1=1';
    $params = [];
    
    if ($statusFilter) {
        $sql .= ' AND o.order_status = ?';
        $params[] = $statusFilter;
    }
    
    if ($search) {
        $sql .= ' AND (o.customer_name LIKE ? OR o.customer_email LIKE ? OR o.id LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= ' GROUP BY o.id ORDER BY o.created_at DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $orders = [];
}

include('includes/header.php');
?>

<div class="card">
    <div class="card-header">
        <h3>All Orders</h3>
    </div>

    <!-- Filters -->
    <form method="GET" style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
        <input type="text" name="search" placeholder="Search by name, email, or order ID..." 
               value="<?php echo htmlspecialchars($search); ?>" 
               style="flex: 1; min-width: 250px; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px;">
        
        <select name="status" style="padding: 10px; border: 2px solid #dee2e6; border-radius: 6px;">
            <option value="">All Status</option>
            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
            <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
        
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="orders.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
    </form>

    <!-- Orders Table -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <p>No orders found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                            <td><?php echo $order['item_count']; ?> items</td>
                            <td><?php echo formatCurrency($order['total_amount']); ?></td>
                            <td><?php echo getOrderStatusBadge($order['order_status']); ?></td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                            <td>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; text-align: center; color: #999;">
        Total: <?php echo count($orders); ?> order(s)
    </div>
</div>

<?php include('includes/footer.php'); ?>
