<?php
    // shop.php - The Dynamic Product Catalogue

    // Define the project's base URL (Update 'sp-gadgets' if necessary)
    $project_base = '/sp-gadgets/'; 
    
    // 1. INCLUDE DATABASE CONFIGURATION
    require_once('../api/config.php');

    // 2. FETCH INITIAL BATCH OF PRODUCTS FROM THE DATABASE (Max 30)
    $allProductsJson = '[]'; // Default empty array in case of failure or no products
    $uniqueCategories = [];
    $uniqueBrands = [];
    $minPrice = 0;
    $maxPrice = 10000;
    
    try {
        $pdo = connectDB();
        
        // Fetch products with stock_quantity
        $stmt = $pdo->prepare('SELECT 
            id, name, brand, category, price, item_condition, image, stock_quantity 
            FROM products
            LIMIT 30');

        $stmt->execute();
            
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Encode the PHP array into a JSON string for JavaScript
        $allProductsJson = json_encode($products);
        
        // Fetch unique categories
        $categoryStmt = $pdo->prepare('SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != "" ORDER BY category');
        $categoryStmt->execute();
        $uniqueCategories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Fetch unique brands
        $brandStmt = $pdo->prepare('SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != "" ORDER BY brand');
        $brandStmt->execute();
        $uniqueBrands = $brandStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Fetch min and max prices for the price slider
        $priceStmt = $pdo->prepare('SELECT MIN(price) as min_price, MAX(price) as max_price FROM products');
        $priceStmt->execute();
        $priceRange = $priceStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($priceRange) {
            $minPrice = floor($priceRange['min_price']);
            $maxPrice = ceil($priceRange['max_price']);
        }
        
    } catch (\Exception $e) {
        // Log error (for debugging)
        error_log("Error fetching products: " . $e->getMessage());
        // For security, do not show the error to the user on the live page
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Gadgets - Shop</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="../styles/shop.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
      
        :root {
            --primary: #1F95B1;
            --accent: #5CB9A4;
            --text: #0f172a;
            --text-muted: #6b7280;
            --bg: #ffffff;
            --bg-light: #f8fafc;
            --border: #e2e8f0;
            
            /* Updated color mappings for the shop page */
            --primary-dark: #0f172a;
            --primary-medium: #1F95B1;
            --accent-warm: #5CB9A4;
            --accent-terracotta: #1F95B1;
            --neutral-light: #f8fafc;
            --neutral-mid: #e2e8f0;
            --white: #ffffff;
            --shadow-subtle: 0 2px 12px rgba(31, 149, 177, 0.08);
            --shadow-hover: 0 8px 24px rgba(31, 149, 177, 0.15);
            --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, var(--neutral-light) 0%, var(--neutral-mid) 100%);
            color: var(--primary-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .header {
            background: var(--primary-dark);
            color: var(--white);
            padding: 2rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--accent-warm), var(--accent-terracotta));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cart-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            color: var(--neutral-light);
        }

        .cart-count {
            background: var(--accent-terracotta);
            color: var(--white);
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .shop-container {
            max-width: 1400px;
            margin: 3rem auto;
            padding: 0 2rem;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-title {
          font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: var(--primary-medium);
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        /* Mobile Filter Toggle Button */
        .filter-toggle-btn {
            display: none;
            width: 100%;
            padding: 1rem;
            background: var(--primary-dark);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .filter-toggle-btn:hover {
            background: var(--accent-terracotta);
        }

        @media (max-width: 1023px) {
            .filter-toggle-btn {
                display: flex;
            }
            
            .shop-filter-sidebar {
                display: none;
            }
            
            .shop-filter-sidebar.active {
                display: block;
            }
        }

        .shop-main-content {
            display: grid;
            gap: 3rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 1024px) {
            .shop-main-content {
                grid-template-columns: 280px 1fr;
            }
        }

        .shop-filter-sidebar {
            background: var(--white);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-subtle);
            height: fit-content;
            position: sticky;
            top: 120px;
            animation: slideInLeft 0.6s ease-out 0.3s both;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 1023px) {
            .shop-filter-sidebar {
                position: relative;
                top: 0;
            }
        }

        .filter-header {
            font-family: 'Crimson Pro', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--accent-warm);
        }

        /* Collapsible Filter Section Styles */
        .filter-section {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--neutral-mid);
        }

        .filter-section:last-of-type {
            border-bottom: none;
        }

        .filter-section details {
            margin-bottom: 1rem;
        }

        .filter-section summary {
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary-medium);
            padding: 0.75rem 0;
            cursor: pointer;
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .filter-section summary::-webkit-details-marker {
            display: none;
        }

        .filter-section summary::after {
            content: '+';
            font-size: 1.2rem;
            font-weight: 700;
            transition: transform 0.3s ease;
        }

        .filter-section details[open] summary::after {
            content: '−';
        }

        .filter-section summary:hover {
            color: var(--accent-terracotta);
        }

        .filter-title {
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary-medium);
            margin-bottom: 1rem;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem 0;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            padding: 0.25rem 0;
        }

        .checkbox-item:hover {
            color: var(--accent-terracotta);
            transform: translateX(4px);
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--accent-terracotta);
        }

        .checkbox-item label {
            cursor: pointer;
            font-size: 0.95rem;
        }

        /* Price Range Slider Styles */
        .price-range-container {
            padding: 1rem 0;
        }

        .price-display {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--primary-medium);
            font-weight: 600;
        }

        .price-slider-wrapper {
            position: relative;
            height: 40px;
            margin-bottom: 1rem;
        }

        .price-slider {
            position: absolute;
            width: 100%;
            height: 5px;
            background: var(--neutral-mid);
            border-radius: 5px;
            top: 50%;
            transform: translateY(-50%);
        }

        .price-slider-range {
            position: absolute;
            height: 5px;
            background: var(--accent-terracotta);
            border-radius: 5px;
            top: 50%;
            transform: translateY(-50%);
        }

        input[type="range"] {
            position: absolute;
            width: 100%;
            height: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
        }

        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: var(--primary-dark);
            border: 3px solid var(--white);
            border-radius: 50%;
            cursor: pointer;
            pointer-events: all;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        input[type="range"]::-webkit-slider-thumb:hover {
            background: var(--accent-terracotta);
            transform: scale(1.1);
        }

        input[type="range"]::-moz-range-thumb {
            width: 18px;
            height: 18px;
            background: var(--primary-dark);
            border: 3px solid var(--white);
            border-radius: 50%;
            cursor: pointer;
            pointer-events: all;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        input[type="range"]::-moz-range-thumb:hover {
            background: var(--accent-terracotta);
            transform: scale(1.1);
        }

        .clear-filters {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary-dark);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1.5rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
        }

        .clear-filters:hover {
            background: var(--accent-terracotta);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .product-section {
            animation: slideInRight 0.6s ease-out 0.4s both;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-input {
            flex-grow: 1;
            max-width: 400px;
            padding: 0.75rem 1rem;
            border: 2px solid var(--neutral-mid);
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'DM Sans', sans-serif;
            transition: var(--transition);
            box-shadow: var(--shadow-subtle);
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .search-input::placeholder {
            color: var(--primary-medium);
            opacity: 0.7;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-warm);
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.2);
        }

        .results-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
            .search-input {
                max-width: 100%;
            }
            .results-meta {
                justify-content: space-between;
                width: 100%;
                margin-top: 0.5rem;
            }
        }

        .results-count {
            font-size: 1.1rem;
            color: var(--primary-medium);
        }

        .sort-select {
            padding: 0.65rem 1rem;
            border: 2px solid var(--neutral-mid);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            background: var(--white);
            cursor: pointer;
            transition: var(--transition);
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--accent-warm);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .product-card {
            background: var(--white);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-subtle);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            animation: scaleIn 0.5s ease-out both;
        }

        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.15s; }
        .product-card:nth-child(3) { animation-delay: 0.2s; }
        .product-card:nth-child(4) { animation-delay: 0.25s; }
        .product-card:nth-child(5) { animation-delay: 0.3s; }
        .product-card:nth-child(6) { animation-delay: 0.35s; }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .product-image-wrapper {
            position: relative;
            width: 100%;
            height: 280px;
            background: linear-gradient(135deg, var(--neutral-light), var(--neutral-mid));
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-condition {
            position: absolute;
            top: 1rem;
            left: 1rem;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .condition-new {
            background: rgba(40, 167, 69, 0.95);
            color: var(--white);
        }

        .condition-refurbished {
            background: rgba(212, 165, 116, 0.95);
            color: var(--primary-dark);
        }

        .condition-used {
            background: rgba(108, 117, 125, 0.95);
            color: var(--white);
        }

        /* Out of Stock Badge */
        .out-of-stock-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(220, 53, 69, 0.95);
            color: var(--white);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .product-card.out-of-stock .product-image {
            opacity: 0.5;
            filter: grayscale(50%);
        }

        .product-details {
            padding: 1.5rem;
        }

        .product-category {
            font-size: 0.8rem;
            color: var(--accent-terracotta);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .product-name {
            font-family: 'Crimson Pro', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .product-brand {
            font-size: 0.9rem;
            color: var(--primary-medium);
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent-terracotta);
            margin-bottom: 1rem;
            font-family: 'Crimson Pro', serif;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 0.85rem;
            background: var(--primary-dark);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'DM Sans', sans-serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .add-to-cart-btn:hover:not(:disabled) {
            background: var(--accent-terracotta);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(200, 92, 62, 0.3);
        }

        .add-to-cart-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .add-to-cart-btn:disabled {
            background: var(--neutral-mid);
            color: var(--text-muted);
            cursor: not-allowed;
            opacity: 0.6;
        }

        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 3rem;
            padding: 2rem 0;
        }

        .pagination-btn,
        .page-number {
            padding: 0.75rem 1.25rem;
            background: var(--white);
            border: 2px solid var(--neutral-mid);
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            color: var(--primary-dark);
        }

        .pagination-btn:hover:not(:disabled),
        .page-number:hover {
            background: var(--primary-dark);
            color: var(--white);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .pagination-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .page-number.active {
            background: var(--accent-terracotta);
            color: var(--white);
            border-color: var(--accent-terracotta);
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-subtle);
        }

        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .no-results h3 {
            font-family: 'Crimson Pro', serif;
            font-size: 2rem;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: var(--primary-medium);
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
            }

            .shop-main-content {
                gap: 2rem;
            }
        }
    </style>
</head>
<body>
    
  <!-- Navbar -->
  <header class="navbar">
    <div class="navbar-container">
      <!-- Left: Logo + Brand -->
      <div class="navbar-brand">
        <img src="../assets/icon.png" alt="SP Gadgets" class="navbar-logo">
        <div class="navbar-text">
          <h1 class="navbar-title">SP Gadgets</h1>
          <p class="navbar-subtitle">Shinkomania Plug</p>
        </div>
      </div>

      <!-- Center: Nav Links (Desktop) -->
      <nav class="navbar-nav" aria-label="Primary navigation">
        <a href="#home" class="nav-link">Home</a>
        <a href="#products" class="nav-link">Products</a>
        <a href="#features" class="nav-link">Features</a>
        <a href="#contact" class="nav-link">Contact</a>
      </nav>

      <!-- Right: Actions -->
      <div class="navbar-actions">
        <!-- Cart Icon -->
        <button class="cart-btn" aria-label="Shopping cart">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/>
            <circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <span class="cart-badge" id="cart-badge" aria-hidden="true">0</span>
        </button>

        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menu-toggle" aria-label="Menu" aria-expanded="false">
          <span class="hamburger"></span>
          <span class="hamburger"></span>
          <span class="hamburger"></span>
        </button>
      </div>
    </div>
  </header>

  <main>
        <div class="shop-container">
        <h2 class="page-title">Premium Electronics</h2>
        <p class="page-subtitle">Handpicked tech for discerning enthusiasts</p>

        <!-- Mobile Filter Toggle Button -->
        <button class="filter-toggle-btn" id="filterToggleBtn">
            <i class="fas fa-filter"></i>
            <span>Filters</span>
        </button>

        <div class="shop-main-content">
            <aside class="shop-filter-sidebar" id="filterSidebar">
                <h3 class="filter-header">Filters</h3>

                <!-- Category Filter (Collapsible) -->
                <div class="filter-section">
                    <details open>
                        <summary>Category</summary>
                        <div class="checkbox-group" id="categoryFilters">
                            <?php foreach($uniqueCategories as $category): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" value="<?php echo htmlspecialchars($category); ?>" data-filter="category">
                                <span><?php echo htmlspecialchars(ucfirst($category)); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>

                <!-- Condition Filter (Collapsible) -->
                <div class="filter-section">
                    <details open>
                        <summary>Condition</summary>
                        <div class="checkbox-group" id="conditionFilters">
                            <label class="checkbox-item">
                                <input type="checkbox" value="new" data-filter="condition">
                                <span>New</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" value="refurbished" data-filter="condition">
                                <span>Refurbished</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" value="used" data-filter="condition">
                                <span>Used</span>
                            </label>
                        </div>
                    </details>
                </div>

                <!-- Brand Filter (Collapsible) -->
                <div class="filter-section">
                    <details open>
                        <summary>Brand</summary>
                        <div class="checkbox-group" id="brandFilters">
                            <?php foreach($uniqueBrands as $brand): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" value="<?php echo htmlspecialchars($brand); ?>" data-filter="brand">
                                <span><?php echo htmlspecialchars($brand); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>

                <!-- Price Range Filter (Collapsible with Double Slider) -->
                <div class="filter-section">
                    <details open>
                        <summary>Price Range</summary>
                        <div class="price-range-container">
                            <div class="price-display">
                                <span id="minPriceDisplay">₦<?php echo number_format($minPrice); ?></span>
                                <span id="maxPriceDisplay">₦<?php echo number_format($maxPrice); ?></span>
                            </div>
                            <div class="price-slider-wrapper">
                                <div class="price-slider">
                                    <div class="price-slider-range" id="priceSliderRange"></div>
                                </div>
                                <input type="range" id="minPriceSlider" min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" value="<?php echo $minPrice; ?>" step="100">
                                <input type="range" id="maxPriceSlider" min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" value="<?php echo $maxPrice; ?>" step="100">
                            </div>
                            <!-- Hidden inputs for actual filter values -->
                            <input type="hidden" id="minPrice" value="<?php echo $minPrice; ?>">
                            <input type="hidden" id="maxPrice" value="<?php echo $maxPrice; ?>">
                        </div>
                    </details>
                </div>

                <button class="clear-filters" id="clearFilters">Clear All Filters</button>
            </aside>

            <section class="product-section">
                <input type="text" id="searchBar" placeholder="Search by product name or brand..." class="search-input">
                <div class="results-header">
                    <div class="results-count" id="resultsCount">Showing 0 products</div>
                    <select class="sort-select" id="sortSelect">
                        <option value="featured">Featured</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="name">Name: A to Z</option>
                    </select>
                </div>

                <div class="product-grid" id="productGrid">
                    <!-- Products will be dynamically inserted here -->
                </div>

                <div class="pagination-controls" id="paginationControls">
                    <!-- Pagination will be dynamically inserted here -->
                </div>
            </section>
        </div>
    </div>
  </main>
  
  <!-- Pass PHP data to JavaScript -->
  <script>
    // Make product data and price range available to JavaScript
    window.allProducts = <?php echo $allProductsJson; ?>;
    window.priceRange = {
        min: <?php echo $minPrice; ?>,
        max: <?php echo $maxPrice; ?>
    };

    // assets/js/shop.js - Complete Shop Functionality

// ========================================
// STATE MANAGEMENT
// ========================================
const shopState = {
    allProducts: [],
    filteredProducts: [],
    currentPage: 1,
    productsPerPage: 9,
    filters: {
        categories: [],
        brands: [],
        conditions: [],
        minPrice: 0,
        maxPrice: Infinity,
    },
    searchQuery: '',
    sortBy: 'featured'
};

// ========================================
// INITIALIZATION
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    initializeShop();
});

function initializeShop() {
    // Load products from window object (passed from PHP)
    if (window.allProducts && Array.isArray(window.allProducts)) {
        shopState.allProducts = window.allProducts;
        shopState.filteredProducts = [...shopState.allProducts];
        
        // Initialize price filter with actual min/max
        if (window.priceRange) {
            shopState.filters.minPrice = window.priceRange.min;
            shopState.filters.maxPrice = window.priceRange.max;
        }
    } else {
        console.error('No products data found');
        shopState.allProducts = [];
        shopState.filteredProducts = [];
    }
    
    // Initialize UI
    initializePriceSlider();
    initializeEventListeners();
    getInitialCartCount();
    
    // Initial render
    applyFilters();
}

// ========================================
// CART API INTEGRATION
// ========================================

/**
 * Fetch initial cart count from server
 */
async function getInitialCartCount() {
    try {
        const response = await fetch('../api/cart_api.php?action=get_count');
        const data = await response.json();
        
        if (data.success && typeof data.count !== 'undefined') {
            updateCartCountUI(data.count);
        }
    } catch (error) {
        console.error('Error fetching cart count:', error);
    }
}

/**
 * Add product to cart on server
 */
async function updateCartOnServer(productId) {
    try {
        const response = await fetch('../api/cart_api.php?action=add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateCartCountUI(data.count);
            showAddToCartFeedback(productId);
            return true;
        } else {
            console.error('Failed to add to cart:', data.message);
            return false;
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        return false;
    }
}

/**
 * Update cart count in UI
 */
function updateCartCountUI(count) {
    const cartBadge = document.getElementById('cart-badge');
    if (cartBadge) {
        cartBadge.textContent = count;
        
        // Add animation
        cartBadge.style.transform = 'scale(1.3)';
        setTimeout(() => {
            cartBadge.style.transform = 'scale(1)';
        }, 200);
    }
}

/**
 * Show visual feedback when item is added to cart
 */
function showAddToCartFeedback(productId) {
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    if (button) {
        const originalText = button.textContent;
        button.textContent = '✓ Added!';
        button.style.background = '#28a745';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 1500);
    }
}

// ========================================
// EVENT LISTENERS
// ========================================
function initializeEventListeners() {
    // Filter checkboxes
    const filterCheckboxes = document.querySelectorAll('input[data-filter]');
    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', handleFilterChange);
    });
    
    // Price sliders
    const minPriceSlider = document.getElementById('minPriceSlider');
    const maxPriceSlider = document.getElementById('maxPriceSlider');
    
    if (minPriceSlider) {
        minPriceSlider.addEventListener('input', handlePriceSliderChange);
    }
    if (maxPriceSlider) {
        maxPriceSlider.addEventListener('input', handlePriceSliderChange);
    }
    
    // Search input
    const searchBar = document.getElementById('searchBar');
    if (searchBar) {
        searchBar.addEventListener('input', handleSearchInput);
    }
    
    // Sort select
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', handleSortChange);
    }
    
    // Clear filters button
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }
    
    // Mobile filter toggle
    const filterToggleBtn = document.getElementById('filterToggleBtn');
    const filterSidebar = document.getElementById('filterSidebar');
    
    if (filterToggleBtn && filterSidebar) {
        filterToggleBtn.addEventListener('click', () => {
            filterSidebar.classList.toggle('active');
        });
    }
}

/**
 * Re-attach event listeners to "Add to Cart" buttons after rendering
 */
function attachAddToCartListeners() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = button.getAttribute('data-product-id');
            if (productId && !button.disabled) {
                await updateCartOnServer(productId);
            }
        });
    });
}

// ========================================
// PRICE SLIDER FUNCTIONALITY
// ========================================
function initializePriceSlider() {
    const minSlider = document.getElementById('minPriceSlider');
    const maxSlider = document.getElementById('maxPriceSlider');
    const sliderRange = document.getElementById('priceSliderRange');
    
    if (!minSlider || !maxSlider || !sliderRange) return;
    
    const min = parseInt(minSlider.min);
    const max = parseInt(minSlider.max);
    
    // Update visual range
    updatePriceSliderVisual();
}

function handlePriceSliderChange() {
    const minSlider = document.getElementById('minPriceSlider');
    const maxSlider = document.getElementById('maxPriceSlider');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    
    let minVal = parseInt(minSlider.value);
    let maxVal = parseInt(maxSlider.value);
    
    // Ensure min doesn't exceed max
    if (minVal > maxVal - 100) {
        minVal = maxVal - 100;
        minSlider.value = minVal;
    }
    
    // Update hidden inputs
    minPriceInput.value = minVal;
    maxPriceInput.value = maxVal;
    
    // Update display
    updatePriceSliderVisual();
    
    // Update filters
    shopState.filters.minPrice = minVal;
    shopState.filters.maxPrice = maxVal;
    
    applyFilters();
}

function updatePriceSliderVisual() {
    const minSlider = document.getElementById('minPriceSlider');
    const maxSlider = document.getElementById('maxPriceSlider');
    const sliderRange = document.getElementById('priceSliderRange');
    const minDisplay = document.getElementById('minPriceDisplay');
    const maxDisplay = document.getElementById('maxPriceDisplay');
    
    if (!minSlider || !maxSlider) return;
    
    const min = parseInt(minSlider.min);
    const max = parseInt(minSlider.max);
    const minVal = parseInt(minSlider.value);
    const maxVal = parseInt(maxSlider.value);
    
    // Update text displays
    if (minDisplay) minDisplay.textContent = `₦${minVal.toLocaleString()}`;
    if (maxDisplay) maxDisplay.textContent = `₦${maxVal.toLocaleString()}`;
    
    // Update visual range bar
    if (sliderRange) {
        const percentMin = ((minVal - min) / (max - min)) * 100;
        const percentMax = ((maxVal - min) / (max - min)) * 100;
        
        sliderRange.style.left = percentMin + '%';
        sliderRange.style.width = (percentMax - percentMin) + '%';
    }
}

// ========================================
// FILTER HANDLING
// ========================================
function handleFilterChange(e) {
    const filterType = e.target.getAttribute('data-filter');
    const value = e.target.value;
    const isChecked = e.target.checked;
    
    if (filterType === 'category') {
        if (isChecked) {
            shopState.filters.categories.push(value);
        } else {
            shopState.filters.categories = shopState.filters.categories.filter(c => c !== value);
        }
    } else if (filterType === 'brand') {
        if (isChecked) {
            shopState.filters.brands.push(value);
        } else {
            shopState.filters.brands = shopState.filters.brands.filter(b => b !== value);
        }
    } else if (filterType === 'condition') {
        if (isChecked) {
            shopState.filters.conditions.push(value);
        } else {
            shopState.filters.conditions = shopState.filters.conditions.filter(c => c !== value);
        }
    }
    
    applyFilters();
}

function handleSearchInput(e) {
    shopState.searchQuery = e.target.value.toLowerCase().trim();
    applyFilters();
}

function handleSortChange(e) {
    shopState.sortBy = e.target.value;
    sortProducts();
    renderProducts();
}

function clearAllFilters() {
    // Reset filter state
    shopState.filters = {
        categories: [],
        brands: [],
        conditions: [],
        minPrice: window.priceRange ? window.priceRange.min : 0,
        maxPrice: window.priceRange ? window.priceRange.max : Infinity,
    };
    shopState.searchQuery = '';
    shopState.currentPage = 1;
    
    // Uncheck all checkboxes
    const checkboxes = document.querySelectorAll('input[type="checkbox"][data-filter]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    // Reset search input
    const searchBar = document.getElementById('searchBar');
    if (searchBar) searchBar.value = '';
    
    // Reset price sliders
    const minSlider = document.getElementById('minPriceSlider');
    const maxSlider = document.getElementById('maxPriceSlider');
    
    if (minSlider && window.priceRange) {
        minSlider.value = window.priceRange.min;
    }
    if (maxSlider && window.priceRange) {
        maxSlider.value = window.priceRange.max;
    }
    
    updatePriceSliderVisual();
    applyFilters();
}

// ========================================
// FILTERING LOGIC
// ========================================
function applyFilters() {
    let filtered = [...shopState.allProducts];
    
    // Filter by category
    if (shopState.filters.categories.length > 0) {
        filtered = filtered.filter(product => 
            shopState.filters.categories.includes(product.category)
        );
    }
    
    // Filter by brand
    if (shopState.filters.brands.length > 0) {
        filtered = filtered.filter(product => 
            shopState.filters.brands.includes(product.brand)
        );
    }
    
    // Filter by condition (using item_condition field)
    if (shopState.filters.conditions.length > 0) {
        filtered = filtered.filter(product => 
            shopState.filters.conditions.includes(product.item_condition)
        );
    }
    
    // Filter by price range
    filtered = filtered.filter(product => {
        const price = parseFloat(product.price);
        return price >= shopState.filters.minPrice && price <= shopState.filters.maxPrice;
    });
    
    // Filter by search query (name and brand)
    if (shopState.searchQuery) {
        filtered = filtered.filter(product => {
            const name = (product.name || '').toLowerCase();
            const brand = (product.brand || '').toLowerCase();
            return name.includes(shopState.searchQuery) || brand.includes(shopState.searchQuery);
        });
    }
    
    shopState.filteredProducts = filtered;
    shopState.currentPage = 1; // Reset to first page
    
    sortProducts();
    renderProducts();
}

// ========================================
// SORTING LOGIC
// ========================================
function sortProducts() {
    const products = shopState.filteredProducts;
    
    switch (shopState.sortBy) {
        case 'price-low':
            products.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
            break;
        case 'price-high':
            products.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
            break;
        case 'name':
            products.sort((a, b) => a.name.localeCompare(b.name));
            break;
        case 'featured':
        default:
            // Keep original order or implement custom featured logic
            break;
    }
}

// ========================================
// RENDERING
// ========================================
function renderProducts() {
    const productGrid = document.getElementById('productGrid');
    const resultsCount = document.getElementById('resultsCount');
    
    if (!productGrid) return;
    
    // Calculate pagination
    const startIndex = (shopState.currentPage - 1) * shopState.productsPerPage;
    const endIndex = startIndex + shopState.productsPerPage;
    const productsToShow = shopState.filteredProducts.slice(startIndex, endIndex);
    
    // Update results count
    if (resultsCount) {
        resultsCount.textContent = `Showing ${shopState.filteredProducts.length} product${shopState.filteredProducts.length !== 1 ? 's' : ''}`;
    }
    
    // Clear grid
    productGrid.innerHTML = '';
    
    // Handle no results
    if (productsToShow.length === 0) {
        productGrid.innerHTML = `
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>No Products Found</h3>
                <p>Try adjusting your filters or search terms</p>
            </div>
        `;
        renderPagination();
        return;
    }
    
    // Render products
    productsToShow.forEach(product => {
        const card = renderProductCard(product);
        productGrid.appendChild(card);
    });
    
    // Render pagination
    renderPagination();
    
    // Re-attach cart button listeners
    attachAddToCartListeners();
}

function renderProductCard(product) {
    const card = document.createElement('div');
    const isOutOfStock = parseInt(product.stock_quantity) === 0;
    
    card.className = `product-card${isOutOfStock ? ' out-of-stock' : ''}`;
    
    // Determine condition class
    let conditionClass = 'condition-new';
    if (product.item_condition === 'refurbished') {
        conditionClass = 'condition-refurbished';
    } else if (product.item_condition === 'used') {
        conditionClass = 'condition-used';
    }
    
    // Build image path
    const imagePath = product.image ? `../assets/products/${product.image}` : '../assets/products/placeholder.jpg';
    
    card.innerHTML = `
        <div class="product-image-wrapper">
            <img src="${imagePath}" alt="${escapeHtml(product.name)}" class="product-image" onerror="this.src='../assets/products/placeholder.jpg'">
            <span class="product-condition ${conditionClass}">${escapeHtml(product.item_condition)}</span>
            ${isOutOfStock ? '<span class="out-of-stock-badge">OUT OF STOCK</span>' : ''}
        </div>
        <div class="product-details">
            <div class="product-category">${escapeHtml(product.category)}</div>
            <h3 class="product-name">${escapeHtml(product.name)}</h3>
            <div class="product-brand">${escapeHtml(product.brand)}</div>
            <div class="product-price">₦${formatPrice(product.price)}</div>
            <button class="add-to-cart-btn" data-product-id="${product.id}" ${isOutOfStock ? 'disabled' : ''}>
                ${isOutOfStock ? 'Out of Stock' : 'Add to Cart'}
            </button>
        </div>
    `;
    
    return card;
}

// ========================================
// PAGINATION
// ========================================
function renderPagination() {
    const paginationContainer = document.getElementById('paginationControls');
    if (!paginationContainer) return;
    
    const totalPages = Math.ceil(shopState.filteredProducts.length / shopState.productsPerPage);
    
    // Hide pagination if only one page or no products
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    // Previous button
    paginationHTML += `
        <button class="pagination-btn" id="prevPage" ${shopState.currentPage === 1 ? 'disabled' : ''}>
            Previous
        </button>
    `;
    
    // Page numbers (show max 5 pages)
    const maxPagesToShow = 5;
    let startPage = Math.max(1, shopState.currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage < maxPagesToShow - 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button class="page-number ${i === shopState.currentPage ? 'active' : ''}" data-page="${i}">
                ${i}
            </button>
        `;
    }
    
    // Next button
    paginationHTML += `
        <button class="pagination-btn" id="nextPage" ${shopState.currentPage === totalPages ? 'disabled' : ''}>
            Next
        </button>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
    
    // Attach pagination event listeners
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageButtons = document.querySelectorAll('.page-number');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (shopState.currentPage > 1) {
                shopState.currentPage--;
                renderProducts();
                scrollToTop();
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (shopState.currentPage < totalPages) {
                shopState.currentPage++;
                renderProducts();
                scrollToTop();
            }
        });
    }
    
    pageButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const page = parseInt(e.target.getAttribute('data-page'));
            shopState.currentPage = page;
            renderProducts();
            scrollToTop();
        });
    });
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function formatPrice(price) {
    return parseFloat(price).toLocaleString('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
  </script>
  
  <!-- Include the shop.js file -->
  <script src="../assets/js/shop.js"></script>
</body>
</html>