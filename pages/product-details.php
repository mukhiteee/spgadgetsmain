<?php
// pages/product-details.php - Single Product View
require_once('../api/config.php');

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: ../shop/shop.php');
    exit;
}

// Fetch product details
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        header('Location: ../shop/shop.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: ../shop/shop.php');
    exit;
}

$isOutOfStock = (int)$product['stock_quantity'] === 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - SP Gadgets</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0f172a;
            --primary-medium: #1F95B1;
            --accent-terracotta: #1F95B1;
            --neutral-light: #f8fafc;
            --neutral-mid: #e2e8f0;
            --white: #ffffff;
            --shadow-subtle: 0 2px 12px rgba(31, 149, 177, 0.08);
            --shadow-hover: 0 8px 24px rgba(31, 149, 177, 0.15);
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--neutral-light) 0%, var(--neutral-mid) 100%);
            color: var(--primary-dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-medium);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            color: var(--accent-terracotta);
            transform: translateX(-5px);
        }

        .product-details-container {
            background: var(--white);
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--shadow-subtle);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        @media (max-width: 768px) {
            .product-details-container {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 20px;
            }
        }

        .product-image-section {
            position: relative;
        }

        .product-main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 12px;
            background: var(--neutral-light);
        }

        .product-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-new {
            background: #28a745;
            color: white;
        }

        .badge-refurbished {
            background: #ffc107;
            color: #333;
        }

        .badge-used {
            background: #6c757d;
            color: white;
        }

        .badge-out-of-stock {
            background: #dc3545;
            color: white;
        }

        .product-info-section h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--primary-dark);
        }

        .product-brand {
            font-size: 1.2rem;
            color: var(--primary-medium);
            margin-bottom: 20px;
            font-weight: 500;
        }

        .product-price {
            font-size: 3rem;
            font-weight: 700;
            color: var(--accent-terracotta);
            margin-bottom: 30px;
        }

        .product-meta {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--neutral-light);
            border-radius: 8px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.85rem;
            color: var(--primary-medium);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .meta-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .product-description {
            margin-bottom: 30px;
            line-height: 1.8;
            color: #555;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .quantity-label {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 2px solid var(--neutral-mid);
            border-radius: 8px;
            padding: 5px;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--neutral-light);
            color: var(--primary-dark);
            font-size: 1.2rem;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
            font-weight: 700;
        }

        .quantity-btn:hover:not(:disabled) {
            background: var(--accent-terracotta);
            color: white;
        }

        .quantity-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .quantity-display {
            min-width: 50px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 18px;
            background: var(--primary-dark);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .add-to-cart-btn:hover:not(:disabled) {
            background: var(--accent-terracotta);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .stock-info {
            margin-top: 20px;
            padding: 15px;
            background: var(--neutral-light);
            border-radius: 8px;
            font-weight: 600;
        }

        .stock-available {
            color: #28a745;
        }

        .stock-low {
            color: #ffc107;
        }

        .stock-out {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../shop/shop.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>

        <div class="product-details-container">
            <!-- Product Image Section -->
            <div class="product-image-section">
                <?php
                $imagePath = $product['image'] ? '..$product["image"]' . htmlspecialchars($product['image']) : '../assets/products/placeholder.jpg';
                ?>
                <img src="<?php echo $imagePath; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="product-main-image"
                     onerror="this.src='https://via.placeholder.com/500?text=No+Image'">
                
                <?php if ($isOutOfStock): ?>
                    <div class="product-badge badge-out-of-stock">Out of Stock</div>
                <?php else: ?>
                    <div class="product-badge badge-<?php echo $product['item_condition']; ?>">
                        <?php echo ucfirst($product['item_condition']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info Section -->
            <div class="product-info-section">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                <div class="product-price">₦<?php echo number_format($product['price'], 2); ?></div>

                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label">Category</span>
                        <span class="meta-value"><?php echo htmlspecialchars(ucfirst($product['category'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Condition</span>
                        <span class="meta-value"><?php echo htmlspecialchars(ucfirst($product['item_condition'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Stock</span>
                        <span class="meta-value"><?php echo $product['stock_quantity']; ?> units</span>
                    </div>
                </div>

                <?php if (isset($product['description']) && !empty($product['description'])): ?>
                <div class="product-description">
                    <h3>Product Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!$isOutOfStock): ?>
                <div class="quantity-selector">
                    <span class="quantity-label">Quantity:</span>
                    <div class="quantity-controls">
                        <button class="quantity-btn" id="decreaseQty">−</button>
                        <span class="quantity-display" id="quantityDisplay">1</span>
                        <button class="quantity-btn" id="increaseQty">+</button>
                    </div>
                </div>

                <button class="add-to-cart-btn" id="addToCartBtn">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>

                <div class="stock-info">
                    <?php
                    $stockQty = (int)$product['stock_quantity'];
                    if ($stockQty > 10) {
                        echo '<span class="stock-available"><i class="fas fa-check-circle"></i> In Stock - Available</span>';
                    } elseif ($stockQty > 0) {
                        echo '<span class="stock-low"><i class="fas fa-exclamation-circle"></i> Only ' . $stockQty . ' left in stock!</span>';
                    }
                    ?>
                </div>
                <?php else: ?>
                <button class="add-to-cart-btn" disabled>
                    Out of Stock
                </button>
                <div class="stock-info">
                    <span class="stock-out"><i class="fas fa-times-circle"></i> Currently unavailable</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Product data
        const product = <?php echo json_encode($product); ?>;
        const maxStock = parseInt(product.stock_quantity);
        let currentQty = 1;

        // Quantity controls
        const decreaseBtn = document.getElementById('decreaseQty');
        const increaseBtn = document.getElementById('increaseQty');
        const qtyDisplay = document.getElementById('quantityDisplay');
        const addToCartBtn = document.getElementById('addToCartBtn');

        if (decreaseBtn && increaseBtn) {
            decreaseBtn.addEventListener('click', () => {
                if (currentQty > 1) {
                    currentQty--;
                    updateQtyDisplay();
                }
            });

            increaseBtn.addEventListener('click', () => {
                if (currentQty < maxStock) {
                    currentQty++;
                    updateQtyDisplay();
                } else {
                    showToast('Maximum stock quantity reached', 'warning');
                }
            });
        }

        function updateQtyDisplay() {
            qtyDisplay.textContent = currentQty;
            decreaseBtn.disabled = currentQty <= 1;
            increaseBtn.disabled = currentQty >= maxStock;
        }

        // Add to cart functionality
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', () => {
                addToCart(product, currentQty);
            });
        }

        function addToCart(productData, quantity) {
            try {
                let cart = JSON.parse(localStorage.getItem('sp_cart') || '[]');
                
                const existingItem = cart.find(item => item.id === productData.id);
                
                if (existingItem) {
                    const newQty = existingItem.quantity + quantity;
                    if (newQty <= productData.stock_quantity) {
                        existingItem.quantity = newQty;
                        showToast(`Updated quantity to ${newQty}`, 'success');
                    } else {
                        showToast('Cannot exceed stock quantity', 'warning');
                        return;
                    }
                } else {
                    cart.push({
                        id: productData.id,
                        name: productData.name,
                        brand: productData.brand,
                        price: parseFloat(productData.price),
                        image: productData.image,
                        stock_quantity: productData.stock_quantity,
                        quantity: quantity
                    });
                    showToast(`Added ${quantity} item(s) to cart`, 'success');
                }
                
                localStorage.setItem('sp_cart', JSON.stringify(cart));
                
                // Visual feedback
                addToCartBtn.textContent = '✓ Added to Cart!';
                addToCartBtn.style.background = '#28a745';
                setTimeout(() => {
                    addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                    addToCartBtn.style.background = '';
                }, 2000);
                
            } catch (error) {
                console.error('Error adding to cart:', error);
                showToast('Failed to add to cart', 'error');
            }
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.style.cssText = `
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: ${type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : '#dc3545'};
                color: ${type === 'warning' ? '#333' : 'white'};
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideInUp 0.3s ease-out;
            `;
            
            const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠️' : '✗';
            toast.innerHTML = `<span style="margin-right:10px;font-size:1.2rem;">${icon}</span>${message}`;
            
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Initialize
        updateQtyDisplay();
    </script>
</body>
</html>