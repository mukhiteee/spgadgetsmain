<?php
// pages/checkout.php - Enhanced Checkout Page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SP Gadgets</title>
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0f172a;
            --primary-medium: #1F95B1;
            --accent-terracotta: #1F95B1;
            --neutral-light: #f8fafc;
            --neutral-mid: #e2e8f0;
            --white: #ffffff;
            --shadow-subtle: 0 2px 12px rgba(31, 149, 177, 0.08);
            --success: #28a745;
            --error: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, sans-serif;
            background: linear-gradient(135deg, var(--neutral-light) 0%, var(--neutral-mid) 100%);
            color: var(--primary-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-medium);
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            color: var(--accent-terracotta);
            transform: translateX(-5px);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--primary-medium);
            margin-bottom: 2rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 450px;
            gap: 2rem;
        }

        .checkout-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-subtle);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            border-bottom: 2px solid var(--accent-terracotta);
            padding-bottom: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .form-group label .required {
            color: var(--error);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--neutral-mid);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-terracotta);
            box-shadow: 0 0 0 3px rgba(31, 149, 177, 0.1);
        }

        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
            border-color: var(--error);
        }

        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 0.25rem;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .order-summary {
            background: var(--white);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: var(--shadow-subtle);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .summary-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
        }

        .summary-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }

        .summary-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--neutral-mid);
        }

        .summary-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .summary-item-details {
            flex: 1;
        }

        .summary-item-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .summary-item-price {
            color: var(--primary-medium);
            font-size: 0.85rem;
        }

        .summary-item-qty {
            color: var(--primary-medium);
            font-size: 0.85rem;
        }

        .summary-calculations {
            padding: 1rem 0;
            border-top: 2px solid var(--neutral-mid);
        }

        .calculation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .calculation-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-terracotta);
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--neutral-mid);
        }

        .place-order-btn {
            width: 100%;
            padding: 1.2rem;
            background: var(--primary-dark);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 1rem;
        }

        .place-order-btn:hover {
            background: var(--accent-terracotta);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(31, 149, 177, 0.3);
        }

        .place-order-btn:disabled {
            background: var(--neutral-mid);
            cursor: not-allowed;
            transform: none;
        }

        .empty-cart-message {
            text-align: center;
            padding: 3rem;
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-subtle);
        }

        .empty-cart-message i {
            font-size: 4rem;
            color: var(--neutral-mid);
            margin-bottom: 1rem;
        }

        .empty-cart-message h2 {
            margin-bottom: 1rem;
        }

        .empty-cart-message .shop-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.75rem 2rem;
            background: var(--primary-dark);
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .empty-cart-message .shop-link:hover {
            background: var(--accent-terracotta);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: var(--white);
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--neutral-mid);
            border-top-color: var(--accent-terracotta);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: relative;
                top: 0;
            }
        }

        @media (max-width: 768px) {
            .checkout-container {
                padding: 0 1rem;
                margin: 1.5rem auto;
            }

            .page-title {
                font-size: 2rem;
            }

            .checkout-form,
            .order-summary {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <p>Processing your order...</p>
        </div>
    </div>

    <div class="checkout-container">
        <a href="../shop/shop.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>

        <h1 class="page-title">Checkout</h1>
        <p class="page-subtitle">Complete your order</p>

        <div id="checkoutContent">
            <!-- Content will be loaded by JavaScript -->
        </div>
    </div>

    <script>
        // Load cart from localStorage
        let cart = JSON.parse(localStorage.getItem('checkout_cart') || '[]');
        
        // Constants
        const SHIPPING_FEE = 5000; // ₦5,000
        const TAX_RATE = 0.075; // 7.5% VAT

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            if (cart.length === 0) {
                showEmptyCart();
            } else {
                showCheckoutForm();
            }
        });

        function showEmptyCart() {
            document.getElementById('checkoutContent').innerHTML = `
                <div class="empty-cart-message">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Add some products to your cart before checking out.</p>
                    <a href="../shop/shop.php" class="shop-link">Continue Shopping</a>
                </div>
            `;
        }

        function showCheckoutForm() {
            const subtotal = calculateSubtotal();
            const tax = calculateTax(subtotal);
            const total = subtotal + SHIPPING_FEE + tax;

            document.getElementById('checkoutContent').innerHTML = `
                <div class="checkout-grid">
                    <div class="checkout-form">
                        <form id="checkoutForm">
                            <div class="form-section">
                                <h3>Contact Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Full Name <span class="required">*</span></label>
                                        <input type="text" name="fullName" id="fullName" required>
                                        <span class="error-message" id="fullNameError">Please enter your full name</span>
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address <span class="required">*</span></label>
                                        <input type="email" name="email" id="email" required>
                                        <span class="error-message" id="emailError">Please enter a valid email</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number <span class="required">*</span></label>
                                    <input type="tel" name="phone" id="phone" required placeholder="+234 XXX XXX XXXX">
                                    <span class="error-message" id="phoneError">Please enter a valid phone number</span>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Shipping Information</h3>
                                <div class="form-group">
                                    <label>Shipping Address <span class="required">*</span></label>
                                    <textarea name="address" id="address" rows="3" required placeholder="Street address, apartment, suite, etc."></textarea>
                                    <span class="error-message" id="addressError">Please enter your shipping address</span>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>City <span class="required">*</span></label>
                                        <input type="text" name="city" id="city" required>
                                        <span class="error-message" id="cityError">Please enter your city</span>
                                    </div>
                                    <div class="form-group">
                                        <label>State <span class="required">*</span></label>
                                        <select name="state" id="state" required>
                                            <option value="">Select State</option>
                                            <option value="Abia">Abia</option>
                                            <option value="Adamawa">Adamawa</option>
                                            <option value="Akwa Ibom">Akwa Ibom</option>
                                            <option value="Anambra">Anambra</option>
                                            <option value="Bauchi">Bauchi</option>
                                            <option value="Bayelsa">Bayelsa</option>
                                            <option value="Benue">Benue</option>
                                            <option value="Borno">Borno</option>
                                            <option value="Cross River">Cross River</option>
                                            <option value="Delta">Delta</option>
                                            <option value="Ebonyi">Ebonyi</option>
                                            <option value="Edo">Edo</option>
                                            <option value="Ekiti">Ekiti</option>
                                            <option value="Enugu">Enugu</option>
                                            <option value="FCT">Federal Capital Territory</option>
                                            <option value="Gombe">Gombe</option>
                                            <option value="Imo">Imo</option>
                                            <option value="Jigawa">Jigawa</option>
                                            <option value="Kaduna">Kaduna</option>
                                            <option value="Kano">Kano</option>
                                            <option value="Katsina">Katsina</option>
                                            <option value="Kebbi">Kebbi</option>
                                            <option value="Kogi">Kogi</option>
                                            <option value="Kwara">Kwara</option>
                                            <option value="Lagos">Lagos</option>
                                            <option value="Nasarawa">Nasarawa</option>
                                            <option value="Niger">Niger</option>
                                            <option value="Ogun">Ogun</option>
                                            <option value="Ondo">Ondo</option>
                                            <option value="Osun">Osun</option>
                                            <option value="Oyo">Oyo</option>
                                            <option value="Plateau">Plateau</option>
                                            <option value="Rivers">Rivers</option>
                                            <option value="Sokoto">Sokoto</option>
                                            <option value="Taraba">Taraba</option>
                                            <option value="Yobe">Yobe</option>
                                            <option value="Zamfara">Zamfara</option>
                                        </select>
                                        <span class="error-message" id="stateError">Please select your state</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Payment Method</h3>
                                <div class="form-group">
                                    <label>Select Payment Method <span class="required">*</span></label>
                                    <select name="paymentMethod" id="paymentMethod" required>
                                        <option value="">Choose...</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="card">Credit/Debit Card</option>
                                        <option value="cash_on_delivery">Cash on Delivery</option>
                                    </select>
                                    <span class="error-message" id="paymentMethodError">Please select a payment method</span>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Order Notes (Optional)</h3>
                                <div class="form-group">
                                    <label>Additional Information</label>
                                    <textarea name="orderNotes" id="orderNotes" rows="3" placeholder="Any special instructions for your order?"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="order-summary">
                        <h3 class="summary-title">Order Summary</h3>
                        <div class="summary-items" id="orderItems"></div>
                        <div class="summary-calculations">
                            <div class="calculation-row">
                                <span>Subtotal:</span>
                                <span>₦${formatPrice(subtotal)}</span>
                            </div>
                            <div class="calculation-row">
                                <span>Shipping:</span>
                                <span>₦${formatPrice(SHIPPING_FEE)}</span>
                            </div>
                            <div class="calculation-row">
                                <span>Tax (7.5%):</span>
                                <span>₦${formatPrice(tax)}</span>
                            </div>
                            <div class="calculation-row total">
                                <span>Total:</span>
                                <span id="orderTotal">₦${formatPrice(total)}</span>
                            </div>
                        </div>
                        <button type="button" class="place-order-btn" onclick="placeOrder()">
                            <i class="fas fa-lock"></i> Place Order
                        </button>
                    </div>
                </div>
            `;

            renderOrderItems();
        }

        function renderOrderItems() {
            const container = document.getElementById('orderItems');
            container.innerHTML = '';

            cart.forEach(item => {
                const imagePath = item.image ? 
                    (item.image.startsWith('http') ? item.image : `../assets/products/${item.image}`) : 
                    '../assets/products/placeholder.jpg';

                const itemEl = document.createElement('div');
                itemEl.className = 'summary-item';
                itemEl.innerHTML = `
                    <img src="${imagePath}" alt="${item.name}" class="summary-item-image" onerror="this.src='https://via.placeholder.com/60'">
                    <div class="summary-item-details">
                        <div class="summary-item-name">${item.name}</div>
                        <div class="summary-item-price">₦${formatPrice(item.price)}</div>
                        <div class="summary-item-qty">Qty: ${item.quantity}</div>
                    </div>
                `;
                container.appendChild(itemEl);
            });
        }

        function calculateSubtotal() {
            return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        }

        function calculateTax(subtotal) {
            return subtotal * TAX_RATE;
        }

        function formatPrice(price) {
            return parseFloat(price).toLocaleString('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        async function placeOrder() {
            // Validate form
            if (!validateForm()) {
                return;
            }

            // Show loading
            document.getElementById('loadingOverlay').classList.add('active');

            // Get form data
            const formData = new FormData(document.getElementById('checkoutForm'));
            
            // Add cart and totals
            const subtotal = calculateSubtotal();
            const tax = calculateTax(subtotal);
            const total = subtotal + SHIPPING_FEE + tax;

            formData.append('cart', JSON.stringify(cart));
            formData.append('subtotal', subtotal);
            formData.append('shipping', SHIPPING_FEE);
            formData.append('tax', tax);
            formData.append('total', total);

            try {
                const response = await fetch('../api/process_order.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Clear cart
                    localStorage.removeItem('sp_cart');
                    localStorage.removeItem('checkout_cart');
                    
                    // Redirect to success page
                    window.location.href = `order-success.php?order=${result.order_number}`;
                } else {
                    alert('Error: ' + result.message);
                    document.getElementById('loadingOverlay').classList.remove('active');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while processing your order. Please try again.');
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }

        function validateForm() {
            let isValid = true;
            const fields = ['fullName', 'email', 'phone', 'address', 'city', 'state', 'paymentMethod'];

            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                const error = document.getElementById(fieldId + 'Error');

                if (!field.value.trim()) {
                    field.classList.add('error');
                    error.classList.add('show');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                    error.classList.remove('show');
                }

                // Special validation for email
                if (fieldId === 'email' && field.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(field.value)) {
                        field.classList.add('error');
                        error.classList.add('show');
                        isValid = false;
                    }
                }
            });

            if (!isValid) {
                alert('Please fill in all required fields correctly.');
            }

            return isValid;
        }
    </script>
</body>
</html>