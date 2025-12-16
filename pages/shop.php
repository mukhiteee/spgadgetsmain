<?php
    // shop.php - The Dynamic Product Catalogue

    // Define the project's base URL (Update 'sp-gadgets' if necessary)
    $project_base = '/sp-gadgets/'; 
    
    // 1. INCLUDE DATABASE CONFIGURATION
    require_once('../api/config.php');

    // 2. FETCH INITIAL BATCH OF PRODUCTS FROM THE DATABASE (Max 30)
    $allProductsJson = '[]'; // Default empty array in case of failure or no products
    
    try {
        $pdo = connectDB();
        
        // Use PREPARED STATEMENT even for a simple SELECT to ensure future safety, 
        // though we are currently using a hardcoded LIMIT.
        $stmt = $pdo->prepare('SELECT 
            id, name, brand, category, price, item_condition, image 
            FROM products
            LIMIT 30'); // <<< NEW LIMIT APPLIED HERE

        $stmt->execute();
            
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Encode the PHP array into a JSON string for JavaScript
        $allProductsJson = json_encode($products);
        
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
    <title>SP Gadgets</title>
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

        .filter-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--neutral-mid);
        }

        .filter-section:last-child {
            border-bottom: none;
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

        .price-inputs {
            display: flex;
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .price-input {
            flex: 1;
        }

        .price-input input {
            width: 100%;
            padding: 0.65rem;
            border: 2px solid var(--neutral-mid);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'DM Sans', sans-serif;
            transition: var(--transition);
        }

        .price-input input:focus {
            outline: none;
            border-color: var(--accent-warm);
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
            flex-grow: 1; /* Allows it to take up available horizontal space */
            max-width: 400px;
            padding: 0.75rem 1rem;
            border: 2px solid var(--neutral-mid);
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'DM Sans', sans-serif;
            transition: var(--transition);
            box-shadow: var(--shadow-subtle);
            color: var(--primary-dark);
        }

        .search-input::placeholder {
            color: var(--primary-medium);
            opacity: 0.7;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-warm); /* Highlight focus with your accent color */
            box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.2); /* Soft glow */
        }

        .results-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        /* Responsive adjustment for small screens */
        @media (max-width: 768px) {
            .results-header {
                flex-direction: column;
                align-items: stretch; /* Stretch items to full width */
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

        .add-to-cart-btn:hover {
            background: var(--accent-terracotta);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(200, 92, 62, 0.3);
        }

        .add-to-cart-btn:active {
            transform: translateY(0);
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

        <!-- Shop Now Button -->
        <!-- <button class="shop-btn">Shop Now</button> -->

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

        <div class="shop-main-content">
            <aside class="shop-filter-sidebar">
                <h3 class="filter-header">Filters</h3>

                <div class="filter-section">
                    <h4 class="filter-title">Category</h4>
                    <div class="checkbox-group" id="categoryFilters">
                        <label class="checkbox-item">
                            <input type="checkbox" value="laptops" data-filter="category">
                            <span>Laptops</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" value="phones" data-filter="category">
                            <span>Phones</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" value="tablets" data-filter="category">
                            <span>Tablets</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" value="accessories" data-filter="category">
                            <span>Accessories</span>
                        </label>
                    </div>
                </div>

                <div class="filter-section">
                    <h4 class="filter-title">Condition</h4>
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
                </div>

                <div class="filter-section">
                    <h4 class="filter-title">Brand</h4>
                    <div class="checkbox-group" id="brandFilters">
                        <label class="checkbox-item">
                            <input type="checkbox" value="Apple" data-filter="brand">
                            <span>Apple</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" value="Samsung" data-filter="brand">
                            <span>Samsung</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" value="Dell" data-filter="brand">
                            <span>Dell</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" value="Sony" data-filter="brand">
                            <span>Sony</span>
                        </label>
                        <label class="checkbox-item">
                            <input type="checkbox" value="Bose" data-filter="brand">
                            <span>Bose</span>
                        </label>
                    </div>
                </div>

                <div class="filter-section">
                    <h4 class="filter-title">Price Range</h4>
                    <div class="price-inputs">
                        <div class="price-input">
                            <input type="number" id="minPrice" placeholder="Min" min="0">
                        </div>
                        <div class="price-input">
                            <input type="number" id="maxPrice" placeholder="Max" min="0">
                        </div>
                    </div>
                </div>

                <button class="clear-filters" id="clearFilters">Clear All Filters</button>
            </aside>

            <main class="product-section">
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
            </main>
        </div>
    </div>
  </main>
  <script>
    
  </script>
</body>
</html>