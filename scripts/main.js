// ===== Cart Badge Management =====
let cartCount = 0;

function updateCartBadge() {
  const badge = document.getElementById('cart-badge');
  if (!badge) return;

  badge.textContent = String(cartCount);
  
  if (cartCount > 0) {
    badge.classList.add('visible');
  } else {
    badge.classList.remove('visible');
  }
}

// ===== Mobile Menu Management =====
const menuToggle = document.getElementById('menu-toggle');
const mobileOverlay = document.getElementById('mobile-overlay');
const overlayClose = document.getElementById('overlay-close');
const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

function openMenu() {
  menuToggle.classList.add('active');
  menuToggle.setAttribute('aria-expanded', 'true');
  mobileOverlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
  overlayClose?.focus();
}

function closeMenu() {
  menuToggle.classList.remove('active');
  menuToggle.setAttribute('aria-expanded', 'false');
  mobileOverlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  menuToggle.focus();
}

if (menuToggle) {
  menuToggle.addEventListener('click', () => {
    if (menuToggle.classList.contains('active')) {
      closeMenu();
    } else {
      openMenu();
    }
  });
}

if (overlayClose) {
  overlayClose.addEventListener('click', closeMenu);
}

// Close on overlay background click
const overlayBg = mobileOverlay;
if (overlayBg) {
  overlayBg.addEventListener('click', (e) => {
    if (e.target === overlayBg) {
      closeMenu();
    }
  });
}

// Close when clicking nav links
mobileNavLinks.forEach((link) => {
  link.addEventListener('click', closeMenu);
});

// Close on Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && menuToggle.classList.contains('active')) {
    closeMenu();
  }
});

// ===== Slideshow Management =====
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const slideCount = slides.length;
let autoplayInterval;

function createSlideIndicators() {
  const indicatorsContainer = document.getElementById('slide-indicators');
  if (!indicatorsContainer) return;

  for (let i = 0; i < slideCount; i++) {
    const indicator = document.createElement('div');
    indicator.className = 'indicator';
    if (i === 0) indicator.classList.add('active');
    indicator.addEventListener('click', () => goToSlide(i));
    indicatorsContainer.appendChild(indicator);
  }
}

function updateSlide() {
  slides.forEach((slide, idx) => {
    slide.classList.toggle('active', idx === currentSlide);
  });

  const indicators = document.querySelectorAll('.indicator');
  indicators.forEach((indicator, idx) => {
    indicator.classList.toggle('active', idx === currentSlide);
  });
}

function nextSlide() {
  currentSlide = (currentSlide + 1) % slideCount;
  updateSlide();
  resetAutoplay();
}

function prevSlide() {
  currentSlide = (currentSlide - 1 + slideCount) % slideCount;
  updateSlide();
  resetAutoplay();
}

function goToSlide(idx) {
  currentSlide = idx;
  updateSlide();
  resetAutoplay();
}

function startAutoplay() {
  autoplayInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
}

function resetAutoplay() {
  clearInterval(autoplayInterval);
  startAutoplay();
}

const prevBtn = document.getElementById('prev-slide');
const nextBtn = document.getElementById('next-slide');

if (prevBtn) prevBtn.addEventListener('click', prevSlide);
if (nextBtn) nextBtn.addEventListener('click', nextSlide);

// Initialize slideshow
if (slideCount > 0) {
  createSlideIndicators();
  startAutoplay();
}

// Initialize
updateCartBadge();
