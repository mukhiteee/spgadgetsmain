<?php
// pages/product-details.php - Mobile-Optimized Comprehensive Product View
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
    
    // Fetch product images from gallery
    $imagesStmt = $pdo->prepare('
        SELECT image_url, alt_text 
        FROM product_images 
        WHERE product_id = ? 
        ORDER BY image_order ASC
    ');
    $imagesStmt->execute([$productId]);
    $productImages = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no gallery images, use the main product image as fallback
    if (empty($productImages) && !empty($product['image'])) {
        $productImages = [
            ['image_url' => $product['image'], 'alt_text' => $product['name']]
        ];
    }
    
    // Fetch related products (same category, different product)
    $relatedStmt = $pdo->prepare('
        SELECT id, name, brand, price, image, stock_quantity 
        FROM products 
        WHERE category = ? AND id != ? 
        LIMIT 4
    ');
    $relatedStmt->execute([$product['category'], $productId]);
    $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, var(--neutral-light) 0%, var(--neutral-mid) 100%);
            color: var(--primary-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-medium);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .back-button:hover {
            color: var(--accent-terracotta);
            transform: translateX(-5px);
        }

        /* Main Product Section */
        .product-details-container {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-subtle);
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 20px;
        }

        /* IMAGE GALLERY */
        .product-image-section {
            position: relative;
            width: 100%;
        }

        .main-image-container {
            position: relative;
            width: 100%;
            height: 400px;
            border-radius: 12px;
            overflow: hidden;
            background: var(--neutral-light);
            margin-bottom: 1rem;
            cursor: zoom-in;
        }

        .product-main-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .main-image-container:hover .product-main-image {
            transform: scale(1.05);
        }

        .main-image-container.zoomed {
            cursor: zoom-out;
        }

        .main-image-container.zoomed .product-main-image {
            transform: scale(2);
        }

        .image-thumbnails {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding: 0.5rem 0;
            -webkit-overflow-scrolling: touch;
        }

        .image-thumbnails::-webkit-scrollbar {
            height: 4px;
        }

        .image-thumbnails::-webkit-scrollbar-thumb {
            background: var(--primary-medium);
            border-radius: 2px;
        }

        .thumbnail {
            min-width: 70px;
            width: 70px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .thumbnail:hover, .thumbnail.active {
            border-color: var(--accent-terracotta);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--primary-dark);
            transition: all 0.3s;
            z-index: 10;
        }

        .gallery-nav:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .gallery-nav.prev { left: 0.5rem; }
        .gallery-nav.next { right: 0.5rem; }
        .gallery-nav:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            z-index: 5;
        }

        .badge-new { background: #28a745; color: white; }
        .badge-refurbished { background: #ffc107; color: #333; }
        .badge-used { background: #6c757d; color: white; }
        .badge-out-of-stock { background: #dc3545; color: white; }

        /* PRODUCT INFO */
        .product-info-section h1 {
            font-size: 1.75rem;
            margin-bottom: 8px;
            color: var(--primary-dark);
            line-height: 1.3;
        }

        .product-brand {
            font-size: 1rem;
            color: var(--primary-medium);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .product-sku {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 15px;
        }

        .product-price {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--accent-terracotta);
            margin-bottom: 15px;
        }

        .product-meta {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 20px;
            padding: 15px;
            background: var(--neutral-light);
            border-radius: 8px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--neutral-mid);
        }

        .meta-item:last-child {
            border-bottom: none;
        }

        .meta-label {
            font-size: 0.85rem;
            color: var(--primary-medium);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .product-highlights {
            background: var(--neutral-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .product-highlights h3 {
            font-size: 1.1rem;
            margin-bottom: 12px;
            color: var(--primary-dark);
        }

        .product-highlights ul {
            list-style: none;
            padding: 0;
        }

        .product-highlights li {
            padding: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .product-highlights li::before {
            content: "✓";
            color: #28a745;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .quantity-label {
            font-weight: 600;
            font-size: 1rem;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 2px solid var(--neutral-mid);
            border-radius: 8px;
            padding: 4px;
        }

        .quantity-btn {
            width: 36px;
            height: 36px;
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
            min-width: 40px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            margin-bottom: 15px;
        }

        .add-to-cart-btn {
            padding: 16px;
            background: var(--primary-dark);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .add-to-cart-btn:active {
            transform: scale(0.98);
        }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .wishlist-btn {
            width: 56px;
            height: 56px;
            background: white;
            border: 2px solid var(--neutral-mid);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.3rem;
            color: var(--primary-medium);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wishlist-btn:active {
            transform: scale(0.95);
        }

        .stock-info {
            padding: 12px;
            background: var(--neutral-light);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .stock-available { color: #28a745; }
        .stock-low { color: #ffc107; }
        .stock-out { color: #dc3545; }

        .delivery-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 15px;
            background: var(--neutral-light);
            border-radius: 8px;
        }

        .delivery-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delivery-item i {
            font-size: 1.3rem;
            color: var(--primary-medium);
            flex-shrink: 0;
        }

        .delivery-item strong {
            display: block;
            font-size: 0.95rem;
        }

        .delivery-item p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
        }

        /* TABS SECTION */
        .product-tabs-section {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 20px;
        }

        .tabs-header {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--neutral-mid);
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tabs-header::-webkit-scrollbar {
            height: 0;
        }

        .tab-btn {
            padding: 12px 16px;
            background: none;
            border: none;
            font-size: 0.95rem;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .tab-btn:active {
            transform: scale(0.98);
        }

        .tab-btn.active {
            color: var(--accent-terracotta);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent-terracotta);
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .tab-content h3 {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .tab-content h4 {
            font-size: 1.05rem;
            margin-bottom: 10px;
            color: var(--primary-medium);
        }

        .tab-content p {
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .tab-content ul {
            line-height: 1.8;
            padding-left: 20px;
        }

        .specs-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .spec-row {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            background: var(--neutral-light);
            border-radius: 8px;
            gap: 10px;
        }

        .spec-label {
            font-weight: 600;
            color: var(--primary-medium);
            font-size: 0.9rem;
        }

        .spec-value {
            color: var(--primary-dark);
            text-align: right;
            font-size: 0.9rem;
        }

        /* RELATED PRODUCTS */
        .related-products-section {
            background: var(--white);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-subtle);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary-dark);
        }

        .related-products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .related-product-card {
            background: var(--neutral-light);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s;
        }

        .related-product-card:active {
            transform: scale(0.98);
        }

        .related-product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .related-product-info {
            padding: 12px;
        }

        .related-product-name {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.3;
        }

        .related-product-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--accent-terracotta);
        }

        /* TABLET & DESKTOP */
        @media (min-width: 768px) {
            .container {
                padding: 40px 20px;
            }

            .product-details-container {
                grid-template-columns: 1fr 1fr;
                gap: 60px;
                padding: 40px;
            }

            .main-image-container {
                height: 500px;
            }

            .product-info-section h1 {
                font-size: 2.5rem;
            }

            .product-price {
                font-size: 3rem;
            }

            .product-meta {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                padding: 20px;
            }

            .meta-item {
                flex-direction: column;
                align-items: flex-start;
                border-bottom: none;
            }

            .specs-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .related-products-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
            }

            .related-product-image {
                height: 200px;
            }

            .action-buttons {
                display: flex;
            }

            .add-to-cart-btn {
                flex: 1;
                font-size: 1.2rem;
                padding: 18px;
            }

            .wishlist-btn {
                width: 60px;
                height: 60px;
            }

            .delivery-info {
                display: flex;
                gap: 30px;
            }

            .thumbnail {
                min-width: 80px;
                width: 80px;
                height: 80px;
            }

            .gallery-nav {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .gallery-nav.prev { left: 1rem; }
            .gallery-nav.next { right: 1rem; }

            .product-tabs-section {
                padding: 40px;
            }

            .tab-btn {
                padding: 15px 30px;
                font-size: 1.1rem;
            }
        }

        /* LARGE DESKTOP */
        @media (min-width: 1200px) {
            .related-products-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../pages/shop.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>

        <!-- MAIN PRODUCT SECTION -->
        <div class="product-details-container">
            <!-- Product Image Gallery -->
            <div class="product-image-section">
                <div class="main-image-container" id="mainImageContainer">
                    <?php if (!empty($productImages)): ?>
                        <img src="<?php echo htmlspecialchars($productImages[0]['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($productImages[0]['alt_text'] ?? $product['name']); ?>" 
                             class="product-main-image" 
                             id="mainImage"
                             onerror="this.src='https://via.placeholder.com/500?text=No+Image'">
                    <?php endif; ?>
                    
                    <?php if ($isOutOfStock): ?>
                        <div class="product-badge badge-out-of-stock">Out of Stock</div>
                    <?php else: ?>
                        <div class="product-badge badge-<?php echo $product['item_condition']; ?>">
                            <?php echo ucfirst($product['item_condition']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($productImages) > 1): ?>
                        <button class="gallery-nav prev" id="prevImage">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="gallery-nav next" id="nextImage">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (count($productImages) > 1): ?>
                <div class="image-thumbnails" id="imageThumbnails">
                    <?php foreach ($productImages as $index => $image): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                             data-index="<?php echo $index; ?>"
                             onclick="changeImage(<?php echo $index; ?>)">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                 alt="Thumbnail <?php echo $index + 1; ?>"
                                 onerror="this.src='https://via.placeholder.com/80?text=No+Image'">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Info Section -->
            <div class="product-info-section">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-brand">
                    <i class="fas fa-certificate"></i> <?php echo htmlspecialchars($product['brand']); ?>
                </div>
                <div class="product-sku">SKU: SP-<?php echo str_pad($product['id'], 6, '0', STR_PAD_LEFT); ?></div>
                
                <div class="product-price">₦<?php echo number_format($product['price'], 2); ?></div>

                <!-- Product Meta Info -->
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-layer-group"></i> Category</span>
                        <span class="meta-value"><?php echo htmlspecialchars(ucfirst($product['category'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-tag"></i> Condition</span>
                        <span class="meta-value"><?php echo htmlspecialchars(ucfirst($product['item_condition'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><i class="fas fa-box"></i> In Stock</span>
                        <span class="meta-value"><?php echo $product['stock_quantity']; ?> units</span>
                    </div>
                </div>

                <!-- Key Features -->
                <div class="product-highlights">
                    <h3><i class="fas fa-star"></i> Key Features</h3>
                    <ul>
                        <li>Brand: <?php echo htmlspecialchars($product['brand']); ?></li>
                        <li>Condition: <?php echo ucfirst($product['item_condition']); ?></li>
                        <li>Original Product with Warranty</li>
                        <li>Fast & Secure Delivery</li>
                        <li>7-Day Return Policy</li>
                    </ul>
                </div>

                <?php if (!$isOutOfStock): ?>
                <!-- Quantity Selector -->
                <div class="quantity-selector">
                    <span class="quantity-label">Quantity:</span>
                    <div class="quantity-controls">
                        <button class="quantity-btn" id="decreaseQty">−</button>
                        <span class="quantity-display" id="quantityDisplay">1</span>
                        <button class="quantity-btn" id="increaseQty">+</button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="add-to-cart-btn" id="addToCartBtn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="wishlist-btn" onclick="addToWishlist()">
                        <i class="far fa-heart"></i>
                    </button>
                </div>

                <!-- Stock Info -->
                <div class="stock-info">
                    <?php
                    $stockQty = (int)$product['stock_quantity'];
                    if ($stockQty > 10) {
                        echo '<span class="stock-available"><i class="fas fa-check-circle"></i> In Stock - Available</span>';
                    } elseif ($stockQty > 0) {
                        echo '<span class="stock-low"><i class="fas fa-exclamation-circle"></i> Only ' . $stockQty . ' left - Order soon!</span>';
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

                <!-- Delivery Info -->
                <div class="delivery-info">
                    <div class="delivery-item">
                        <i class="fas fa-shipping-fast"></i>
                        <div>
                            <strong>Free Shipping</strong>
                            <p>On orders over ₦50,000</p>
                        </div>
                    </div>
                    <div class="delivery-item">
                        <i class="fas fa-undo"></i>
                        <div>
                            <strong>7-Day Returns</strong>
                            <p>Easy returns policy</p>
                        </div>
                    </div>
                    <div class="delivery-item">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>Secure Payment</strong>
                            <p>100% secure checkout</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS SECTION -->
        <div class="product-tabs-section">
            <div class="tabs-header">
                <button class="tab-btn active" onclick="switchTab(event, 'description')">
                    <i class="fas fa-align-left"></i> Description
                </button>
                <button class="tab-btn" onclick="switchTab(event, 'specifications')">
                    <i class="fas fa-list"></i> Specs
                </button>
                <button class="tab-btn" onclick="switchTab(event, 'shipping')">
                    <i class="fas fa-truck"></i> Shipping
                </button>
            </div>

            <!-- Description Tab -->
            <div class="tab-content active" id="description-tab">
                <h3>Product Description</h3>
                <?php if (!empty($product['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <?php else: ?>
                    <p>This is a high-quality <?php echo htmlspecialchars($product['name']); ?> from <?php echo htmlspecialchars($product['brand']); ?>. Perfect for those looking for reliable and durable electronics.</p>
                    <p>Key benefits include excellent build quality, reliable performance, and great value for money.</p>
                <?php endif; ?>
            </div>

            <!-- Specifications Tab -->
            <div class="tab-content" id="specifications-tab">
                <h3>Technical Specifications</h3>
                <div class="specs-grid">
                    <div class="spec-row">
                        <span class="spec-label">Brand</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['brand']); ?></span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Model</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['name']); ?></span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Category</span>
                        <span class="spec-value"><?php echo ucfirst($product['category']); ?></span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Condition</span>
                        <span class="spec-value"><?php echo ucfirst($product['item_condition']); ?></span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">SKU</span>
                        <span class="spec-value">SP-<?php echo str_pad($product['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="spec-row">
                        <span class="spec-label">Warranty</span>
                        <span class="spec-value">1 Year</span>
                    </div>
                </div>
            </div>

            <!-- Shipping Tab -->
            <div class="tab-content" id="shipping-tab">
                <h3>Shipping & Returns</h3>
                
                <h4><i class="fas fa-shipping-fast"></i> Shipping Policy</h4>
                <ul>
                    <li>Free shipping on orders above ₦50,000</li>
                    <li>Standard delivery: 3-5 business days</li>
                    <li>Express delivery available</li>
                    <li>Order tracking provided</li>
                </ul>

                <h4><i class="fas fa-undo"></i> Returns Policy</h4>
                <ul>
                    <li>7-day return policy on all products</li>
                    <li>Products must be in original condition</li>
                    <li>Refund processed within 5-7 days</li>
                </ul>

                <h4><i class="fas fa-credit-card"></i> Payment Methods</h4>
                <ul>
                    <li>Bank Transfer</li>
                    <li>Credit/Debit Card</li>
                    <li>Cash on Delivery</li>
                </ul>
            </div>
        </div>

        <!-- RELATED PRODUCTS -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="related-products-section">
            <h2 class="section-title">You May Also Like</h2>
            <div class="related-products-grid">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="related-product-card" onclick="window.location.href='product-details.php?id=<?php echo $relatedProduct['id']; ?>'">
                    <img src="<?php echo htmlspecialchars($relatedProduct['image']); ?>" 
                         alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>" 
                         class="related-product-image"
                         onerror="this.src='https://via.placeholder.com/200?text=No+Image'">
                    <div class="related-product-info">
                        <div class="related-product-name"><?php echo htmlspecialchars($relatedProduct['name']); ?></div>
                        <div class="related-product-price">₦<?php echo number_format($relatedProduct['price'], 2); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const product = <?php echo json_encode($product); ?>;
        const maxStock = parseInt(product.stock_quantity);
        let currentQty = 1;
        const galleryImages = <?php echo json_encode($productImages); ?>;
        let currentImageIndex = 0;

        // IMAGE GALLERY
        function changeImage(index) {
            if (index < 0 || index >= galleryImages.length) return;
            currentImageIndex = index;
            const mainImage = document.getElementById('mainImage');
            const thumbnails = document.querySelectorAll('.thumbnail');
            mainImage.src = galleryImages[index].image_url;
            mainImage.alt = galleryImages[index].alt_text || 'Product image';
            thumbnails.forEach((thumb, i) => thumb.classList.toggle('active', i === index));
            updateNavButtons();
        }

        function updateNavButtons() {
            const prevBtn = document.getElementById('prevImage');
            const nextBtn = document.getElementById('nextImage');
            if (prevBtn) prevBtn.disabled = currentImageIndex === 0;
            if (nextBtn) nextBtn.disabled = currentImageIndex === galleryImages.length - 1;
        }

        const prevBtn = document.getElementById('prevImage');
        const nextBtn = document.getElementById('nextImage');
        if (prevBtn) prevBtn.addEventListener('click', () => { if (currentImageIndex > 0) changeImage(currentImageIndex - 1); });
        if (nextBtn) nextBtn.addEventListener('click', () => { if (currentImageIndex < galleryImages.length - 1) changeImage(currentImageIndex + 1); });

        // Zoom
        const mainImageContainer = document.getElementById('mainImageContainer');
        const mainImage = document.getElementById('mainImage');
        let isZoomed = false;
        if (mainImageContainer) {
            mainImageContainer.addEventListener('click', (e) => {
                if (e.target.closest('.gallery-nav')) return;
                isZoomed = !isZoomed;
                mainImageContainer.classList.toggle('zoomed', isZoomed);
            });
        }

        // Keyboard
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && currentImageIndex > 0) changeImage(currentImageIndex - 1);
            else if (e.key === 'ArrowRight' && currentImageIndex < galleryImages.length - 1) changeImage(currentImageIndex + 1);
            else if (e.key === 'Escape' && isZoomed) { mainImageContainer.classList.remove('zoomed'); isZoomed = false; }
        });

        // Touch swipe
        let touchStartX = 0, touchEndX = 0;
        if (mainImageContainer) {
            mainImageContainer.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; });
            mainImageContainer.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                if (touchEndX < touchStartX - 50 && currentImageIndex < galleryImages.length - 1) changeImage(currentImageIndex + 1);
                if (touchEndX > touchStartX + 50 && currentImageIndex > 0) changeImage(currentImageIndex - 1);
            });
        }

        // TABS
        function switchTab(event, tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // QUANTITY
        const decreaseBtn = document.getElementById('decreaseQty');
        const increaseBtn = document.getElementById('increaseQty');
        const qtyDisplay = document.getElementById('quantityDisplay');
        const addToCartBtn = document.getElementById('addToCartBtn');

        if (decreaseBtn && increaseBtn) {
            decreaseBtn.addEventListener('click', () => { if (currentQty > 1) { currentQty--; updateQtyDisplay(); } });
            increaseBtn.addEventListener('click', () => {
                if (currentQty < maxStock) { currentQty++; updateQtyDisplay(); }
                else showToast('Maximum stock quantity reached', 'warning');
            });
        }

        function updateQtyDisplay() {
            qtyDisplay.textContent = currentQty;
            decreaseBtn.disabled = currentQty <= 1;
            increaseBtn.disabled = currentQty >= maxStock;
        }

        // ADD TO CART
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', () => addToCart(product, currentQty));
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

        function addToWishlist() {
            showToast('Added to wishlist!', 'success');
        }

        function showToast(message, type = 'info') {
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) existingToast.remove();
            
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                left: 20px;
                background: ${type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : '#dc3545'};
                color: ${type === 'warning' ? '#333' : 'white'};
                padding: 14px 18px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideInUp 0.3s ease-out;
                font-size: 0.95rem;
            `;
            
            const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠️' : '✗';
            toast.innerHTML = `<span style="margin-right:8px;font-size:1.1rem;">${icon}</span>${message}`;
            
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        updateQtyDisplay();
        updateNavButtons();
    </script>
</body>
</html>