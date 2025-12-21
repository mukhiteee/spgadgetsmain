<?php
session_start();
require_once('../api/config.php');
require_once('../api/auth.php');
require_once('../api/wishlist.php');

if (!isUserLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$wishlist = getUserWishlist($user['id']);
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #0f172a; line-height: 1.6; }
        .header { background: white; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .header-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 700; background: linear-gradient(135deg, #1F95B1, #5CB9A4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .back-btn { padding: 10px 20px; background: #f1f5f9; color: #0f172a; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 32px; font-weight: 700; }
        .wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .wishlist-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: all 0.3s; border: 1px solid #e2e8f0; }
        .wishlist-card:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
        .product-image { width: 100%; height: 260px; object-fit: cover; }
        .product-info { padding: 20px; }
        .product-name { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .product-price { font-size: 24px; font-weight: 800; color: #1F95B1; margin-bottom: 15px; }
        .wishlist-actions { display: flex; gap: 10px; }
        .btn { padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; border: none; flex: 1; }
        .btn-primary { background: linear-gradient(135deg, #1F95B1, #5CB9A4); color: white; }
        .btn-outline { background: white; color: #ef4444; border: 2px solid #ef4444; }
        .btn:hover { transform: translateY(-2px); }
        .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 16px; }
        .empty-state i { font-size: 80px; color: #e2e8f0; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <a href="../index.html" class="logo"><i class="fas fa-store"></i> SP Gadgets</a>
            <a href="my-account.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Account</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">My Wishlist (<?php echo count($wishlist); ?>)</h1>
        </div>

        <?php if (!empty($wishlist)): ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist as $item): ?>
                    <div class="wishlist-card">
                        <img src="../<?php echo $item['image'] ?? 'assets/products/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="product-price">₦<?php echo number_format($item['price'], 0); ?></div>
                            <div class="wishlist-actions">
                                <button class="btn btn-primary" onclick="addToCart(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <button class="btn btn-outline" onclick="removeFromWishlist(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-heart"></i>
                <h3>Your Wishlist is Empty</h3>
                <p>Start adding products you love!</p>
                <a href="shop.php" class="btn btn-primary" style="display: inline-block; margin-top: 20px;">
                    <i class="fas fa-store"></i> Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function removeFromWishlist(productId) {
            if (!confirm('Remove this item from wishlist?')) return;
            
            try {
                const response = await fetch('../api/wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove&product_id=${productId}`
                });
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to remove item');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to remove item');
            }
        }

        function addToCart(productId) {
            // Add to cart logic
            alert('Added to cart!');
        }
    </script>
</body>
</html>
