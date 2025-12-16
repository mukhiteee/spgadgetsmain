<head>
    <link rel="stylesheet" href="styles/header.css">
</head>

<header class="navbar">
    <div class="navbar-container">
      <!-- Left: Logo + Brand -->
      <div class="navbar-brand">
        <img src="/assets/icon.png" alt="SP Gadgets" class="navbar-logo">
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
        <button class="shop-btn">Shop Now</button>

        <!-- Mobile Menu Toggle -->
        <button class="menu-toggle" id="menu-toggle" aria-label="Menu" aria-expanded="false">
          <span class="hamburger"></span>
          <span class="hamburger"></span>
          <span class="hamburger"></span>
        </button>
      </div>
    </div>
  </header>

  <!-- Mobile Menu Overlay -->
  <div class="mobile-overlay" id="mobile-overlay" aria-hidden="true">
    <div class="mobile-overlay-content">
      <div class="mobile-overlay-header">
        <div class="navbar-brand">
          <img src="assets/icon.png" alt="SP Gadgets" class="navbar-logo">
          <div class="navbar-text">
            <h2 class="navbar-title">SP Gadgets</h2>
            <p class="navbar-subtitle">Shinkomania Plug</p>
          </div>
        </div>
        <button class="mobile-overlay-close" id="overlay-close" aria-label="Close menu">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <nav class="mobile-nav" aria-label="Mobile navigation">
        <a href="#home" class="mobile-nav-link">Home</a>
        <a href="#products" class="mobile-nav-link">Products</a>
        <a href="#features" class="mobile-nav-link">Features</a>
        <a href="#contact" class="mobile-nav-link">Contact</a>
      </nav>

      <div class="mobile-cta">
        <button class="shop-btn">Shop Now</button>
      </div>
    </div>
  </div>