<?php
// admin/categories.php - Categories Management
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Categories';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = connectDB();
        
        // Add new category
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', $name));
            $description = trim($_POST['description']);
            
            $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, status) VALUES (?, ?, ?, "active")');
            $stmt->execute([$name, $slug, $description]);
            
            $success = "Category added successfully!";
        }
        
        // Edit category
        if (isset($_POST['action']) && $_POST['action'] === 'edit') {
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', $name));
            $description = trim($_POST['description']);
            $status = $_POST['status'];
            
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, status = ? WHERE id = ?');
            $stmt->execute([$name, $slug, $description, $status, $id]);
            
            $success = "Category updated successfully!";
        }
        
        // Delete category
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            
            $success = "Category deleted successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all categories
try {
    $pdo = connectDB();
    $stmt = $pdo->query('SELECT c.*, COUNT(p.id) as product_count 
                         FROM categories c 
                         LEFT JOIN products p ON c.name = p.category 
                         GROUP BY c.id 
                         ORDER BY c.display_order ASC, c.name ASC');
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching categories: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - SP Gadgets Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root[data-theme="dark"] {
            --bg-primary: #0f0f0f;
            --bg-secondary: #212121;
            --bg-tertiary: #282828;
            --bg-hover: #3f3f3f;
            --text-primary: #ffffff;
            --text-secondary: #aaaaaa;
            --text-tertiary: #717171;
            --accent: #1F95B1;
            --accent-hover: #5CB9A4;
            --border: #3f3f3f;
            --success: #0f9d58;
            --warning: #f9ab00;
            --error: #dd2c00;
            --sidebar-width: 240px;
        }

        :root[data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #e9ecef;
            --bg-hover: #dee2e6;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --text-tertiary: #adb5bd;
            --accent: #1F95B1;
            --accent-hover: #5CB9A4;
            --border: #dee2e6;
            --success: #0f9d58;
            --warning: #f9ab00;
            --error: #dd2c00;
            --sidebar-width: 240px;
        }

        *, *::before, *::after {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Same as index.php */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid var(--border);
            z-index: 100;
        }

        .sidebar::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--bg-hover);
            border-radius: 4px;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .sidebar-logo img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }

        .sidebar-logo-text {
            font-size: 18px;
            font-weight: 500;
        }

        .sidebar-nav {
            padding: 12px 0;
        }

        .nav-section-title {
            padding: 8px 20px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-tertiary);
            font-weight: 500;
            margin-top: 16px;
        }

        .nav-section-title:first-child {
            margin-top: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 10px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
            font-weight: 400;
        }

        .nav-item:hover {
            background: var(--bg-hover);
        }

        .nav-item.active {
            background: var(--bg-hover);
            border-left: 3px solid var(--accent);
            padding-left: 17px;
        }

        .nav-item i {
            width: 24px;
            font-size: 20px;
            color: var(--text-primary);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 500;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: background 0.2s;
        }

        .icon-btn:hover {
            background: var(--bg-hover);
        }

        .mobile-menu-btn {
            display: none;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
        }

        /* Content Area */
        .content-area {
            padding: 24px;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(15, 157, 88, 0.15);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .alert-error {
            background: rgba(221, 44, 0, 0.15);
            color: var(--error);
            border: 1px solid var(--error);
        }

        /* Header with Actions */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 500;
        }

        /* Button */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .category-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }

        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .category-name {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .category-slug {
            font-size: 12px;
            color: var(--text-tertiary);
        }

        .category-actions {
            display: flex;
            gap: 8px;
        }

        .category-description {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .category-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .product-count {
            font-size: 13px;
            color: var(--text-tertiary);
        }

        .product-count strong {
            color: var(--text-primary);
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .badge-success {
            background: rgba(15, 157, 88, 0.15);
            color: var(--success);
        }

        .badge-inactive {
            background: rgba(107, 114, 128, 0.15);
            color: var(--text-tertiary);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-secondary);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 500;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-tertiary);
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <img src="../assets/icon.png" alt="SP Gadgets">
                    <span class="sidebar-logo-text">SP Gadgets</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Main</div>
                <a href="index.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a href="customers.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>

                <div class="nav-section-title">Content</div>
                <a href="reviews.php" class="nav-item">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <a href="categories.php" class="nav-item active">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>

                <div class="nav-section-title">Settings</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="icon-btn mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">Categories</h1>
                </div>
                <div class="top-bar-right">
                    <button class="icon-btn" onclick="toggleTheme()" title="Toggle Theme">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    <button class="icon-btn">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="page-header">
                    <h1>Product Categories</h1>
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <i class="fas fa-plus"></i>
                        Add Category
                    </button>
                </div>

                <div class="categories-grid">
                    <?php if (empty($categories)): ?>
                        <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                            <i class="fas fa-tags" style="font-size: 48px; opacity: 0.3; margin-bottom: 16px; display: block;"></i>
                            <p style="color: var(--text-secondary);">No categories yet. Add your first category to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="category-card">
                                <div class="category-header">
                                    <div>
                                        <div class="category-name"><?php echo htmlspecialchars($category['name']); ?></div>
                                        <div class="category-slug">/<?php echo htmlspecialchars($category['slug']); ?></div>
                                    </div>
                                    <div class="category-actions">
                                        <button class="btn btn-secondary btn-sm" onclick='editCategory(<?php echo json_encode($category); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <?php if ($category['description']): ?>
                                    <div class="category-description">
                                        <?php echo htmlspecialchars($category['description']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="category-footer">
                                    <div class="product-count">
                                        <strong><?php echo $category['product_count']; ?></strong> products
                                    </div>
                                    <span class="badge <?php echo $category['status'] === 'active' ? 'badge-success' : 'badge-inactive'; ?>">
                                        <?php echo ucfirst($category['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Category Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Category</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" required placeholder="e.g., Smartphones">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Brief description of this category..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Category</h3>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_description"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Delete Category</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<strong id="delete_name"></strong>"?</p>
                    <p style="color: var(--error); margin-top: 12px;">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Theme Toggle
        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
        });

        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            updateThemeIcon(newTheme);
        }

        function updateThemeIcon(theme) {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
            }
        }

        function loadTheme() {
            const savedTheme = localStorage.getItem('admin-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        }

        // Modal Functions
        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';
            document.getElementById('edit_status').value = category.status;
            document.getElementById('editModal').classList.add('active');
        }

        function deleteCategory(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            document.getElementById('deleteModal').classList.add('active');
        }

        // Close modal on overlay click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>