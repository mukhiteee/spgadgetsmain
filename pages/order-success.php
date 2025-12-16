<?php
// pages/order-success.php - Order Confirmation Page
require_once('../api/config.php');

// Get order number from URL
$orderNumber = $_GET['order'] ?? '';

if (empty($orderNumber)) {
    header('Location: ../pages/shoptest4.php');
    exit;
}

// Fetch order details
try {
    $pdo = connectDB();
    
    // Get order
    $stmt = $pdo->prepare('
        SELECT * FROM orders 
        WHERE order_number = ?
    ');
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: ../shop/shop.php');
        exit;
    }
    
    // Get order items
    $itemsStmt = $pdo->prepare('
        SELECT * FROM order_items 
        WHERE order_id = ?
    ');
    $itemsStmt->execute([$order['id']]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    header('Location: ../shop/shop.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - SP Gadgets</title>
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
            --success: #28a745;
            --shadow-subtle: 0 2px 12px rgba(31, 149, 177, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, sans-serif;
            background: linear-gradient(135deg, var(--neutral-light) 0%, var(--neutral-mid) 100%);
            color: var(--primary-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .success-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .success-card {
            background: var(--white);
            border-radius: 16px;
            padding: 3rem;
            box-shadow: var(--shadow-subtle);
            text-align: center;
            margin-bottom: 2rem;
            animation: slideInUp 0.5s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 2.5rem;
            color: var(--white);
        }

        .success-card h1 {
            font-size: 2.5rem;
            color: var(--success);
            margin-bottom: 0.5rem;
        }

        .success-card p {
            font-size: 1.1rem;
            color: var(--primary-medium);
            margin-bottom: 2rem;
        }

        .order-number {
            display: inline-block;
            background: var(--neutral-light);
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .order-details {
            background: var(--white);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 2rem;
            animation: slideInUp 0.5s ease-out 0.3s both;
        }

        .detail-section {
            margin-bottom: 2rem;
        }

        .detail-section:last-child {
            margin-bottom: 0;
        }

        .detail-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--primary-dark);
            border-bottom: 2px solid var(--accent-terracotta);
            padding-bottom: 0.5rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: var(--primary-medium);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1rem;
            color: var(--primary-dark);
            font-weight: 500;
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .order-items-table th {
            text-align: left;
            padding: 1rem;
            background: var(--neutral-light);
            font-weight: 600;
            border-bottom: 2px solid var(--neutral-mid);
        }

        .order-items-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--neutral-mid);
        }

        .order-items-table tr:last-child td {
            border-bottom: none;
        }

        .order-totals {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--neutral-mid);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        .total-row.final {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-terracotta);
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--neutral-mid);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideInUp 0.5s ease-out 0.4s both;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--primary-dark);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--accent-terracotta);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--neutral-light);
            color: var(--primary-dark);
            border: 2px solid var(--neutral-mid);
        }

        .btn-secondary:hover {
            border-color: var(--accent-terracotta);
            background: var(--white);
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cfe2ff;
            color: #084298;
        }

        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
        }

        .info-box p {
            margin: 0;
            color: #0c5460;
        }

        @media (max-width: 768px) {
            .success-container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }

            .success-card {
                padding: 2rem 1.5rem;
            }

            .success-card h1 {
                font-size: 2rem;
            }

            .order-details {
                padding: 1.5rem;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .order-items-table {
                font-size: 0.9rem;
            }

            .order-items-table th,
            .order-items-table td {
                padding: 0.75rem 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <!-- Success Message -->
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your purchase. Your order has been received and is being processed.</p>
            <div class="order-number">
                Order #<?php echo htmlspecialchars($order['order_number']); ?>
            </div>
            <p style="font-size: 0.9rem; margin-top: 1rem;">
                A confirmation email has been sent to <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong>
            </p>
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <!-- Customer Information -->
            <div class="detail-section">
                <h3>Customer Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="detail-section">
                <h3>Shipping Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Address</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">City</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['city']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">State</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['state']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Order Status -->
            <div class="detail-section">
                <h3>Order Status</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Order Status</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Order Date</span>
                        <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="detail-section">
                <h3>Order Items</h3>
                <table class="order-items-table">
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
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                                <small style="color: var(--primary-medium);"><?php echo htmlspecialchars($item['product_brand']); ?></small>
                            </td>
                            <td>₦<?php echo number_format($item['product_price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₦<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Order Totals -->
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>₦<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span>₦<?php echo number_format($order['shipping_fee'], 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Tax (7.5%):</span>
                        <span>₦<?php echo number_format($order['tax'], 2); ?></span>
                    </div>
                    <div class="total-row final">
                        <span>Total:</span>
                        <span>₦<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($order['order_notes'])): ?>
            <div class="detail-section">
                <h3>Order Notes</h3>
                <p><?php echo nl2br(htmlspecialchars($order['order_notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="../shop/shop.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Order
            </button>
        </div>

        <!-- Information Box -->
        <div class="info-box">
            <p><strong>What's next?</strong> We'll send you an email confirmation and updates about your order status. You can expect delivery within 3-7 business days.</p>
        </div>
    </div>

    <script>
        // Clear any remaining cart data
        localStorage.removeItem('sp_cart');
        localStorage.removeItem('checkout_cart');
    </script>
</body>
</html>