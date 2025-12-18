<?php
// admin/add-product.php - Add New Product
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Add New Product';

$error = '';
$success = '';

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
            $pdo = connectDB();
            
            $stmt = $pdo->prepare('INSERT INTO products (name, brand, category, price, stock_quantity, item_condition, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $brand, $category, $price, $stock_quantity, $item_condition, $description, $image]);
            
            $productId = $pdo->lastInsertId();
            
            // Handle additional images
            if (!empty($_POST['additional_images'])) {
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
            
            logAdminActivity($_SESSION['admin_id'], 'create', 'product', $productId, "Added product: $name");
            
            header('Location: products.php?success=' . urlencode('Product added successfully'));
            exit;
            
        } catch (Exception $e) {
            $error = 'Failed to add product: ' . $e->getMessage();
        }
    }
}

include('includes/header.php');
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
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
                   placeholder="e.g. iPhone 13 Pro Max"
                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>

        <!-- Brand -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Brand <span style="color: red;">*</span>
            </label>
            <input type="text" name="brand" required 
                   style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                   placeholder="e.g. Apple"
                   value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>">
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
                    <option value="smartphones">Smartphones</option>
                    <option value="laptops">Laptops</option>
                    <option value="tablets">Tablets</option>
                    <option value="accessories">Accessories</option>
                    <option value="audio">Audio</option>
                    <option value="wearables">Wearables</option>
                    <option value="gaming">Gaming</option>
                    <option value="cameras">Cameras</option>
                </select>
            </div>

            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Condition <span style="color: red;">*</span>
                </label>
                <select name="item_condition" required 
                        style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;">
                    <option value="new">New</option>
                    <option value="refurbished">Refurbished</option>
                    <option value="used">Used</option>
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
                       placeholder="e.g. 250000"
                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>

            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                    Stock Quantity <span style="color: red;">*</span>
                </label>
                <input type="number" name="stock_quantity" required min="0"
                       style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                       placeholder="e.g. 50"
                       value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>">
            </div>
        </div>

        <!-- Description -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Description
            </label>
            <textarea name="description" rows="5"
                      style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem; resize: vertical;"
                      placeholder="Product description, features, specifications..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <!-- Main Image URL -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Main Image URL <span style="color: red;">*</span>
            </label>
            <input type="url" name="image" required 
                   style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem;"
                   placeholder="https://example.com/image.jpg"
                   value="<?php echo htmlspecialchars($_POST['image'] ?? ''); ?>">
            <small style="color: #666; display: block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Enter the full URL of the product image
            </small>
        </div>

        <!-- Additional Images -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">
                Additional Images (Gallery)
            </label>
            <textarea name="additional_images" rows="5"
                      style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 8px; font-size: 1rem; resize: vertical;"
                      placeholder="Enter one image URL per line&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg&#10;https://example.com/image4.jpg"><?php echo htmlspecialchars($_POST['additional_images'] ?? ''); ?></textarea>
            <small style="color: #666; display: block; margin-top: 5px;">
                <i class="fas fa-info-circle"></i> Enter one image URL per line (optional)
            </small>
        </div>

        <!-- Buttons -->
        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                <i class="fas fa-save"></i> Add Product
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
