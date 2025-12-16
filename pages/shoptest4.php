<?php
    // shop.php - The Dynamic Product Catalogue

    $project_base = '/sp-gadgets/'; 
    
    require_once('../api/config.php');

    // Fetch filter options and initial products
    $uniqueCategories = [];
    $uniqueBrands = [];
    $minPrice = 0;
    $maxPrice = 10000;
    $initialProducts = [];
    
    try {
        $pdo = connectDB();
        
        // Fetch initial products (first 9 for page load)
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
    <style>
       /* styles/shop.css - Complete Shop Page Styles with Mobile Fixes */

:root {
    --primary: #1F95B1;
    --accent: #5CB9A4;
    --text: #0f172a;
    --text-muted: #6b7280;
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
    --shadow-subtle: 0 2px 12px rgba(31, 149, 177, 0.08);
    --shadow-hover: 0 8px 24px rgba(31, 149, 177, 0.15);
    --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

:dark {
    /* --- BRAND COLORS (UNTOUCHED) --- */
    --primary: #1F95B1;
    --accent: #5CB9A4;
    --primary-medium: #1F95B1;
    --accent-warm: #5CB9A4;
    --accent-terracotta: #1F95B1; 

    /* --- BACKGROUNDS (MAXIMUM DARKNESS) --- */
    --bg: #000000;
    --bg-light: #0a0a0a;
    --neutral-light: #0a0a0a;
    --white: #000000;
    
    /* --- TEXT (BRIGHTEST WHITE) --- */
    --text: #fdfdfd;
    --text-muted: #888888;
    --primary-dark: #fdfdfd;
    
    /* --- BORDERS & SHADOWS --- */
    --border: #222222;
    --neutral-mid: #222222;
    --shadow-subtle: 0 1px 4px rgba(31, 149, 177, 0.4);
    --shadow-hover: 0 4px 12px rgba(31, 149, 177, 0.6);
    --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, var(--neutral-light) 0%, var(--neutral-mid) 100%);
    color: var(--primary-dark);
    line-height: 1.6;
    min-height: 100vh;
    overflow-x: hidden;
}

/* Shop Container */
.shop-container {
    max-width: 1400px;
    margin: 3rem auto;
    padding: 0 2rem;
    animation: fadeIn 0.8s ease-out 0.2s both;
    width: 100%;
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
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
    letter-spacing: -1px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.page-subtitle {
    font-size: 1.1rem;
    color: var(--primary-medium);
    margin-bottom: 2.5rem;
    font-weight: 400;
}

/* Mobile Filter Toggle */
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

/* Shop Layout */
.shop-main-content {
    display: grid;
    gap: 3rem;
    grid-template-columns: 1fr;
    width: 100%;
    max-width: 100%;
}

@media (min-width: 1024px) {
    .shop-main-content {
        grid-template-columns: 280px 1fr;
    }
}

/* Filter Sidebar */
.shop-filter-sidebar {
    background: var(--white);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: var(--shadow-subtle);
    height: fit-content;
    position: sticky;
    top: 120px;
}

.filter-header {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--accent-warm);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Collapsible Filter Sections */
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
}

.filter-section details[open] summary::after {
    content: '−';
}

.filter-section summary:hover {
    color: var(--accent-terracotta);
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

/* Price Range Slider */
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
    width: 18px;
    height: 18px;
    background: var(--primary-dark);
    border: 3px solid var(--white);
    border-radius: 50%;
    cursor: pointer;
    pointer-events: all;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
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
    font-size: 0.95rem;
}

.clear-filters:hover {
    background: var(--accent-terracotta);
    transform: translateY(-2px);
    box-shadow: var(--shadow-hover);
}

/* Product Section */
.product-section {
    position: relative;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

/* Loading Overlay */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 10;
    border-radius: 16px;
}

.loading-overlay.active {
    display: flex;
}

.loader {
    width: 50px;
    height: 50px;
    border: 5px solid var(--neutral-mid);
    border-top-color: var(--accent-terracotta);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Search and Results Header */
.search-input {
    width: 100%;
    max-width: 400px;
    padding: 0.75rem 1rem;
    border: 2px solid var(--neutral-mid);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    box-shadow: var(--shadow-subtle);
    margin-bottom: 1rem;
}

.search-input:focus {
    outline: none;
    border-color: var(--accent-warm);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.2);
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
    width: 100%;
}

.results-count {
    font-size: 1.1rem;
    color: var(--primary-medium);
}

.sort-select {
    padding: 0.65rem 1rem;
    border: 2px solid var(--neutral-mid);
    border-radius: 8px;
    font-size: 0.95rem;
    background: var(--white);
    cursor: pointer;
    transition: var(--transition);
}

.sort-select:focus {
    outline: none;
    border-color: var(--accent-warm);
}

/* Product Grid */
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
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--primary-dark);
    margin-bottom: 0.75rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.product-brand {
    font-size: 0.9rem;
    color: var(--primary-medium);
    margin-bottom: 0.5rem;
}

.product-price {
    font-size: 1.8rem;
    font-weight: 500;
    color: var(--accent-terracotta);
    margin-bottom: 1rem;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    text-transform: uppercase;
}

.add-to-cart-btn:hover:not(:disabled) {
    background: var(--accent-terracotta);
    transform: translateY(-2px);
}

.add-to-cart-btn:disabled {
    background: var(--neutral-mid);
    color: var(--text-muted);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Pagination */
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
    color: var(--primary-dark);
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
    background: var(--accent-terracotta);
    color: var(--white);
    border-color: var(--accent-terracotta);
}

/* No Results */
.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--white);
    border-radius: 16px;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

/* Cart Sidebar */
.cart-sidebar {
    position: fixed;
    top: 0;
    right: -450px;
    width: 100%;
    max-width: 450px;
    height: 100vh;
    background: var(--white);
    box-shadow: -4px 0 20px rgba(0, 0, 0, 0.2);
    transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    display: flex;
    flex-direction: column;
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
    transition: opacity 0.4s ease, visibility 0.4s ease;
    z-index: 999;
}

.cart-overlay.active {
    opacity: 1;
    visibility: visible;
}

.cart-header {
    padding: 2rem;
    border-bottom: 2px solid var(--neutral-mid);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cart-title {
    font-size: 1.5rem;
    font-weight: 700;
}

.cart-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    transition: var(--transition);
}

.cart-close:hover {
    color: var(--accent-terracotta);
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.cart-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--neutral-mid);
}

.cart-item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.cart-item-price {
    color: var(--accent-terracotta);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 28px;
    height: 28px;
    border: 1px solid var(--neutral-mid);
    background: var(--white);
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quantity-btn:hover {
    background: var(--accent-terracotta);
    color: var(--white);
}

.quantity-display {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
}

.remove-item-btn {
    margin-left: auto;
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 1.2rem;
}

.cart-footer {
    padding: 1.5rem 2rem;
    border-top: 2px solid var(--neutral-mid);
    background: var(--bg-light);
}

.cart-total {
    display: flex;
    justify-content: space-between;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.checkout-btn {
    width: 100%;
    padding: 1rem;
    background: var(--primary-dark);
    color: var(--white);
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    text-transform: uppercase;
}

.checkout-btn:hover {
    background: var(--accent-terracotta);
}

.empty-cart {
    text-align: center;
    padding: 3rem 2rem;
}

.empty-cart-icon {
    font-size: 3rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

/* Toast Notification Styles */
.toast-notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #dc3545;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    animation: slideInUp 0.3s ease-out;
    min-width: 300px;
    max-width: 400px;
}

.toast-notification.success {
    background: #28a745;
}

.toast-notification.warning {
    background: #ffc107;
    color: #333;
}

.toast-notification.info {
    background: #17a2b8;
}

.toast-icon {
    font-size: 1.5rem;
}

.toast-message {
    flex: 1;
    font-weight: 500;
}

.toast-close {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.8;
    transition: opacity 0.2s;
}

.toast-close:hover {
    opacity: 1;
}

.cart-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    padding: 4px 8px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.75rem;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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

@keyframes slideInUp {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideOutDown {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(100px);
        opacity: 0;
    }
}

.toast-notification.hiding {
    animation: slideOutDown 0.3s ease-out forwards;
}

/* ========================================
   MOBILE RESPONSIVE STYLES
   ======================================== */

@media (max-width: 768px) {
    .shop-container {
        padding: 0 1rem;
        margin: 1.5rem auto;
    }

    .page-title {
        font-size: 2rem;
    }

    .page-subtitle {
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }

    .product-card {
        border-radius: 12px;
    }

    .product-image-wrapper {
        height: 200px;
    }

    .product-details {
        padding: 1rem;
    }

    .product-name {
        font-size: 1.1rem;
    }

    .product-price {
        font-size: 1.4rem;
    }

    .add-to-cart-btn {
        padding: 0.7rem;
        font-size: 0.85rem;
    }

    .shop-filter-sidebar {
        padding: 1.5rem;
        border-radius: 12px;
    }

    .filter-header {
        font-size: 1.2rem;
    }
    
    .cart-sidebar {
        max-width: 100%;
        right: -100%;
    }

    .cart-sidebar.open {
        right: 0;
    }

    .results-header {
        flex-direction: column;
        align-items: stretch;
    }

    .search-input {
        max-width: 100%;
        width: 100%;
    }

    .sort-select {
        width: 100%;
    }

    .results-count {
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .toast-notification {
        bottom: 20px;
        right: 20px;
        left: 20px;
        min-width: auto;
        max-width: none;
    }
}

/* Very Small Screens (480px and below) */
@media (max-width: 480px) {
    .shop-container {
        padding: 0 0.5rem;
    }

    .page-title {
        font-size: 1.5rem;
    }

    .product-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .filter-toggle-btn {
        font-size: 0.9rem;
        padding: 0.8rem;
    }

    .cart-item {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .cart-item-image {
        width: 100%;
        height: 150px;
    }

    .cart-header {
        padding: 1.5rem;
    }

    .cart-footer {
        padding: 1rem 1.5rem;
    }

    .pagination-btn,
    .page-number {
        padding: 0.5rem 0.8rem;
        font-size: 0.85rem;
    }
}
    </style>
</head>
<body>
    
  <!-- Navbar -->
  <header class="navbar">
    <div class="navbar-container">
      <div class="navbar-brand">
        <img src="../assets/icon.png" alt="SP Gadgets" class="navbar-logo">
        <div class="navbar-text">
          <h1 class="navbar-title">SP Gadgets</h1>
          <p class="navbar-subtitle">Shinkomania Plug</p>
        </div>
      </div>

      <nav class="navbar-nav" aria-label="Primary navigation">
        <a href="#home" class="nav-link">Home</a>
        <a href="#products" class="nav-link">Products</a>
        <a href="#features" class="nav-link">Features</a>
        <a href="#contact" class="nav-link">Contact</a>
      </nav>

      <div class="navbar-actions">
        <button class="cart-btn" id="cartBtn" aria-label="Shopping cart">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="9" cy="21" r="1"/>
            <circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <span class="cart-badge" id="cart-badge">0</span>
        </button>

        <button class="menu-toggle" id="menu-toggle" aria-label="Menu">
          <span class="hamburger"></span>
          <span class="hamburger"></span>
          <span class="hamburger"></span>
        </button>
      </div>
    </div>
  </header>

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
                  <input type="checkbox" value="<?php echo htmlspecialchars($category); ?>" data-filter="category">
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

          <input type="text" id="searchBar" placeholder="Search by product name or brand..." class="search-input">
          
          <div class="results-header">
            <div class="results-count" id="resultsCount">Showing <?php echo count($initialProducts); ?> products</div>
            <select class="sort-select" id="sortSelect">
              <option value="featured">Featured</option>
              <option value="price-low">Price: Low to High</option>
              <option value="price-high">Price: High to Low</option>
              <option value="name">Name: A to Z</option>
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
    // Pass initial data to JavaScript
    window.shopInitialData = {
        products: <?php echo json_encode($initialProducts); ?>,
        priceRange: {
            min: <?php echo $minPrice; ?>,
            max: <?php echo $maxPrice; ?>
        },
        totalProducts: <?php echo $totalProducts; ?>
    };

    // assets/js/shop.js - Optimized Shop with Initial Server-Side Render

// ========================================
// STATE MANAGEMENT
// ========================================
const shopState = {
    currentPage: 1,
    productsPerPage: 10,
    filters: {
        categories: [],
        brands: [],
        conditions: [],
        minPrice: 0,
        maxPrice: 999999999,
    },
    originalPriceRange: { min: 0, max: 999999999 }, // Store original values
    searchQuery: '',
    sortBy: 'featured',
    totalPages: 1,
    totalResults: 0,
    isFiltered: false // Track if any filters are active
};

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
// INITIALIZATION
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
    }
    
    // Initialize UI
    initializePriceSlider();
    initializeEventListeners();
    initializeCart();
    
    // Attach listeners to initial products (server-rendered)
    attachAddToCartListeners();
    
    // Render initial pagination
    renderPagination();
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
    shopState.filters = {
        categories: [],
        brands: [],
        conditions: [],
        minPrice: shopState.originalPriceRange.min,
        maxPrice: shopState.originalPriceRange.max,
    };
    shopState.searchQuery = '';
    shopState.currentPage = 1;
    shopState.sortBy = 'featured';
    shopState.isFiltered = false;
    
    const checkboxes = document.querySelectorAll('input[type="checkbox"][data-filter]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    const searchBar = document.getElementById('searchBar');
    if (searchBar) searchBar.value = '';
    
    const minSlider = document.getElementById('minPriceSlider');
    const maxSlider = document.getElementById('maxPriceSlider');
    const sortSelect = document.getElementById('sortSelect');
    
    if (minSlider) minSlider.value = shopState.originalPriceRange.min;
    if (maxSlider) maxSlider.value = shopState.originalPriceRange.max;
    if (sortSelect) sortSelect.value = 'featured';
    
    updatePriceSliderVisual();
    
    // Reload page to show initial products
    window.location.reload();
}

// ========================================
// FETCH PRODUCTS FROM API (Only when filtered)
// ========================================
async function fetchProducts() {
    // Don't fetch if no filters are active
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
        
        // Only add price params if they differ from original range
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
        // Remove existing listeners to avoid duplicates
        button.replaceWith(button.cloneNode(true));
    });
    
    // Re-query after cloning
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

// Toast Notification System
function showToast(message, type = 'info') {
    // Remove any existing toasts
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    
    // Choose icon based on type
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
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
}

// Navigate to product details page
function viewProduct(productId) {
    window.location.href = `product-details.php?id=${productId}`;
}
  </script>
  
  <script src="../assets/js/shop.js"></script>
</body>
</html>