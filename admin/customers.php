<?php
// admin/customers.php - Customer Management
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Customers';

// Fetch customers
try {
    $pdo = connectDB();
    
    $search = $_GET['search'] ?? '';
    
    $sql = 'SELECT 
                customer_email,
                customer_name,
                COUNT(DISTINCT id) as order_count,
                SUM(total_amount) as total_spent,
                MAX(created_at) as last_order
            FROM orders 
            WHERE 1=1';
    
    $params = [];
    
    if ($search) {
        $sql .= ' AND (customer_name LIKE ? OR customer_email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $sql .= ' GROUP BY customer_email, customer_name ORDER BY total_spent DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $customers = [];
}

include('includes/header.php');
?>

<div class="card">
    <div class="card-header">
        <h3>Customers</h3>
    </div>

    <!-- Search -->
    <form method="GET" style="margin-bottom: 20px;">
        <div style="display: flex; gap: 15px;">
            <input type="text" name="search" placeholder="Search by name or email..." 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   style="flex: 1; padding: 10px; border: 2px solid #dee2e6; border-radius: 6px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <a href="customers.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
        </div>
    </form>

    <!-- Customers Table -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Last Order</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <p>No customers found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($customer['customer_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer['customer_email']); ?></td>
                            <td><?php echo $customer['order_count']; ?></td>
                            <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                            <td><?php echo formatDate($customer['last_order']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; text-align: center; color: #999;">
        Total: <?php echo count($customers); ?> customer(s)
    </div>
</div>

<?php include('includes/footer.php'); ?>
