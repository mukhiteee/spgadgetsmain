<?php
    // api/filter_api.php - Handles dynamic filtering and pagination from the database

    // Ensure database connection configuration is available
    require_once('config.php'); 
    
    header('Content-Type: application/json');
    $response = ['success' => false, 'products' => [], 'totalCount' => 0, 'message' => 'Invalid Request'];

    // 1. INPUT VALIDATION
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        $response['message'] = 'No filter data received.';
        echo json_encode($response);
        exit;
    }
    
    // Extract filter parameters from JavaScript's POST body
    $filters = $data['filters'] ?? [];
    $searchQuery = $data['searchQuery'] ?? '';
    $sortBy = $data['sortBy'] ?? 'featured';
    $currentPage = (int)($data['currentPage'] ?? 1);
    $productsPerPage = (int)($data['productsPerPage'] ?? 9);

    try {
        $pdo = connectDB();
        
        // --- 2. BUILD DYNAMIC SQL QUERY (WHERE Clause) ---
        $whereClauses = [];
        $params = [];
        
        // Search Filter (by name or brand)
        if (!empty($searchQuery)) {
            $whereClauses[] = "(name LIKE :search OR brand LIKE :search)";
            $params[':search'] = '%' . $searchQuery . '%';
        }

        // Category Filter (Uses IN clause for multiple selections)
        if (!empty($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            $whereClauses[] = "category IN ($placeholders)";
            foreach ($filters['categories'] as $cat) {
                $params[] = $cat; // Unnamed parameters for IN clause
            }
        }
        
        // Brand Filter (Uses IN clause)
        if (!empty($filters['brands'])) {
            $placeholders = implode(',', array_fill(0, count($filters['brands']), '?'));
            $whereClauses[] = "brand IN ($placeholders)";
            foreach ($filters['brands'] as $brand) {
                $params[] = $brand;
            }
        }
        
        // Condition Filter (Uses IN clause)
        if (!empty($filters['conditions'])) {
            $placeholders = implode(',', array_fill(0, count($filters['conditions']), '?'));
            $whereClauses[] = "item_condition IN ($placeholders)";
            foreach ($filters['conditions'] as $condition) {
                $params[] = $condition;
            }
        }

        // Price Filter (Min/Max)
        if (isset($filters['minPrice']) && isset($filters['maxPrice'])) {
            $whereClauses[] = "price >= :minPrice AND price <= :maxPrice";
            $params[':minPrice'] = $filters['minPrice'];
            $params[':maxPrice'] = $filters['maxPrice'];
        }

        $whereSql = '';
        if (!empty($whereClauses)) {
            $whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // --- 3. COUNT TOTAL RESULTS (Crucial for pagination) ---
        $countSql = "SELECT COUNT(*) AS count FROM products" . $whereSql;
        
        // We handle parameters dynamically for the COUNT query
        $countStmt = $pdo->prepare($countSql);
        
        // Execute parameters: this part must be careful with mixed named and unnamed params
        $executeParams = [];
        foreach ($params as $key => $value) {
            if (is_string($key) && $key[0] === ':') {
                $executeParams[$key] = $value; // Named parameters
            } else {
                $executeParams[] = $value; // Unnamed parameters
            }
        }

        $countStmt->execute(is_string(key($executeParams)) ? $executeParams : array_values($executeParams));
        $totalCount = $countStmt->fetchColumn();

        // --- 4. BUILD SORTING & PAGINATION ---
        $orderBy = '';
        switch ($sortBy) {
            case 'price-low':
                $orderBy = 'ORDER BY price ASC';
                break;
            case 'price-high':
                $orderBy = 'ORDER BY price DESC';
                break;
            case 'name':
                $orderBy = 'ORDER BY name ASC';
                break;
            case 'featured':
            default:
                $orderBy = 'ORDER BY id DESC'; // Default sort
                break;
        }

        $offset = ($currentPage - 1) * $productsPerPage;
        $limitSql = "LIMIT :limit OFFSET :offset";
        
        // --- 5. EXECUTE FINAL PRODUCT QUERY ---
        $sql = "SELECT 
            id, name, brand, category, price, item_condition, image, stock_quantity 
            FROM products" . $whereSql . " " . $orderBy . " " . $limitSql;

        $stmt = $pdo->prepare($sql);
        
        // Bind the LIMIT and OFFSET parameters
        $stmt->bindParam(':limit', $productsPerPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        // Execute the statement using the parameters built earlier
        $stmt->execute($executeParams);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- 6. SEND SUCCESS RESPONSE ---
        $response['success'] = true;
        $response['products'] = $products;
        $response['totalCount'] = (int)$totalCount;
        $response['message'] = 'Products fetched successfully.';

    } catch (\Exception $e) {
        $response['message'] = "Database Error: " . $e->getMessage();
    }
    
    // Send the JSON response back to JavaScript
    echo json_encode($response);
?>