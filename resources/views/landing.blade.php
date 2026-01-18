<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>3K Fitness</title>

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Styles -->
  <link rel="stylesheet" href="{{ asset('landing/styles.css') }}" />

  <!-- JS -->
  <script src="{{ asset('landing/script.js') }}" defer></script>
</head>
<body>

  <!-- ======= HEADER / NAV ======= -->
  <header>
    <nav>
      <a href="#hero" class="logo">
        <img src="{{ asset('landing/images/logo.png') }}" alt="3K Fitness Logo" />
        <span>3K Fitness</span>
      </a>

      <ul class="nav-links">
        <li><a href="#hero">Home</a></li>
        <li><a href="#gallery">Gallery</a></li>
        <li><a href="#contact">Location</a></li>
        <li><span class="hours-badge">MON-SUN 7AM–10PM</span></li>
      </ul>

      <ul class="nav-links nav-utility">
        <li>
          <a href="https://www.facebook.com/3ksmusclefitnesscenter" target="_blank" title="Facebook Page">
            <i class="fab fa-facebook-f"></i>
          </a>
        </li>
        <li>
          <a href="https://drive.google.com/file/d/1gv-mECmMa0FAX77WSxMWkb32zuxqrO7i/view?usp=sharing" target="_blank" title="Download App">
            <i class="fas fa-mobile-alt"></i>
          </a>
        </li>
      </ul>
    </nav>
  </header>

  <!-- ======= HERO SECTION ======= -->
  <section id="hero" style="background-image: url('{{ asset('landing/images/cover.jpg') }}')">
    <div class="hero-content">
      <h1>Go Beyond Limits</h1>
      <p>Train like a beast, look like a beauty.</p>
      <a href="https://drive.google.com/file/d/1gv-mECmMa0FAX77WSxMWkb32zuxqrO7i/view?usp=sharing" target="_blank" class="btn-primary">Get the App</a>
    </div>
  </section>

  <!-- ======= GALLERY / CAROUSEL ======= -->
  <section id="gallery" class="carousel-wrapper">
    <h2 class="carousel-title">Our Gym in Action</h2>

    <div class="carousel-container">
      <div class="carousel-slide">
        <img src="{{ asset('landing/images/img1.jpg') }}" alt="Gym 1" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img2.jpg') }}" alt="Gym 2" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img3.jpg') }}" alt="Gym 3" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img4.jpg') }}" alt="Gym 4" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img5.jpg') }}" alt="Gym 5" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img6.jpg') }}" alt="Gym 6" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img7.jpg') }}" alt="Gym 7" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img8.jpg') }}" alt="Gym 8" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img9.jpg') }}" alt="Gym 9" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img10.jpg') }}" alt="Gym 10" onclick="openModal(this)" />
        <img src="{{ asset('landing/images/img11.jpg') }}" alt="Gym 11" onclick="openModal(this)" />
      </div>

      <button class="carousel-btn prev" onclick="moveSlide(-1)">❮</button>
      <button class="carousel-btn next" onclick="moveSlide(1)">❯</button>
    </div>
  </section>

  <!-- ===== Modal Lightbox ===== -->
  <div id="modal" class="modal" onclick="closeModal()">
    <img id="modal-img" />
  </div>

  <!-- ======= LOCATION MAP ======= -->
  <section id="contact">
    <h2>📍 Visit Us</h2>
    <p>2/F Victory Central Station Bldg, Anonas Cor. 18th Street, West Bajac-Bajac, Olongapo, Philippines</p>

    <p><strong>🕒 Operating Hours:</strong><br>
      Monday – Sunday: 7:00 AM – 10:00 PM</p>

    <div class="map-container">
      <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.7137465244917!2d120.28289311492712!3d14.838311089681472!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x339671ef28241b4d%3A0x6dc9b25b6e34a003!2sVictory%20Central%20Mall!5e0!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph"
        width="100%" height="350" style="border:0;" allowfullscreen=""
        loading="lazy" referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </section>

  <!-- ======= FOOTER ======= -->
  <footer>
    <p>&copy; 2026 3K Fitness. All Rights Reserved.</p>
    <p><strong>Hours:</strong> Mon–Sun: 7AM – 10PM</p>
  </footer>

</body>
</html>
