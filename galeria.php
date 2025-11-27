<?php
include_once 'config/database.php';
include_once 'models/Logo.php';

$database = new Database();
$db = $database->getConnection();
$logo = new Logo($db);
$logo_actual = $logo->obtenerLogoActivo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Galería - Rancho Las Trojes</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/styles.css">
  <link id="darkModeStylesheet" rel="stylesheet" href="assets/css/dark-mode.css" disabled>
  <style>
    /* --- ESTILOS ESPECÍFICOS DE LA GALERÍA --- */
    .gallery-section {
      background-color: var(--white);
      margin-top: 2rem;
      border-radius: 1.25rem;
      overflow: hidden;
    }
    .search-header {
      background: linear-gradient(135deg, var(--off-white), var(--off-white-light));
      padding: 2rem;
      border-bottom: 1px solid var(--divider);
    }
    .search-container {
      display: flex;
      align-items: center;
      background-color: var(--white);
      border-radius: 12px;
      padding: 1rem;
      border: 1px solid var(--divider);
      max-width: 500px;
      margin: 0 auto;
    }
    .search-icon {
      color: var(--text-color);
      margin-right: 1rem;
    }
    .search-input {
      border: none;
      outline: none;
      flex: 1;
      font-size: 1rem;
      color: var(--black-blue);
      font-family: inherit;
    }
    .search-input::placeholder {
      color: var(--text-color);
    }
    .search-clear {
      color: var(--text-color);
      cursor: pointer;
      padding: 0.25rem;
      margin-left: 0.5rem;
    }
    .search-clear:hover {
      color: var(--brown);
    }
    .controls-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 2rem;
      background-color: var(--white);
      border-bottom: 1px solid var(--divider);
      flex-wrap: wrap;
      gap: 1rem;
    }
    .view-controls {
      display: flex;
      gap: 0.5rem;
      background-color: var(--off-white-light);
      padding: 0.25rem;
      border-radius: 12px;
    }
    .view-button {
      padding: 0.75rem;
      border: none;
      background: none;
      border-radius: 8px;
      color: var(--text-color);
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .view-button:hover {
      background-color: var(--white);
      color: var(--brown);
    }
    .view-button.active {
      background-color: var(--white);
      color: var(--brown);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .categories-container {
      display: flex;
      gap: 1rem;
      overflow-x: auto;
      padding-bottom: 0.25rem;
    }
    .categories-container::-webkit-scrollbar {
      height: 4px;
    }
    .categories-container::-webkit-scrollbar-track {
      background: transparent;
    }
    .categories-container::-webkit-scrollbar-thumb {
      background: var(--divider);
      border-radius: 2px;
    }
    .category-tab {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem 1rem;
      background-color: var(--off-white-light);
      border: 1px solid var(--divider);
      border-radius: 20px;
      color: var(--text-color);
      cursor: pointer;
      transition: all 0.3s ease;
      white-space: nowrap;
      font-size: 0.875rem;
      font-weight: 500;
    }
    .category-tab:hover {
      background-color: var(--brown);
      border-color: var(--brown);
      color: var(--white);
    }
    .category-tab.active {
      background-color: var(--brown);
      border-color: var(--brown);
      color: var(--white);
    }
    .category-badge {
      background-color: rgba(255, 255, 255, 0.3);
      color: var(--text-color);
      border-radius: 10px;
      padding: 0.125rem 0.5rem;
      font-size: 0.75rem;
      font-weight: 600;
      min-width: 20px;
      text-align: center;
    }
    .category-tab.active .category-badge {
      background-color: rgba(255, 255, 255, 0.3);
      color: var(--white);
    }
    .gallery-content {
      padding: 2rem;
      min-height: 400px;
    }
    .gallery-grid {
      display: grid;
      gap: 1rem;
    }
    .gallery-grid.grid-mode {
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    .gallery-grid.masonry-mode {
      display: block;
      columns: 4;
      column-gap: 1rem;
      column-fill: balance;
    }
    .gallery-item {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      break-inside: avoid;
      margin-bottom: 1rem;
      transform-origin: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    .gallery-item:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
      z-index: 10;
    }
    .gallery-item.masonry-item {
      height: auto;
    }
    /* Masonry Layout helpers */
    .gallery-item.masonry-item:nth-child(7n+1) { min-height: 280px; }
    .gallery-item.masonry-item:nth-child(7n+2) { min-height: 180px; }
    .gallery-item.masonry-item:nth-child(7n+3) { min-height: 320px; }
    .gallery-item.masonry-item:nth-child(7n+4) { min-height: 220px; }
    .gallery-item.masonry-item:nth-child(7n+5) { min-height: 250px; }
    .gallery-item.masonry-item:nth-child(7n+6) { min-height: 200px; }
    .gallery-item.masonry-item:nth-child(7n+7) { min-height: 300px; }
    .gallery-item.masonry-item:nth-child(13n+1) {
      min-height: 350px;
      border: 4px solid var(--brown);
      box-shadow: 0 8px 32px rgba(139, 94, 60, 0.2);
    }
    .gallery-item.masonry-item:nth-child(13n+1):hover {
      box-shadow: 0 20px 60px rgba(139, 94, 60, 0.3);
      border-color: var(--black-blue);
    }
    .gallery-item.grid-item {
      height: 260px;
    }
    .gallery-item-image {
      width: 100%;
      height: 100%;
      min-height: 100%;
      object-fit: cover;
      object-position: center;
      transition: all 0.4s ease;
      display: block;
      background-color: #000;
    }
    .gallery-item:hover .gallery-item-image {
      transform: scale(1.08);
      filter: saturate(1.1) brightness(1.1);
    }
    .gallery-item.masonry-item {
      display: flex;
      flex-direction: column;
    }
    .gallery-item.masonry-item .gallery-item-image {
      flex: 1;
      min-height: 0;
    }
    .gallery-item-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.9) 0%,
        rgba(0, 0, 0, 0.7) 40%,
        rgba(0, 0, 0, 0.3) 70%,
        transparent 100%
      );
      padding: 2rem 1.5rem 1.5rem;
      color: white;
      transform: translateY(20px);
      opacity: 0;
      transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      pointer-events: none;
    }
    .gallery-item:hover .gallery-item-overlay {
      transform: translateY(0);
      opacity: 1;
    }
    .gallery-item-indicators {
      position: absolute;
      top: 1rem;
      right: 1rem;
      display: flex;
      gap: 0.5rem;
      z-index: 5;
      pointer-events: none;
    }
    .media-indicator {
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(10px);
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.3rem;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
    }
    .gallery-item-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
      line-height: 1.3;
    }
    .gallery-item-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      opacity: 0.95;
    }
    .likes-container {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(255, 255, 255, 0.15);
      padding: 0.3rem 0.8rem;
      border-radius: 15px;
      backdrop-filter: blur(5px);
    }
    .location-text {
      font-size: 0.75rem;
      opacity: 0.9;
      max-width: 150px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--text-color);
    }
    .empty-state i {
      font-size: 3rem;
      margin-bottom: 1rem;
      color: var(--brown);
    }
    .empty-state-text {
      font-size: 1.125rem;
      font-weight: 500;
    }
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.95);
      z-index: 2000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
      backdrop-filter: blur(20px);
    }
    .modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }
    .modal-content {
      position: relative;
      width: 100vw;
      height: 100vh;
      display: flex;
      flex-direction: column;
      background: transparent;
      border-radius: 0;
      overflow: hidden;
      transform: scale(0.95);
      transition: transform 0.3s ease;
    }
    .modal-overlay.active .modal-content {
      transform: scale(1);
    }
    .modal-close {
      position: fixed;
      top: 2rem;
      right: 2rem;
      width: 50px;
      height: 50px;
      border: none;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: white;
      font-size: 1.2rem;
      z-index: 2001;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }
    .modal-close:hover {
      background: rgba(139, 94, 60, 0.8);
      border-color: rgba(255, 255, 255, 0.4);
      transform: scale(1.1);
    }
    .modal-nav {
      position: fixed;
      top: 50%;
      transform: translateY(-50%);
      width: 60px;
      height: 60px;
      border: none;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: white;
      font-size: 1.5rem;
      z-index: 2001;
      transition: all 0.3s ease;
      border: 2px solid rgba(255, 255, 255, 0.2);
      opacity: 0.8;
    }
    .modal-nav:hover {
      background: rgba(139, 94, 60, 0.8);
      border-color: rgba(255, 255, 255, 0.4);
      transform: translateY(-50%) scale(1.1);
      opacity: 1;
    }
    .modal-nav.prev {
      left: 2rem;
    }
    .modal-nav.next {
      right: 2rem;
    }
    .modal-image-container {
      flex: 1;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      background: transparent;
      min-height: 0;
    }
    .modal-image {
      max-width: calc(100vw - 2rem);
      max-height: calc(100vh - 200px);
      width: auto;
      height: auto;
      object-fit: contain;
      border-radius: 8px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
      transition: all 0.3s ease;
    }
    .modal-video {
      max-width: calc(100vw - 2rem);
      max-height: calc(100vh - 200px);
      width: 100%;
      height: auto;
      border-radius: 8px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }
    .play-overlay {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      font-size: 4rem;
      opacity: 0.9;
      background: rgba(0, 0, 0, 0.5);
      border-radius: 50%;
      width: 80px;
      height: 80px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-info {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
      padding: 3rem 4rem 2rem;
      color: white;
      z-index: 2001;
    }
    .modal-title {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: white;
    }
    .modal-description {
      color: rgba(255, 255, 255, 0.8);
      line-height: 1.6;
      margin-bottom: 1.5rem;
      font-size: 1.1rem;
    }
    .modal-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-like-button {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 0.5rem 1rem;
      border-radius: 25px;
      cursor: pointer;
      font-size: 1rem;
      color: white;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
    }
    .modal-like-button:hover {
      background: rgba(139, 94, 60, 0.3);
      border-color: rgba(139, 94, 60, 0.5);
    }
    .modal-date-location {
      text-align: right;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.9rem;
    }
    @media (max-width: 1200px) {
      .gallery-grid.masonry-mode {
        columns: 3;
      }
    }
    @media (max-width: 1024px) {
      .container-wide {
        margin: auto;
      }
      .controls-container {
        flex-direction: column;
        align-items: stretch;
      }
      .view-controls {
        align-self: flex-start;
      }
      .categories-container {
        width: 100%;
      }
      .gallery-grid.masonry-mode {
        columns: 3;
        column-gap: 0.8rem;
      }
    }
    @media (max-width: 768px) {
      .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      }
      .gallery-grid.masonry-mode {
        columns: 2;
        column-gap: 1rem;
      }
      .gallery-item.masonry-item:nth-child(7n+1) { min-height: 250px; }
      .gallery-item.masonry-item:nth-child(7n+2) { min-height: 160px; }
      .gallery-item.masonry-item:nth-child(7n+3) { min-height: 280px; }
      .gallery-item.masonry-item:nth-child(7n+4) { min-height: 200px; }
      .gallery-item.masonry-item:nth-child(7n+5) { min-height: 220px; }
      .gallery-item.masonry-item:nth-child(7n+6) { min-height: 180px; }
      .gallery-item.masonry-item:nth-child(7n+7) { min-height: 260px; }
      .gallery-item.masonry-item:nth-child(13n+1) {
        min-height: 300px;
      }
      .gallery-item.grid-item {
        height: 220px;
      }
      .modal-content {
        margin: 1rem;
        max-height: 85vh;
      }
      .modal-image-container {
        height: 300px;
      }
      .modal-close {
        top: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
      }
      .modal-nav {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
      }
      .modal-nav.prev {
        left: 1rem;
      }
      .modal-nav.next {
        right: 1rem;
      }
      .modal-image-container {
        padding: 0.5rem;
      }
      .modal-image, .modal-video {
        max-width: calc(100vw - 1rem);
        max-height: calc(100vh - 180px);
      }
      .modal-info {
        padding: 2rem 2rem 1.5rem;
      }
      .modal-title {
        font-size: 1.5rem;
      }
      .modal-description {
        font-size: 1rem;
      }
      .modal-meta {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
      }
      .modal-date-location {
        text-align: left;
        width: 100%;
      }
    }
    @media (max-width: 480px) {
      .gallery-grid {
        grid-template-columns: 1fr;
      }
      .gallery-grid.masonry-mode {
        columns: 1;
        column-gap: 0;
      }
      .gallery-item.masonry-item:nth-child(n) { min-height: 200px; }
      .gallery-item.masonry-item:nth-child(3n+1) { min-height: 240px; }
      .gallery-item.masonry-item:nth-child(5n+1) { min-height: 180px; }
      .gallery-item.masonry-item:nth-child(7n+1) { min-height: 220px; }
      .gallery-item.masonry-item:nth-child(13n+1) { min-height: 260px; }
      .search-header {
        padding: 1rem;
      }
      .controls-container {
        padding: 1rem;
      }
      .gallery-content {
        padding: 1rem;
      }
      .gallery-item-overlay {
        padding: 1.5rem 1rem 1rem;
      }
      .gallery-item-title {
        font-size: 1.1rem;
      }
      .modal-close {
        top: 0.5rem;
        right: 0.5rem;
        width: 40px;
        height: 40px;
        font-size: 1rem;
      }
      .modal-nav {
        width: 45px;
        height: 45px;
        font-size: 1.2rem;
      }
      .modal-nav.prev {
        left: 0.5rem;
      }
      .modal-nav.next {
        right: 0.5rem;
      }
      .modal-image-container {
        padding: 0.25rem;
      }
      .modal-image, .modal-video {
        max-width: calc(100vw - 0.5rem);
        max-height: calc(100vh - 160px);
      }
      .modal-info {
        padding: 1.5rem 1rem 1rem;
      }
      .modal-title {
        font-size: 1.3rem;
        margin-bottom: 0.3rem;
      }
      .modal-description {
        font-size: 0.9rem;
        margin-bottom: 1rem;
      }
    }
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .gallery-item {
      animation: fadeInUp 0.6s ease forwards;
    }
    .gallery-item:nth-child(odd) {
      animation-delay: 0.1s;
    }
    .gallery-item:nth-child(even) {
      animation-delay: 0.2s;
    }
    .gallery-item:nth-child(4n) {
      animation-delay: 0.3s;
    }
  </style>
</head>
<body>
  <header>
    <div class="container-wide">
      <div class="header-wrapper">
        <div class="logo-container">
          <div class="logo-circle"></div>
          <div class="logo">
            <img src="<?php echo $logo_actual; ?>" alt="Rancho Las Trojes Logo">
          </div>
        </div>
         <div class="header-group">
          <div class="header-actions">
            <a href="#" id="themeToggle" class="btn-icon" aria-label="Cambiar tema">
              <i class="fa-solid fa-moon"></i>
            </a>
            <a href="/contacto.php" class="btn-text">
              <i class="fa-solid fa-phone"></i><span>Contáctanos</span>
            </a>
          </div>
          <div class="nav_hamburger" id="hamburgerButton">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
          </div>
        </div>
      </div>
    </div>
  </header>
  
  <div class="nav-menu-overlay" id="navMenuOverlay">
    <div class="nav-menu-content">
      <ul class="nav-menu-list">
        <li class="nav-menu-item"><a href="#" class="nav-menu-link">Inicio</a></li>
        <li class="nav-menu-item"><a href="#" class="nav-menu-link">Nosotros</a></li>
        <li class="nav-menu-item"><a href="/galeria.php" class="nav-menu-link">Galería</a></li>
        <li class="nav-menu-item"><a href="#" class="nav-menu-link">Precios</a></li>
        <li class="nav-menu-item"><a href="#" class="nav-menu-link">Tienda</a></li>
        <li class="nav-menu-item"><a href="#" class="nav-menu-link">Blog</a></li>
        <li class="nav-menu-item"><a href="/contacto.php" class="nav-menu-link">Contacto</a></li>
      </ul>
    </div>
  </div>

  <section class="page-header-start container-wide">
    <img src="assets/images/42c08f60-d5b7-4aec-87cd-632c3a0ed6a6.jpeg" alt="Fondo Galería">
    <div class="page-header-overlay">
      <h1 class="page-header-title">Galería</h1>
      <p class="page-header-subtitle">Inicio / Galería</p>
    </div>
  </section>
  
  <section class="gallery-section container-wide">
    <div class="search-header">
      <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Buscar fotos y videos..." id="searchInput">
        <i class="fas fa-times search-clear" id="searchClear" style="display: none;"></i>
      </div>
    </div>
    
    <div class="controls-container">
      <div class="view-controls">
        <button class="view-button" id="masonryButton" data-view="masonry"><i class="fas fa-th"></i></button>
        <button class="view-button active" id="gridButton" data-view="grid"><i class="fas fa-th-large"></i></button>
      </div>
      <div class="categories-container" id="categoriesContainer">
        <div class="category-tab active" data-category="todo">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Cargando...</span>
        </div>
      </div>
    </div>
    
    <div class="gallery-content">
      <div class="gallery-grid grid-mode" id="galleryGrid">
        <!-- Los medios se generarán aquí por JavaScript -->
      </div>
    </div>
  </section>

  <div class="modal-overlay" id="modalOverlay">
    <div class="modal-content">
      <button class="modal-close" id="modalClose"><i class="fas fa-times"></i></button>
      <button class="modal-nav prev" id="modalPrev"><i class="fas fa-chevron-left"></i></button>
      <button class="modal-nav next" id="modalNext"><i class="fas fa-chevron-right"></i></button>
      
      <div class="modal-image-container">
        <img class="modal-image" id="modalImage" src="" alt="" style="display: none;">
        <video class="modal-video" id="modalVideo" controls style="display: none;">
          <source id="modalVideoSource" src="" type="video/mp4">
          Tu navegador no soporta el elemento de video.
        </video>
      </div>
      
      <div class="modal-info">
        <h2 class="modal-title" id="modalTitle"></h2>
        <p class="modal-description" id="modalDescription"></p>
        <div class="modal-meta">
          <button class="modal-like-button" id="modalLikeButton">
            <i class="fas fa-heart"></i>
            <span id="modalLikes">0</span>
          </button>
          <div class="modal-date-location">
            <div id="modalDate"></div>
            <div id="modalLocation"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER COMPLETO ORIGINAL -->
  <footer class="footer-container container-wide">
    <div class="container">
      <div class="footer-menu-wrapper">
        <nav class="footer-menu">
          <a href="#">Inicio</a>
          <a href="#">Nosotros</a>
          <a href="/galeria.php">Galería</a>
          <a href="#">Precios</a>
          <a href="#">Tienda</a>
          <a href="#">Blog</a>
          <a href="/contacto.php">Contacto</a>
        </nav>
      </div>
      <div class="footer-content">
        <div class="footer-column">
          <div class="footer-contact-section">
            <h3 class="footer-contact-title">Contáctanos</h3>
            <p class="footer-contact-description fade-up-animation">
              Estamos disponibles para resolver tus dudas y brindarte más información.
            </p>
            <div class="footer-contact-persons">
              <div class="footer-contact-person">
                <p>Ricardo Torres</p>
                <div class="footer-contact-methods">
                  <a href="tel:+524432020019" class="footer-social-icon" aria-label="Llamar a Ricardo">
                    <i class="fas fa-phone"></i>
                  </a>
                  <a href="https://wa.me/+524432020019" target="_blank" rel="noopener noreferrer" class="footer-social-icon" aria-label="Enviar WhatsApp a Ricardo">
                    <i class="fa-brands fa-whatsapp"></i>
                  </a>
                </div>
              </div>
              <div class="footer-contact-person">
                <p>Juan Luis Peña</p>
                <div class="footer-contact-methods">
                  <a href="tel:+524433953204" class="footer-social-icon" aria-label="Llamar a Juan Luis">
                    <i class="fas fa-phone"></i>
                  </a>
                  <a href="https://wa.me/+524433953204" target="_blank" rel="noopener noreferrer" class="footer-social-icon" aria-label="Enviar WhatsApp a Juan Luis">
                    <i class="fa-brands fa-whatsapp"></i>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="footer-column">
          <div class="footer-logo-wrapper">
            <div class="footer-logo-center">
              <img src="<?php echo $logo_actual; ?>" alt="Rancho Las Trojes Logo">
            </div>
            <div class="footer-logo-address">
              <p><i class="fas fa-map-marker-alt"></i>Estamos ubicados en Morelia, Michoacán</p>
            </div>
          </div>
        </div>
        <div class="footer-column">
          <div class="footer-social">
            <h3 class="footer-social-title">Síguenos</h3>
            <p class="footer-social-description fade-up-animation">
              Síguenos en nuestras redes para enterarte de promociones y mucho más.
            </p>
            <div class="footer-social-icons">
              <a href="#" aria-label="Facebook" class="footer-social-icon">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="#" aria-label="Instagram" class="footer-social-icon">
                <i class="fab fa-instagram"></i>
              </a>
              <a href="#" aria-label="Twitter" class="footer-social-icon">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="#" aria-label="LinkedIn" class="footer-social-icon">
                <i class="fab fa-linkedin-in"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
      <hr class="footer-divider">
      <div class="footer-bottom">
        <div>
          &copy; <span id="current-year">2025</span> Rancho Las Trojes. Todos los derechos reservados.
        </div>
        <div>
          Desarrollado por <a href="https://wa.me/+522215682994" target="_blank" rel="noopener noreferrer"><strong>Xhunco</strong></a>
        </div>
      </div>
    </div>
  </footer>
  
  <script>
    // --- VARIABLES GLOBALES ---
    let mediaItems = [];
    let allCategories = [];
    let currentView = 'grid';
    let currentCategory = 'todo';
    let currentSearch = '';
    let selectedMedia = null;

    // --- REFERENCIAS AL DOM ---
    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');
    const masonryButton = document.getElementById('masonryButton');
    const gridButton = document.getElementById('gridButton');
    const galleryGrid = document.getElementById('galleryGrid');
    const categoriesContainer = document.getElementById('categoriesContainer');
    
    const modalOverlay = document.getElementById('modalOverlay');
    const modalClose = document.getElementById('modalClose');
    const modalPrev = document.getElementById('modalPrev');
    const modalNext = document.getElementById('modalNext');
    const modalImage = document.getElementById('modalImage');
    const modalVideo = document.getElementById('modalVideo');
    const modalVideoSource = document.getElementById('modalVideoSource');
    const modalTitle = document.getElementById('modalTitle');
    const modalDescription = document.getElementById('modalDescription');
    const modalLikeButton = document.getElementById('modalLikeButton');
    const modalLikes = document.getElementById('modalLikes');
    const modalDate = document.getElementById('modalDate');
    const modalLocation = document.getElementById('modalLocation');

    // --- INICIALIZACIÓN ---
    document.addEventListener('DOMContentLoaded', function() {
      setupUI(); 
      fetchDataAndRender(); 
      setupGalleryListeners();
    });

    // --- LÓGICA UI ---
    function setupUI() {
      const yearSpan = document.getElementById('current-year');
      if (yearSpan) yearSpan.textContent = new Date().getFullYear();

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
        if (localStorage.getItem('darkMode') === 'enabled') {
          enableDarkMode();
        }
        themeToggle.addEventListener('click', function(e) {
          e.preventDefault();
          if (darkModeStylesheet.disabled) {
            enableDarkMode();
          } else {
            disableDarkMode();
          }
        });
      }

      const originalHamburger = document.getElementById('hamburgerButton');
      const navMenuOverlay = document.getElementById('navMenuOverlay');
      if (originalHamburger && navMenuOverlay) {
        let fixedHamburger = null;
        let isFixed = false;
        function createFixedClone() {
          if (fixedHamburger) return;
          fixedHamburger = originalHamburger.cloneNode(true);
          fixedHamburger.id = 'fixedHamburgerButton';
          fixedHamburger.classList.add('fixed');
          fixedHamburger.style.display = 'none';
          document.body.appendChild(fixedHamburger);
          fixedHamburger.addEventListener('click', toggleMenu);
        }
        function updateFixedHamburgerPosition() {
          if (!fixedHamburger) return;
          const originalRect = originalHamburger.getBoundingClientRect();
          const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
          const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
          fixedHamburger.style.top = (originalRect.top + scrollTop) + 'px';
          fixedHamburger.style.left = (originalRect.left + scrollLeft) + 'px';
        }
        function toggleFixedHamburger() {
          if (!fixedHamburger) return;
          const headerRect = document.querySelector('header')?.getBoundingClientRect();
          if (headerRect && headerRect.bottom < 80) {
            if (!isFixed) {
              updateFixedHamburgerPosition();
              fixedHamburger.style.display = 'flex';
              originalHamburger.style.visibility = 'hidden';
              if (originalHamburger.classList.contains('active')) {
                fixedHamburger.classList.add('active');
              }
              isFixed = true;
            }
          } else {
            if (isFixed) {
              fixedHamburger.style.display = 'none';
              originalHamburger.style.visibility = 'visible';
              isFixed = false;
            }
          }
        }
        function toggleMenu() {
          originalHamburger.classList.toggle('active');
          if (fixedHamburger && isFixed) {
            fixedHamburger.classList.toggle('active');
          }
          navMenuOverlay.classList.toggle('active');
        }
        createFixedClone();
        originalHamburger.addEventListener('click', toggleMenu);
        window.addEventListener('scroll', toggleFixedHamburger);
        window.addEventListener('resize', updateFixedHamburgerPosition);
        document.querySelectorAll('.nav-menu-link').forEach(link => {
          link.addEventListener('click', () => {
            originalHamburger.classList.remove('active');
            if (fixedHamburger && isFixed) fixedHamburger.classList.remove('active');
            navMenuOverlay.classList.remove('active');
          });
        });
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && navMenuOverlay.classList.contains('active')) {
            originalHamburger.classList.remove('active');
            if (fixedHamburger && isFixed) fixedHamburger.classList.remove('active');
            navMenuOverlay.classList.remove('active');
          }
        });
      }
    }

    // --- API Y DATOS ---
    async function fetchDataAndRender() {
      try {
        const response = await fetch('api/galeria.php');
        if (!response.ok) throw new Error('Error HTTP');
        const data = await response.json();

        mediaItems = data.medios || [];
        allCategories = data.categorias || [];

        renderCategories();
        renderGallery();

      } catch (error) {
        console.error("Error cargando galería:", error);
        galleryGrid.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Error al cargar la galería. Intenta recargar.</p></div>`;
      }
    }

    function renderCategories() {
      let html = `<div class="category-tab active" data-category="todo"><i class="fas fa-th"></i><span>Todo</span><div class="category-badge">${mediaItems.length}</div></div>`;
      
      allCategories.forEach(cat => {
        // USAMOS EL SLUG QUE VIENE DEL BACKEND (Modelo Categoria.php)
        const key = cat.slug; 
        html += `
          <div class="category-tab" data-category="${key}">
            <i class="${cat.icono || 'fas fa-folder'}"></i>
            <span>${cat.nombre}</span>
            <div class="category-badge" id="badge-${key}">0</div>
          </div>
        `;
      });
      categoriesContainer.innerHTML = html;
      
      // Calcular contadores
      updateCategoryCounts();
    }

    function updateCategoryCounts() {
        allCategories.forEach(cat => {
            const key = cat.slug;
            // Comparamos el slug de la categoría con la 'category' del medio (que también es slug desde backend)
            const count = mediaItems.filter(m => m.category === key).length;
            const badge = document.getElementById(`badge-${key}`);
            if(badge) badge.textContent = count;
        });
    }

    function renderGallery() {
      const filtered = filterMedia();
      
      if (filtered.length === 0) {
        galleryGrid.innerHTML = `<div class="empty-state"><i class="fas fa-search"></i><p>No hay resultados.</p></div>`;
        return;
      }

      const html = filtered.map(item => {
        const itemClass = `gallery-item ${currentView}-item`;
        
        let mediaHtml = '';
        if (item.type === 'video') {
            const posterAttr = item.thumbnail ? `poster="${item.thumbnail}"` : '';
            const srcUrl = item.thumbnail ? item.url : `${item.url}#t=0.5`;
            
            mediaHtml = `
                <video 
                    src="${srcUrl}" 
                    ${posterAttr}
                    class="gallery-item-image" 
                    muted 
                    playsinline 
                    loop
                    preload="metadata"
                    onmouseover="this.play()" 
                    onmouseout="this.pause();this.currentTime=0;"
                    style="background-color: #000;"
                ></video>
            `;
        } else {
            mediaHtml = `<img src="${item.url}" alt="${item.title}" class="gallery-item-image" loading="lazy">`;
        }

        return `
          <div class="${itemClass}" data-id="${item.id}" onclick="openModal('${item.id}')">
            ${mediaHtml}
            
            <div class="gallery-item-indicators">
              ${item.type === 'video' ? '<div class="media-indicator"><i class="fas fa-play"></i></div>' : ''}
            </div>
            
            <div class="gallery-item-overlay">
              <h3 class="gallery-item-title">${item.title}</h3>
              <div class="gallery-item-meta">
                <div class="likes-container">
                  <i class="fas fa-heart" style="color: ${item.isLiked ? '#ff3040' : '#fff'}"></i>
                  <span>${item.likes}</span>
                </div>
                ${item.location ? `<div class="location-text"><i class="fas fa-map-marker-alt"></i> ${item.location}</div>` : ''}
              </div>
            </div>
          </div>
        `;
      }).join('');
      
      galleryGrid.innerHTML = html;
    }

    function filterMedia() {
      // FILTRADO POR SLUG EXACTO
      let list = currentCategory === 'todo' ? mediaItems : mediaItems.filter(m => m.category === currentCategory);
      if(currentSearch) {
        const s = currentSearch.toLowerCase();
        list = list.filter(m => m.title.toLowerCase().includes(s) || m.description.toLowerCase().includes(s));
      }
      return list;
    }

    // --- EVENTOS ---
    function setupGalleryListeners() {
      searchInput.addEventListener('input', (e) => {
        currentSearch = e.target.value;
        searchClear.style.display = currentSearch ? 'block' : 'none';
        renderGallery();
      });
      
      searchClear.addEventListener('click', () => {
        searchInput.value = ''; currentSearch = ''; 
        searchClear.style.display = 'none'; renderGallery();
      });

      masonryButton.addEventListener('click', () => { switchView('masonry'); });
      gridButton.addEventListener('click', () => { switchView('grid'); });

      categoriesContainer.addEventListener('click', (e) => {
        const tab = e.target.closest('.category-tab');
        if(!tab) return;
        document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentCategory = tab.dataset.category;
        renderGallery();
      });

      modalClose.addEventListener('click', closeModal);
      modalPrev.addEventListener('click', () => navigateModal(-1));
      modalNext.addEventListener('click', () => navigateModal(1));
      modalOverlay.addEventListener('click', (e) => { if(e.target === modalOverlay) closeModal(); });
      modalLikeButton.addEventListener('click', toggleLike);
      
      document.addEventListener('keydown', (e) => {
        if(!modalOverlay.classList.contains('active')) return;
        if(e.key === 'Escape') closeModal();
        if(e.key === 'ArrowLeft') navigateModal(-1);
        if(e.key === 'ArrowRight') navigateModal(1);
      });
    }

    function switchView(view) {
        currentView = view;
        document.querySelectorAll('.view-button').forEach(b => b.classList.remove('active'));
        document.getElementById(view + 'Button').classList.add('active');
        galleryGrid.className = `gallery-grid ${view}-mode`;
        renderGallery();
    }

    // --- MODAL ---
    function openModal(id) {
      selectedMedia = mediaItems.find(m => m.id == id);
      if(!selectedMedia) return;

      modalTitle.textContent = selectedMedia.title;
      modalDescription.textContent = selectedMedia.description;
      modalLikes.textContent = selectedMedia.likes;
      modalDate.textContent = formatDateToSpanish(selectedMedia.date);
      modalLocation.textContent = selectedMedia.location || '';
      modalLikeButton.querySelector('i').style.color = selectedMedia.isLiked ? '#ff3040' : '#fff';

      if(selectedMedia.type === 'video') {
        modalImage.style.display = 'none';
        modalVideo.style.display = 'block';
        const cleanUrl = selectedMedia.url.split('#')[0];
        modalVideoSource.src = cleanUrl;
        if (selectedMedia.thumbnail) modalVideo.poster = selectedMedia.thumbnail;
        modalVideo.load();
        modalVideo.play();
      } else {
        modalVideo.style.display = 'none';
        modalVideo.pause();
        modalImage.style.display = 'block';
        modalImage.src = selectedMedia.url;
      }
      
      modalOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      modalOverlay.classList.remove('active');
      document.body.style.overflow = '';
      modalVideo.pause();
    }

    function navigateModal(dir) {
        if(!selectedMedia) return;
        const list = filterMedia();
        const idx = list.findIndex(m => m.id == selectedMedia.id);
        let newIdx = idx + dir;
        if(newIdx < 0) newIdx = list.length - 1;
        if(newIdx >= list.length) newIdx = 0;
        openModal(list[newIdx].id);
    }

    function formatDateToSpanish(dateString) {
      if (!dateString) return '';
      const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
      const date = new Date(dateString);
      return `${date.getUTCDate()} de ${months[date.getUTCMonth()]} de ${date.getUTCFullYear()}`;
    }

    async function toggleLike() {
      if(!selectedMedia) return;
      
      selectedMedia.isLiked = !selectedMedia.isLiked;
      selectedMedia.likes += selectedMedia.isLiked ? 1 : -1;
      
      modalLikes.textContent = selectedMedia.likes;
      modalLikeButton.querySelector('i').style.color = selectedMedia.isLiked ? '#ff3040' : '#fff';
      
      renderGallery(); 

      try {
        await fetch('api/actualizar_likes.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id: selectedMedia.id,
                likes: selectedMedia.likes,
                isLiked: selectedMedia.isLiked
            })
        });
      } catch (e) {
        console.error("Error actualizando like", e);
      }
    }
  </script>
</body>
</html>