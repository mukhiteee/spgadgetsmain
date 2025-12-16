<?php
// api/process_order.php - Process and Save Order
header('Content-Type: application/json');

require_once('config.php');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $pdo = connectDB();
    
    // Get form data
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $paymentMethod = trim($_POST['paymentMethod'] ?? '');
    $orderNotes = trim($_POST['orderNotes'] ?? '');
    
    // Get cart and totals
    $cartJson = $_POST['cart'] ?? '[]';
    $cart = json_decode($cartJson, true);
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $shipping = floatval($_POST['shipping'] ?? 0);
    $tax = floatval($_POST['tax'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);
    
    // Validate required fields
    if (empty($fullName) || empty($email) || empty($phone) || empty($address) || 
        empty($city) || empty($state) || empty($paymentMethod)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    // Validate cart
    if (empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }
    
    // Generate unique order number
    $orderNumber = 'SPG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert order into orders table
        $stmt = $pdo->prepare('
            INSERT INTO orders (
                order_number, customer_name, customer_email, customer_phone,
                shipping_address, city, state, payment_method, subtotal,
                shipping_fee, tax, total_amount, order_notes, order_status, payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $orderNumber,
            $fullName,
            $email,
            $phone,
            $address,
            $city,
            $state,
            $paymentMethod,
            $subtotal,
            $shipping,
            $tax,
            $total,
            $orderNotes,
            'pending',
            'pending'
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items and update stock
        $itemStmt = $pdo->prepare('
            INSERT INTO order_items (
                order_id, product_id, product_name, product_brand,
                product_price, quantity, subtotal
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stockStmt = $pdo->prepare('
            UPDATE products 
            SET stock_quantity = stock_quantity - ? 
            WHERE id = ? AND stock_quantity >= ?
        ');
        
        foreach ($cart as $item) {
            $itemSubtotal = $item['price'] * $item['quantity'];
            
            // Insert order item
            $itemStmt->execute([
                $orderId,
                $item['id'],
                $item['name'],
                $item['brand'],
                $item['price'],
                $item['quantity'],
                $itemSubtotal
            ]);
            
            // Update stock quantity
            $stockStmt->execute([
                $item['quantity'],
                $item['id'],
                $item['quantity']
            ]);
            
            // Check if stock was actually updated
            if ($stockStmt->rowCount() === 0) {
                throw new Exception("Insufficient stock for product: {$item['name']}");
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Return success
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_number' => $orderNumber,
            'order_id' => $orderId
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Order processing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
}
?>