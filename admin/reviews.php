<?php
// admin/reviews.php - Review Moderation
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Review Moderation';

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reviewId = intval($_GET['id']);
    $action = $_GET['action'];
    
    try {
        $pdo = connectDB();
        
        if ($action === 'approve') {
            $stmt = $pdo->prepare('UPDATE product_reviews SET is_approved = 1 WHERE id = ?');
            $stmt->execute([$reviewId]);
            logAdminActivity($_SESSION['admin_id'], 'approve', 'review', $reviewId, 'Approved review');
            $msg = 'Review approved';
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare('UPDATE product_reviews SET is_approved = 0 WHERE id = ?');
            $stmt->execute([$reviewId]);
            logAdminActivity($_SESSION['admin_id'], 'reject', 'review', $reviewId, 'Rejected review');
            $msg = 'Review rejected';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM product_reviews WHERE id = ?');
            $stmt->execute([$reviewId]);
            logAdminActivity($_SESSION['admin_id'], 'delete', 'review', $reviewId, 'Deleted review');
            $msg = 'Review deleted';
        }
        
        header('Location: reviews.php?success=' . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $error = 'Action failed';
    }
}

// Fetch reviews
try {
    $pdo = connectDB();
    
    $filter = $_GET['filter'] ?? 'all';
    
    $sql = 'SELECT r.*, p.name as product_name FROM product_reviews r LEFT JOIN products p ON r.product_id = p.id WHERE 1=1';
    
    if ($filter === 'pending') {
        $sql .= ' AND r.is_approved = 0';
    } elseif ($filter === 'approved') {
        $sql .= ' AND r.is_approved = 1';
    }
    
    $sql .= ' ORDER BY r.created_at DESC';
    
    $stmt = $pdo->query($sql);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $reviews = [];
}

include('includes/header.php');
?>

<div class="card">
    <div class="card-header">
        <h3>Product Reviews</h3>
    </div>

    <!-- Filters -->
    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
        <a href="reviews.php?filter=all" class="btn <?php echo ($filter ?? 'all') === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
            All Reviews
        </a>
        <a href="reviews.php?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
            Pending
        </a>
        <a href="reviews.php?filter=approved" class="btn <?php echo $filter === 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">
            Approved
        </a>
    </div>

    <?php if (empty($reviews)): ?>
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-star" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
            <p>No reviews found</p>
        </div>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div style="border: 2px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                    <div>
                        <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                        <div style="color: #ffa500; margin: 5px 0;">
                            <?php for($i=0; $i<5; $i++): ?>
                                <?php if ($i < $review['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <small style="color: #999;"><?php echo formatDateTime($review['created_at']); ?></small>
                    </div>
                    <div>
                        <?php if ($review['is_verified_purchase']): ?>
                            <span class="badge badge-success">Verified Purchase</span>
                        <?php endif; ?>
                        <?php if ($review['is_approved']): ?>
                            <span class="badge badge-success">Approved</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-bottom: 10px;">
                    <strong>Product:</strong> <?php echo htmlspecialchars($review['product_name']); ?>
                </div>

                <div style="margin-bottom: 10px;">
                    <strong><?php echo htmlspecialchars($review['review_title']); ?></strong>
                </div>

                <p style="margin-bottom: 15px;"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>

                <div style="display: flex; gap: 10px;">
                    <?php if (!$review['is_approved']): ?>
                        <a href="reviews.php?action=approve&id=<?php echo $review['id']; ?>" class="btn btn-success" style="padding: 6px 12px; font-size: 0.85rem;">
                            <i class="fas fa-check"></i> Approve
                        </a>
                    <?php else: ?>
                        <a href="reviews.php?action=reject&id=<?php echo $review['id']; ?>" class="btn btn-warning" style="padding: 6px 12px; font-size: 0.85rem;">
                            <i class="fas fa-times"></i> Reject
                        </a>
                    <?php endif; ?>
                    <a href="reviews.php?action=delete&id=<?php echo $review['id']; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem;" onclick="return confirmDelete('Delete this review?')">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
