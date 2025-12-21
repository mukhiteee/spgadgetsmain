<?php
// admin/inventory.php - Inventory Management Dashboard
define('ADMIN_PAGE', true);
require_once('config.php');
require_once('../api/inventory.php');
requireAdminLogin();

$pageTitle = 'Inventory Management';

// Handle manual stock adjustment
if (isset($_POST['adjust_stock'])) {
    $productId = intval($_POST['product_id']);
    $adjustment = intval($_POST['adjustment']);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    $changeType = $adjustment > 0 ? 'manual_add' : 'manual_subtract';
    
    $result = updateStock(
        $productId,
        $adjustment,
        $changeType,
        'admin',
        $_SESSION['admin_id'],
        $notes
    );
    
    if ($result) {
        logAdminActivity($_SESSION['admin_id'], 'update', 'stock', $productId, "Adjusted stock by $adjustment");
        header('Location: inventory.php?success=' . urlencode('Stock updated successfully'));
        exit;
    } else {
        $error = 'Failed to update stock';
    }
}

// Mark alert as read
if (isset($_GET['mark_read'])) {
    markAlertAsRead(intval($_GET['mark_read']));
    header('Location: inventory.php');
    exit;
}

// Fetch data
$lowStockProducts = getLowStockProducts();
$outOfStockProducts = getOutOfStockProducts();
$unreadAlerts = getUnreadStockAlerts();

// Get stock statistics
try {
    $pdo = connectDB();
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity > 0');
    $inStockCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity = 0');
    $outOfStockCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity <= low_stock_threshold AND stock_quantity > 0');
    $lowStockCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query('SELECT SUM(stock_quantity * price) as inventory_value FROM products');
    $inventoryValue = $stmt->fetchColumn() ?: 0;
    
} catch (Exception $e) {
    error_log("Inventory stats error: " . $e->getMessage());
}

include('includes/header.php');
?>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">Total Inventory Value</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo formatCurrency($inventoryValue); ?></h2>
            </div>
            <i class="fas fa-warehouse" style="font-size: 3rem; opacity: 0.3;"></i>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">In Stock</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo $inStockCount; ?></h2>
                <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">Products available</p>
            </div>
            <i class="fas fa-check-circle" style="font-size: 3rem; opacity: 0.3;"></i>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">Low Stock</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo $lowStockCount; ?></h2>
                <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">Needs restock</p>
            </div>
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; opacity: 0.3;"></i>
        </div>
    </div>

    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="opacity: 0.9; margin-bottom: 5px;">Out of Stock</p>
                <h2 style="font-size: 2rem; margin: 0;"><?php echo $outOfStockCount; ?></h2>
                <p style="font-size: 0.85rem; opacity: 0.8; margin-top: 5px;">Urgent action</p>
            </div>
            <i class="fas fa-times-circle" style="font-size: 3rem; opacity: 0.3;"></i>
        </div>
    </div>
</div>

<!-- Stock Alerts -->
<?php if (!empty($unreadAlerts)): ?>
<div class="card" style="margin-bottom: 20px; border-left: 4px solid #ffc107;">
    <div class="card-header">
        <h3><i class="fas fa-bell"></i> Stock Alerts (<?php echo count($unreadAlerts); ?>)</h3>
    </div>
    
    <?php foreach ($unreadAlerts as $alert): ?>
        <div style="padding: 15px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong><?php echo htmlspecialchars($alert['product_name']); ?></strong>
                <span class="badge badge-<?php echo $alert['alert_type'] === 'out_of_stock' ? 'danger' : 'warning'; ?>" style="margin-left: 10px;">
                    <?php echo ucwords(str_replace('_', ' ', $alert['alert_type'])); ?>
                </span>
                <p style="margin: 5px 0 0 0; color: #666;">
                    Current stock: <?php echo $alert['stock_level']; ?> units • 
                    <?php echo formatDateTime($alert['created_at']); ?>
                </p>
            </div>
            <a href="inventory.php?mark_read=<?php echo $alert['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                <i class="fas fa-check"></i> Mark Read
            </a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Low Stock Products -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> Low Stock Products</h3>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Stock</th>
                        <th>Threshold</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockProducts)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 30px; color: #999;">
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
                                    <span class="badge badge-warning"><?php echo $product['stock_quantity']; ?></span>
                                </td>
                                <td><?php echo $product['low_stock_threshold']; ?></td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                        <i class="fas fa-plus"></i> Restock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Out of Stock Products -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-times-circle" style="color: #dc3545;"></i> Out of Stock Products</h3>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($outOfStockProducts)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 30px; color: #999;">
                                <i class="fas fa-check-circle" style="font-size: 2rem; color: #28a745; margin-bottom: 10px;"></i>
                                <p>No out-of-stock products!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($outOfStockProducts as $product): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                    <small style="color: #999;"><?php echo htmlspecialchars($product['brand']); ?></small>
                                </td>
                                <td><?php echo ucfirst($product['category']); ?></td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;">
                                        <i class="fas fa-plus"></i> Restock
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

<!-- Quick Stock Adjustment -->
<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3><i class="fas fa-sliders-h"></i> Quick Stock Adjustment</h3>
    </div>

    <form method="POST" style="max-width: 600px;">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Select Product</label>
            <select name="product_id" required style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;">
                <option value="">Choose a product...</option>
                <?php
                try {
                    $stmt = $pdo->query('SELECT id, name, stock_quantity FROM products ORDER BY name');
                    while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<option value="' . $p['id'] . '">' . htmlspecialchars($p['name']) . ' (Current: ' . $p['stock_quantity'] . ')</option>';
                    }
                } catch (Exception $e) {}
                ?>
            </select>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Adjustment (+/-)</label>
            <input type="number" name="adjustment" required 
                   style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;"
                   placeholder="e.g. +10 to add, -5 to remove">
            <small style="color: #666; display: block; margin-top: 5px;">
                Use positive numbers to add stock, negative to remove
            </small>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Notes</label>
            <textarea name="notes" rows="3" 
                      style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px;"
                      placeholder="Reason for adjustment..."></textarea>
        </div>

        <button type="submit" name="adjust_stock" class="btn btn-success">
            <i class="fas fa-save"></i> Adjust Stock
        </button>
    </form>
</div>

<?php include('includes/footer.php'); ?>
