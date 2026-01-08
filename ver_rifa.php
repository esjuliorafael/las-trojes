<?php
include_once 'config/database.php';
include_once 'models/Logo.php';

// --- SIMULACIÓN DE DATOS (Backend Mock) ---
$rifa = [
    'id' => 1,
    'titulo' => 'Gran Rifa: Semental "El Patron" + 2 Gallinas',
    'descripcion' => 'Oportunidad única para adquirir genética de primer nivel. El ganador se lleva nuestro semental estrella y dos gallinas probadas para cría. El envío corre por cuenta del ganador.',
    'precio_boleto' => 250.00,
    'fecha_fin' => '2026-02-15 20:00:00',
    'estado' => 'activa',
    'meta_boletos' => 100,
    'boletos_vendidos' => 45,
    'imagenes' => [
        'assets/images/9f13a7d2-8c41-4e8b-b3f2-6d9e0c72f4a1.jpg',
        'assets/images/placeholder.jpg',
        'assets/images/placeholder.jpg'
    ]
];

// Generar boletos (1-100)
$boletos = [];
for ($i = 1; $i <= 100; $i++) {
    $estado = ($i % 3 == 0 || $i % 7 == 0) ? 'vendido' : 'disponible';
    $boletos[] = ['numero' => str_pad($i, 3, '0', STR_PAD_LEFT), 'estado' => $estado];
}

// Participantes Simulados
$participantes = [
    ['nombre' => 'Juan Pérez', 'boletos' => ['003', '007'], 'estado_pago' => 'pagado', 'fecha' => '2026-01-02'],
    ['nombre' => 'Roberto Gómez', 'boletos' => ['021'], 'estado_pago' => 'pendiente', 'fecha' => '2026-01-04'],
    ['nombre' => 'Carlos M.', 'boletos' => ['099', '012', '033'], 'estado_pago' => 'pagado', 'fecha' => '2026-01-01'],
];

$database = new Database();
$db = $database->getConnection();
$logo = new Logo($db);
$logo_actual = $logo->obtenerLogoActivo();

// Preparamos las imágenes para JS
$imagenes_js = $rifa['imagenes'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $rifa['titulo']; ?> - Rancho Las Trojes</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link id="darkModeStylesheet" rel="stylesheet" href="assets/css/dark-mode.css" disabled>

    <style>
        /* --- ESTILOS ESPECÍFICOS DE RIFA --- */
        
        .raffle-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-top: 2rem;
            margin-bottom: 4rem;
        }

        /* Galería */
        .raffle-gallery { display: flex; flex-direction: column; gap: 1.5rem; }
        
        .main-image-container {
            border-radius: 1.5rem; 
            overflow: hidden; 
            border: 1px solid var(--divider);
            aspect-ratio: 1/1; 
            position: relative; 
            background: var(--off-white-light);
            cursor: pointer; /* Cursor de mano */
        }
        
        .main-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        
        .main-image-container:hover .main-image {
            transform: scale(1.05); /* Efecto zoom suave al hover */
        }

        /* Zoom Hint (Igual a producto.php) */
        .zoom-hint {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            pointer-events: none;
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.3s;
        }

        .thumbnails-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        .thumbnail {
            border-radius: 0.75rem; overflow: hidden; border: 2px solid transparent; aspect-ratio: 1/1; cursor: pointer; transition: 0.2s;
        }
        .thumbnail.active { border-color: var(--brown); opacity: 1; }
        .thumbnail:not(.active) { opacity: 0.7; }
        .thumbnail:hover { opacity: 1; border-color: var(--brown); }
        .thumbnail img { width: 100%; height: 100%; object-fit: cover; }

        /* Info Rifa */
        .raffle-info { display: flex; flex-direction: column; gap: 1.5rem; }
        
        .badge-raffle-status {
            display: inline-block; padding: 0.35rem 1rem; border-radius: 50px;
            font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
            width: fit-content;
        }
        .status-activa { background: #dcfce7; color: #166534; }
        .status-finalizada { background: #fee2e2; color: #991b1b; }

        .raffle-title { font-family: 'Lora', serif; font-size: 2.5rem; line-height: 1.2; color: var(--black-blue); margin: 0; }
        .raffle-price { font-size: 2rem; font-weight: 700; color: var(--brown); }
        .raffle-price span { font-size: 1rem; color: var(--text-color); font-weight: 500; }
        .raffle-description { color: var(--text-color); line-height: 1.7; font-size: 1.05rem; }

        /* Timer y Progreso */
        .raffle-timer-box {
            background: var(--off-white-light); border: 1px solid var(--divider); border-radius: 1rem; padding: 1.5rem;
            display: flex; gap: 2rem; justify-content: center; align-items: center; text-align: center;
        }
        .timer-unit .num { display: block; font-size: 1.8rem; font-weight: 700; color: var(--brown); line-height: 1; margin-bottom: 0.2rem; }
        .timer-unit .label { font-size: 0.75rem; text-transform: uppercase; color: var(--text-color); letter-spacing: 0.1em; }

        .progress-container { margin-top: 0.5rem; }
        .progress-labels { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem; font-weight: 600; color: var(--black-blue); }
        .progress-bar-bg { width: 100%; height: 10px; background: var(--off-white-light); border-radius: 10px; overflow: hidden; border: 1px solid var(--divider); }
        .progress-bar-fill { height: 100%; background: var(--brown); width: 0%; transition: width 1s ease; border-radius: 10px; }

        /* SECCIÓN INFERIOR */
        .tickets-section { display: grid; grid-template-columns: 2fr 1fr; gap: 3rem; align-items: start; }
        .controls-wrapper {
            background: var(--white); border: 1px solid var(--divider); border-radius: 1rem; padding: 1rem; margin-bottom: 2rem;
            display: flex; gap: 1rem; flex-wrap: wrap; justify-content: space-between; align-items: center;
        }
        .ticket-search { flex: 1; position: relative; min-width: 250px; }
        .ticket-search input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.8rem; border-radius: 0.5rem; border: 1px solid var(--divider); background: var(--off-white-light); }
        .ticket-search i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-color); }

        .view-tabs { display: flex; gap: 0.5rem; background: var(--off-white-light); padding: 0.3rem; border-radius: 0.5rem; }
        .view-tab { padding: 0.5rem 1rem; border: none; background: none; border-radius: 0.3rem; cursor: pointer; font-weight: 600; color: var(--text-color); transition: 0.2s; }
        .view-tab.active { background: var(--white); color: var(--brown); box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

        /* BOLETOS */
        .tickets-legend { display: flex; gap: 1.5rem; margin-bottom: 1.5rem; font-size: 0.9rem; justify-content: center; }
        .legend-item { display: flex; align-items: center; gap: 0.5rem; }
        .dot { width: 12px; height: 12px; border-radius: 50%; }
        .dot.available { border: 2px solid var(--divider); background: var(--white); }
        .dot.selected { background: var(--brown); border: 2px solid var(--brown); }
        .dot.sold { background: #ef4444; border: 2px solid #ef4444; }

        .quick-select { display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; }
        .btn-quick { padding: 0.5rem 1rem; border: 1px solid var(--brown); color: var(--brown); background: transparent; border-radius: 2rem; cursor: pointer; font-size: 0.85rem; transition: 0.2s; }
        .btn-quick:hover { background: var(--brown); color: white; }

        .tickets-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 0.8rem; }
        .ticket-btn {
            aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center;
            border: 2px solid var(--divider); background: var(--white); border-radius: 0.5rem;
            font-weight: 700; color: var(--black-blue); cursor: pointer; transition: all 0.2s; font-size: 1.1rem;
        }
        .ticket-btn:hover:not(.sold):not(.selected) { border-color: var(--brown); transform: translateY(-2px); }
        .ticket-btn.selected { background: var(--brown); color: white; border-color: var(--brown); transform: scale(1.05); box-shadow: 0 4px 10px rgba(139, 94, 60, 0.3); }
        .ticket-btn.sold { background: var(--off-white-light); color: var(--divider); border-color: transparent; cursor: not-allowed; text-decoration: line-through; }
        .ticket-btn.hidden { display: none; }

        /* PARTICIPANTES */
        .participants-list { display: none; flex-direction: column; gap: 1rem; }
        .participant-card { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid var(--divider); border-radius: 0.8rem; }
        .p-info h4 { margin: 0 0 0.3rem 0; font-size: 1rem; }
        .p-info p { margin: 0; font-size: 0.85rem; color: var(--text-color); }
        .p-status { font-size: 0.8rem; padding: 0.2rem 0.6rem; border-radius: 1rem; text-transform: uppercase; font-weight: 700; }
        .p-paid { background: #dcfce7; color: #166534; }
        .p-pending { background: #fef9c3; color: #854d0e; }

        /* SIDEBAR */
        .selection-sidebar {
            position: sticky; top: 2rem;
            background: var(--white); border: 1px solid var(--divider); border-radius: 1.25rem; padding: 1.5rem;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05);
        }
        .sidebar-header { border-bottom: 1px solid var(--divider); padding-bottom: 1rem; margin-bottom: 1rem; }
        .sidebar-header h3 { margin: 0; font-family: 'Lora', serif; }
        .selected-list { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; max-height: 200px; overflow-y: auto; }
        .selected-chip { background: var(--black-blue); color: white; padding: 0.3rem 0.8rem; border-radius: 2rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; animation: popIn 0.3s; }
        .selected-chip i { cursor: pointer; color: rgba(255,255,255,0.7); }
        .selected-chip i:hover { color: white; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.95rem; color: var(--text-color); }
        .summary-total { display: flex; justify-content: space-between; border-top: 1px solid var(--divider); padding-top: 1rem; margin-top: 1rem; font-weight: 700; font-size: 1.2rem; color: var(--black-blue); }
        .btn-checkout { width: 100%; margin-top: 1.5rem; background: var(--brown); color: white; padding: 1rem; border: none; border-radius: 0.8rem; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-checkout:hover { background: var(--black-blue); }
        .btn-checkout:disabled { background: var(--divider); cursor: not-allowed; }

        /* MODAL CHECKOUT */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content-form { background-color: var(--white); margin: 5% auto; padding: 2rem; border-radius: 1.5rem; width: 90%; max-width: 500px; position: relative; animation: slideUp 0.4s ease; }
        .close-modal-form { position: absolute; right: 1.5rem; top: 1.5rem; font-size: 1.5rem; cursor: pointer; color: var(--text-color); }

        /* --- ESTILOS DEL MODAL DE IMÁGENES (IDÉNTICOS A PRODUCTO.PHP) --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            z-index: 2000;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; visibility: hidden; transition: all 0.3s ease;
            backdrop-filter: blur(20px);
        }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        
        .modal-content-gallery {
            position: relative; width: 100vw; height: 100vh;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }

        .modal-close-gallery {
            position: fixed; top: 2rem; right: 2rem; width: 50px; height: 50px;
            border: none; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(10px);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: white; font-size: 1.2rem; z-index: 2001;
            transition: all 0.3s ease; border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .modal-close-gallery:hover { background: rgba(139, 94, 60, 0.8); transform: scale(1.1); }

        .modal-nav {
            position: fixed; top: 50%; transform: translateY(-50%); width: 60px; height: 60px;
            border: none; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(10px);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: white; font-size: 1.5rem; z-index: 2001;
            transition: all 0.3s ease; border: 2px solid rgba(255, 255, 255, 0.2); opacity: 0.8;
        }
        .modal-nav:hover { background: rgba(139, 94, 60, 0.8); opacity: 1; transform: translateY(-50%) scale(1.1); }
        .modal-nav.prev { left: 2rem; }
        .modal-nav.next { right: 2rem; }

        .modal-image-wrapper { padding: 1rem; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; }
        .modal-image-full { max-width: 90vw; max-height: 85vh; object-fit: contain; border-radius: 8px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5); }

        @keyframes popIn { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        @media (max-width: 900px) {
            .raffle-container { grid-template-columns: 1fr; gap: 2rem; }
            .tickets-section { grid-template-columns: 1fr; }
            .selection-sidebar { position: fixed; bottom: 0; left: 0; top: auto; width: 100%; border-radius: 20px 20px 0 0; z-index: 100; margin: 0; padding: 1rem 1.5rem; }
            .selected-list { display: none; }
            .sidebar-header { display: none; }
            .btn-checkout { margin: 0; }
            
            /* Responsive Modal */
            .modal-close-gallery { top: 1rem; right: 1rem; width: 45px; height: 45px; }
            .modal-nav { width: 45px; height: 45px; font-size: 1.2rem; }
            .modal-nav.prev { left: 1rem; }
            .modal-nav.next { right: 1rem; }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="page-header-start container-wide">
        <img src="<?php echo $rifa['imagenes'][0]; ?>" alt="Fondo" style="filter: brightness(0.4);">
        <div class="page-header-overlay">
            <h1 class="page-header-title animated-text">Rifas Las Trojes</h1>
            <p class="page-header-subtitle">Participa y Gana Genética Pura</p>
        </div>
    </section>

    <div class="container-wide">
        
        <div class="raffle-container container">
            
            <div class="raffle-gallery fade-up-animation">
                <div class="main-image-container" id="mainImageContainer" onclick="openModalIndex(currentIndex)">
                    <img src="<?php echo $rifa['imagenes'][0]; ?>" class="main-image" id="mainImage" alt="Premio Principal">
                    <div class="zoom-hint"><i class="fas fa-search-plus"></i> Ampliar</div>
                </div>

                <div class="thumbnails-grid">
                    <?php foreach ($rifa['imagenes'] as $index => $img): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="selectImage(<?php echo $index; ?>)">
                            <img src="<?php echo $img; ?>" alt="Thumb">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="raffle-info fade-up-animation" style="animation-delay: 0.2s;">
                <span class="badge-raffle-status status-<?php echo $rifa['estado']; ?>">
                    <?php echo ucfirst($rifa['estado']); ?>
                </span>

                <h1 class="raffle-title"><?php echo $rifa['titulo']; ?></h1>

                <div class="raffle-price">
                    $<?php echo number_format($rifa['precio_boleto'], 2); ?>
                    <span>MXN / boleto</span>
                </div>

                <p class="raffle-description"><?php echo $rifa['descripcion']; ?></p>

                <div class="raffle-timer-box">
                    <div class="timer-unit"><span class="num" id="days">00</span><span class="label">Días</span></div>
                    <div class="timer-unit"><span class="num" id="hours">00</span><span class="label">Hrs</span></div>
                    <div class="timer-unit"><span class="num" id="minutes">00</span><span class="label">Min</span></div>
                    <div class="timer-unit"><span class="num" id="seconds">00</span><span class="label">Seg</span></div>
                </div>

                <div class="progress-container">
                    <div class="progress-labels">
                        <span>Progreso de Venta</span>
                        <span><?php echo $rifa['boletos_vendidos']; ?> / <?php echo $rifa['meta_boletos']; ?> Boletos</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: <?php echo ($rifa['boletos_vendidos'] / $rifa['meta_boletos']) * 100; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container tickets-section">
            <div class="main-content">
                <div class="controls-wrapper">
                    <div class="view-tabs">
                        <button class="view-tab active" onclick="switchView('tickets')"><i class="fas fa-ticket-alt"></i> Boletos</button>
                        <button class="view-tab" onclick="switchView('participants')"><i class="fas fa-users"></i> Participantes</button>
                    </div>
                    <div class="ticket-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar número...">
                    </div>
                </div>

                <div id="ticketsView">
                    <div class="quick-select">
                        <span style="font-weight:600; font-size:0.9rem;">Suerte Rápida:</span>
                        <button class="btn-quick" onclick="randomSelect(1)">1 Boleto</button>
                        <button class="btn-quick" onclick="randomSelect(3)">3 Boletos</button>
                        <button class="btn-quick" onclick="randomSelect(5)">5 Boletos</button>
                    </div>
                    <div class="tickets-legend">
                        <div class="legend-item"><div class="dot available"></div> Disponible</div>
                        <div class="legend-item"><div class="dot selected"></div> Tu Selección</div>
                        <div class="legend-item"><div class="dot sold"></div> Vendido</div>
                    </div>
                    <div class="tickets-grid" id="gridContainer">
                        <?php foreach ($boletos as $b): ?>
                            <button class="ticket-btn <?php echo $b['estado']; ?>" 
                                    data-number="<?php echo $b['numero']; ?>"
                                    <?php echo $b['estado'] === 'vendido' ? 'disabled' : 'onclick="toggleTicket(this)"'; ?>>
                                <?php echo $b['numero']; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="participantsView" class="participants-list">
                    <?php foreach ($participantes as $p): ?>
                        <div class="participant-card" data-name="<?php echo strtolower($p['nombre']); ?>" data-tickets="<?php echo implode(',', $p['boletos']); ?>">
                            <div class="p-info">
                                <h4><?php echo $p['nombre']; ?></h4>
                                <p><i class="fas fa-ticket-alt"></i> Boletos: <?php echo implode(', ', $p['boletos']); ?></p>
                                <p style="font-size:0.75rem; color:#999;">Fecha: <?php echo $p['fecha']; ?></p>
                            </div>
                            <span class="p-status p-<?php echo $p['estado_pago']; ?>">
                                <?php echo ucfirst($p['estado_pago']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="selection-sidebar">
                <div class="sidebar-header">
                    <h3>Tu Selección</h3>
                    <p style="font-size:0.85rem; color:var(--text-color);">Selecciona tus números ganadores</p>
                </div>
                <div class="selected-list" id="selectedList">
                    <p style="color:var(--text-color); font-size:0.9rem; font-style:italic;">No has seleccionado boletos.</p>
                </div>
                <div class="summary-row">
                    <span>Cantidad:</span>
                    <span id="qtyLabel">0</span>
                </div>
                <div class="summary-row">
                    <span>Precio unitario:</span>
                    <span>$<?php echo number_format($rifa['precio_boleto'], 0); ?></span>
                </div>
                <div class="summary-total">
                    <span>Total:</span>
                    <span id="totalLabel">$0.00</span>
                </div>
                <button id="btnApartar" class="btn-checkout" disabled onclick="openCheckoutModal()">
                    Apartar Boletos
                </button>
            </div>
        </div>
    </div>

    <div id="checkoutModal" class="modal">
        <div class="modal-content-form">
            <span class="close-modal-form" onclick="closeCheckoutModal()">&times;</span>
            <h2 style="font-family:'Lora', serif; margin-bottom:1.5rem;">Apartar Boletos</h2>
            <form id="reserveForm">
                <div class="form-group"><label>Nombre Completo</label><input type="text" class="form-control" required placeholder="Ej. Juan Pérez"></div>
                <div class="form-group"><label>Teléfono (WhatsApp)</label><input type="tel" class="form-control" required placeholder="Ej. 55 1234 5678"></div>
                <div class="form-group"><label>Estado / Ciudad</label><input type="text" class="form-control" required placeholder="Ej. Jalisco"></div>
                <div style="background:#f9f9f9; padding:1rem; border-radius:0.5rem; margin:1.5rem 0;">
                    <p style="margin:0; font-weight:600;">Boletos a apartar: <span id="modalTicketCount">0</span></p>
                    <p style="margin:0.5rem 0 0 0; font-size:0.9rem;">Números: <span id="modalTicketNumbers"></span></p>
                    <p style="margin:1rem 0 0 0; font-size:1.2rem; font-weight:700; color:var(--brown);">Total: <span id="modalTotal"></span></p>
                </div>
                <button type="submit" class="btn-primary" style="width:100%;">Confirmar Apartado</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="galleryModal">
        <div class="modal-content-gallery">
            <button class="modal-close-gallery" onclick="closeGalleryModal()"><i class="fas fa-times"></i></button>
            <?php if (count($imagenes_js) > 1): ?>
                <button class="modal-nav prev" onclick="navigateModal(-1)"><i class="fas fa-chevron-left"></i></button>
                <button class="modal-nav next" onclick="navigateModal(1)"><i class="fas fa-chevron-right"></i></button>
            <?php endif; ?>
            <div class="modal-image-wrapper">
                <img src="" class="modal-image-full" id="modalImageFull">
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // --- 1. LÓGICA DE GALERÍA Y MODAL DE IMÁGENES ---
        const raffleImages = <?php echo json_encode($imagenes_js); ?>;
        let currentIndex = 0;

        function selectImage(index) {
            currentIndex = index;
            // Actualizar imagen principal
            const mainImg = document.getElementById('mainImage');
            mainImg.style.opacity = '0';
            setTimeout(() => {
                mainImg.src = raffleImages[index];
                mainImg.style.opacity = '1';
            }, 200);

            // Actualizar thumbnails
            document.querySelectorAll('.thumbnail').forEach((t, i) => {
                if (i === index) t.classList.add('active');
                else t.classList.remove('active');
            });
        }

        // Funciones del Modal de Galería
        function openModalIndex(index) {
            currentIndex = index;
            updateModalImage();
            document.getElementById('galleryModal').classList.add('active');
            document.body.style.overflow = 'hidden'; // Evitar scroll
        }

        function closeGalleryModal() {
            document.getElementById('galleryModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function navigateModal(dir) {
            currentIndex += dir;
            if (currentIndex < 0) currentIndex = raffleImages.length - 1;
            if (currentIndex >= raffleImages.length) currentIndex = 0;
            updateModalImage();
        }

        function updateModalImage() {
            document.getElementById('modalImageFull').src = raffleImages[currentIndex];
        }

        // Eventos de teclado para la galería
        document.addEventListener('keydown', (e) => {
            if (document.getElementById('galleryModal').classList.contains('active')) {
                if (e.key === 'Escape') closeGalleryModal();
                if (e.key === 'ArrowLeft') navigateModal(-1);
                if (e.key === 'ArrowRight') navigateModal(1);
            }
        });

        // --- 2. TIMER CUENTA REGRESIVA ---
        const countDownDate = new Date("<?php echo $rifa['fecha_fin']; ?>").getTime();
        const timerInterval = setInterval(function() {
            const now = new Date().getTime();
            const distance = countDownDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("days").innerText = days < 10 ? "0" + days : days;
            document.getElementById("hours").innerText = hours < 10 ? "0" + hours : hours;
            document.getElementById("minutes").innerText = minutes < 10 ? "0" + minutes : minutes;
            document.getElementById("seconds").innerText = seconds < 10 ? "0" + seconds : seconds;

            if (distance < 0) {
                clearInterval(timerInterval);
                document.querySelector('.raffle-timer-box').innerHTML = "<h3>¡RIFA FINALIZADA!</h3>";
            }
        }, 1000);

        // --- 3. GESTIÓN DE VISTAS (TABS) ---
        function switchView(viewName) {
            const ticketsView = document.getElementById('ticketsView');
            const participantsView = document.getElementById('participantsView');
            const searchInput = document.getElementById('searchInput');
            const tabs = document.querySelectorAll('.view-tab');

            tabs.forEach(t => t.classList.remove('active'));
            
            if (viewName === 'tickets') {
                ticketsView.style.display = 'block';
                participantsView.style.display = 'none';
                searchInput.placeholder = 'Buscar número de boleto...';
                tabs[0].classList.add('active');
            } else {
                ticketsView.style.display = 'none';
                participantsView.style.display = 'flex';
                searchInput.placeholder = 'Buscar participante...';
                tabs[1].classList.add('active');
            }
            searchInput.value = '';
            filterItems('');
        }

        // --- 4. BUSCADOR ---
        document.getElementById('searchInput').addEventListener('input', function(e) {
            filterItems(e.target.value.toLowerCase());
        });

        function filterItems(text) {
            const isTicketView = document.getElementById('ticketsView').style.display !== 'none';
            if (isTicketView) {
                document.querySelectorAll('.ticket-btn').forEach(btn => {
                    const num = btn.getAttribute('data-number');
                    if (num.includes(text)) btn.classList.remove('hidden');
                    else btn.classList.add('hidden');
                });
            } else {
                document.querySelectorAll('.participant-card').forEach(card => {
                    const name = card.getAttribute('data-name');
                    const tickets = card.getAttribute('data-tickets');
                    if (name.includes(text) || tickets.includes(text)) card.style.display = 'flex';
                    else card.style.display = 'none';
                });
            }
        }

        // --- 5. SELECCIÓN DE BOLETOS ---
        const ticketPrice = <?php echo $rifa['precio_boleto']; ?>;
        let selectedTickets = [];

        function toggleTicket(btn) {
            if (btn.classList.contains('sold')) return;
            const number = btn.getAttribute('data-number');
            
            if (selectedTickets.includes(number)) {
                selectedTickets = selectedTickets.filter(n => n !== number);
                btn.classList.remove('selected');
            } else {
                selectedTickets.push(number);
                btn.classList.add('selected');
            }
            updateSidebar();
        }

        function randomSelect(qty) {
            const availableBtns = Array.from(document.querySelectorAll('.ticket-btn:not(.sold):not(.selected)'));
            availableBtns.sort(() => Math.random() - 0.5);
            const toSelect = availableBtns.slice(0, qty);
            
            toSelect.forEach(btn => {
                selectedTickets.push(btn.getAttribute('data-number'));
                btn.classList.add('selected');
            });
            updateSidebar();
        }

        function updateSidebar() {
            const list = document.getElementById('selectedList');
            const btn = document.getElementById('btnApartar');
            selectedTickets.sort();

            if (selectedTickets.length === 0) {
                list.innerHTML = '<p style="color:var(--text-color); font-size:0.9rem; font-style:italic;">No has seleccionado boletos.</p>';
                btn.disabled = true;
                btn.textContent = "Apartar Boletos";
            } else {
                list.innerHTML = selectedTickets.map(num => `
                    <div class="selected-chip">#${num} <i class="fas fa-times-circle" onclick="removeTicket('${num}')"></i></div>
                `).join('');
                btn.disabled = false;
                btn.textContent = "Apartar Boletos";
            }

            document.getElementById('qtyLabel').innerText = selectedTickets.length;
            const total = selectedTickets.length * ticketPrice;
            document.getElementById('totalLabel').innerText = "$" + new Intl.NumberFormat('es-MX').format(total);
        }

        window.removeTicket = function(number) {
            const btn = document.querySelector(`.ticket-btn[data-number="${number}"]`);
            if (btn) toggleTicket(btn);
        }

        // --- 6. MODAL CHECKOUT ---
        function openCheckoutModal() {
            if (selectedTickets.length === 0) return;
            document.getElementById('modalTicketCount').innerText = selectedTickets.length;
            document.getElementById('modalTicketNumbers').innerText = selectedTickets.join(', ');
            document.getElementById('modalTotal').innerText = document.getElementById('totalLabel').innerText;
            document.getElementById('checkoutModal').style.display = 'block';
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        // Clic fuera para cerrar el modal de checkout (pero no el de galería, ese tiene su overlay)
        window.onclick = function(event) {
            const modal = document.getElementById('checkoutModal');
            if (event.target == modal) closeCheckoutModal();
        }

        document.getElementById('reserveForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('¡Gracias! Tu apartado ha sido registrado.');
            closeCheckoutModal();
        });
    </script>
</body>
</html>