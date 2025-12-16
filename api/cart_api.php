<?php
// api/cart_api.php - Manages the shopping cart session

// Start the session to access the cart data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Get the action from the URL (e.g., cart_api.php?action=add)
$action = $_GET['action'] ?? '';

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = ['success' => false, 'message' => 'Unknown action'];

// --- ACTION: GET CURRENT CART COUNT ---
if ($action === 'get_count') {
    $totalCount = 0;
    foreach ($_SESSION['cart'] as $quantity) {
        $totalCount += $quantity;
    }
    echo json_encode(['success' => true, 'count' => $totalCount]);
    exit;
}

// --- ACTION: ADD PRODUCT TO CART ---
if ($action === 'add') {
    // Read the JSON data sent by JavaScript fetch()
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['id'] ?? null;
    $quantity = intval($input['quantity'] ?? 1);

    if ($productId) {
        // If product already in cart, increase quantity; otherwise, add new
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }

        // Calculate the new total count of all items
        $newTotalCount = array_sum($_SESSION['cart']);

        echo json_encode([
            'success' => true, 
            'cartCount' => $newTotalCount,
            'message' => 'Product added successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Product ID']);
    }
    exit;
}

echo json_encode($response);