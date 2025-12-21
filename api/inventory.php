<?php
// api/inventory.php - Inventory Management Functions

require_once('../api/config.php');

/**
 * Update product stock and log history
 * 
 * @param int $productId - Product ID
 * @param int $quantityChange - Change amount (positive or negative)
 * @param string $changeType - Type: purchase, manual_add, manual_subtract, return, adjustment
 * @param string $referenceType - Reference: order, admin, return
 * @param int $referenceId - ID of order or admin
 * @param string $notes - Optional notes
 * @return bool - Success status
 */
function updateStock($productId, $quantityChange, $changeType = 'purchase', $referenceType = null, $referenceId = null, $notes = null) {
    try {
        $pdo = connectDB();
        $pdo->beginTransaction();
        
        // Get current stock
        $stmt = $pdo->prepare('SELECT stock_quantity, name, low_stock_threshold FROM products WHERE id = ? FOR UPDATE');
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $pdo->rollBack();
            return false;
        }
        
        $quantityBefore = $product['stock_quantity'];
        $quantityAfter = $quantityBefore + $quantityChange;
        
        // Prevent negative stock
        if ($quantityAfter < 0) {
            $pdo->rollBack();
            return false;
        }
        
        // Update product stock
        $stmt = $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
        $stmt->execute([$quantityAfter, $productId]);
        
        // Log stock history
        $stmt = $pdo->prepare('INSERT INTO stock_history (product_id, change_type, quantity_before, quantity_change, quantity_after, reference_type, reference_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$productId, $changeType, $quantityBefore, $quantityChange, $quantityAfter, $referenceType, $referenceId, $notes]);
        
        // Check for stock alerts
        checkStockAlerts($productId, $quantityBefore, $quantityAfter, $product['low_stock_threshold']);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Stock update error: " . $e->getMessage());
        return false;
    }
}

/**
 * Reduce stock for an order (called after order placement)
 * 
 * @param int $orderId - Order ID
 * @return bool - Success status
 */
function reduceStockForOrder($orderId) {
    try {
        $pdo = connectDB();
        
        // Get order items
        $stmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $result = updateStock(
                $item['product_id'],
                -$item['quantity'], // Negative to reduce stock
                'purchase',
                'order',
                $orderId,
                "Stock reduced for order #$orderId"
            );
            
            if (!$result) {
                error_log("Failed to reduce stock for product {$item['product_id']} in order $orderId");
                return false;
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Reduce stock error: " . $e->getMessage());
        return false;
    }
}

/**
 * Restore stock for cancelled order
 * 
 * @param int $orderId - Order ID
 * @return bool - Success status
 */
function restoreStockForOrder($orderId) {
    try {
        $pdo = connectDB();
        
        // Get order items
        $stmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as $item) {
            $result = updateStock(
                $item['product_id'],
                $item['quantity'], // Positive to restore stock
                'return',
                'order',
                $orderId,
                "Stock restored for cancelled order #$orderId"
            );
            
            if (!$result) {
                error_log("Failed to restore stock for product {$item['product_id']} in order $orderId");
                return false;
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Restore stock error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if product has sufficient stock
 * 
 * @param int $productId - Product ID
 * @param int $quantity - Requested quantity
 * @return bool - True if sufficient stock
 */
function checkStockAvailability($productId, $quantity) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return false;
        }
        
        return $product['stock_quantity'] >= $quantity;
        
    } catch (Exception $e) {
        error_log("Stock check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate cart items have sufficient stock
 * 
 * @param array $cartItems - Array of cart items with product_id and quantity
 * @return array - ['valid' => bool, 'errors' => array]
 */
function validateCartStock($cartItems) {
    $errors = [];
    
    try {
        $pdo = connectDB();
        
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare('SELECT name, stock_quantity FROM products WHERE id = ?');
            $stmt->execute([$item['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $errors[] = "Product not found";
                continue;
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                $errors[] = "{$product['name']}: Only {$product['stock_quantity']} available (requested {$item['quantity']})";
            }
        }
        
    } catch (Exception $e) {
        error_log("Cart validation error: " . $e->getMessage());
        $errors[] = "Unable to validate stock";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Check and create stock alerts
 * 
 * @param int $productId - Product ID
 * @param int $quantityBefore - Stock before change
 * @param int $quantityAfter - Stock after change
 * @param int $threshold - Low stock threshold
 */
function checkStockAlerts($productId, $quantityBefore, $quantityAfter, $threshold) {
    try {
        $pdo = connectDB();
        
        // Out of stock alert
        if ($quantityAfter == 0 && $quantityBefore > 0) {
            $stmt = $pdo->prepare('INSERT INTO stock_alerts (product_id, alert_type, stock_level) VALUES (?, ?, ?)');
            $stmt->execute([$productId, 'out_of_stock', $quantityAfter]);
        }
        
        // Low stock alert
        elseif ($quantityAfter > 0 && $quantityAfter <= $threshold && $quantityBefore > $threshold) {
            $stmt = $pdo->prepare('INSERT INTO stock_alerts (product_id, alert_type, stock_level) VALUES (?, ?, ?)');
            $stmt->execute([$productId, 'low_stock', $quantityAfter]);
        }
        
        // Restocked alert
        elseif ($quantityAfter > $threshold && $quantityBefore <= $threshold) {
            $stmt = $pdo->prepare('INSERT INTO stock_alerts (product_id, alert_type, stock_level) VALUES (?, ?, ?)');
            $stmt->execute([$productId, 'restocked', $quantityAfter]);
        }
        
    } catch (Exception $e) {
        error_log("Stock alert error: " . $e->getMessage());
    }
}

/**
 * Get unread stock alerts
 * 
 * @return array - Array of alerts
 */
function getUnreadStockAlerts() {
    try {
        $pdo = connectDB();
        $stmt = $pdo->query('
            SELECT sa.*, p.name as product_name, p.stock_quantity 
            FROM stock_alerts sa 
            JOIN products p ON sa.product_id = p.id 
            WHERE sa.is_read = 0 
            ORDER BY sa.created_at DESC 
            LIMIT 10
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get alerts error: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark alert as read
 * 
 * @param int $alertId - Alert ID
 */
function markAlertAsRead($alertId) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('UPDATE stock_alerts SET is_read = 1 WHERE id = ?');
        $stmt->execute([$alertId]);
        
    } catch (Exception $e) {
        error_log("Mark alert error: " . $e->getMessage());
    }
}

/**
 * Get stock history for a product
 * 
 * @param int $productId - Product ID
 * @param int $limit - Number of records
 * @return array - Array of history records
 */
function getStockHistory($productId, $limit = 50) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('
            SELECT * FROM stock_history 
            WHERE product_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ');
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get low stock products
 * 
 * @return array - Array of low stock products
 */
function getLowStockProducts() {
    try {
        $pdo = connectDB();
        $stmt = $pdo->query('
            SELECT id, name, brand, stock_quantity, low_stock_threshold 
            FROM products 
            WHERE stock_quantity <= low_stock_threshold 
            AND stock_quantity > 0 
            ORDER BY stock_quantity ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get low stock error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get out of stock products
 * 
 * @return array - Array of out of stock products
 */
function getOutOfStockProducts() {
    try {
        $pdo = connectDB();
        $stmt = $pdo->query('
            SELECT id, name, brand, category 
            FROM products 
            WHERE stock_quantity = 0 
            ORDER BY name ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get out of stock error: " . $e->getMessage());
        return [];
    }
}
?>
