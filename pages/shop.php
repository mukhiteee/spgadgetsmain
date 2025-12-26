<?php
// shop.php - The Dynamic Product Catalogue with GET Parameter Integration

$project_base = '/sp-gadgets/'; 

require_once('../api/config.php');

// ===== COLLECT FILTERS FROM GET PARAMETERS =====
$urlFilters = [
    'category' => isset($_GET['category']) ? trim($_GET['category']) : '',
    'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
    'condition' => isset($_GET['condition']) ? trim($_GET['condition']) : '',
    'brand' => isset($_GET['brand']) ? trim($_GET['brand']) : '',
    'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0,
    'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0,
    'sort' => isset($_GET['sort']) ? trim($_GET['sort']) : 'featured'
];

// Fetch filter options and initial products
$uniqueCategories = [];
$uniqueBrands = [];
$minPrice = 0;
$maxPrice = 10000;
$initialProducts = [];

try {
    $pdo = connectDB();
    
    // Fetch initial products (first 50 for page load)
    $productStmt = $pdo->prepare('SELECT id, name, brand, category, price, item_condition, image, stock_quantity FROM products LIMIT 50');
    $productStmt->execute();
    $initialProducts = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch unique categories
    $categoryStmt = $pdo->prepare('SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != "" ORDER BY category');
    $categoryStmt->execute();
    $uniqueCategories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch unique brands
    $brandStmt = $pdo->prepare('SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != "" ORDER BY brand');
    $brandStmt->execute();
    $uniqueBrands = $brandStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch min and max prices
    $priceStmt = $pdo->prepare('SELECT MIN(price) as min_price, MAX(price) as max_price FROM products');
    $priceStmt->execute();
    $priceRange = $priceStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($priceRange) {
        $minPrice = floor($priceRange['min_price']);
        $maxPrice = ceil($priceRange['max_price']);
    }
    
    // Get total product count
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products');
    $countStmt->execute();
    $totalProducts = $countStmt->fetchColumn();
    
} catch (\Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
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
    <link rel="icon" type="image/png" href="../assets/icon.png">
    <meta name="theme-color" content="#1F95B1">
    <style>/* ========================================
   WORLD-CLASS STORE DESIGN - FULLY RESPONSIVE
   Modern, Premium, Professional
   ======================================== */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

/* =====================================================
   THEME VARIABLES - DARK & LIGHT MODE
   ===================================================== */

/* DARK THEME (Default) */
:root[data-theme="dark"] {
    --primary: #1F95B1;
    --accent: #5CB9A4;
    --text: #ffffff;
    --text-muted: #aaaaaa;
    --bg: #0f0f0f;
    --bg-light: #212121;
    --border: #3f3f3f;
    --primary-dark: #1F95B1;
    --primary-medium: #1F95B1;
    --accent-warm: #5CB9A4;
    --accent-terracotta: #1F95B1;
    --neutral-light: #282828;
    --neutral-mid: #3f3f3f;
    --white: #ffffff;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.4);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.5);
    --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.6);
    --shadow-xl: 0 20px 50px rgba(0, 0, 0, 0.7);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* LIGHT THEME */
:root[data-theme="light"] {
    --primary: #1F95B1;
    --accent: #5CB9A4;
    --text: #0f172a;
    --text-muted: #64748b;
    --bg: #ffffff;
    --bg-light: #f8fafc;
    --border: #e2e8f0;
    --primary-dark: #0f172a;
    --primary-medium: #1F95B1;
    --accent-warm: #5CB9A4;
    --accent-terracotta: #1F95B1;
    --neutral-light: #f8fafc;
    --neutral-mid: #e2e8f0;
    --white: #ffffff;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
    --shadow-xl: 0 20px 50px rgba(0, 0, 0, 0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Smooth transitions for theme changes */
*,
*::before,
*::after {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg-light);
    color: var(--text);
    line-height: 1.6;
    min-height: 100vh;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Shop Container - RESPONSIVE */
.shop-container {
    max-width: 1600px;
    margin: 2rem auto;
    padding: 0 1rem;
    animation: fadeIn 0.6s ease-out;
}

@media (min-width: 640px) {
    .shop-container {
        padding: 0 1.5rem;
    }
}

@media (min-width: 1024px) {
    .shop-container {
        padding: 0 2rem;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.page-title {
    font-size: 1.875rem;
    font-weight: 800;
    color: var(--text);
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
    font-family: 'Inter', sans-serif;
}

@media (min-width: 640px) {
    .page-title {
        font-size: 2.25rem;
    }
}

@media (min-width: 1024px) {
    .page-title {
        font-size: 2.5rem;
    }
}

.page-subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    font-weight: 500;
}

@media (min-width: 640px) {
    .page-subtitle {
        font-size: 1rem;
        margin-bottom: 2rem;
    }
}

/* Mobile Filter Toggle */
.filter-toggle-btn {
    display: flex;
    width: 100%;
    padding: 0.875rem 1.25rem;
    background: var(--primary-dark);
    color: var(--white);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    margin-bottom: 1.5rem;
    transition: var(--transition);
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.filter-toggle-btn:hover {
    background: var(--primary);
    transform: translateY(-1px);
}

@media (min-width: 1024px) {
    .filter-toggle-btn {
        display: none;
    }
}

.shop-filter-sidebar {
    display: none;
}

.shop-filter-sidebar.active {
    display: block;
}

@media (min-width: 1024px) {
    .shop-filter-sidebar {
        display: block !important;
    }
}

/* Shop Layout - RESPONSIVE */
.shop-main-content {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: 1fr;
}

@media (min-width: 1024px) {
    .shop-main-content {
        grid-template-columns: 260px 1fr;
        gap: 2rem;
    }
}

@media (min-width: 1280px) {
    .shop-main-content {
        grid-template-columns: 280px 1fr;
        gap: 2.5rem;
    }
}

/* Filter Sidebar - RESPONSIVE */
.shop-filter-sidebar {
    background: var(--bg);
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
    height: fit-content;
    border: 1px solid var(--border);
    position: relative;
}

@media (min-width: 640px) {
    .shop-filter-sidebar {
        padding: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .shop-filter-sidebar {
        position: sticky;
        top: 100px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }
    
    .shop-filter-sidebar::-webkit-scrollbar {
        width: 4px;
    }
    
    .shop-filter-sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .shop-filter-sidebar::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 10px;
    }
}

.filter-header {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--border);
    font-family: 'Inter', sans-serif;
}

@media (min-width: 640px) {
    .filter-header {
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
    }
}

/* Collapsible Filter Sections */
.filter-section {
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}

@media (min-width: 640px) {
    .filter-section {
        margin-bottom: 1.25rem;
    }
}

.filter-section:last-of-type {
    border-bottom: none;
}

.filter-section details {
    margin-bottom: 0.5rem;
}

.filter-section summary {
    font-weight: 600;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text);
    padding: 0.625rem 0;
    cursor: pointer;
    list-style: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: var(--transition);
}

@media (min-width: 640px) {
    .filter-section summary {
        font-size: 0.875rem;
        padding: 0.75rem 0;
    }
}

.filter-section summary::-webkit-details-marker {
    display: none;
}

.filter-section summary::after {
    content: '+';
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-muted);
}

.filter-section details[open] summary::after {
    content: '−';
}

.filter-section summary:hover {
    color: var(--primary);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.625rem 0;
}

@media (min-width: 640px) {
    .checkbox-group {
        gap: 0.625rem;
        padding: 0.75rem 0;
    }
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: var(--transition);
    padding: 0.25rem 0;
    font-size: 0.8125rem;
    color: var(--text);
}

@media (min-width: 640px) {
    .checkbox-item {
        font-size: 0.875rem;
    }
}

.checkbox-item:hover {
    color: var(--primary);
}

.checkbox-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: var(--primary);
}

/* Price Range Slider */
.price-range-container {
    padding: 0.625rem 0;
}

@media (min-width: 640px) {
    .price-range-container {
        padding: 0.75rem 0;
    }
}

.price-display {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.875rem;
    font-size: 0.8125rem;
    color: var(--text);
    font-weight: 600;
}

@media (min-width: 640px) {
    .price-display {
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }
}

.price-slider-wrapper {
    position: relative;
    height: 32px;
    margin-bottom: 1rem;
}

.price-slider {
    position: absolute;
    width: 100%;
    height: 4px;
    background: var(--border);
    border-radius: 4px;
    top: 50%;
    transform: translateY(-50%);
}

.price-slider-range {
    position: absolute;
    height: 4px;
    background: var(--primary);
    border-radius: 4px;
    top: 50%;
    transform: translateY(-50%);
}

input[type="range"] {
    position: absolute;
    width: 100%;
    height: 4px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    pointer-events: none;
    -webkit-appearance: none;
    appearance: none;
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    background: var(--white);
    border: 2px solid var(--primary);
    border-radius: 50%;
    cursor: pointer;
    pointer-events: all;
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
}

input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.1);
}

input[type="range"]::-moz-range-thumb {
    width: 16px;
    height: 16px;
    background: var(--white);
    border: 2px solid var(--primary);
    border-radius: 50%;
    cursor: pointer;
    pointer-events: all;
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
}

.clear-filters {
    width: 100%;
    padding: 0.625rem 1rem;
    background: var(--primary-dark);
    color: var(--white);
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.8125rem;
    cursor: pointer;
    transition: var(--transition);
    margin-top: 1rem;
}

@media (min-width: 640px) {
    .clear-filters {
        font-size: 0.875rem;
    }
}

.clear-filters:hover {
    background: var(--primary);
    transform: translateY(-1px);
}

/* Product Section */
.product-section {
    position: relative;
    width: 100%;
}

/* Loading Overlay */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(8px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 10;
    border-radius: 12px;
}

:root[data-theme="dark"] .loading-overlay {
    background: rgba(15, 15, 15, 0.9);
}

.loading-overlay.active {
    display: flex;
}

.loader {
    width: 36px;
    height: 36px;
    border: 3px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@media (min-width: 640px) {
    .loader {
        width: 40px;
        height: 40px;
    }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Search and Results Header - RESPONSIVE */
.search-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.875rem;
    transition: var(--transition);
    margin-bottom: 1rem;
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
}

@media (min-width: 640px) {
    .search-input {
        max-width: 400px;
    }
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(31, 149, 177, 0.1);
}

.search-input::placeholder {
    color: var(--text-muted);
}

.results-header {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

@media (min-width: 640px) {
    .results-header {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
}

.results-count {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-muted);
}

@media (min-width: 640px) {
    .results-count {
        font-size: 0.875rem;
    }
}

.sort-select {
    width: 100%;
    padding: 0.625rem 1rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.875rem;
    background: var(--bg);
    color: var(--text);
    cursor: pointer;
    transition: var(--transition);
    font-family: 'Inter', sans-serif;
    font-weight: 500;
}

@media (min-width: 640px) {
    .sort-select {
        width: auto;
    }
}

.sort-select:focus {
    outline: none;
    border-color: var(--primary);
}

/* PREMIUM PRODUCT GRID - FULLY RESPONSIVE */
.product-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 2rem;
}

/* Small Mobile (375px+) */
@media (min-width: 375px) {
    .product-grid {
        gap: 1rem;
    }
}

/* Large Mobile (480px+) */
@media (min-width: 480px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
}

/* Tablet Portrait (640px+) */
@media (min-width: 640px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
    }
}

/* Tablet Landscape (768px+) */
@media (min-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
    }
}

/* Small Desktop (1024px+) */
@media (min-width: 1024px) {
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }
}

/* Medium Desktop (1280px+) */
@media (min-width: 1280px) {
    .product-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 1.5rem;
    }
}

/* Large Desktop (1536px+) */
@media (min-width: 1536px) {
    .product-grid {
        grid-template-columns: repeat(6, 1fr);
        gap: 1.5rem;
    }
}

/* PREMIUM PRODUCT CARD - RESPONSIVE */
.product-card {
    background: var(--bg);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    cursor: pointer;
    border: 1px solid var(--border);
    display: flex;
    flex-direction: column;
}

@media (min-width: 640px) {
    .product-card {
        border-radius: 10px;
    }
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary);
}

.product-image-wrapper {
    position: relative;
    width: 100%;
    height: 140px;
    background: var(--bg-light);
    overflow: hidden;
}

@media (min-width: 375px) {
    .product-image-wrapper {
        height: 160px;
    }
}

@media (min-width: 480px) {
    .product-image-wrapper {
        height: 180px;
    }
}

@media (min-width: 640px) {
    .product-image-wrapper {
        height: 200px;
    }
}

@media (min-width: 1024px) {
    .product-image-wrapper {
        height: 200px;
    }
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
    top: 0.5rem;
    left: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.5625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

@media (min-width: 640px) {
    .product-condition {
        top: 0.625rem;
        left: 0.625rem;
        padding: 0.25rem 0.625rem;
        font-size: 0.625rem;
    }
}

.condition-new {
    background: rgba(34, 197, 94, 0.95);
    color: white;
}

.condition-refurbished {
    background: rgba(251, 191, 36, 0.95);
    color: var(--text);
}

.condition-used {
    background: rgba(107, 114, 128, 0.95);
    color: white;
}

.out-of-stock-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(239, 68, 68, 0.95);
    color: white;
    border-radius: 4px;
    font-size: 0.5625rem;
    font-weight: 700;
    text-transform: uppercase;
}

@media (min-width: 640px) {
    .out-of-stock-badge {
        top: 0.625rem;
        right: 0.625rem;
        padding: 0.25rem 0.625rem;
        font-size: 0.625rem;
    }
}

.product-card.out-of-stock .product-image {
    opacity: 0.5;
    filter: grayscale(50%);
}

.product-details {
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    flex: 1;
}

@media (min-width: 480px) {
    .product-details {
        padding: 0.875rem;
    }
}

@media (min-width: 640px) {
    .product-details {
        padding: 1rem;
    }
}

.product-category {
    font-size: 0.5625rem;
    color: var(--primary);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

@media (min-width: 640px) {
    .product-category {
        font-size: 0.625rem;
        margin-bottom: 0.375rem;
    }
}

.product-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.25rem;
    font-family: 'Inter', sans-serif;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2em;
}

@media (min-width: 480px) {
    .product-name {
        font-size: 0.8125rem;
        margin-bottom: 0.375rem;
        min-height: 2.2em;
    }
}

@media (min-width: 640px) {
    .product-name {
        font-size: 0.875rem;
        min-height: 2.4em;
    }
}

.product-brand {
    font-size: 0.6875rem;
    color: var(--text-muted);
    margin-bottom: 0.375rem;
    font-weight: 500;
}

@media (min-width: 640px) {
    .product-brand {
        font-size: 0.75rem;
        margin-bottom: 0.5rem;
    }
}

.product-price {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.625rem;
    font-family: 'Inter', sans-serif;
    margin-top: auto;
}

@media (min-width: 480px) {
    .product-price {
        font-size: 1.125rem;
    }
}

@media (min-width: 640px) {
    .product-price {
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
    }
}

.add-to-cart-btn {
    width: 100%;
    padding: 0.5rem 0.75rem;
    background: var(--primary-dark);
    color: var(--white);
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.6875rem;
    cursor: pointer;
    transition: var(--transition);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-family: 'Inter', sans-serif;
}

@media (min-width: 480px) {
    .add-to-cart-btn {
        padding: 0.5625rem 0.875rem;
        font-size: 0.75rem;
    }
}

@media (min-width: 640px) {
    .add-to-cart-btn {
        padding: 0.625rem 1rem;
        font-size: 0.75rem;
    }
}

.add-to-cart-btn:hover:not(:disabled) {
    background: var(--primary);
    transform: translateY(-1px);
}

.add-to-cart-btn:disabled {
    background: var(--border);
    color: var(--text-muted);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Pagination - RESPONSIVE */
.pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.375rem;
    margin-top: 1.5rem;
    padding: 1.5rem 0;
    flex-wrap: wrap;
}

@media (min-width: 640px) {
    .pagination-controls {
        gap: 0.5rem;
        margin-top: 2rem;
        padding: 2rem 0;
    }
}

.pagination-btn,
.page-number {
    padding: 0.5rem 0.75rem;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 6px;
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--text);
    font-family: 'Inter', sans-serif;
}

@media (min-width: 640px) {
    .pagination-btn,
    .page-number {
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
    }
}

.pagination-btn:hover:not(:disabled),
.page-number:hover {
    background: var(--primary-dark);
    color: var(--white);
    border-color: var(--primary-dark);
}

.pagination-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.page-number.active {
    background: var(--primary-dark);
    color: var(--white);
    border-color: var(--primary-dark);
}

/* No Results */
.no-results {
    text-align: center;
    padding: 3rem 1.5rem;
    background: var(--bg);
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
}

@media (min-width: 640px) {
    .no-results {
        padding: 4rem 2rem;
    }
}

.no-results-icon {
    font-size: 2.5rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (min-width: 640px) {
    .no-results-icon {
        font-size: 3rem;
    }
}

/* Cart Sidebar - RESPONSIVE */
.cart-sidebar {
    position: fixed;
    top: 0;
    right: -100%;
    width: 100%;
    max-width: 100%;
    height: 100vh;
    background: var(--bg);
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

@media (min-width: 640px) {
    .cart-sidebar {
        max-width: 420px;
        right: -420px;
    }
}

@media (min-width: 768px) {
    .cart-sidebar {
        max-width: 450px;
        right: -450px;
    }
}

.cart-sidebar.open {
    right: 0;
}

.cart-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
    z-index: 999;
}

.cart-overlay.active {
    opacity: 1;
    visibility: visible;
}

.cart-header {
    padding: 1.25rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg);
}

@media (min-width: 640px) {
    .cart-header {
        padding: 1.5rem;
    }
}

.cart-title {
    font-size: 1.125rem;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    color: var(--text);
}

@media (min-width: 640px) {
    .cart-title {
        font-size: 1.25rem;
    }
}

.cart-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-muted);
    transition: var(--transition);
}

.cart-close:hover {
    color: var(--text);
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 0.875rem;
    background: var(--bg);
}

@media (min-width: 640px) {
    .cart-items {
        padding: 1rem;
    }
}

.cart-items::-webkit-scrollbar {
    width: 6px;
}

.cart-items::-webkit-scrollbar-track {
    background: var(--bg-light);
}

.cart-items::-webkit-scrollbar-thumb {
    background: var(--border);
    border-radius: 10px;
}

.cart-item {
    display: flex;
    gap: 0.875rem;
    padding: 0.875rem;
    border-bottom: 1px solid var(--border);
}

@media (min-width: 640px) {
    .cart-item {
        gap: 1rem;
        padding: 1rem;
    }
}

.cart-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .cart-item-image {
        width: 70px;
        height: 70px;
    }
}

.cart-item-details {
    flex: 1;
    min-width: 0;
}

.cart-item-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.8125rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--text);
}

@media (min-width: 640px) {
    .cart-item-name {
        font-size: 0.875rem;
    }
}

.cart-item-price {
    color: var(--text);
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 0.8125rem;
}

@media (min-width: 640px) {
    .cart-item-price {
        font-size: 0.875rem;
    }
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 24px;
    height: 24px;
    border: 1px solid var(--border);
    background: var(--bg);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    transition: var(--transition);
    flex-shrink: 0;
    color: var(--text);
}

.quantity-btn:hover {
    background: var(--primary-dark);
    color: var(--white);
}

.quantity-display {
    min-width: 28px;
    text-align: center;
    font-weight: 600;
    font-size: 0.8125rem;
    color: var(--text);
}

@media (min-width: 640px) {
    .quantity-display {
        min-width: 30px;
        font-size: 0.875rem;
    }
}

.remove-item-btn {
    margin-left: auto;
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 1rem;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .remove-item-btn {
        font-size: 1.125rem;
    }
}

.cart-footer {
    padding: 1.25rem;
    border-top: 1px solid var(--border);
    background: var(--bg);
}

@media (min-width: 640px) {
    .cart-footer {
        padding: 1.5rem;
    }
}

.cart-total {
    display: flex;
    justify-content: space-between;
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 1rem;
    font-family: 'Inter', sans-serif;
    color: var(--text);
}

@media (min-width: 640px) {
    .cart-total {
        font-size: 1.25rem;
    }
}

.checkout-btn {
    width: 100%;
    padding: 0.875rem 1rem;
    background: var(--primary-dark);
    color: var(--white);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition);
    font-family: 'Inter', sans-serif;
}

@media (min-width: 640px) {
    .checkout-btn {
        padding: 1rem;
    }
}

.checkout-btn:hover {
    background: var(--primary);
}

.empty-cart {
    text-align: center;
    padding: 2.5rem 1.5rem;
}

@media (min-width: 640px) {
    .empty-cart {
        padding: 3rem 2rem;
    }
}

.empty-cart-icon {
    font-size: 2.5rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (min-width: 640px) {
    .empty-cart-icon {
        font-size: 3rem;
    }
}

.empty-cart p {
    color: var(--text-muted);
}

/* Toast Notifications - RESPONSIVE */
.toast-notification {
    position: fixed;
    bottom: 1rem;
    right: 1rem;
    left: 1rem;
    background: var(--primary-dark);
    color: white;
    padding: 0.875rem 1rem;
    border-radius: 8px;
    box-shadow: var(--shadow-lg);
    display: flex;
    align-items: center;
    gap: 0.625rem;
    z-index: 10000;
    animation: slideUp 0.3s ease-out;
    font-family: 'Inter', sans-serif;
}

@media (min-width: 640px) {
    .toast-notification {
        left: auto;
        min-width: 300px;
        max-width: 400px;
        padding: 1rem 1.25rem;
        gap: 0.75rem;
        bottom: 1.25rem;
        right: 1.25rem;
    }
}

.toast-notification.success {
    background: #22c55e;
}

.toast-notification.warning {
    background: #f59e0b;
    color: var(--text);
}

.toast-notification.info {
    background: var(--primary);
}

.toast-icon {
    font-size: 1.125rem;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .toast-icon {
        font-size: 1.25rem;
    }
}

.toast-message {
    flex: 1;
    font-weight: 500;
    font-size: 0.8125rem;
}

@media (min-width: 640px) {
    .toast-message {
        font-size: 0.875rem;
    }
}

.toast-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.125rem;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.2s;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .toast-close {
        font-size: 1.25rem;
    }
}

.toast-close:hover {
    opacity: 1;
}

.cart-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ef4444;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.625rem;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', sans-serif;
}

.cart-btn {
    position: relative;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.toast-notification.hiding {
    animation: slideDown 0.3s ease-out forwards;
}

@keyframes slideDown {
    to {
        transform: translateY(100%);
        opacity: 0;
    }
}

.p-d {
    background-color: yellow;
    position: absolute;
    width: 100%;
    z-index: 1;
    top: 0;
    left: 0;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 2rem;
    font-family: 'Inter', sans-serif;
    opacity: 0;
}

/* ========================================
   ADDITIONAL MOBILE OPTIMIZATIONS
   ======================================== */

/* Prevent horizontal scroll on mobile */
@media (max-width: 639px) {
    body {
        overflow-x: hidden;
    }
    
    .shop-container {
        overflow-x: hidden;
    }
    
    /* Touch-friendly tap targets */
    .product-card,
    .add-to-cart-btn,
    .filter-toggle-btn,
    .quantity-btn {
        -webkit-tap-highlight-color: rgba(31, 149, 177, 0.2);
    }
    
    /* Smooth scrolling on iOS */
    .cart-items,
    .shop-filter-sidebar {
        -webkit-overflow-scrolling: touch;
    }
}

/* Landscape phone optimization */
@media (max-width: 896px) and (orientation: landscape) {
    .shop-container {
        margin: 1rem auto;
    }
    
    .page-title {
        font-size: 1.5rem;
        margin-bottom: 0.375rem;
    }
    
    .page-subtitle {
        font-size: 0.8125rem;
        margin-bottom: 1rem;
    }
    
    .product-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 0.875rem;
    }
}
    </style>
</head>
<body>
  <!-- CLEANER NAVBAR -->
  <header class="navbar">
    <div class="navbar-container">
      <!-- Logo + Brand -->
      <a href="index.html" class="navbar-brand">
        <img src="../assets/icon.png" alt="SP Gadgets" class="navbar-logo">
        <div class="navbar-text">
          <h1 class="navbar-title">SP Gadgets</h1>
          <p class="navbar-subtitle">Shinkomania Plug</p>
        </div>
      </a>

      <!-- Center Nav (Desktop Only) -->
      <nav class="navbar-nav" aria-label="Primary navigation">
        <a href="#home" class="nav-link">Home</a>
        <a href="#about" class="nav-link">About</a>
        <a href="pages/shop.php" class="nav-link">Shop</a>
        <a href="#products" class="nav-link">Products</a>
        <a href="#features" class="nav-link">Features</a>
        <a href="#contact" class="nav-link">Contact</a>
      </nav>

      <!-- Right Actions (Minimal) -->
      <div class="navbar-actions">
        <!-- Theme Toggle Button (Add to your top-bar-right section) -->
        <button class="icon-btn theme-toggle" onclick="toggleTheme()" title="Toggle Theme">
            <i class="fas fa-moon theme-icon" id="themeIcon"></i>
        </button>
        <!-- Cart Icon -->
        <button class="icon-btn" aria-label="Shopping cart" id="cartBtn">
          <i class="fas fa-shopping-cart"></i>
          <span class="cart-badge" id="cart-badge">0</span>
        </button>

        <!-- Menu Toggle -->
        <button class="menu-toggle" id="menu-toggle" aria-label="Menu" aria-expanded="false">
          <span class="hamburger"></span>
          <span class="hamburger"></span>
          <span class="hamburger"></span>
        </button>
      </div>
    </div>
  </header>

  <!-- ENHANCED SIDEBAR with Search & User -->
  <div class="mobile-overlay" id="mobile-overlay" aria-hidden="true">
    <div class="mobile-overlay-content">
      <div class="mobile-overlay-header">
        <div class="navbar-brand">
          <img src="../assets/icon.png" alt="SP Gadgets" class="navbar-logo">
          <div class="navbar-text">
            <h2 class="navbar-title">SP Gadgets</h2>
          </div>
        </div>
        <button class="mobile-overlay-close" id="overlay-close" aria-label="Close menu">×</button>
      </div>

      <!-- Search in Sidebar -->
      <div class="sidebar-search">
        <input type="text" placeholder="Search products..." id="sidebarSearchInput" onkeypress="if(event.key==='Enter') performSearch()">
      </div>

      <!-- User Section -->
      <div class="sidebar-user">
        <div class="sidebar-user-icon">
          <i class="fas fa-user"></i>
        </div>
        <div class="sidebar-user-name">Guest</div>
        <div class="sidebar-user-buttons">
          <a href="pages/login.php" class="sidebar-btn sidebar-btn-primary">Login</a>
          <a href="pages/register.php" class="sidebar-btn sidebar-btn-outline">Sign Up</a>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="mobile-nav" aria-label="Mobile navigation">
        <a href="#home" class="mobile-nav-link">
          <i class="fas fa-home"></i>
          <span>Home</span>
        </a>
        <a href="#about" class="mobile-nav-link">
          <i class="fas fa-info-circle"></i>
          <span>About Us</span>
        </a>
        <a href="pages/shop.php" class="mobile-nav-link">
          <i class="fas fa-store"></i>
          <span>Shop</span>
        </a>
        <a href="#products" class="mobile-nav-link">
          <i class="fas fa-box-open"></i>
          <span>Products</span>
        </a>
        <a href="#features" class="mobile-nav-link">
          <i class="fas fa-star"></i>
          <span>Features</span>
        </a>
        <a href="#contact" class="mobile-nav-link">
          <i class="fas fa-phone"></i>
          <span>Contact</span>
        </a>
      </nav>

      <!-- Shop Button -->
      <div class="mobile-cta">
        <a href="pages/shop.php" class="shop-btn">
          <i class="fas fa-shopping-bag"></i> Shop Now
        </a>
      </div>
    </div>
  </div>

  <!-- Cart Sidebar -->
  <div class="cart-overlay" id="cartOverlay"></div>
  <aside class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
      <h2 class="cart-title">Shopping Cart</h2>
      <button class="cart-close" id="cartClose">&times;</button>
    </div>
    <div class="cart-items" id="cartItems"></div>
    <div class="cart-footer">
      <div class="cart-total">
        <span>Total:</span>
        <span id="cartTotal">₦0.00</span>
      </div>
      <button class="checkout-btn" id="checkoutBtn">Proceed to Checkout</button>
    </div>
  </aside>

  <main>
    <div class="shop-container">
      <h2 class="page-title">Premium Electronics</h2>
      <p class="page-subtitle">Handpicked tech for discerning enthusiasts</p>

      <button class="filter-toggle-btn" id="filterToggleBtn">
        <i class="fas fa-filter"></i>
        <span>Filters</span>
      </button>

      <div class="shop-main-content">
        <aside class="shop-filter-sidebar" id="filterSidebar">
          <h3 class="filter-header">Filters</h3>

          <div class="filter-section">
            <details open>
              <summary>Category</summary>
                <div class="checkbox-group" id="categoryFilters">
                    <?php foreach($uniqueCategories as $category): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" 
                            value="<?php echo htmlspecialchars($category); ?>" 
                            data-filter="category"
                            <?php echo ($urlFilters['category'] === $category) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars(ucfirst($category)); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </details>
          </div>

          <div class="filter-section">
            <details open>
              <summary>Condition</summary>
              <div class="checkbox-group">
                <label class="checkbox-item">
                    <input type="checkbox" 
                        value="new" 
                        data-filter="condition"
                        <?php echo ($urlFilters['condition'] === 'new') ? 'checked' : ''; ?>>
                    <span>New</span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" 
                        value="refurbished" 
                        data-filter="condition"
                        <?php echo ($urlFilters['condition'] === 'refurbished') ? 'checked' : ''; ?>>
                    <span>Refurbished</span>
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" 
                        value="used" 
                        data-filter="condition"
                        <?php echo ($urlFilters['condition'] === 'used') ? 'checked' : ''; ?>>
                    <span>Used</span>
                </label>
              </div>
            </details>
          </div>

          <div class="filter-section">
            <details open>
              <summary>Brand</summary>
                <div class="checkbox-group" id="brandFilters">
                    <?php foreach($uniqueBrands as $brand): ?>
                    <label class="checkbox-item">
                        <input type="checkbox" 
                            value="<?php echo htmlspecialchars($brand); ?>" 
                            data-filter="brand"
                            <?php echo ($urlFilters['brand'] === $brand) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($brand); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </details>
          </div>

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
                <input type="hidden" id="minPrice" value="<?php echo $minPrice; ?>">
                <input type="hidden" id="maxPrice" value="<?php echo $maxPrice; ?>">
              </div>
            </details>
          </div>

          <button class="clear-filters" id="clearFilters">Clear All Filters</button>
        </aside>

        <section class="product-section">
          <div class="loading-overlay" id="loadingOverlay">
            <div class="loader"></div>
          </div>

          <input type="text" id="searchBar" placeholder="Search by product name or brand..." class="search-input" value="<?php echo htmlspecialchars($urlFilters['search']); ?>">
          
          <div class="results-header">
            <div class="results-count" id="resultsCount">Showing <?php echo count($initialProducts); ?> products</div>
            <select class="sort-select" id="sortSelect">
                <option value="featured" <?php echo ($urlFilters['sort'] === 'featured') ? 'selected' : ''; ?>>Featured</option>
                <option value="price-low" <?php echo ($urlFilters['sort'] === 'price-low') ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price-high" <?php echo ($urlFilters['sort'] === 'price-high') ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="name" <?php echo ($urlFilters['sort'] === 'name') ? 'selected' : ''; ?>>Name: A to Z</option>
            </select>
          </div>

          <div class="product-grid" id="productGrid">
            <?php foreach($initialProducts as $product): 
                $isOutOfStock = (int)$product['stock_quantity'] === 0;
                $conditionClass = 'condition-new';
                if ($product['item_condition'] === 'refurbished') {
                    $conditionClass = 'condition-refurbished';
                } elseif ($product['item_condition'] === 'used') {
                    $conditionClass = 'condition-used';
                }
                $imagePath = $product['image'];
            ?>
            <div class="product-card<?php echo $isOutOfStock ? ' out-of-stock' : ''; ?>">
                <div class="product-image-wrapper">
                <a href="product-details.php?id=<?php echo $product['id']; ?>" class="p-d">View</a>
                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image" onerror="this.src=''">
                    <span class="product-condition <?php echo $conditionClass; ?>"><?php echo htmlspecialchars($product['item_condition']); ?></span>
                    <?php if($isOutOfStock): ?>
                    <span class="out-of-stock-badge">OUT OF STOCK</span>
                    <?php endif; ?>
                </div>
                <div class="product-details">
                    <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                    <div class="product-price">₦<?php echo number_format($product['price'], 2); ?></div>
                    <button class="add-to-cart-btn" 
                            data-product-id="<?php echo $product['id']; ?>" 
                            data-product='<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES); ?>' 
                            <?php echo $isOutOfStock ? 'disabled' : ''; ?>>
                        <?php echo $isOutOfStock ? 'Out of Stock' : 'Add to Cart'; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="pagination-controls" id="paginationControls"></div>
        </section>
      </div>
    </div>
  </main>
  
  <script>
    // Pass initial data to JavaScript INCLUDING URL FILTERS
    window.shopInitialData = {
        products: <?php echo json_encode($initialProducts); ?>,
        priceRange: {
            min: <?php echo $minPrice; ?>,
            max: <?php echo $maxPrice; ?>
        },
        totalProducts: <?php echo $totalProducts; ?>,
        urlFilters: <?php echo json_encode($urlFilters); ?>
    };
// ========================================
// STATE MANAGEMENT WITH URL FILTERS
// ========================================
const shopState = {
    currentPage: 1,
    productsPerPage: 15,
    filters: {
        categories: [],
        brands: [],
        conditions: [],
        minPrice: 0,
        maxPrice: 999999999,
    },
    originalPriceRange: { min: 0, max: 999999999 },
    searchQuery: '',
    sortBy: 'featured',
    totalPages: 1,
    totalResults: 0,
    isFiltered: false
};

// ========================================
// INITIALIZATION WITH URL FILTERS
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    initializeShop();
});

function initializeShop() {
    // Get initial data from window object (passed from PHP)
    if (window.shopInitialData) {
        shopState.originalPriceRange = {
            min: window.shopInitialData.priceRange.min,
            max: window.shopInitialData.priceRange.max
        };
        shopState.filters.minPrice = window.shopInitialData.priceRange.min;
        shopState.filters.maxPrice = window.shopInitialData.priceRange.max;
        shopState.totalResults = window.shopInitialData.totalProducts;
        shopState.totalPages = Math.ceil(window.shopInitialData.totalProducts / shopState.productsPerPage);
        
        // ===== APPLY URL FILTERS =====
        const urlFilters = window.shopInitialData.urlFilters;
        
        if (urlFilters.category) {
            shopState.filters.categories = [urlFilters.category];
            shopState.isFiltered = true;
        }
        
        if (urlFilters.brand) {
            shopState.filters.brands = [urlFilters.brand];
            shopState.isFiltered = true;
        }
        
        if (urlFilters.condition) {
            shopState.filters.conditions = [urlFilters.condition];
            shopState.isFiltered = true;
        }
        
        if (urlFilters.search) {
            shopState.searchQuery = urlFilters.search;
            shopState.isFiltered = true;
        }
        
        if (urlFilters.min_price > 0) {
            shopState.filters.minPrice = urlFilters.min_price;
            shopState.isFiltered = true;
        }
        
        if (urlFilters.max_price > 0) {
            shopState.filters.maxPrice = urlFilters.max_price;
            shopState.isFiltered = true;
        }
        
        if (urlFilters.sort && urlFilters.sort !== 'featured') {
            shopState.sortBy = urlFilters.sort;
            shopState.isFiltered = true;
        }
    }
    
    // Initialize UI
    initializePriceSlider();
    initializeEventListeners();
    initializeCart();
    
    // Attach listeners to initial products (server-rendered)
    attachAddToCartListeners();
    
    // If filters from URL exist, fetch filtered products immediately
    if (shopState.isFiltered) {
        fetchProducts();
    } else {
        // Render initial pagination for unfiltered state
        renderPagination();
    }
}

// ========================================
// CART MANAGEMENT (LocalStorage)
// ========================================
const CartManager = {
    getCart() {
        const cart = localStorage.getItem('sp_cart');
        return cart ? JSON.parse(cart) : [];
    },
    
    saveCart(cart) {
        localStorage.setItem('sp_cart', JSON.stringify(cart));
        this.updateCartCount();
    },
    
    addItem(productId, productData) {
        const cart = this.getCart();
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            if (existingItem.quantity < productData.stock_quantity) {
                existingItem.quantity++;
            } else {
                showToast('Cannot add more items. Stock limit reached!', 'warning');
                return false;
            }
        } else {
            cart.push({
                id: productId,
                name: productData.name,
                brand: productData.brand,
                price: parseFloat(productData.price),
                image: productData.image,
                stock_quantity: productData.stock_quantity,
                quantity: 1
            });
        }
        
        this.saveCart(cart);
        return true;
    },
    
    removeItem(productId) {
        let cart = this.getCart();
        cart = cart.filter(item => item.id !== productId);
        this.saveCart(cart);
    },
    
    updateQuantity(productId, quantity) {
        const cart = this.getCart();
        const item = cart.find(item => item.id === productId);
        
        if (item) {
            if (quantity <= 0) {
                this.removeItem(productId);
            } else if (quantity <= item.stock_quantity) {
                item.quantity = quantity;
                this.saveCart(cart);
            } else {
                showToast('Cannot exceed stock quantity!', 'warning');
                return false;
            }
        }
        return true;
    },
    
    getCartCount() {
        const cart = this.getCart();
        return cart.reduce((total, item) => total + item.quantity, 0);
    },
    
    getCartTotal() {
        const cart = this.getCart();
        return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    },
    
    updateCartCount() {
        const count = this.getCartCount();
        const badge = document.getElementById('cart-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.transform = 'scale(1.3)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 200);
        }
    },
    
    clearCart() {
        localStorage.removeItem('sp_cart');
        this.updateCartCount();
    }
};

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
    
    // Search input with debounce
    const searchBar = document.getElementById('searchBar');
    if (searchBar) {
        let searchTimeout;
        searchBar.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                shopState.searchQuery = e.target.value.toLowerCase().trim();
                shopState.currentPage = 1;
                shopState.isFiltered = true;
                fetchProducts();
            }, 500);
        });
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

// ========================================
// CART SIDEBAR
// ========================================
function initializeCart() {
    CartManager.updateCartCount();
    
    const cartBtn = document.getElementById('cartBtn');
    const cartClose = document.getElementById('cartClose');
    const cartOverlay = document.getElementById('cartOverlay');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cartBtn) {
        cartBtn.addEventListener('click', openCart);
    }
    
    if (cartClose) {
        cartClose.addEventListener('click', closeCart);
    }
    
    if (cartOverlay) {
        cartOverlay.addEventListener('click', closeCart);
    }
    
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', handleCheckout);
    }
}

function openCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartSidebar && cartOverlay) {
        cartSidebar.classList.add('open');
        cartOverlay.classList.add('active');
        renderCartItems();
    }
}

function closeCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartSidebar && cartOverlay) {
        cartSidebar.classList.remove('open');
        cartOverlay.classList.remove('active');
    }
}

function renderCartItems() {
    const cart = CartManager.getCart();
    const cartItemsContainer = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    if (!cartItemsContainer) return;
    
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h3>Your cart is empty</h3>
                <p>Add some products to get started!</p>
            </div>
        `;
        if (cartTotal) cartTotal.textContent = '₦0.00';
        return;
    }
    
    cartItemsContainer.innerHTML = '';
    
    cart.forEach(item => {
        const cartItemEl = document.createElement('div');
        cartItemEl.className = 'cart-item';
        
        const imagePath = item.image ? `${item.image}` : '';
        
        cartItemEl.innerHTML = `
            <img src="${imagePath}" alt="${escapeHtml(item.name)}" class="cart-item-image" onerror="this.src='../assets/products/placeholder.jpg'">
            <div class="cart-item-details">
                <div class="cart-item-name">${escapeHtml(item.name)}</div>
                <div class="cart-item-price">₦${formatPrice(item.price)}</div>
                <div class="cart-item-controls">
                    <button class="quantity-btn" onclick="updateCartItemQuantity(${item.id}, ${item.quantity - 1})">−</button>
                    <span class="quantity-display">${item.quantity}</span>
                    <button class="quantity-btn" onclick="updateCartItemQuantity(${item.id}, ${item.quantity + 1})">+</button>
                </div>
            </div>
            <button class="remove-item-btn" onclick="removeCartItem(${item.id})" title="Remove item">
                <i class="fas fa-trash"></i>
            </button>
        `;
        
        cartItemsContainer.appendChild(cartItemEl);
    });
    
    const total = CartManager.getCartTotal();
    if (cartTotal) {
        cartTotal.textContent = `₦${formatPrice(total)}`;
    }
}

window.updateCartItemQuantity = function(productId, newQuantity) {
    CartManager.updateQuantity(productId, newQuantity);
    renderCartItems();
};

window.removeCartItem = function(productId) {
    CartManager.removeItem(productId);
    renderCartItems();
    showToast('Item removed from cart', 'success');
};

function handleCheckout() {
    const cart = CartManager.getCart();
    
    if (cart.length === 0) {
        showToast('Your cart is empty!', 'info');
        return;
    }
    
    localStorage.setItem('checkout_cart', JSON.stringify(cart));
    window.location.href = '../pages/checkout.php';
}

// ========================================
// PRICE SLIDER FUNCTIONALITY
// ========================================
function initializePriceSlider() {
    updatePriceSliderVisual();
}

let priceSliderTimeout;
function handlePriceSliderChange() {
    const minSlider = document.getElementById('minPriceSlider');
    const maxSlider = document.getElementById('maxPriceSlider');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');
    
    let minVal = parseInt(minSlider.value);
    let maxVal = parseInt(maxSlider.value);
    
    if (minVal > maxVal - 100) {
        minVal = maxVal - 100;
        minSlider.value = minVal;
    }
    
    minPriceInput.value = minVal;
    maxPriceInput.value = maxVal;
    updatePriceSliderVisual();
    
    shopState.filters.minPrice = minVal;
    shopState.filters.maxPrice = maxVal;
    
    clearTimeout(priceSliderTimeout);
    priceSliderTimeout = setTimeout(() => {
        shopState.currentPage = 1;
        shopState.isFiltered = true;
        fetchProducts();
    }, 500);
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
    
    if (minDisplay) minDisplay.textContent = `₦${minVal.toLocaleString()}`;
    if (maxDisplay) maxDisplay.textContent = `₦${maxVal.toLocaleString()}`;
    
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
    
    shopState.currentPage = 1;
    shopState.isFiltered = true;
    fetchProducts();
}

function handleSortChange(e) {
    shopState.sortBy = e.target.value;
    shopState.currentPage = 1;
    shopState.isFiltered = true;
    fetchProducts();
}

function clearAllFilters() {
    // Redirect to clean URL without parameters
    window.location.href = window.location.pathname;
}

// ========================================
// FETCH PRODUCTS FROM API
// ========================================
async function fetchProducts() {
    if (!shopState.isFiltered) {
        return;
    }
    
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    if (loadingOverlay) {
        loadingOverlay.classList.add('active');
    }
    
    try {
        const params = new URLSearchParams();
        
        if (shopState.filters.categories.length > 0) {
            params.append('categories', shopState.filters.categories.join(','));
        }
        if (shopState.filters.brands.length > 0) {
            params.append('brands', shopState.filters.brands.join(','));
        }
        if (shopState.filters.conditions.length > 0) {
            params.append('conditions', shopState.filters.conditions.join(','));
        }
        
        if (shopState.filters.minPrice !== shopState.originalPriceRange.min) {
            params.append('minPrice', shopState.filters.minPrice);
        }
        if (shopState.filters.maxPrice !== shopState.originalPriceRange.max) {
            params.append('maxPrice', shopState.filters.maxPrice);
        }
        
        if (shopState.searchQuery) {
            params.append('search', shopState.searchQuery);
        }
        
        if (shopState.sortBy !== 'featured') {
            params.append('sort', shopState.sortBy);
        }
        
        params.append('page', shopState.currentPage);
        params.append('per_page', shopState.productsPerPage);
        
        const url = `../api/products_api.php?${params.toString()}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            shopState.totalPages = data.pagination.total_pages;
            shopState.totalResults = data.pagination.total_results;
            
            renderProducts(data.products);
        } else {
            console.error('API error:', data.message);
            renderNoResults();
        }
        
    } catch (error) {
        console.error('Fetch error:', error);
        renderNoResults();
    } finally {
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }
}

// ========================================
// RENDERING
// ========================================
function renderProducts(products) {
    const productGrid = document.getElementById('productGrid');
    const resultsCount = document.getElementById('resultsCount');
    
    if (!productGrid) return;
    
    if (resultsCount) {
        resultsCount.textContent = `Showing ${shopState.totalResults} product${shopState.totalResults !== 1 ? 's' : ''}`;
    }
    
    productGrid.innerHTML = '';
    
    if (products.length === 0) {
        renderNoResults();
        renderPagination();
        return;
    }
    
    products.forEach(product => {
        const card = renderProductCard(product);
        productGrid.appendChild(card);
    });
    
    renderPagination();
    attachAddToCartListeners();
}

function renderProductCard(product) {
    const card = document.createElement('div');
    const isOutOfStock = parseInt(product.stock_quantity) === 0;
    
    card.className = `product-card${isOutOfStock ? ' out-of-stock' : ''}`;
    
    let conditionClass = 'condition-new';
    if (product.item_condition === 'refurbished') {
        conditionClass = 'condition-refurbished';
    } else if (product.item_condition === 'used') {
        conditionClass = 'condition-used';
    }
    
    const imagePath = product.image;
    
    card.innerHTML = `
        <div class="product-image-wrapper" onclick="viewProduct(${product.id})">
            <a href="product-details.php?id=${product.id}" class="p-d">View</a>
            <img src="${imagePath}" alt="${escapeHtml(product.name)}" class="product-image" onerror="this.src='../assets/products/placeholder.jpg'">
            <span class="product-condition ${conditionClass}">${escapeHtml(product.item_condition)}</span>
            ${isOutOfStock ? '<span class="out-of-stock-badge">OUT OF STOCK</span>' : ''}
        </div>
        <div class="product-details" onclick="viewProduct(${product.id})">
            <div class="product-category">${escapeHtml(product.category)}</div>
            <h3 class="product-name">${escapeHtml(product.name)}</h3>
            <div class="product-brand">${escapeHtml(product.brand)}</div>
            <div class="product-price">₦${formatPrice(product.price)}</div>
            <button class="add-to-cart-btn" 
                    data-product-id="${product.id}" 
                    data-product='${JSON.stringify(product).replace(/'/g, "&apos;")}' 
                    onclick="event.stopPropagation()"
                    ${isOutOfStock ? 'disabled' : ''}>
                ${isOutOfStock ? 'Out of Stock' : 'Add to Cart'}
            </button>
        </div>
    `;
    
    return card;
}

function renderNoResults() {
    const productGrid = document.getElementById('productGrid');
    if (productGrid) {
        productGrid.innerHTML = `
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>No Products Found</h3>
                <p>Try adjusting your filters or search terms</p>
            </div>
        `;
    }
}

function attachAddToCartListeners() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.replaceWith(button.cloneNode(true));
    });
    
    const newButtons = document.querySelectorAll('.add-to-cart-btn');
    newButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (button.disabled) return;
            
            const productData = JSON.parse(button.getAttribute('data-product'));
            const success = CartManager.addItem(productData.id, productData);
            
            if (success) {
                showAddToCartFeedback(button);
            }
        });
    });
}

function showAddToCartFeedback(button) {
    const originalText = button.textContent;
    const originalBg = button.style.background;
    
    button.textContent = '✓ Added!';
    button.style.background = '#28a745';
    button.disabled = true;
    
    setTimeout(() => {
        button.textContent = originalText;
        button.style.background = originalBg;
        button.disabled = false;
    }, 1500);
}

// ========================================
// PAGINATION
// ========================================
function renderPagination() {
    const paginationContainer = document.getElementById('paginationControls');
    if (!paginationContainer) return;
    
    if (shopState.totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let paginationHTML = '';
    
    paginationHTML += `
        <button class="pagination-btn" id="prevPage" ${shopState.currentPage === 1 ? 'disabled' : ''}>
            Previous
        </button>
    `;
    
    const maxPagesToShow = 5;
    let startPage = Math.max(1, shopState.currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(shopState.totalPages, startPage + maxPagesToShow - 1);
    
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
    
    paginationHTML += `
        <button class="pagination-btn" id="nextPage" ${shopState.currentPage === shopState.totalPages ? 'disabled' : ''}>
            Next
        </button>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
    
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageButtons = document.querySelectorAll('.page-number');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (shopState.currentPage > 1) {
                shopState.currentPage--;
                shopState.isFiltered = true;
                fetchProducts();
                scrollToTop();
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (shopState.currentPage < shopState.totalPages) {
                shopState.currentPage++;
                shopState.isFiltered = true;
                fetchProducts();
                scrollToTop();
            }
        });
    }
    
    pageButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const page = parseInt(e.target.getAttribute('data-page'));
            shopState.currentPage = page;
            shopState.isFiltered = true;
            fetchProducts();
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

function showToast(message, type = 'info') {
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✓';
    if (type === 'error') icon = '✗';
    if (type === 'warning') icon = '⚠️';
    
    toast.innerHTML = `
        <span class="toast-icon">${icon}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
}

function viewProduct(productId) {
    window.location.href = `product-details.php?id=${productId}`;
}

// Theme Toggle Functionality
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('admin-theme', newTheme);
    
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.getElementById('themeIcon');
    if (theme === 'dark') {
        icon.className = 'fas fa-moon theme-icon';
    } else {
        icon.className = 'fas fa-sun theme-icon';
    }
}

// Load saved theme on page load
function loadTheme() {
    const savedTheme = localStorage.getItem('admin-theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

// Initialize theme when page loads
loadTheme();
  </script>

  <script src="../scripts/main.js"></script>
</body>
</html>