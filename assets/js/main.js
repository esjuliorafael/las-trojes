/* =============================================
   MAIN JS - Rancho Las Trojes
   Carrito & Lógica de Checkout (Directo y Tradicional)
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

/**
 * Flujo Tradicional: Añade y muestra feedback, el usuario sigue comprando.
 */
function addToCart(id, type, name, price) {
    const existingIndex = cart.findIndex(i => i.id === id);

    if (existingIndex > -1) {
        if (type === 'ave') {
            alert("Esta ave ya está en tu carrito (Stock único).");
            return;
        } else {
            cart[existingIndex].cantidad++;
            alert("Cantidad actualizada.");
        }
    } else {
        cart.push({ id, tipo: type, nombre: name, precio: parseFloat(price), cantidad: 1 });
        alert("Agregado al carrito.");
    }
    saveCart();
    updateCartUI();
}

/**
 * Flujo Rápido (Comprar Ahora): Añade y lleva directo al pago.
 */
function buyNow(id, type, name, price) {
    const existingIndex = cart.findIndex(i => i.id === id);

    if (existingIndex > -1) {
        if (type === 'ave') {
            // Ya está en el carrito, solo redirigir
            // No hacemos nada
        } else {
            // Articulo: asegurar que haya al menos 1
            // Si el usuario quería "comprar ahora", asumo que quiere procesar lo que tenga
            // Opcional: Podríamos incrementar, pero para "Comprar Ahora" 
            // a veces se prefiere asegurar que el ítem esté presente.
            // Aquí simplemente nos aseguramos que esté en el carrito.
        }
    } else {
        cart.push({ id, tipo: type, nombre: name, precio: parseFloat(price), cantidad: 1 });
    }
    
    saveCart();
    updateCartUI();
    
    // REDIRECCIÓN INMEDIATA
    window.location.href = 'checkout.php';
}

function removeFromCart(index) {
    if(confirm("¿Eliminar este producto?")) {
        cart.splice(index, 1);
        saveCart();
        // Si estamos en checkout, re-renderizar
        if (document.getElementById('cartItemsContainer')) {
            renderCheckout();
        } else {
            updateCartUI();
        }
    }
}

function saveCart() { localStorage.setItem('rlt_cart', JSON.stringify(cart)); }

function updateCartUI() {
    const countBadge = document.getElementById('cart-count');
    if (countBadge) {
        const totalItems = cart.reduce((sum, item) => sum + item.cantidad, 0);
        countBadge.textContent = totalItems;
        countBadge.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

/* --- CHECKOUT PAGE LOGIC --- */

function renderCheckout() {
    const container = document.getElementById('cartItemsContainer');
    if (!container) return; // No estamos en checkout.php

    if (cart.length === 0) {
        container.innerHTML = "<p>Tu carrito está vacío.</p>";
        document.getElementById('btnPlaceOrder').disabled = true;
        // Limpiar resumen
        document.getElementById('summarySubtotal').textContent = "$0.00";
        document.getElementById('summaryShipping').textContent = "$0.00";
        document.getElementById('summaryTotal').textContent = "$0.00";
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

    // Manejo de Formularios Dinámicos
    const addressSec = document.getElementById('addressSection');
    const airportSec = document.getElementById('airportSection');
    const dirInput = document.getElementById('direccionInput');

    if (hasArt) {
        addressSec.classList.remove('hidden');
        dirInput.setAttribute('required', 'true');
    } else {
        addressSec.classList.add('hidden');
        dirInput.removeAttribute('required');
    }

    if (hasAve) {
        airportSec.classList.remove('hidden');
    } else {
        airportSec.classList.add('hidden');
    }

    // Llenar Select de Estados si está vacío
    const select = document.getElementById('estadoSelect');
    if (select.options.length <= 1) {
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
    const estado = document.getElementById('estadoSelect').value;
    const btn = document.getElementById('btnPlaceOrder');
    
    if (!estado) {
        document.getElementById('summaryShipping').textContent = "Selecciona estado...";
        document.getElementById('summaryTotal').textContent = "---";
        btn.disabled = true;
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
        btn.disabled = false;

    } catch (e) {
        console.error(e);
        document.getElementById('summaryShipping').textContent = "Error";
    }
}

async function processOrder() {
    const btn = document.getElementById('btnPlaceOrder');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    const nombre = document.getElementById('nombreCliente').value;
    const tel = document.getElementById('telCliente').value;
    const estado = document.getElementById('estadoSelect').value;
    const direccionInput = document.getElementById('direccionInput');
    
    // Determinar dirección final
    let direccionFinal = "";
    const hasArt = cart.some(i => i.tipo === 'articulo');
    if (hasArt) {
        direccionFinal = direccionInput.value;
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
        const response = await fetch('api/checkout.php?accion=crear_orden', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
        const res = await response.json();

        if (res.success) {
            localStorage.removeItem('rlt_cart');
            window.location.href = res.whatsapp_link;
        } else {
            alert("Error: " + res.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-whatsapp"></i> Finalizar Pedido';
        }
    } catch (e) {
        alert("Error de conexión");
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Finalizar Pedido';
    }
}

// Inicializar UI al cargar cualquier página
document.addEventListener('DOMContentLoaded', updateCartUI);