<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curated Electronics - Premium Tech Collection</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1a2332;
            --primary-medium: #2d3e50;
            --accent-warm: #d4a574;
            --accent-terracotta: #c85c3e;
            --neutral-light: #f7f5f2;
            --neutral-mid: #e8e5e0;
            --white: #ffffff;
            --shadow-subtle: 0 2px 12px rgba(26, 35, 50, 0.08);
            --shadow-hover: 0 8px 24px rgba(26, 35, 50, 0.15);
            --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
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
            font-family: 'Crimson Pro', serif;
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
            font-family: 'Crimson Pro', serif;
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
    <header class="header">
        <div class="header-content">
            <h1 class="logo">Curated</h1>
            <div class="cart-info">
                <span>Cart</span>
                <span class="cart-count" id="cartCount">0</span>
            </div>
        </div>
    </header>

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
                <div class="results-header">
                    <div class="results-count" id="resultsCount">Showing 12 products</div>
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

    <script>
        // Product data
        const allProducts = [
            {
                id: 1,
                name: "MacBook Pro 16",
                brand: "Apple",
                category: "laptops",
                price: 2499,
                condition: "new",
                image: "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500&h=500&fit=crop"
            },
            {
                id: 2,
                name: "iPhone 15 Pro",
                brand: "Apple",
                category: "phones",
                price: 1199,
                condition: "new",
                image: "https://images.unsplash.com/photo-1592286927505-f0b0ae5b3a1e?w=500&h=500&fit=crop"
            },
            {
                id: 3,
                name: "Galaxy S24 Ultra",
                brand: "Samsung",
                category: "phones",
                price: 1299,
                condition: "new",
                image: "https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=500&h=500&fit=crop"
            },
            {
                id: 4,
                name: "XPS 15 Laptop",
                brand: "Dell",
                category: "laptops",
                price: 1899,
                condition: "refurbished",
                image: "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500&h=500&fit=crop"
            },
            {
                id: 5,
                name: "iPad Pro 12.9",
                brand: "Apple",
                category: "tablets",
                price: 1099,
                condition: "new",
                image: "https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=500&h=500&fit=crop"
            },
            {
                id: 6,
                name: "Galaxy Tab S9",
                brand: "Samsung",
                category: "tablets",
                price: 899,
                condition: "new",
                image: "https://images.unsplash.com/photo-1561154464-82e9adf32764?w=500&h=500&fit=crop"
            },
            {
                id: 7,
                name: "WH-1000XM5",
                brand: "Sony",
                category: "accessories",
                price: 399,
                condition: "new",
                image: "https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=500&h=500&fit=crop"
            },
            {
                id: 8,
                name: "QuietComfort 45",
                brand: "Bose",
                category: "accessories",
                price: 329,
                condition: "refurbished",
                image: "https://images.unsplash.com/photo-1484704849700-f032a568e944?w=500&h=500&fit=crop"
            },
            {
                id: 9,
                name: "MacBook Air M2",
                brand: "Apple",
                category: "laptops",
                price: 1299,
                condition: "used",
                image: "https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=500&h=500&fit=crop"
            },
            {
                id: 10,
                name: "Galaxy Buds Pro",
                brand: "Samsung",
                category: "accessories",
                price: 199,
                condition: "new",
                image: "https://images.unsplash.com/photo-1590658165737-15a047b7a0f8?w=500&h=500&fit=crop"
            },
            {
                id: 11,
                name: "iPhone 14",
                brand: "Apple",
                category: "phones",
                price: 899,
                condition: "refurbished",
                image: "https://images.unsplash.com/photo-1556656793-08538906a9f8?w=500&h=500&fit=crop"
            },
            {
                id: 12,
                name: "Dell Ultrasharp Monitor",
                brand: "Dell",
                category: "accessories",
                price: 649,
                condition: "new",
                image: "https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=500&h=500&fit=crop"
            },
            {
                id: 13,
                name: "iPad Air",
                brand: "Apple",
                category: "tablets",
                price: 749,
                condition: "used",
                image: "https://images.unsplash.com/photo-1585790050230-5dd28404f1e6?w=500&h=500&fit=crop"
            },
            {
                id: 14,
                name: "Galaxy S23",
                brand: "Samsung",
                category: "phones",
                price: 799,
                condition: "refurbished",
                image: "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&h=500&fit=crop"
            },
            {
                id: 15,
                name: "XPS 13 Laptop",
                brand: "Dell",
                category: "laptops",
                price: 1499,
                condition: "new",
                image: "https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500&h=500&fit=crop"
            },
            {
                id: 16,
                name: "AirPods Pro",
                brand: "Apple",
                category: "accessories",
                price: 249,
                condition: "new",
                image: "https://images.unsplash.com/photo-1606841837239-c5a1a4a07af7?w=500&h=500&fit=crop"
            }
        ];

        // State management
        let currentPage = 1;
        const productsPerPage = 6;
        let filteredProducts = [...allProducts];
        let cartCount = 0;

        // Filter state
        const filters = {
            category: [],
            condition: [],
            brand: [],
            minPrice: null,
            maxPrice: null,
            sort: 'featured'
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            setupEventListeners();
            applyFilters();
        });

        // Event listeners
        function setupEventListeners() {
            // Filter checkboxes
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', handleFilterChange);
            });

            // Price inputs
            document.getElementById('minPrice').addEventListener('input', debounce(handlePriceChange, 500));
            document.getElementById('maxPrice').addEventListener('input', debounce(handlePriceChange, 500));

            // Sort select
            document.getElementById('sortSelect').addEventListener('change', handleSortChange);

            // Clear filters
            document.getElementById('clearFilters').addEventListener('click', clearAllFilters);
        }

        // Handle filter changes
        function handleFilterChange(e) {
            const filterType = e.target.dataset.filter;
            const value = e.target.value;

            if (e.target.checked) {
                filters[filterType].push(value);
            } else {
                filters[filterType] = filters[filterType].filter(item => item !== value);
            }

            currentPage = 1;
            applyFilters();
        }

        // Handle price changes
        function handlePriceChange() {
            filters.minPrice = parseFloat(document.getElementById('minPrice').value) || null;
            filters.maxPrice = parseFloat(document.getElementById('maxPrice').value) || null;
            currentPage = 1;
            applyFilters();
        }

        // Handle sort changes
        function handleSortChange(e) {
            filters.sort = e.target.value;
            applyFilters();
        }

        // Clear all filters
        function clearAllFilters() {
            // Reset filter state
            filters.category = [];
            filters.condition = [];
            filters.brand = [];
            filters.minPrice = null;
            filters.maxPrice = null;
            filters.sort = 'featured';

            // Reset UI
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            document.getElementById('sortSelect').value = 'featured';

            currentPage = 1;
            applyFilters();
        }

        // Apply filters
        function applyFilters() {
            filteredProducts = allProducts.filter(product => {
                // Category filter
                if (filters.category.length > 0 && !filters.category.includes(product.category)) {
                    return false;
                }

                // Condition filter
                if (filters.condition.length > 0 && !filters.condition.includes(product.condition)) {
                    return false;
                }

                // Brand filter
                if (filters.brand.length > 0 && !filters.brand.includes(product.brand)) {
                    return false;
                }

                // Price filter
                if (filters.minPrice !== null && product.price < filters.minPrice) {
                    return false;
                }
                if (filters.maxPrice !== null && product.price > filters.maxPrice) {
                    return false;
                }

                return true;
            });

            // Apply sorting
            sortProducts();

            // Update display
            updateResultsCount();
            renderProducts();
            renderPagination();
        }

        // Sort products
        function sortProducts() {
            switch (filters.sort) {
                case 'price-low':
                    filteredProducts.sort((a, b) => a.price - b.price);
                    break;
                case 'price-high':
                    filteredProducts.sort((a, b) => b.price - a.price);
                    break;
                case 'name':
                    filteredProducts.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                default:
                    // Featured - keep original order
                    break;
            }
        }

        // Update results count
        function updateResultsCount() {
            const count = filteredProducts.length;
            document.getElementById('resultsCount').textContent = 
                `Showing ${count} product${count !== 1 ? 's' : ''}`;
        }

        // Render products
        function renderProducts() {
            const grid = document.getElementById('productGrid');
            const startIndex = (currentPage - 1) * productsPerPage;
            const endIndex = startIndex + productsPerPage;
            const productsToShow = filteredProducts.slice(startIndex, endIndex);

            if (productsToShow.length === 0) {
                grid.innerHTML = `
                    <div class="no-results" style="grid-column: 1 / -1;">
                        <div class="no-results-icon">🔍</div>
                        <h3>No products found</h3>
                        <p>Try adjusting your filters to see more results</p>
                    </div>
                `;
                return;
            }

            grid.innerHTML = productsToShow.map((product, index) => `
                <div class="product-card" style="animation-delay: ${index * 0.05}s">
                    <div class="product-image-wrapper">
                        <img src="${product.image}" alt="${product.name}" class="product-image">
                        <span class="product-condition condition-${product.condition}">
                            ${product.condition}
                        </span>
                    </div>
                    <div class="product-details">
                        <div class="product-category">${product.category}</div>
                        <h3 class="product-name">${product.name}</h3>
                        <div class="product-brand">${product.brand}</div>
                        <div class="product-price">$${product.price.toLocaleString()}</div>
                        <button class="add-to-cart-btn" onclick="addToCart(${product.id})">
                            Add to Cart
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Render pagination
        function renderPagination() {
            const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
            const paginationContainer = document.getElementById('paginationControls');

            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            let paginationHTML = `
                <button class="pagination-btn" onclick="changePage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''}>
                    Previous
                </button>
            `;

            // Show page numbers
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    paginationHTML += `
                        <button class="page-number ${i === currentPage ? 'active' : ''}" 
                                onclick="changePage(${i})">
                            ${i}
                        </button>
                    `;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    paginationHTML += '<span style="padding: 0.75rem;">...</span>';
                }
            }

            paginationHTML += `
                <button class="pagination-btn" onclick="changePage(${currentPage + 1})" 
                        ${currentPage === totalPages ? 'disabled' : ''}>
                    Next
                </button>
            `;

            paginationContainer.innerHTML = paginationHTML;
        }

        // Change page
        function changePage(page) {
            const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
            if (page < 1 || page > totalPages) return;

            currentPage = page;
            renderProducts();
            renderPagination();

            // Scroll to top of products
            document.querySelector('.product-section').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }

        // Add to cart
        function addToCart(productId) {
            cartCount++;
            document.getElementById('cartCount').textContent = cartCount;

            // Visual feedback
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Added!';
            btn.style.background = '#28a745';

            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
            }, 1500);
        }

        // Debounce helper
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</body>
</html>