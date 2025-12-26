<?php
// admin/add-product.php - Add New Product
define('ADMIN_PAGE', true);
require_once('config.php');
requireAdminLogin();

$pageTitle = 'Add Product';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = connectDB();
        
        // Validate required fields
        $required = ['name', 'brand', 'category', 'price', 'stock_quantity', 'condition'];
        $errors = [];
        
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        
        if (empty($errors)) {
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../assets/products/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($fileExt, $allowedExts)) {
                    $fileName = uniqid('product_') . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'assets/products/' . $fileName;
                    }
                }
            }
            
            // Insert product
            $stmt = $pdo->prepare('INSERT INTO products 
                (name, brand, category, description, price, stock_quantity, `item_condition`, image, specifications, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            
            $stmt->execute([
                $_POST['name'],
                $_POST['brand'],
                $_POST['category'],
                $_POST['description'],
                $_POST['price'],
                $_POST['stock_quantity'],
                $_POST['condition'],
                $imagePath,
                $_POST['specifications']
            ]);
            
            $success = "Product added successfully!";
            header('Location: products.php?success=' . urlencode($success));
            exit;
        }
    } catch (Exception $e) {
        $error = "Error adding product: " . $e->getMessage();
    }
}

// Fetch categories
try {
    $pdo = connectDB();
    $categoriesStmt = $pdo->query('SELECT DISTINCT category FROM products ORDER BY category');
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - SP Gadgets Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
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

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Same as before */
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

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
            font-size: 14px;
        }

        .back-btn:hover {
            color: var(--accent);
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
            max-width: 1000px;
        }

        /* Form */
        .form-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
        }

        .form-section {
            margin-bottom: 32px;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        .form-section-title {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }

        label .required {
            color: var(--error);
        }

        input[type="text"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--accent);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Image Upload */
        .image-upload {
            position: relative;
        }

        .image-preview {
            width: 100%;
            max-width: 300px;
            height: 300px;
            border: 2px dashed var(--border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: var(--bg-tertiary);
            margin-top: 8px;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview-placeholder {
            text-align: center;
            color: var(--text-secondary);
        }

        .image-preview-placeholder i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
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

        .btn-secondary:hover {
            background: var(--bg-tertiary);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        /* Alert */
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(15, 157, 88, 0.15);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(221, 44, 0, 0.15);
            border: 1px solid var(--error);
            color: var(--error);
        }

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        /* Helper Text */
        .helper-text {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Mobile Responsive */
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

            .content-area {
                padding: 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                padding: 12px 16px;
            }

            .page-title {
                font-size: 18px;
            }

            .back-btn span {
                display: none;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        .mobile-menu-btn {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
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
                <a href="products.php" class="nav-item active">
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
                <a href="categories.php" class="nav-item">
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
                    <a href="products.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        <span>Back to Products</span>
                    </a>
                </div>
                <div class="top-bar-right">
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
                <h1 style="margin-bottom: 24px;">Add New Product</h1>

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

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Please fix the following errors:</strong>
                            <ul>
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo $err; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-card">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3 class="form-section-title">Basic Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Product Name <span class="required">*</span></label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Brand <span class="required">*</span></label>
                                    <input type="text" name="brand" value="<?php echo htmlspecialchars($_POST['brand'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Category <span class="required">*</span></label>
                                    <select name="category" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars(ucfirst($cat)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="laptops">Laptops</option>
                                        <option value="phones">Phones</option>
                                        <option value="tablets">Tablets</option>
                                        <option value="accessories">Accessories</option>
                                        <option value="gaming">Gaming</option>
                                        <option value="audio">Audio</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Condition <span class="required">*</span></label>
                                    <select name="condition" required>
                                        <option value="">Select Condition</option>
                                        <option value="new" <?php echo (isset($_POST['condition']) && $_POST['condition'] === 'new') ? 'selected' : ''; ?>>New</option>
                                        <option value="used" <?php echo (isset($_POST['condition']) && $_POST['condition'] === 'used') ? 'selected' : ''; ?>>Used</option>
                                        <option value="refurbished" <?php echo (isset($_POST['condition']) && $_POST['condition'] === 'refurbished') ? 'selected' : ''; ?>>Refurbished</option>
                                    </select>
                                </div>

                                <div class="form-group full-width">
                                    <label>Description</label>
                                    <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    <span class="helper-text">Provide a detailed description of the product</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory -->
                        <div class="form-section">
                            <h3 class="form-section-title">Pricing & Inventory</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Price (₦) <span class="required">*</span></label>
                                    <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Stock Quantity <span class="required">*</span></label>
                                    <input type="number" name="stock_quantity" min="0" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Product Image -->
                        <div class="form-section">
                            <h3 class="form-section-title">Product Image</h3>
                            <div class="form-group">
                                <label>Upload Image</label>
                                <input type="file" name="image" accept="image/*" onchange="previewImage(this)">
                                <span class="helper-text">Recommended size: 800x800px. Max file size: 5MB</span>
                                <div class="image-preview" id="imagePreview">
                                    <div class="image-preview-placeholder">
                                        <i class="fas fa-image"></i>
                                        <p>Image preview will appear here</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specifications -->
                        <div class="form-section">
                            <h3 class="form-section-title">Specifications</h3>
                            <div class="form-group">
                                <label>Product Specifications</label>
                                <textarea name="specifications"><?php echo htmlspecialchars($_POST['specifications'] ?? ''); ?></textarea>
                                <span class="helper-text">Enter specifications, one per line (e.g., "Processor: Intel Core i7")</span>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Add Product
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    </script>
</body>
</html>