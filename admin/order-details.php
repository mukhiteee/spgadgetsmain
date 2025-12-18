<?php
// admin/order-details.php - View Order Details
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $newStatus = $_POST['status'];
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE orders SET order_status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $orderId]);
        
        logAdminActivity($_SESSION['admin_id'], 'update', 'order', $orderId, "Changed status to: $newStatus");
        
        header('Location: order-details.php?id=' . $orderId . '&success=' . urlencode('Status updated'));
        exit;
    } catch (Exception $e) {
        $error = 'Failed to update status';
    }
}

// Fetch order details
try {
    $pdo = connectDB();
    
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php?error=' . urlencode('Order not found'));
        exit;
    }
    
    // Fetch order items
    $stmt = $pdo->prepare('SELECT oi.*, p.name, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?');
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    header('Location: orders.php?error=' . urlencode('Failed to load order'));
    exit;
}

$pageTitle = 'Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);

include('includes/header.php');
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <!-- Order Details -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Order Details</h3>
            <?php echo getOrderStatusBadge($order['order_status']); ?>
        </div>

        <table style="width: 100%;">
            <tr>
                <td style="padding: 12px; background: #f8f9fa; font-weight: 600; width: 200px;">Order ID</td>
                <td style="padding: 12px;">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">Order Date</td>
                <td style="padding: 12px;"><?php echo formatDateTime($order['created_at']); ?></td>
            </tr>
            <tr>
                <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">Customer Name</td>
                <td style="padding: 12px;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
            </tr>
            <tr>
                <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">Email</td>
                <td style="padding: 12px;"><?php echo htmlspecialchars($order['customer_email']); ?></td>
            </tr>
            <tr>
                <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">Phone</td>
                <td style="padding: 12px;"><?php echo htmlspecialchars($order['customer_phone']); ?></td>
            </tr>
            <tr>
                <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">Shipping Address</td>
                <td style="padding: 12px;"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></td>
            </tr>
            <tr>
                <td style="padding: 12px; background: #f8f9fa; font-weight: 600;">Payment Method</td>
                <td style="padding: 12px;"><?php echo ucfirst($order['payment_method']); ?></td>
            </tr>
        </table>

        <!-- Order Items -->
        <div style="margin-top: 30px;">
            <h4 style="margin-bottom: 15px;">Order Items</h4>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;"
                                         onerror="this.src='https://via.placeholder.com/50'">
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo formatCurrency($item['price']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatCurrency($item['price'] * $item['quantity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #f8f9fa;">
                        <td colspan="3" style="text-align: right; font-weight: 600; font-size: 1.2rem;">Total:</td>
                        <td style="font-weight: 700; font-size: 1.2rem; color: var(--primary);">
                            <?php echo formatCurrency($order['total_amount']); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <div>
        <div class="card">
            <div class="card-header">
                <h3>Update Status</h3>
            </div>
            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <select name="status" style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;">
                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </form>
        </div>

        <div class="card" style="margin-top: 20px;">
            <a href="orders.php" class="btn btn-secondary" style="width: 100%; text-align: center;">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
