<?php
// api/products_api.php - Product Filtering API
header('Content-Type: application/json');

// Include database configuration
require_once('config.php');

try {
    $pdo = connectDB();
    
    // Get filter parameters
    $categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
    $brands = isset($_GET['brands']) ? explode(',', $_GET['brands']) : [];
    $conditions = isset($_GET['conditions']) ? explode(',', $_GET['conditions']) : [];
    $minPrice = isset($_GET['minPrice']) ? (float)$_GET['minPrice'] : 0;
    $maxPrice = isset($_GET['maxPrice']) ? (float)$_GET['maxPrice'] : PHP_FLOAT_MAX;
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'featured';
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 9;
    
    // Build the SQL query dynamically
    $sql = 'SELECT id, name, brand, category, price, item_condition, image, stock_quantity FROM products WHERE 1=1';
    $params = [];
    
    // Category filter
    if (!empty($categories)) {
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $sql .= " AND category IN ($placeholders)";
        $params = array_merge($params, $categories);
    }
    
    // Brand filter
    if (!empty($brands)) {
        $placeholders = str_repeat('?,', count($brands) - 1) . '?';
        $sql .= " AND brand IN ($placeholders)";
        $params = array_merge($params, $brands);
    }
    
    // Condition filter
    if (!empty($conditions)) {
        $placeholders = str_repeat('?,', count($conditions) - 1) . '?';
        $sql .= " AND item_condition IN ($placeholders)";
        $params = array_merge($params, $conditions);
    }
    
    // Price filter
    $sql .= ' AND price >= ? AND price <= ?';
    $params[] = $minPrice;
    $params[] = $maxPrice;
    
    // Search filter
    if (!empty($searchQuery)) {
        $sql .= ' AND (name LIKE ? OR brand LIKE ?)';
        $searchParam = '%' . $searchQuery . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Sorting
    switch ($sortBy) {
        case 'price-low':
            $sql .= ' ORDER BY price ASC';
            break;
        case 'price-high':
            $sql .= ' ORDER BY price DESC';
            break;
        case 'name':
            $sql .= ' ORDER BY name ASC';
            break;
        case 'featured':
        default:
            $sql .= ' ORDER BY id DESC'; // Default sorting
            break;
    }
    
    // Count total results (before pagination)
    $countSql = preg_replace('/^SELECT .+ FROM/', 'SELECT COUNT(*) FROM', $sql);
    $countSql = preg_replace('/ ORDER BY .+$/', '', $countSql);
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalResults = $countStmt->fetchColumn();
    
    // Add pagination
    $offset = ($page - 1) * $perPage;
    $sql .= ' LIMIT ? OFFSET ?';
    $params[] = $perPage;
    $params[] = $offset;
    
    // Execute main query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $totalPages = ceil($totalResults / $perPage);
    
    // Return response
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_results' => $totalResults,
            'total_pages' => $totalPages
        ]
    ]);
    
} catch (\Exception $e) {
    // Log error
    error_log("Products API Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch products',
        'error' => $e->getMessage()
    ]);
}
?>