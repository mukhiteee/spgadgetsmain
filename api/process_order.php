<?php
// api/process_order.php - Process Order with Inventory Management
require_once('../../api/config.php');
require_once('inventory.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    $required = ['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'payment_method', 'cart'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $cart = $data['cart'];
    
    if (empty($cart)) {
        throw new Exception('Cart is empty');
    }
    
    // Validate email
    if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // ========================================
    // STEP 1: VALIDATE STOCK AVAILABILITY
    // ========================================
    $stockValidation = validateCartStock($cart);
    
    if (!$stockValidation['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Some items are out of stock or have insufficient quantity',
            'errors' => $stockValidation['errors']
        ]);
        exit;
    }
    
    $pdo = connectDB();
    $pdo->beginTransaction();
    
    // ========================================
    // STEP 2: CALCULATE TOTAL
    // ========================================
    $totalAmount = 0;
    $validatedCart = [];
    
    foreach ($cart as $item) {
        $stmt = $pdo->prepare('SELECT id, name, price, stock_quantity FROM products WHERE id = ?');
        $stmt->execute([$item['id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception("Product not found: {$item['id']}");
        }
        
        // Double-check stock
        if ($product['stock_quantity'] < $item['quantity']) {
            throw new Exception("{$product['name']} is out of stock");
        }
        
        $itemTotal = $product['price'] * $item['quantity'];
        $totalAmount += $itemTotal;
        
        $validatedCart[] = [
            'product_id' => $product['id'],
            'product_name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $item['quantity']
        ];
    }
    
    // ========================================
    // STEP 3: CREATE ORDER
    // ========================================
    $stmt = $pdo->prepare('
        INSERT INTO orders (
            customer_name, 
            customer_email, 
            customer_phone, 
            shipping_address, 
            payment_method, 
            total_amount, 
            order_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $data['customer_name'],
        $data['customer_email'],
        $data['customer_phone'],
        $data['shipping_address'],
        $data['payment_method'],
        $totalAmount,
        'pending'
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // ========================================
    // STEP 4: CREATE ORDER ITEMS
    // ========================================
    $stmt = $pdo->prepare('
        INSERT INTO order_items (order_id, product_id, product_name, price, quantity) 
        VALUES (?, ?, ?, ?, ?)
    ');
    
    foreach ($validatedCart as $item) {
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['price'],
            $item['quantity']
        ]);
    }
    
    // ========================================
    // STEP 5: REDUCE STOCK (INVENTORY MANAGEMENT)
    // ========================================
    $stockReduced = reduceStockForOrder($orderId);
    
    if (!$stockReduced) {
        // If stock reduction fails, rollback the entire order
        $pdo->rollBack();
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update inventory. Order cancelled.'
        ]);
        exit;
    }
    
    // ========================================
    // STEP 6: COMMIT TRANSACTION
    // ========================================
    $pdo->commit();
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $orderId,
        'order_number' => str_pad($orderId, 6, '0', STR_PAD_LEFT),
        'total_amount' => $totalAmount
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Order processing error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
