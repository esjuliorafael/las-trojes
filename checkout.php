<?php
include_once 'config/database.php';
include_once 'models/Logo.php';
$db = (new Database())->getConnection();
$logo_actual = (new Logo($db))->obtenerLogoActivo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Rancho Las Trojes</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    
    <style>
        .checkout-section { background: var(--white); border-radius: 1.25rem; padding: 2rem; margin-top: 2rem; min-height: 500px; }
        .checkout-section h2 { font-size: 2.5em; font-weight: 600; margin-bottom: 1rem; }
        .checkout-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 3rem; }
        @media (max-width: 900px) { .checkout-grid { grid-template-columns: 1fr; } }
        
        .section-title { font-family: 'Lora', serif; font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--black-blue); border-bottom: 1px solid var(--divider); padding-bottom: 0.5rem; }
        
        /* Lista Carrito */
        .cart-item { display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid var(--off-white-light); }
        .cart-item:last-child { border-bottom: none; }
        .item-info h4 { margin: 0 0 0.25rem 0; color: var(--black-blue); }
        .item-info span { font-size: 0.9rem; color: var(--text-color); }
        .item-price { font-weight: 600; color: var(--brown); }
        .btn-remove { color: #ef4444; background: none; border: none; cursor: pointer; margin-left: 1rem; font-size: 1.1rem; }

        /* Formulario Layout (Los estilos de los inputs ahora vienen de forms.css) */
        .checkout-form .form-group { margin-bottom: 1.25rem; }
        .checkout-form label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-color); }
        
        /* Alertas Informativas */
        .alert-info { background: #e3f2fd; color: #0d47a1; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.9rem; line-height: 1.5; border-left: 4px solid #2196f3; }

        /* Resumen Costos */
        .order-summary { background: var(--off-white-light); padding: 1.5rem; border-radius: 1rem; margin-top: 2rem; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: var(--text-color); }
        .summary-row.total { font-size: 1.25rem; font-weight: 700; color: var(--black-blue); border-top: 1px solid var(--divider); padding-top: 1rem; margin-top: 1rem; }
        
        /* Botón Checkout */
        .btn-checkout { width: 100%; background: var(--brown); color: white; padding: 1rem; border: none; border-radius: 0.75rem; font-size: 1.1rem; font-weight: 600; cursor: pointer; margin-top: 1.5rem; transition: background 0.3s; }
        .btn-checkout:hover { background: var(--black-blue); }
        
        .hidden { display: none; }
    </style>
</head>
<body>

    <header>
        <div class="container-wide">
            <div class="header-wrapper">
                <div class="logo-container">
                    <div class="logo-circle"></div>
                    <div class="logo"><img src="<?php echo $logo_actual; ?>" alt="Logo"></div>
                </div>
                <div class="header-group">
                    <a href="tienda.php" class="btn-text"><i class="fas fa-arrow-left"></i><span>Seguir Comprando</span></a>
                </div>
            </div>
        </div>
    </header>

    <section class="checkout-section container-wide">
        <div class="container">
            <div class="checkout-grid">
                
                <div class="checkout-form-col">
                    <h2 class="animated-text">
                        <span class="word">Datos</span>
                        <span class="word lora-italic">de</span>
                        <span class="word lora-italic">Envío</span>
                    </h2>
                    
                    <form id="checkoutForm" class="fade-up-animation" onsubmit="event.preventDefault(); processOrder();">
                        <div class="form-group">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" id="nombreCliente" class="form-control" required placeholder="Tu nombre">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono (WhatsApp)</label>
                            <input type="tel" id="telCliente" class="form-control" required placeholder="Para enviarte el seguimiento">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Estado de Destino</label>
                            <select id="estadoSelect" class="form-control select" required onchange="calculateShipping()">
                                <option value="">Selecciona tu estado...</option>
                                </select>
                        </div>

                        <div id="addressSection" class="hidden">
                            <div class="alert-info">
                                <i class="fas fa-box"></i> Tienes artículos físicos en tu carrito. Necesitamos tu dirección completa.
                            </div>
                            <div class="form-group">
                                <label>Dirección Completa (Calle, Número, Col, CP, Ciudad)</label>
                                <input type="text" id="direccionInput" class="form-control" placeholder="Ej: Av. Madero 123, Col. Centro, CP 58000, Morelia">
                            </div>
                        </div>

                        <div id="airportSection" class="hidden">
                            <div class="alert-info">
                                <i class="fas fa-feather"></i> Tienes aves en tu carrito. El envío se realiza al aeropuerto o terminal más cercano a tu estado. Nos pondremos en contacto para coordinar.
                            </div>
                        </div>

                        <button type="submit" class="btn-checkout" id="btnPlaceOrder">
                            <i class="fab fa-whatsapp"></i> Finalizar Pedido
                        </button>
                    </form>
                </div>

                <div class="order-summary-col">
                    <h2 class="animated-text">
                        <span class="word">Resumen</span>
                        <span class="word lora-italic">del</span>
                        <span class="word lora-italic">Pedido</span>
                    </h2>
                    <div id="cartItemsContainer" class="fade-up-animation">
                        </div>

                    <div class="order-summary fade-up-animation">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="summarySubtotal">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Envío Estimado</span>
                            <span id="summaryShipping">Calculando...</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="summaryTotal">$0.00</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <footer class="footer-container container-wide">
        <div class="container">
            <div class="footer-menu-wrapper">
            <nav class="footer-menu">
                <a href="#">Inicio</a>
                <a href="#">Nosotros</a>
                <a href="#">Galería</a>
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
    <script src="assets/js/main.js"></script>
    <script>
        // Inicializar Checkout al cargar
        document.addEventListener('DOMContentLoaded', () => {
            renderCheckout();
        });
    </script>
</body>
</html>