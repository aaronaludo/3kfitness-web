// ===== Carousel Core Variables =====
const slideContainer = document.querySelector('.carousel-slide');
let originalSlides = Array.from(slideContainer.children);
const visibleCards = 3;
const cardGap = 20; // same as CSS gap in px
const cardWidth = 300; // same as CSS width in px
const totalOriginal = originalSlides.length;

let slideIndex = visibleCards; // Start after clones
let isTransitioning = false;

// ===== Clone Slides for Seamless Loop =====
const clonesBefore = originalSlides.slice(-visibleCards).map(el => el.cloneNode(true));
const clonesAfter = originalSlides.slice(0, visibleCards).map(el => el.cloneNode(true));

// Append/prepend clones
clonesBefore.forEach(clone => slideContainer.insertBefore(clone, slideContainer.firstChild));
clonesAfter.forEach(clone => slideContainer.appendChild(clone));

// Update full slide list
const allSlides = Array.from(slideContainer.children);

// ===== Initial Position =====
updatePosition();

// ===== Update Slide Position =====
function updatePosition(animate = false) {
  const totalOffset = (cardWidth + cardGap) * slideIndex;
  slideContainer.style.transition = animate ? 'transform 0.5s ease-in-out' : 'none';
  slideContainer.style.transform = `translateX(-${totalOffset}px)`;
}

// ===== Slide Movement Function =====
function moveSlide(direction) {
  if (isTransitioning) return;
  isTransitioning = true;

  slideIndex += direction;
  updatePosition(true);
}

// ===== Looping Back Logic =====
slideContainer.addEventListener('transitionend', () => {
  isTransitioning = false;

  if (slideIndex >= allSlides.length - visibleCards) {
    slideIndex = visibleCards;
    updatePosition(false);
  }

  if (slideIndex < visibleCards) {
    slideIndex = allSlides.length - (visibleCards * 2);
    updatePosition(false);
  }
});

// ===== Autoplay Function =====
let autoplay = setInterval(() => moveSlide(1), 3000);

slideContainer.addEventListener('mouseenter', () => clearInterval(autoplay));
slideContainer.addEventListener('mouseleave', () => {
  autoplay = setInterval(() => moveSlide(1), 3000);
});

// ===== Touch Swipe Support =====
let startX = 0;

slideContainer.addEventListener('touchstart', (e) => {
  clearInterval(autoplay);
  startX = e.touches[0].clientX;
});

slideContainer.addEventListener('touchend', (e) => {
  const endX = e.changedTouches[0].clientX;
  const delta = endX - startX;

  if (delta > 50) moveSlide(-1);
  else if (delta < -50) moveSlide(1);

  autoplay = setInterval(() => moveSlide(1), 3000);
});

// ===== Modal / Lightbox Viewer =====
function openModal(image) {
  document.getElementById('modal-img').src = image.src;
  document.getElementById('modal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('modal').style.display = 'none';
}
// ===== Responsive Navbar Toggle =====
const hamburger = document.getElementById('hamburger');
const navbar = document.querySelector('nav');

hamburger.addEventListener('click', () => {
  navbar.classList.toggle('active');
});
