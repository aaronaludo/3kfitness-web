// ================== CAROUSEL ==================
const slideContainer = document.querySelector('.carousel-slide');

if (slideContainer) {
  const originalSlides = Array.from(slideContainer.children);
  const visibleCards = 3;
  let slideIndex = visibleCards;
  let isTransitioning = false;

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

  function getMetrics() {
    const slide = slideContainer.querySelector('img');
    const styles = getComputedStyle(slideContainer);

    const gap = parseFloat(styles.gap) || 0;
    const width = slide.getBoundingClientRect().width;

    return { width, gap };
  }

  function updatePosition(animate = false) {
    const { width, gap } = getMetrics();
    const offset = (width + gap) * slideIndex;

    slideContainer.style.transition = animate
      ? 'transform 0.5s ease-in-out'
      : 'none';

    slideContainer.style.transform = `translateX(-${offset}px)`;
  }

  updatePosition();

  window.moveSlide = function (direction) {
    if (isTransitioning) return;
    isTransitioning = true;

    slideIndex += direction;
    updatePosition(true);
  };

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

  let autoplay = setInterval(() => moveSlide(1), 3000);

  slideContainer.addEventListener('mouseenter', () => clearInterval(autoplay));
  slideContainer.addEventListener('mouseleave', () => {
    autoplay = setInterval(() => moveSlide(1), 3000);
  });

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
const fallbackReviewsData = [
  { name: 'Mark D.', stars: 5, text: 'The gym environment is motivating and well-maintained.', date: '2024-12-01' },
  { name: 'Janelle R.', stars: 4, text: 'Sulit ang membership! I’ve seen real progress.', date: '2024-11-20' },
  { name: 'Kevin S.', stars: 5, text: 'Clean facilities and friendly staff.', date: '2024-11-10' },
  { name: 'Brian L.', stars: 3, text: 'Good gym but can get crowded at peak hours.', date: '2024-10-15' },
  { name: 'Angela M.', stars: 5, text: 'Trainers are approachable and motivating.', date: '2024-10-05' }
];

const injectedReviews =
  typeof window !== 'undefined' && Array.isArray(window.reviewsData) && window.reviewsData.length
    ? window.reviewsData
    : null;

const allReviewsData = injectedReviews || fallbackReviewsData;

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

  if (starFilter !== 'all') {
    filtered = filtered.filter(r => Number(r.stars) === parseInt(starFilter));
  }

  const parseDateValue = value => {
    const parsed = Date.parse(value || '');
    return Number.isNaN(parsed) ? 0 : parsed;
  };

  filtered.sort((a, b) => {
    const aDate = parseDateValue(a.date);
    const bDate = parseDateValue(b.date);
    return dateSort === 'newest' ? bDate - aDate : aDate - bDate;
  });

  list.innerHTML = filtered.map(r => {
    const stars = Math.min(5, Math.max(1, Number(r.stars) || 5));
    const dateLabel = formatReviewDate(r.date);
    const name = r.name || 'Member';
    const text = r.text || '';

    return `
      <div class="review-item">
        <div class="stars">${'★'.repeat(stars)}${'☆'.repeat(5 - stars)}</div>
        <p>“${text}”</p>
        <strong>— ${name}</strong><br>
        <span class="review-date">${dateLabel}</span>
      </div>
    `;
  }).join('');
}
