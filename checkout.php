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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Rancho Las Trojes</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link id="darkModeStylesheet" rel="stylesheet" href="assets/css/dark-mode.css" disabled>

    <style>
        /* Estilos específicos del Layout de Checkout */
        .checkout-section {
            background: var(--white);
            padding: 3rem 0;
            margin-top: 2rem;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 3rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Títulos */
        .checkout-form-col h2,
        .order-summary-col h2 {
            font-size: 2.5em;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--black-blue);
        }

        @media (max-width: 512px) {
            .checkout-form-col h2,
            .order-summary-col h2 {
                font-size: 1.75em;
            }
        }

        /* Lista Carrito en Resumen */
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--divider);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--black-blue);
            font-size: 1rem;
        }

        .item-info span {
            font-size: 0.85rem;
            color: var(--text-color);
        }

        .item-price {
            font-weight: 600;
            color: var(--brown);
        }

        .btn-remove {
            color: #ef4444;
            background: none;
            border: none;
            cursor: pointer;
            margin-left: 1rem;
            font-size: 1rem;
            transition: transform 0.2s;
        }

        .btn-remove:hover {
            transform: scale(1.1);
        }

        /* Alertas Informativas */
        .alert-info {
            background: rgba(33, 150, 243, 0.1);
            color: #0d47a1;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.5;
            border-left: 4px solid #2196f3;
        }

        /* Caja de Resumen de Costos */
        .order-summary {
            background: var(--off-white-light);
            padding: 1.5rem;
            border-radius: 1rem;
            margin-top: 2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--text-color);
        }

        .summary-row.total {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--black-blue);
            border-top: 1px solid var(--divider);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        /* Botón Checkout */
        .btn-checkout {
            width: 100%;
            background: var(--brown);
            color: var(--white);
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-checkout:hover {
            background: var(--black-blue);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-checkout:disabled {
            background: var(--divider);
            cursor: not-allowed;
            transform: none;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

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
                            <input type="text" id="nombreCliente" class="form-control" required placeholder="Tu nombre completo">
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
                                <label class="form-label">Dirección Completa</label>
                                <input type="text" id="direccionInput" class="form-control" placeholder="Calle, Número, Col, CP, Ciudad">
                            </div>
                        </div>

                        <div id="airportSection" class="hidden">
                            <div class="alert-info">
                                <i class="fas fa-feather"></i> Tienes aves en tu carrito. El envío se realiza al aeropuerto o terminal más cercano a tu estado. Nos pondremos en contacto para coordinar la entrega.
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

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Inicializar Checkout al cargar la página
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof renderCheckout === 'function') {
                renderCheckout();
            } else {
                console.error("Error: main.js no se cargó correctamente o falta la función renderCheckout.");
            }
        });
    </script>
</body>
</html>