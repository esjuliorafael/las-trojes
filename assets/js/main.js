/* =============================================
   MAIN JS - Rancho Las Trojes
   Carrito & Lógica de Checkout + Mini Cart + FAB
============================================= */

let cart = JSON.parse(localStorage.getItem('rlt_cart')) || [];
const STATES_MX = [
    "Aguascalientes", "Baja California", "Baja California Sur", "Campeche", "Coahuila", "Colima", 
    "Chiapas", "Chihuahua", "Ciudad de México", "Durango", "Guanajuato", "Guerrero", "Hidalgo", 
    "Jalisco", "México", "Michoacán", "Morelos", "Nayarit", "Nuevo León", "Oaxaca", "Puebla", 
    "Querétaro", "Quintana Roo", "San Luis Potosí", "Sinaloa", "Sonora", "Tabasco", "Tamaulipas", 
    "Tlaxcala", "Veracruz", "Yucatán", "Zacatecas"
];

/* --- ACCIONES DE CARRITO --- */

function addToCart(id, type, name, price) {
    const existingIndex = cart.findIndex(i => i.id === id);

    if (existingIndex > -1) {
        if (type === 'ave') {
            alert("Esta ave ya está en tu carrito (Stock único).");
            return;
        } else {
            cart[existingIndex].cantidad++;
        }
    } else {
        cart.push({ id, tipo: type, nombre: name, precio: parseFloat(price), cantidad: 1 });
    }
    
    saveCart();
    updateCartUI();
    
    // Abrir Mini Cart automáticamente
    renderMiniCartContents();
    openMiniCart();
}

function buyNow(id, type, name, price) {
    const existingIndex = cart.findIndex(i => i.id === id);

    if (existingIndex > -1) {
        if (type === 'ave') {
            // Ya está
        }
    } else {
        cart.push({ id, tipo: type, nombre: name, precio: parseFloat(price), cantidad: 1 });
    }
    
    saveCart();
    updateCartUI();
    window.location.href = 'checkout.php';
}

function removeFromCart(index) {
    if(confirm("¿Eliminar este producto?")) {
        cart.splice(index, 1);
        saveCart();
        
        if (document.getElementById('cartItemsContainer')) {
            renderCheckout();
        } 
        
        updateCartUI();
        renderMiniCartContents();
    }
}

function saveCart() { localStorage.setItem('rlt_cart', JSON.stringify(cart)); }

function updateCartUI() {
    const totalItems = cart.reduce((sum, item) => sum + item.cantidad, 0);

    // 1. Badge del Header (Escritorio)
    const countBadge = document.getElementById('cart-count');
    if (countBadge) {
        countBadge.textContent = totalItems;
        countBadge.style.display = totalItems > 0 ? 'flex' : 'none';
    }

    // 2. Lógica del Botón Flotante Móvil (FAB)
    const mobileFab = document.getElementById('mobileFloatingCart');
    const mobileBadge = document.getElementById('mobile-cart-count');
    
    if (mobileFab && mobileBadge) {
        mobileBadge.textContent = totalItems;
        
        // Solo mostrar si hay items Y NO está oculto por el sheet
        if (totalItems > 0) {
            mobileFab.classList.add('visible'); 
        } else {
            mobileFab.classList.remove('visible');
        }
    }
}

/* --- MINI CART LOGIC (Dropdown / Bottom Sheet) --- */

function renderMiniCartContents() {
    const container = document.getElementById('miniCartItems');
    const totalEl = document.getElementById('miniCartTotal');
    
    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-msg"><i class="fas fa-shopping-basket" style="font-size: 2rem; color: #ddd; margin-bottom: 10px;"></i><br>Tu carrito está vacío</div>';
        if(totalEl) totalEl.textContent = '$0.00';
        return;
    }

    let html = '';
    let total = 0;

    cart.forEach((item, index) => {
        const itemTotal = item.precio * item.cantidad;
        total += itemTotal;

        html += `
            <div class="mini-cart-item">
                <div class="mini-item-info">
                    <h4>${item.nombre}</h4>
                    <div class="mini-item-details">
                        ${item.tipo === 'ave' ? 'Ave única' : 'Cant: ' + item.cantidad} 
                        <span class="mini-item-price">$${itemTotal.toFixed(2)}</span>
                    </div>
                </div>
                <button class="mini-remove-btn" onclick="removeFromCart(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    });

    container.innerHTML = html;
    if(totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
}

/**
 * ABRIR MINI CART
 * Agregamos lógica para ocultar el FAB cuando el sheet sube.
 */
function openMiniCart() {
    const miniCart = document.getElementById('miniCart');
    const overlay = document.getElementById('miniCartOverlay');
    const mobileFab = document.getElementById('mobileFloatingCart'); // Referencia al FAB

    if(miniCart) miniCart.classList.add('active');
    if(overlay) overlay.classList.add('active');

    // NUEVO: Ocultar el botón flotante para que no estorbe
    if(mobileFab) mobileFab.classList.add('hidden-by-sheet');
}

/**
 * CERRAR MINI CART
 * Agregamos lógica para volver a mostrar el FAB cuando el sheet baja.
 */
function closeMiniCartFn() {
    const miniCart = document.getElementById('miniCart');
    const overlay = document.getElementById('miniCartOverlay');
    const mobileFab = document.getElementById('mobileFloatingCart'); // Referencia al FAB

    if(miniCart) miniCart.classList.remove('active');
    if(overlay) overlay.classList.remove('active');

    // NUEVO: Volver a mostrar el botón flotante
    if(mobileFab) mobileFab.classList.remove('hidden-by-sheet');
}

// Inicialización de eventos
document.addEventListener('DOMContentLoaded', function() {
    updateCartUI();

    // Referencias
    const toggleBtn = document.getElementById('cartToggleBtn');
    const closeBtn = document.getElementById('closeMiniCart');
    const overlay = document.getElementById('miniCartOverlay');
    const miniCart = document.getElementById('miniCart');
    const mobileFab = document.getElementById('mobileFloatingCart');

    // Toggle al dar clic en el icono del header
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (miniCart && miniCart.classList.contains('active')) {
                closeMiniCartFn();
            } else {
                renderMiniCartContents();
                openMiniCart();
            }
        });
    }

    // Toggle al dar clic en el Botón Flotante Móvil
    if (mobileFab) {
        mobileFab.addEventListener('click', function() {
            renderMiniCartContents();
            openMiniCart();
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeMiniCartFn);
    if (overlay) overlay.addEventListener('click', closeMiniCartFn);

    // Cerrar al dar clic fuera (Solo Desktop)
    document.addEventListener('click', function(e) {
        if (window.innerWidth > 768 && miniCart && miniCart.classList.contains('active')) {
            if (!miniCart.contains(e.target) && !toggleBtn.contains(e.target)) {
                closeMiniCartFn();
            }
        }
    });
});


/* --- CHECKOUT PAGE LOGIC --- */

function renderCheckout() {
    const container = document.getElementById('cartItemsContainer');
    if (!container) return; 

    if (cart.length === 0) {
        container.innerHTML = "<p>Tu carrito está vacío.</p>";
        const btn = document.getElementById('btnPlaceOrder');
        if(btn) btn.disabled = true;
        
        if(document.getElementById('summarySubtotal')) document.getElementById('summarySubtotal').textContent = "$0.00";
        if(document.getElementById('summaryShipping')) document.getElementById('summaryShipping').textContent = "$0.00";
        if(document.getElementById('summaryTotal')) document.getElementById('summaryTotal').textContent = "$0.00";
        return;
    }

    let html = '';
    let subtotal = 0;
    let hasArt = false;
    let hasAve = false;

    cart.forEach((item, index) => {
        const totalItem = item.precio * item.cantidad;
        subtotal += totalItem;
        if(item.tipo === 'articulo') hasArt = true;
        if(item.tipo === 'ave') hasAve = true;

        html += `
        <div class="cart-item">
            <div class="item-info">
                <h4>${item.nombre}</h4>
                <span>${item.tipo === 'ave' ? 'Ave única' : 'Cant: '+item.cantidad}</span>
            </div>
            <div style="display:flex; align-items:center;">
                <div class="item-price">$${totalItem.toFixed(2)}</div>
                <button class="btn-remove" onclick="removeFromCart(${index})"><i class="fas fa-trash"></i></button>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    document.getElementById('summarySubtotal').textContent = `$${subtotal.toFixed(2)}`;

    const addressSec = document.getElementById('addressSection');
    const airportSec = document.getElementById('airportSection');
    const dirInput = document.getElementById('direccionInput');

    if (addressSec && dirInput) {
        if (hasArt) {
            addressSec.classList.remove('hidden');
            dirInput.setAttribute('required', 'true');
        } else {
            addressSec.classList.add('hidden');
            dirInput.removeAttribute('required');
        }
    }

    if (airportSec) {
        if (hasAve) {
            airportSec.classList.remove('hidden');
        } else {
            airportSec.classList.add('hidden');
        }
    }

    const select = document.getElementById('estadoSelect');
    if (select && select.options.length <= 1) {
        STATES_MX.forEach(edo => {
            const opt = document.createElement('option');
            opt.value = edo;
            opt.textContent = edo;
            select.appendChild(opt);
        });
    }

    calculateShipping();
}

async function calculateShipping() {
    const estadoSelect = document.getElementById('estadoSelect');
    if(!estadoSelect) return;
    
    const estado = estadoSelect.value;
    const btn = document.getElementById('btnPlaceOrder');
    
    if (!estado) {
        document.getElementById('summaryShipping').textContent = "Selecciona estado...";
        document.getElementById('summaryTotal').textContent = "---";
        if(btn) btn.disabled = true;
        return;
    }

    const hasArt = cart.some(i => i.tipo === 'articulo');
    const hasAve = cart.some(i => i.tipo === 'ave');

    try {
        const response = await fetch('api/checkout.php?accion=calcular_envio', {
            method: 'POST',
            body: JSON.stringify({ estado, tiene_articulos: hasArt, tiene_aves: hasAve })
        });
        const data = await response.json();
        
        const envio = parseFloat(data.costo_envio);
        const subtotal = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
        const total = subtotal + envio;

        document.getElementById('summaryShipping').textContent = `$${envio.toFixed(2)}`;
        document.getElementById('summaryTotal').textContent = `$${total.toFixed(2)}`;
        if(btn) btn.disabled = false;

    } catch (e) {
        console.error(e);
        document.getElementById('summaryShipping').textContent = "Error";
    }
}

async function processOrder() {
    // 1. Referencias DOM
    const btn = document.getElementById('btnPlaceOrder');
    const formContainer = document.querySelector('.checkout-form-col'); 
    const form = document.getElementById('checkoutForm');
    
    // 2. Bloqueo de UI (Evitar doble clic)
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    // 3. Recolección de Datos
    const nombre = document.getElementById('nombreCliente').value;
    const tel = document.getElementById('telCliente').value;
    const estado = document.getElementById('estadoSelect').value;
    const direccionInput = document.getElementById('direccionInput');
    
    // Validación básica de HTML5 (si el navegador no lo hizo)
    if (!form.checkValidity()) {
        form.reportValidity();
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Finalizar Pedido';
        return;
    }

    let direccionFinal = "";
    // Asegurarnos de que cart existe y tiene datos
    if (!cart || cart.length === 0) {
        alert("Tu carrito está vacío.");
        window.location.reload();
        return;
    }

    const hasArt = cart.some(i => i.tipo === 'articulo');
    if (hasArt) {
        direccionFinal = direccionInput ? direccionInput.value : '';
    } else {
        direccionFinal = "Ocurre / Aeropuerto (Coordinar por Tel)";
    }

    const orderData = {
        cliente: {
            nombre: nombre,
            telefono: tel,
            direccion: direccionFinal,
            estado: estado
        },
        carrito: cart
    };

    try {
        // 4. Envío al Backend
        const response = await fetch('api/checkout.php?accion=crear_orden', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
        const res = await response.json();

        if (res.success) {
            // ============================================================
            // CASO ÉXITO: VISTA POST-CHECKOUT
            // ============================================================
            
            // A. Limpieza de datos locales (Crítico para consistencia)
            localStorage.removeItem('rlt_cart');
            cart = []; 
            updateCartUI(); // Resetear badges a 0

            // B. Scroll arriba para que el usuario vea el mensaje
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // C. Inyección de HTML (Reemplazo Inline)
            formContainer.innerHTML = `
                <div class="checkout-feedback feedback-success">
                    <i class="fas fa-check-circle feedback-icon"></i>
                    <h2 class="feedback-title">¡Gracias, ${nombre.split(' ')[0]}!</h2>
                    <p class="feedback-text">
                        Tu orden <span class="order-ref">#${res.orden_id}</span> ha sido registrada correctamente.<br>
                        Estamos abriendo WhatsApp para confirmar tu pago y envío.
                    </p>
                    
                    <a href="${res.whatsapp_link}" target="_blank" class="btn-whatsapp-redirect">
                        <i class="fab fa-whatsapp"></i> Enviar Mensaje Ahora
                    </a>
                    
                    <span class="redirect-timer">
                        <i class="fas fa-circle-notch fa-spin"></i> Redirigiendo en <strong id="countdown">3</strong> segundos...
                    </span>
                </div>
            `;

            // D. Automatización de Redirección
            let seconds = 3;
            const countSpan = document.getElementById('countdown');
            
            const timer = setInterval(() => {
                seconds--;
                if(countSpan) countSpan.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    // 1. Abrir WhatsApp en nueva pestaña
                    window.open(res.whatsapp_link, '_blank');
                    
                    // 2. Redirigir la pestaña actual al Home tras un breve delay
                    // Esto limpia el estado visual y evita que el usuario use el botón "Atrás" hacia un checkout roto
                    setTimeout(() => {
                        window.location.href = 'index.php'; 
                    }, 1000);
                }
            }, 1000);

        } else {
            // ============================================================
            // CASO ERROR: FEEDBACK INLINE (No intrusivo)
            // ============================================================
            
            // Buscar si ya existe la caja de error para no duplicarla
            let errorBox = document.getElementById('checkoutErrorBox');
            if (!errorBox) {
                errorBox = document.createElement('div');
                errorBox.id = 'checkoutErrorBox';
                errorBox.className = 'alert-info'; 
                // Estilos inline específicos para error grave
                errorBox.style.background = '#fee2e2';
                errorBox.style.color = '#991b1b';
                errorBox.style.borderLeft = '4px solid #ef4444';
                errorBox.style.marginTop = '1rem';
                
                // Insertar antes del botón de submit
                form.insertBefore(errorBox, btn);
            }

            errorBox.innerHTML = `
                <div style="display:flex; gap:10px; align-items:center;">
                    <i class="fas fa-exclamation-triangle" style="font-size:1.5rem;"></i>
                    <div>
                        <strong>No pudimos procesar tu pedido</strong><br>
                        ${res.message}
                    </div>
                </div>
            `;

            // Restaurar botón para reintentar
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-whatsapp"></i> Intentar Nuevamente';
            
            // Scroll hacia el error
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

    } catch (e) {
        console.error(e);
        alert("Error de conexión. Por favor verifica tu internet.");
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Finalizar Pedido';
    }
}

/* --- INICIALIZACIÓN AUTOMÁTICA DEL CHECKOUT --- */
function initCheckoutSystem() {
    // Verificamos si estamos en la página correcta buscando el contenedor
    const checkoutContainer = document.getElementById('cartItemsContainer');
    
    // Solo ejecutamos si existe el contenedor y la función
    if (checkoutContainer && typeof renderCheckout === 'function') {
        renderCheckout();
    }
}

// Lógica robusta de carga:
// Si el documento aún está cargando, esperamos el evento.
// Si ya cargó (porque el script está al final), ejecutamos directamente.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCheckoutSystem);
} else {
    initCheckoutSystem();
}