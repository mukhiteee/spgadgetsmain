<?php
// admin/edit-product.php - Edit Product
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Edit Product';

$productId = intval($_GET['id'] ?? 0);

if (!$productId) {
    header('Location: products.php');
    exit;
}

$error = '';
$success = '';

// Fetch product
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: products.php?error=' . urlencode('Product not found'));
        exit;
    }
    
    // Fetch product images
    $stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY image_order');
    $stmt->execute([$productId]);
    $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    header('Location: products.php?error=' . urlencode('Failed to load product'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $brand = sanitizeInput($_POST['brand'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $item_condition = $_POST['item_condition'] ?? 'new';
    $description = sanitizeInput($_POST['description'] ?? '');
    $image = sanitizeInput($_POST['image'] ?? '');
    
    if (empty($name) || empty($brand) || empty($category) || $price <= 0) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE products SET name = ?, brand = ?, category = ?, price = ?, stock_quantity = ?, item_condition = ?, description = ?, image = ? WHERE id = ?');
            $stmt->execute([$name, $brand, $category, $price, $stock_quantity, $item_condition, $description, $image, $productId]);
            
            // Update gallery images if provided
            if (!empty($_POST['additional_images'])) {
                // Delete existing gallery images
                $stmt = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
                $stmt->execute([$productId]);
                
                $additionalImages = array_filter(array_map('trim', explode("\n", $_POST['additional_images'])));
                $order = 1;
                
                // Insert main image as first gallery image
                if ($image) {
                    $stmt = $pdo->prepare('INSERT INTO product_images (product_id, image_url, image_order, is_primary, alt_text) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$productId, $image, $order, 1, $name]);
                    $order++;
                }
                
                // Insert additional images
                foreach ($additionalImages as $imgUrl) {
                    if (!empty($imgUrl)) {
                        $stmt = $pdo->prepare('INSERT INTO product_images (product_id, image_url, image_order, is_primary, alt_text) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$productId, $imgUrl, $order, 0, $name . ' - Image ' . $order]);
                        $order++;
                    }
                }
            }
            
            logAdminActivity($_SESSION['admin_id'], 'update', 'product', $productId, "Updated product: $name");
            
            header('Location: products.php?success=' . urlencode('Product updated successfully'));
            exit;
            
        } catch (Exception $e) {
            $error = 'Failed to update product: ' . $e->getMessage();
        }
    }
}

// Prepare additional images textarea value
$additionalImagesText = '';
foreach ($productImages as $img) {
    if (!$img['is_primary']) {
        $additionalImagesText .= $img['image_url'] . "\n";
    }
}

include('includes/header.php');
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-edit"></i> Edit Product</h3>
    </div>

    <?php if ($error): ?>
        <div style="padding: 15px; background: #f8d7da; color: #721c24; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="max-width: 800px;">
        <!-- Product Name -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Product Name <span style="color: red;">*</span>
            </label>
            <input type="text" name="name" required 
                   style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                   value="<?php echo htmlspecialchars($product['name']); ?>">
        </div>

        <!-- Brand -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Brand <span style="color: red;">*</span>
            </label>
            <input type="text" name="brand" required 
                   style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                   value="<?php echo htmlspecialchars($product['brand']); ?>">
        </div>

        <!-- Category & Condition -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Category <span style="color: red;">*</span>
                </label>
                <select name="category" required 
                        style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;">
                    <option value="">Select Category</option>
                    <option value="smartphones" <?php echo $product['category'] === 'smartphones' ? 'selected' : ''; ?>>Smartphones</option>
                    <option value="laptops" <?php echo $product['category'] === 'laptops' ? 'selected' : ''; ?>>Laptops</option>
                    <option value="tablets" <?php echo $product['category'] === 'tablets' ? 'selected' : ''; ?>>Tablets</option>
                    <option value="accessories" <?php echo $product['category'] === 'accessories' ? 'selected' : ''; ?>>Accessories</option>
                    <option value="audio" <?php echo $product['category'] === 'audio' ? 'selected' : ''; ?>>Audio</option>
                    <option value="wearables" <?php echo $product['category'] === 'wearables' ? 'selected' : ''; ?>>Wearables</option>
                    <option value="gaming" <?php echo $product['category'] === 'gaming' ? 'selected' : ''; ?>>Gaming</option>
                    <option value="cameras" <?php echo $product['category'] === 'cameras' ? 'selected' : ''; ?>>Cameras</option>
                </select>
            </div>

            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Condition <span style="color: red;">*</span>
                </label>
                <select name="item_condition" required 
                        style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;">
                    <option value="new" <?php echo $product['item_condition'] === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="refurbished" <?php echo $product['item_condition'] === 'refurbished' ? 'selected' : ''; ?>>Refurbished</option>
                    <option value="used" <?php echo $product['item_condition'] === 'used' ? 'selected' : ''; ?>>Used</option>
                </select>
            </div>
        </div>

        <!-- Price & Stock -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Price (₦) <span style="color: red;">*</span>
                </label>
                <input type="number" name="price" required min="0" step="0.01"
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                       value="<?php echo $product['price']; ?>">
            </div>

            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Stock Quantity <span style="color: red;">*</span>
                </label>
                <input type="number" name="stock_quantity" required min="0"
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                       value="<?php echo $product['stock_quantity']; ?>">
            </div>
        </div>

        <!-- Description -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Description
            </label>
            <textarea name="description" rows="5"
                      style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem; resize: vertical;"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </div>

        <!-- Main Image URL -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Main Image URL <span style="color: red;">*</span>
            </label>
            <input type="url" name="image" required 
                   style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                   value="<?php echo htmlspecialchars($product['image']); ?>">
            <?php if ($product['image']): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="Current image" 
                         style="max-width: 200px; border-radius: 8px; border: 2px solid #dee2e6;"
                         onerror="this.style.display='none'">
                </div>
            <?php endif; ?>
        </div>

        <!-- Additional Images -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Additional Images (Gallery)
            </label>
            <textarea name="additional_images" rows="5"
                      style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem; resize: vertical;"><?php echo htmlspecialchars($additionalImagesText); ?></textarea>
            <small style="color: #666; display: block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Enter one image URL per line (this will replace existing gallery)
            </small>
        </div>

        <!-- Current Gallery Preview -->
        <?php if (!empty($productImages)): ?>
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Current Gallery Images
            </label>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php foreach ($productImages as $img): ?>
                    <img src="<?php echo htmlspecialchars($img['image_url']); ?>" 
                         alt="Gallery image" 
                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid <?php echo $img['is_primary'] ? '#28a745' : '#dee2e6'; ?>;"
                         onerror="this.style.display='none'">
                <?php endforeach; ?>
            </div>
            <small style="color: #666; display: block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Green border = Primary image
            </small>
        </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                <i class="fas fa-save"></i> Update Product
            </button>
            <a href="products.php" class="btn btn-secondary" style="padding: 12px 30px;">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<style>
    .btn-secondary {
        background: #6c757d;
        color: white;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
</style>

<?php include('includes/footer.php'); ?>
