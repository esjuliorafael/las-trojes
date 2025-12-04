<footer class="footer-container container-wide">
  <div class="container">
    <div class="footer-menu-wrapper">
      <nav class="footer-menu">
        <a href="index.php">Inicio</a>
        <a href="#">Nosotros</a>
        <a href="galeria.php">Galería</a>
        <a href="tienda.php">Tienda</a>
        <a href="contacto.php">Contacto</a>
      </nav>
    </div>
    <div class="footer-content">
      <div class="footer-column">
        <div class="footer-contact-section">
          <h3 class="footer-contact-title">Contáctanos</h3>
          <p class="footer-contact-description">Estamos disponibles para resolver tus dudas.</p>
          <div class="footer-contact-persons">
            <div class="footer-contact-person">
              <p>Ricardo Torres</p>
              <div class="footer-contact-methods">
                <a href="tel:+524432020019" class="footer-social-icon"><i class="fas fa-phone"></i></a>
                <a href="https://wa.me/+524432020019" target="_blank" class="footer-social-icon"><i class="fa-brands fa-whatsapp"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="footer-column">
        <div class="footer-logo-wrapper">
          <div class="footer-logo-center">
            <img src="<?php echo isset($logo_actual) ? $logo_actual : 'assets/images/logo.png'; ?>" alt="Logo">
          </div>
          <div class="footer-logo-address">
            <p><i class="fas fa-map-marker-alt"></i> Morelia, Michoacán</p>
          </div>
        </div>
      </div>
      <div class="footer-column">
        <div class="footer-social">
          <h3 class="footer-social-title">Síguenos</h3>
          <div class="footer-social-icons">
            <a href="#" class="footer-social-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="footer-social-icon"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
      </div>
    </div>
    <hr class="footer-divider">
    <div class="footer-bottom">
      <div>&copy; <span id="current-year"><?php echo date('Y'); ?></span> Rancho Las Trojes.</div>
      <div>Desarrollado por <strong>Xhunco</strong></div>
    </div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Año Actual
    const yearSpan = document.getElementById('current-year');
    if (yearSpan) yearSpan.textContent = new Date().getFullYear();

    // 2. Dark Mode
    const themeToggle = document.getElementById('themeToggle');
    const darkModeStylesheet = document.getElementById('darkModeStylesheet');
    if (themeToggle && darkModeStylesheet) {
        const themeIcon = themeToggle.querySelector('i');
        function enableDarkMode() {
            darkModeStylesheet.disabled = false;
            themeIcon.classList.replace('fa-moon', 'fa-sun');
            localStorage.setItem('darkMode', 'enabled');
        }
        function disableDarkMode() {
            darkModeStylesheet.disabled = true;
            themeIcon.classList.replace('fa-sun', 'fa-moon');
            localStorage.setItem('darkMode', 'disabled');
        }
        if (localStorage.getItem('darkMode') === 'enabled') enableDarkMode();

        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            darkModeStylesheet.disabled ? enableDarkMode() : disableDarkMode();
        });
    }

    // 3. Menú Hamburguesa
    const hamburger = document.getElementById('hamburgerButton');
    const navOverlay = document.getElementById('navMenuOverlay');
    if (hamburger && navOverlay) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navOverlay.classList.toggle('active');
        });
        // Cerrar al dar click en enlaces
        document.querySelectorAll('.nav-menu-link').forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navOverlay.classList.remove('active');
            });
        });
    }
    
    // 4. Actualizar Carrito UI (Si existe main.js cargado)
    if(typeof updateCartUI === 'function') {
        updateCartUI();
    }
});
</script>