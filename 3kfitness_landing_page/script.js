// ================== CAROUSEL ==================
const slideContainer = document.querySelector('.carousel-slide');

if (slideContainer) {
  let originalSlides = Array.from(slideContainer.children);
  const visibleCards = 3;
  let slideIndex = visibleCards;
  let isTransitioning = false;

  // ===== CLONE SLIDES =====
  const clonesBefore = originalSlides
    .slice(-visibleCards)
    .map(slide => slide.cloneNode(true));

  const clonesAfter = originalSlides
    .slice(0, visibleCards)
    .map(slide => slide.cloneNode(true));

  clonesBefore.forEach(clone =>
    slideContainer.insertBefore(clone, slideContainer.firstChild)
  );

  clonesAfter.forEach(clone =>
    slideContainer.appendChild(clone)
  );

  const allSlides = Array.from(slideContainer.children);

  // ===== GET REAL WIDTH & GAP =====
  function getMetrics() {
    const slide = slideContainer.querySelector('img');
    const styles = getComputedStyle(slideContainer);

    const gap = parseFloat(styles.gap) || 0;
    const width = slide.getBoundingClientRect().width;

    return { width, gap };
  }

  // ===== UPDATE POSITION =====
  function updatePosition(animate = false) {
    const { width, gap } = getMetrics();
    const offset = (width + gap) * slideIndex;

    slideContainer.style.transition = animate
      ? 'transform 0.5s ease-in-out'
      : 'none';

    slideContainer.style.transform = `translateX(-${offset}px)`;
  }

  // INITIAL POSITION
  updatePosition();

  // ===== MOVE SLIDE (GLOBAL FOR BUTTONS) =====
  window.moveSlide = function (direction) {
    if (isTransitioning) return;
    isTransitioning = true;

    slideIndex += direction;
    updatePosition(true);
  };

  // ===== LOOP HANDLER =====
  slideContainer.addEventListener('transitionend', () => {
    isTransitioning = false;

    if (slideIndex >= allSlides.length - visibleCards) {
      slideIndex = visibleCards;
      updatePosition(false);
    }

    if (slideIndex < visibleCards) {
      slideIndex = allSlides.length - visibleCards * 2;
      updatePosition(false);
    }
  });

  // ===== AUTOPLAY =====
  let autoplay = setInterval(() => moveSlide(1), 3000);

  slideContainer.addEventListener('mouseenter', () => clearInterval(autoplay));
  slideContainer.addEventListener('mouseleave', () => {
    autoplay = setInterval(() => moveSlide(1), 3000);
  });

  // ===== TOUCH SUPPORT =====
  let startX = 0;

  slideContainer.addEventListener('touchstart', e => {
    clearInterval(autoplay);
    startX = e.touches[0].clientX;
  });

  slideContainer.addEventListener('touchend', e => {
    const endX = e.changedTouches[0].clientX;
    const delta = endX - startX;

    if (delta > 50) moveSlide(-1);
    else if (delta < -50) moveSlide(1);

    autoplay = setInterval(() => moveSlide(1), 3000);
  });

  // ===== RECALCULATE ON RESIZE =====
  window.addEventListener('resize', () => updatePosition(false));
}

// ================== MODAL / LIGHTBOX ==================
function openModal(image) {
  const modal = document.getElementById('modal');
  const modalImg = document.getElementById('modal-img');

  modalImg.src = image.src;
  modal.style.display = 'flex';
}

function closeModal() {
  document.getElementById('modal').style.display = 'none';
}

// ================== HAMBURGER NAV ==================
const hamburger = document.getElementById('hamburger');
const nav = document.querySelector('nav');

if (hamburger && nav) {
  hamburger.addEventListener('click', () => {
    nav.classList.toggle('active');
  });
}

// ================== REVIEWS DATA ==================
const allReviewsData = [
  { name: "Mark D.", stars: 5, text: "The gym environment is motivating and well-maintained.", date: "2024-12-01" },
  { name: "Janelle R.", stars: 4, text: "Sulit ang membership! I’ve seen real progress.", date: "2024-11-20" },
  { name: "Kevin S.", stars: 5, text: "Clean facilities and friendly staff.", date: "2024-11-10" },
  { name: "Brian L.", stars: 3, text: "Good gym but can get crowded at peak hours.", date: "2024-10-15" },
  { name: "Angela M.", stars: 5, text: "Trainers are approachable and motivating.", date: "2024-10-05" }
];

// ================== REVIEWS MODAL ==================
function openReviewsModal() {
  document.getElementById('reviews-modal').style.display = 'flex';
  renderReviews();
}

function closeReviewsModal() {
  document.getElementById('reviews-modal').style.display = 'none';
}

function formatReviewDate(value) {
  if (!value) return '';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
}

function renderReviews() {
  const starFilter = document.getElementById('starFilter').value;
  const dateSort = document.getElementById('dateSort').value;
  const list = document.getElementById('reviewsList');

  let filtered = [...allReviewsData];

  if (starFilter !== "all") {
    filtered = filtered.filter(r => r.stars === parseInt(starFilter));
  }

  filtered.sort((a, b) =>
    dateSort === "newest"
      ? new Date(b.date) - new Date(a.date)
      : new Date(a.date) - new Date(b.date)
  );

  list.innerHTML = filtered.map(r => `
    <div class="review-item">
      <div class="stars">${"★".repeat(r.stars)}${"☆".repeat(5 - r.stars)}</div>
      <p>“${r.text}”</p>
      <strong>— ${r.name}</strong><br>
      <span class="review-date">${formatReviewDate(r.date)}</span>
    </div>
  `).join("");
}
