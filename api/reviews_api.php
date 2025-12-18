<?php
// api/reviews_api.php - Product Reviews API
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once('config.php');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = connectDB();
    
    switch ($action) {
        case 'get_reviews':
            getProductReviews($pdo);
            break;
            
        case 'submit_review':
            submitReview($pdo);
            break;
            
        case 'vote_helpful':
            voteHelpful($pdo);
            break;
            
        case 'get_stats':
            getReviewStats($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Reviews API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}

// ========================================
// GET PRODUCT REVIEWS
// ========================================
function getProductReviews($pdo) {
    $productId = intval($_GET['product_id'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    // Get total count
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM product_reviews WHERE product_id = ? AND is_approved = 1');
    $countStmt->execute([$productId]);
    $totalReviews = $countStmt->fetchColumn();
    
    // Get reviews
    $stmt = $pdo->prepare('
        SELECT 
            id, customer_name, rating, review_title, review_text,
            is_verified_purchase, helpful_count, not_helpful_count,
            admin_response, created_at
        FROM product_reviews
        WHERE product_id = ? AND is_approved = 1
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ');
    
    $stmt->execute([$productId, $perPage, $offset]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($reviews as &$review) {
        $review['created_at'] = date('F j, Y', strtotime($review['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_reviews' => intval($totalReviews),
            'total_pages' => ceil($totalReviews / $perPage)
        ]
    ]);
}

// ========================================
// SUBMIT REVIEW
// ========================================
function submitReview($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $productId = intval($_POST['product_id'] ?? 0);
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $reviewTitle = trim($_POST['review_title'] ?? '');
    $reviewText = trim($_POST['review_text'] ?? '');
    
    // Validate
    if (!$productId || !$customerName || !$customerEmail || !$rating || !$reviewTitle || !$reviewText) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        return;
    }
    
    // Check if product exists
    $productCheck = $pdo->prepare('SELECT id FROM products WHERE id = ?');
    $productCheck->execute([$productId]);
    if (!$productCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        return;
    }
    
    // Check if user has purchased this product (verified purchase)
    $isVerified = 0;
    $orderId = null;
    
    $orderCheck = $pdo->prepare('
        SELECT DISTINCT o.id 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_email = ? 
        AND oi.product_id = ?
        AND o.order_status != "cancelled"
        LIMIT 1
    ');
    $orderCheck->execute([$customerEmail, $productId]);
    $orderResult = $orderCheck->fetch();
    
    if ($orderResult) {
        $isVerified = 1;
        $orderId = $orderResult['id'];
    }
    
    // Check if user already reviewed this product
    $reviewCheck = $pdo->prepare('SELECT id FROM product_reviews WHERE product_id = ? AND customer_email = ?');
    $reviewCheck->execute([$productId, $customerEmail]);
    if ($reviewCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
        return;
    }
    
    // Insert review
    $stmt = $pdo->prepare('
        INSERT INTO product_reviews 
        (product_id, order_id, customer_name, customer_email, rating, review_title, review_text, is_verified_purchase, is_approved)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ');
    
    $stmt->execute([
        $productId,
        $orderId,
        $customerName,
        $customerEmail,
        $rating,
        $reviewTitle,
        $reviewText,
        $isVerified
    ]);
    
    // Update product average rating
    updateProductRating($pdo, $productId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully',
        'is_verified_purchase' => $isVerified == 1
    ]);
}

// ========================================
// VOTE HELPFUL/NOT HELPFUL
// ========================================
function voteHelpful($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        return;
    }
    
    $reviewId = intval($_POST['review_id'] ?? 0);
    $voteType = $_POST['vote_type'] ?? ''; // 'helpful' or 'not_helpful'
    $userIdentifier = $_POST['user_identifier'] ?? ''; // email or session ID
    
    if (!$reviewId || !$voteType || !$userIdentifier) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        return;
    }
    
    if (!in_array($voteType, ['helpful', 'not_helpful'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid vote type']);
        return;
    }
    
    // Check if user already voted
    $checkStmt = $pdo->prepare('SELECT id, vote_type FROM review_helpfulness WHERE review_id = ? AND user_identifier = ?');
    $checkStmt->execute([$reviewId, $userIdentifier]);
    $existingVote = $checkStmt->fetch();
    
    if ($existingVote) {
        if ($existingVote['vote_type'] === $voteType) {
            echo json_encode(['success' => false, 'message' => 'You have already voted']);
            return;
        }
        
        // User is changing their vote
        $updateStmt = $pdo->prepare('UPDATE review_helpfulness SET vote_type = ? WHERE id = ?');
        $updateStmt->execute([$voteType, $existingVote['id']]);
        
        // Update counts (decrement old, increment new)
        if ($voteType === 'helpful') {
            $pdo->prepare('UPDATE product_reviews SET helpful_count = helpful_count + 1, not_helpful_count = not_helpful_count - 1 WHERE id = ?')->execute([$reviewId]);
        } else {
            $pdo->prepare('UPDATE product_reviews SET helpful_count = helpful_count - 1, not_helpful_count = not_helpful_count + 1 WHERE id = ?')->execute([$reviewId]);
        }
        
    } else {
        // New vote
        $insertStmt = $pdo->prepare('INSERT INTO review_helpfulness (review_id, user_identifier, vote_type) VALUES (?, ?, ?)');
        $insertStmt->execute([$reviewId, $userIdentifier, $voteType]);
        
        // Update count
        if ($voteType === 'helpful') {
            $pdo->prepare('UPDATE product_reviews SET helpful_count = helpful_count + 1 WHERE id = ?')->execute([$reviewId]);
        } else {
            $pdo->prepare('UPDATE product_reviews SET not_helpful_count = not_helpful_count + 1 WHERE id = ?')->execute([$reviewId]);
        }
    }
    
    // Get updated counts
    $countStmt = $pdo->prepare('SELECT helpful_count, not_helpful_count FROM product_reviews WHERE id = ?');
    $countStmt->execute([$reviewId]);
    $counts = $countStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Vote recorded',
        'helpful_count' => intval($counts['helpful_count']),
        'not_helpful_count' => intval($counts['not_helpful_count'])
    ]);
}

// ========================================
// GET REVIEW STATISTICS
// ========================================
function getReviewStats($pdo) {
    $productId = intval($_GET['product_id'] ?? 0);
    
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        return;
    }
    
    // Get rating distribution
    $stmt = $pdo->prepare('
        SELECT 
            rating,
            COUNT(*) as count
        FROM product_reviews
        WHERE product_id = ? AND is_approved = 1
        GROUP BY rating
        ORDER BY rating DESC
    ');
    $stmt->execute([$productId]);
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format distribution (ensure all ratings 1-5 are present)
    $ratingDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($distribution as $row) {
        $ratingDist[intval($row['rating'])] = intval($row['count']);
    }
    
    // Get average and total
    $statsStmt = $pdo->prepare('
        SELECT 
            AVG(rating) as average_rating,
            COUNT(*) as total_reviews
        FROM product_reviews
        WHERE product_id = ? AND is_approved = 1
    ');
    $statsStmt->execute([$productId]);
    $stats = $statsStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'average_rating' => round(floatval($stats['average_rating']), 1),
        'total_reviews' => intval($stats['total_reviews']),
        'rating_distribution' => $ratingDist
    ]);
}

// ========================================
// HELPER: UPDATE PRODUCT RATING
// ========================================
function updateProductRating($pdo, $productId) {
    $stmt = $pdo->prepare('
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as review_count
        FROM product_reviews
        WHERE product_id = ? AND is_approved = 1
    ');
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    
    $avgRating = round(floatval($result['avg_rating']), 1);
    $reviewCount = intval($result['review_count']);
    
    $updateStmt = $pdo->prepare('UPDATE products SET average_rating = ?, review_count = ? WHERE id = ?');
    $updateStmt->execute([$avgRating, $reviewCount, $productId]);
}
?>