<?php
// pages/wishlist.php - User Wishlist Page
session_start();
require_once('../api/config.php');
require_once('../api/auth.php');
require_once('../api/wishlist.php');

// Require login
if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$wishlistItems = getUserWishlist($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - SP Gadgets</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 700;
        }
        
        .wishlist-count {
            font-size: 16px;
            color: #64748b;
        }
        
        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .wishlist-item {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .wishlist-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
        }
        
        .item-image {
            width: 100%;
            height: 260px;
            object-fit: cover;
            background: #f8fafc;
        }
        
        .item-content {
            padding: 20px;
        }
        
        .item-category {
            font-size: 12px;
            text-transform: uppercase;
            color: #1F95B1;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .item-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .item-brand {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 15px;
        }
        
        .item-price {
            font-size: 24px;
            font-weight: 800;
            color: #1F95B1;
            margin-bottom: 15px;
        }
        
        .item-stock {
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .in-stock {
            color: #10b981;
            font-weight: 600;
        }
        
        .out-of-stock {
            color: #ef4444;
            font-weight: 600;
        }
        
        .item-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(31, 149, 177, 0.3);
        }
        
        .btn-remove {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-remove:hover {
            background: #fecaca;
        }
        
        /* Empty State */
        .empty-wishlist {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .empty-wishlist i {
            font-size: 100px;
            color: #e2e8f0;
            margin-bottom: 25px;
        }
        
        .empty-wishlist h3 {
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .empty-wishlist p {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 30px;
        }
        
        .btn-shop {
            display: inline-block;
            padding: 15px 35px;
            background: linear-gradient(135deg, #1F95B1, #5CB9A4);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-shop:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(31, 149, 177, 0.3);
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0f172a;
            color: white;
            padding: 16px 24px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            z-index: 9999;
            transform: translateX(400px);
            transition: transform 0.4s;
            font-weight: 600;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .wishlist-grid {
                grid-template-columns: 1fr;
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
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-heart" style="color: #ef4444;"></i> My Wishlist
            </h1>
            <div class="wishlist-count">
                <strong><?php echo count($wishlistItems); ?></strong> item<?php echo count($wishlistItems) !== 1 ? 's' : ''; ?>
            </div>
        </div>

        <?php if (!empty($wishlistItems)): ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="wishlist-item" data-product-id="<?php echo $item['id']; ?>">
                        <img src="../<?php echo htmlspecialchars($item['image_url'] ?? 'assets/products/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
                        
                        <div class="item-content">
                            <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="item-brand"><?php echo htmlspecialchars($item['brand'] ?? 'SP Gadgets'); ?></div>
                            <div class="item-price">₦<?php echo number_format($item['price'], 0); ?></div>
                            
                            <div class="item-stock">
                                <?php if ($item['stock_quantity'] > 0): ?>
                                    <span class="in-stock"><i class="fas fa-check-circle"></i> In Stock</span>
                                <?php else: ?>
                                    <span class="out-of-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-actions">
                                <button class="btn btn-primary" onclick="moveToCart(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <button class="btn btn-remove" onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart-broken"></i>
                <h3>Your Wishlist is Empty</h3>
                <p>Save your favorite products to buy them later!</p>
                <a href="shop.php" class="btn-shop">
                    <i class="fas fa-store"></i> Start Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        // Show toast
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Remove from wishlist
        async function removeFromWishlist(productId) {
            if (!confirm('Remove this item from your wishlist?')) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('product_id', productId);
                
                const response = await fetch('../api/wishlist.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('✓ Removed from wishlist');
                    // Remove item from DOM
                    const item = document.querySelector(`[data-product-id="${productId}"]`);
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.remove();
                        // Check if empty
                        if (document.querySelectorAll('.wishlist-item').length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    showToast('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('❌ Failed to remove item');
            }
        }

        // Move to cart
        function moveToCart(productId) {
            // Get product data
            const item = document.querySelector(`[data-product-id="${productId}"]`);
            const name = item.querySelector('.item-name').textContent;
            const priceText = item.querySelector('.item-price').textContent.replace(/[₦,]/g, '');
            const price = parseInt(priceText);
            const image = item.querySelector('.item-image').src;
            
            // Add to cart
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const existing = cart.find(item => item.id === productId);
            
            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({ id: productId, name, price, image, quantity: 1 });
            }
            
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Remove from wishlist
            removeFromWishlist(productId);
            showToast('✓ Added to cart!');
        }
    </script>
</body>
</html>
