<?php
// admin/products.php - Product Management
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Products Management';

// Handle delete
if (isset($_GET['delete']) && hasPermission('admin')) {
    $productId = (int)$_GET['delete'];
    try {
        $pdo = connectDB();
        
        // Delete product images first
        $stmt = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
        $stmt->execute([$productId]);
        
        // Delete product
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        
        logAdminActivity($_SESSION['admin_id'], 'delete', 'product', $productId, 'Deleted product');
        
        header('Location: products.php?success=' . urlencode('Product deleted successfully'));
        exit;
    } catch (Exception $e) {
        header('Location: products.php?error=' . urlencode('Failed to delete product'));
        exit;
    }
}

// Fetch products
try {
    $pdo = connectDB();
    
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    
    $sql = 'SELECT * FROM products WHERE 1=1';
    $params = [];
    
    if ($search) {
        $sql .= ' AND (name LIKE ? OR brand LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($category) {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    
    $sql .= ' ORDER BY id DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for filter
    $stmt = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category');
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $products = [];
    $categories = [];
}

include('includes/header.php');
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>All Products</h3>
        <a href="add-product.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px;">
        
        <select name="category" style="padding: 10px; border: 2px solid #dee2e6; border-radius: 6px;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst($cat)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="products.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
    </form>

    <!-- Products Table -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Condition</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <p>No products found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="Product" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;"
                                     onerror="this.src='https://via.placeholder.com/50'">
                            </td>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['brand']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($product['category'])); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td>
                                <span class="badge <?php echo $product['stock_quantity'] == 0 ? 'badge-danger' : ($product['stock_quantity'] < 10 ? 'badge-warning' : 'badge-success'); ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($product['item_condition']); ?></td>
                            <td>
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (hasPermission('admin')): ?>
                                    <a href="products.php?delete=<?php echo $product['id']; ?>" 
                                       class="btn btn-danger" 
                                       style="padding: 6px 12px; font-size: 0.85rem;"
                                       onclick="return confirmDelete('Delete this product?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; text-align: center; color: #999;">
        Total: <?php echo count($products); ?> product(s)
    </div>
</div>

<?php include('includes/footer.php'); ?>
