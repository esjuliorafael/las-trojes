<?php
include_once 'config/database.php';
include_once 'models/Producto.php';
include_once 'models/Logo.php';

$database = new Database();
$db = $database->getConnection();

$productoModel = new Producto($db);
$logoModel = new Logo($db);

$logo_actual = $logoModel->obtenerLogoActivo();

$id_producto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$producto = $productoModel->leerUno($id_producto);

if (!$producto || !$producto['activo']) {
    header("Location: tienda.php");
    exit;
}

$galeria = isset($producto['galeria']) ? $producto['galeria'] : [];

// Preparar array de imágenes para JS (Portada + Galería)
$imagenes_js = [];
if (!empty($producto['portada'])) {
    $imagenes_js[] = $producto['portada'];
}
foreach ($galeria as $img) {
    $imagenes_js[] = $img['ruta_archivo'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - Rancho Las Trojes</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css">
    <link id="darkModeStylesheet" rel="stylesheet" href="assets/css/dark-mode.css" disabled>
    
    <style>
        /* --- Estilos Generales Producto --- */
        .product-detail-section {
            background: var(--white);
            padding: 3rem 0;
            margin-top: 2rem;
        }

        .product-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        @media (max-width: 900px) {
            .product-container {
                grid-template-columns: 1fr;
            }
        }

        /* --- Galería --- */
        .product-gallery {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .main-image-container {
            width: 100%;
            height: 400px;
            border-radius: 1rem;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .main-image:hover {
            transform: scale(1.05);
        }

        .zoom-hint {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            pointer-events: none;
        }

        .thumbnails-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.5rem;
        }

        .thumbnail {
            height: 80px;
            border-radius: 0.5rem;
            overflow: hidden;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .thumbnail.active,
        .thumbnail:hover {
            opacity: 1;
            border-color: var(--brown);
        }

        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- Info --- */
        .product-info h2 {
            font-size: 2.5em;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--black-blue);
        }

        .product-price {
            font-size: 1.5rem;
            color: var(--brown);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .product-meta {
            background: var(--off-white-light);
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--divider);
        }

        .meta-item:last-child {
            border-bottom: none;
        }

        .meta-label {
            font-weight: 600;
            color: var(--text-color);
        }

        .meta-value {
            font-weight: 500;
            color: var(--black-blue);
        }

        .product-description {
            margin-bottom: 2rem;
            line-height: 1.8;
            color: var(--text-color);
        }

        /* --- Botones de Acción --- */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        /* Botón Añadir al Carrito */
        .btn-cart {
            flex: 1;
            background: var(--black-blue);
            color: var(--white);
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-cart:hover {
            background: #2c3e50;
            transform: translateY(-2px);
        }

        /* Botón Comprar Ahora */
        .btn-buy {
            flex: 1;
            background: var(--brown);
            color: var(--white);
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(139, 94, 60, 0.3);
        }

        .btn-buy:hover {
            background: #6d4a2f;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 94, 60, 0.4);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .status-disponible { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-reservado { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-vendido { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* --- ESTILOS DEL MODAL --- */
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
            align-items: center;
            justify-content: center;
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

        .modal-nav.prev { left: 2rem; }
        .modal-nav.next { right: 2rem; }

        .modal-image-container {
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .modal-image {
            max-width: 90vw;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        @media (max-width: 768px) {
            .modal-close { top: 1rem; right: 1rem; width: 45px; height: 45px; }
            .modal-nav { width: 45px; height: 45px; font-size: 1.2rem; }
            .modal-nav.prev { left: 1rem; }
            .modal-nav.next { right: 1rem; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="page-header-start container-wide">
        <img src="assets/images/42c08f60-d5b7-4aec-87cd-632c3a0ed6a6.jpeg" alt="Fondo Tienda">
        <div class="page-header-overlay">
            <h1 class="page-header-title animated-text">
                <span class="word">Tienda</span>
            </h1>
            <p class="page-header-subtitle fade-up-animation">Inicio / Tienda / <?php echo htmlspecialchars($producto['nombre']); ?></p>
        </div>
    </section>

    <section class="product-detail-section container-wide">
        <div class="container">
            <div class="product-container">

                <div class="product-gallery">
                    <div class="main-image-container" onclick="openModalIndex(currentIndex)">
                        <img id="mainImg"
                             src="<?php echo !empty($producto['portada']) ? $producto['portada'] : 'assets/images/placeholder.jpg'; ?>"
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                             class="main-image">
                        <div class="zoom-hint"><i class="fas fa-search-plus"></i> Ampliar</div>
                    </div>

                    <div class="thumbnails-grid">
                        <?php foreach ($imagenes_js as $index => $img): ?>
                            <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="selectImage(<?php echo $index; ?>)">
                                <img src="<?php echo $img; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="product-info">
                    <?php if ($producto['tipo'] === 'ave'): ?>
                        <span class="status-badge status-<?php echo $producto['estado_venta']; ?> fade-up-animation">
                            <?php echo ucfirst($producto['estado_venta']); ?>
                        </span>
                    <?php endif; ?>

                    <h2 class="animated-text">
                        <span class="word"><?php echo htmlspecialchars($producto['nombre']); ?></<span>
                    </h2>
                    <div class="product-price fade-up-animation">$<?php echo number_format($producto['precio'], 2); ?> MXN</div>

                    <div class="product-description fade-up-animation">
                        <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
                    </div>

                    <div class="product-meta fade-up-animation">
                        <div class="meta-item">
                            <span class="meta-label">Categoría:</span>
                            <span class="meta-value"><?php echo ucfirst($producto['tipo']); ?></span>
                        </div>

                        <?php if ($producto['tipo'] === 'ave'): ?>
                            <div class="meta-item">
                                <span class="meta-label">Anillo:</span>
                                <span class="meta-value"><?php echo $producto['anillo'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Edad / Etapa:</span>
                                <span class="meta-value"><?php echo $producto['edad'] ?: 'N/A'; ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Propósito:</span>
                                <span class="meta-value"><?php echo $producto['proposito'] ?: 'N/A'; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="meta-item">
                                <span class="meta-label">Stock Disponible:</span>
                                <span class="meta-value"><?php echo $producto['stock']; ?> unidades</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="action-buttons">
                        <?php
                        $puede_comprar = ($producto['tipo'] === 'ave')
                            ? ($producto['estado_venta'] === 'disponible')
                            : ($producto['stock'] > 0);

                        // Datos para JS
                        $js_id = $producto['id'];
                        $js_tipo = $producto['tipo'];
                        $js_nombre = htmlspecialchars($producto['nombre']);
                        $js_precio = $producto['precio'];
                        ?>

                        <?php if ($puede_comprar): ?>
                            <button class="btn-cart fade-up-animation" onclick="addToCart(
                                <?php echo $js_id; ?>, '<?php echo $js_tipo; ?>', '<?php echo $js_nombre; ?>', <?php echo $js_precio; ?>
                            )">
                                <i class="fas fa-cart-plus"></i> Añadir al Carrito
                            </button>

                            <button class="btn-buy fade-up-animation" onclick="buyNow(
                                <?php echo $js_id; ?>, '<?php echo $js_tipo; ?>', '<?php echo $js_nombre; ?>', <?php echo $js_precio; ?>
                            )">
                                <i class="fas fa-bolt"></i> Comprar Ahora
                            </button>
                        <?php else: ?>
                            <button class="btn-cart fade-up-animation" style="background: var(--text-color); cursor: not-allowed; width:100%;" disabled>
                                No Disponible
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php include 'includes/faq.php'; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="modal-overlay" id="productModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <?php if (count($imagenes_js) > 1): ?>
                <button class="modal-nav prev" onclick="navigateModal(-1)"><i class="fas fa-chevron-left"></i></button>
                <button class="modal-nav next" onclick="navigateModal(1)"><i class="fas fa-chevron-right"></i></button>
            <?php endif; ?>
            <div class="modal-image-container">
                <img src="" class="modal-image" id="modalImageFull">
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // --- LÓGICA DE GALERÍA Y MODAL DEL PRODUCTO ---
        const productImages = <?php echo json_encode($imagenes_js); ?>;
        let currentIndex = 0;

        function selectImage(index) {
            currentIndex = index;
            document.getElementById('mainImg').src = productImages[index];
            document.querySelectorAll('.thumbnail').forEach((t, i) => {
                if (i === index) t.classList.add('active');
                else t.classList.remove('active');
            });
        }

        function openModalIndex(index) {
            currentIndex = index;
            updateModalImage();
            document.getElementById('productModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function navigateModal(dir) {
            currentIndex += dir;
            if (currentIndex < 0) currentIndex = productImages.length - 1;
            if (currentIndex >= productImages.length) currentIndex = 0;
            updateModalImage();
        }

        function updateModalImage() {
            document.getElementById('modalImageFull').src = productImages[currentIndex];
        }

        document.addEventListener('keydown', (e) => {
            if (!document.getElementById('productModal').classList.contains('active')) return;
            if (e.key === 'Escape') closeModal();
            if (e.key === 'ArrowLeft') navigateModal(-1);
            if (e.key === 'ArrowRight') navigateModal(1);
        });
    </script>
</body>
</html>