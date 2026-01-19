<?php
// --- 1. DEPENDENCIAS ORIGINALES DE LAS TROJES ---
include_once 'config/database.php';
include_once 'models/Logo.php';

// Conexión principal (Las Trojes) para el Header/Logo
$database = new Database();
$db = $database->getConnection();
$logo = new Logo($db);
$logo_actual = $logo->obtenerLogoActivo();

// --- 2. CONEXIÓN AL SISTEMA DE RIFAS ---
class DatabaseRifas
{
    private $host = "localhost";
    private $db_name = "granlivo_rifas_las_trojes_db";
    private $username = "granlivo_admin"; 
    private $password = "j10u22l12i9O16*";    
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Error de conexión a Rifas: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

$id_rifa = isset($_GET['id']) ? $_GET['id'] : 1;
$dbRifas = (new DatabaseRifas())->getConnection();

// A. Consulta Rifa
$queryRifa = "SELECT * FROM rifas WHERE id = :id LIMIT 1";
$stmt = $dbRifas->prepare($queryRifa);
$stmt->bindParam(':id', $id_rifa);
$stmt->execute();
$rifa_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rifa_data) die("Rifa no encontrada");

// B. Consulta Boletos Ocupados y Participantes
$queryVentas = "SELECT numero_boleto, estado_pago, cliente_nombre, cliente_estado FROM ventas WHERE rifa_id = :id AND estado_pago IN ('pagado', 'pendiente')";
$stmtVentas = $dbRifas->prepare($queryVentas);
$stmtVentas->bindParam(':id', $id_rifa);
$stmtVentas->execute();
$ocupados_raw = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

// Mapear ocupados
$mapa_ocupados = [];
$lista_participantes = [];

foreach ($ocupados_raw as $occ) {
    $num = intval($occ['numero_boleto']);
    $mapa_ocupados[$num] = $occ['estado_pago'];

    // Lista para la pestaña "Participantes"
    $lista_participantes[] = [
        'numero' => str_pad($num, $rifa_data['cifras'], '0', STR_PAD_LEFT),
        'nombre' => $occ['cliente_nombre'],
        'estado' => $occ['cliente_estado'],
        'status' => $occ['estado_pago']
    ];
}

// C. Consulta Galería Adicional
$queryGaleria = "SELECT ruta_imagen FROM rifas_galeria WHERE rifa_id = :id ORDER BY id ASC";
$stmtGal = $dbRifas->prepare($queryGaleria);
$stmtGal->bindParam(':id', $id_rifa);
$stmtGal->execute();
$galeria_db = $stmtGal->fetchAll(PDO::FETCH_ASSOC);

// Construcción de URLs de imágenes
$base_url_uploads = "https://rifas.rancholastrojes.com.mx/assets/uploads/";
$imagenes_rifa = [];

// 1. Imagen de Portada
if (!empty($rifa_data['imagen'])) {
    $imagenes_rifa[] = $base_url_uploads . $rifa_data['imagen'];
} else {
    $imagenes_rifa[] = "assets/images/placeholder.jpg"; 
}

// 2. Imágenes de Galería
foreach ($galeria_db as $img) {
    if (!empty($img['ruta_imagen'])) {
        $imagenes_rifa[] = $base_url_uploads . "galeria/" . $img['ruta_imagen'];
    }
}

// Configurar datos finales
$rifa = [
    'id' => $rifa_data['id'],
    'titulo' => $rifa_data['titulo'],
    'descripcion' => $rifa_data['descripcion'],
    'precio_boleto' => $rifa_data['precio_boleto'],
    'fecha_sorteo' => $rifa_data['fecha_sorteo'], 
    'estado' => $rifa_data['estado'],
    'meta_boletos' => $rifa_data['cantidad_boletos'],
    'boletos_vendidos' => count($ocupados_raw),
    'imagenes' => $imagenes_rifa,
    'cifras' => $rifa_data['cifras'],
    'usa_cero' => $rifa_data['usa_cero']
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($rifa['titulo']); ?> - Rancho Las Trojes</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Lora:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link id="darkModeStylesheet" rel="stylesheet" href="assets/css/dark-mode.css" disabled>

    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .raffle-detail-section { padding: 3rem 0; margin-top: 2rem; background-color: var(--white); }
        .raffle-container { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 4rem; }
        @media (max-width: 900px) { .raffle-container { grid-template-columns: 1fr; gap: 2rem; } }
        .main-image-container { position: relative; overflow: hidden; aspect-ratio: 4/3; cursor: pointer; background: var(--off-white-light); border: 1px solid var(--divider); border-radius: 1.5rem; box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1); }
        .main-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .main-image-container:hover .main-image { transform: scale(1.02); }
        .thumbnails-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.8rem; margin-top: 1.5rem; }
        .thumbnail { overflow: hidden; aspect-ratio: 1/1; cursor: pointer; border: 2px solid transparent; border-radius: 0.75rem; opacity: 0.7; transition: all 0.2s; }
        .thumbnail.active, .thumbnail:hover { transform: translateY(-2px); border-color: var(--brown); opacity: 1; }
        .thumbnail img { width: 100%; height: 100%; object-fit: cover; }
        .raffle-title { margin-top: 1rem; font-family: 'Lora', serif; font-size: 2.2rem; line-height: 1.2; color: var(--black-blue); }
        .raffle-price { margin: 0.5rem 0; font-size: 2rem; font-weight: 700; color: var(--brown); }
        .raffle-timer-box { display: flex; gap: 1rem; width: fit-content; padding: 1rem; margin: 1.5rem 0; color: white; background: var(--black-blue); border-radius: 1rem; }
        .timer-unit { display: flex; flex-direction: column; min-width: 50px; text-align: center; }
        .timer-unit .num { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .timer-unit .label { margin-top: 4px; font-size: 0.7rem; text-transform: uppercase; opacity: 0.7; }
        .controls-wrapper { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: space-between; padding-bottom: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--divider); }
        .view-tabs { display: flex; gap: 0.5rem; padding: 4px; background: var(--off-white); border-radius: 8px; }
        .view-tab { display: flex; gap: 6px; align-items: center; padding: 0.5rem 1rem; font-weight: 600; color: #666; cursor: pointer; background: transparent; border: none; border-radius: 6px; transition: 0.2s; }
        .view-tab.active { color: var(--brown); background: white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        .view-tab:hover:not(.active) { color: var(--black-blue); background: rgba(255, 255, 255, 0.5); }
        .ticket-search { position: relative; flex: 1; max-width: 300px; }
        .ticket-search input { width: 100%; padding: 0.6rem 1rem 0.6rem 2.2rem; font-family: inherit; border: 1px solid var(--divider); border-radius: 8px; }
        .ticket-search i { position: absolute; top: 50%; left: 10px; color: #999; transform: translateY(-50%); }
        .quick-select { display: flex; gap: 0.5rem; align-items: center; padding: 0.8rem; margin-bottom: 1.5rem; background: #fff8f1; border: 1px solid #fed7aa; border-radius: 8px; }
        .btn-quick { padding: 4px 12px; font-size: 0.85rem; font-weight: 600; color: #c2410c; cursor: pointer; background: white; border: 1px solid #fdba74; border-radius: 20px; transition: 0.2s; }
        .btn-quick:hover { color: white; background: #c2410c; }
        .tickets-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 0.75rem; padding: 1rem 0; }
        @media (max-width: 768px) { .tickets-grid { grid-template-columns: repeat(8, 1fr); } }
        @media (max-width: 512px) { .tickets-grid { grid-template-columns: repeat(6, 1fr); } }
        .ticket-btn { aspect-ratio: 1; font-size: 0.95rem; font-weight: 700; color: #4b5563; cursor: pointer; background: white; border: 1px solid var(--divider); border-radius: 8px; transition: 0.2s; }
        .ticket-btn:hover:not(.sold):not(.pending) { color: var(--brown); background: #fff8f1; border-color: var(--brown); transform: scale(1.05); }
        .ticket-btn.selected { color: white; background: var(--brown); border-color: var(--brown); box-shadow: 0 4px 6px rgba(165, 42, 42, 0.3); transform: scale(1.1); }
        .ticket-btn.sold { color: #991b1b; cursor: not-allowed; background: #fee2e2; border-color: transparent; opacity: 0.5; }
        .ticket-btn.pending { color: #854d0e; cursor: not-allowed; background: #fef9c3; border-color: #fde047; opacity: 0.8; }
        .tickets-legend { display: flex; gap: 1.5rem; justify-content: center; padding-top: 1rem; margin-top: 1rem; font-size: 0.9rem; color: #666; border-top: 1px solid var(--divider); }
        .legend-item { display: flex; gap: 6px; align-items: center; }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot.available { background: white; border: 1px solid #ccc; }
        .dot.selected { background: var(--brown); }
        .dot.sold { background: #fee2e2; }
        .participants-list { display: none; max-height: 500px; overflow-y: auto; }
        .participants-list.active { display: block; }
        .participant-row { display: flex; justify-content: space-between; padding: 0.8rem; font-size: 0.9rem; border-bottom: 1px solid #eee; }
        .participant-row:last-child { border-bottom: none; }
        .p-number { padding: 2px 8px; font-weight: 700; color: var(--brown); background: #fff8f1; border-radius: 4px; }
        .selection-sidebar { position: sticky; top: 2rem; height: fit-content; padding: 1.5rem; background: var(--white); border: 1px solid var(--divider); border-radius: 1.25rem; box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.05); }
        .modal { display: none; position: fixed; top: 0; left: 0; z-index: 2000; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); }
        .modal-content-form { position: relative; width: 90%; max-width: 500px; padding: 2rem; margin: 5% auto; background-color: var(--white); border-radius: 1.5rem; animation: fadeIn 0.3s; }
        .selected-tickets-list { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .empty-selection-msg { width: 100%; font-size: 1rem; font-style: italic; color: #999; text-align: left; }
        .selected-ticket-tag { display: inline-flex; padding: 0.5rem 0.75rem; font-size: 1em; font-weight: 600; color: var(--white); background-color: var(--brown); border-radius: 0.75rem; transition: background-color 0.2s ease; }
        .btn-primary { background: var(--brown); color: var(--white); padding: 1rem 2rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 1em; font-weight: 600; transition: background-color 0.2s ease; width: 100%; }
        .btn-primary:hover { background: var(--black-blue); }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <section class="page-header-start container-wide">
        <img src="<?php echo $rifa['imagenes'][0]; ?>" alt="Fondo" style="filter: brightness(0.3) blur(2px);">
        <div class="page-header-overlay">
            <h1 class="page-header-title animated-text">Rifas Las Trojes</h1>
            <p class="page-header-subtitle">Participa y Gana Genética Pura</p>
        </div>
    </section>

    <div class="raffle-detail-section container-wide">

        <div class="raffle-container container">
            <div class="raffle-gallery">
                <div class="main-image-container">
                    <img src="<?php echo $rifa['imagenes'][0]; ?>" class="main-image" id="mainImage" alt="Premio Principal">
                </div>
                <div class="thumbnails-grid">
                    <?php foreach ($rifa['imagenes'] as $index => $img): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" onclick="selectImage(<?php echo $index; ?>)">
                            <img src="<?php echo $img; ?>" alt="Thumb">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="raffle-info">
                <span style="background:#dcfce7; color:#166534; padding:0.3rem 1rem; border-radius:20px; font-weight:700; font-size:0.8rem; text-transform:uppercase;">
                    <?php echo ucfirst($rifa['estado']); ?>
                </span>

                <h1 class="raffle-title"><?php echo htmlspecialchars($rifa['titulo']); ?></h1>

                <div class="raffle-price">
                    $<?php echo number_format($rifa['precio_boleto'], 2); ?>
                    <span style="font-size:1rem; color:#666; font-weight:500;">MXN / boleto</span>
                </div>

                <?php if ($rifa['fecha_sorteo']): ?>
                    <div class="raffle-timer-box" data-date="<?php echo $rifa['fecha_sorteo']; ?>">
                        <div class="timer-unit"><span class="num" id="days">00</span><span class="label">Días</span></div>
                        <div class="timer-unit"><span class="num" id="hours">00</span><span class="label">Hrs</span></div>
                        <div class="timer-unit"><span class="num" id="minutes">00</span><span class="label">Min</span></div>
                        <div class="timer-unit"><span class="num" id="seconds">00</span><span class="label">Seg</span></div>
                    </div>
                <?php endif; ?>

                <p style="line-height:1.6; color:#4b5563; margin-top:1rem;"><?php echo nl2br(htmlspecialchars($rifa['descripcion'])); ?></p>

                <div style="margin-top:2rem;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:0.5rem; font-weight:600;">
                        <span>Progreso</span>
                        <span><?php echo $rifa['boletos_vendidos']; ?> / <?php echo $rifa['meta_boletos']; ?> Vendidos</span>
                    </div>
                    <div style="width:100%; height:10px; background:#f3f4f6; border-radius:10px; overflow:hidden;">
                        <div style="height:100%; background:var(--brown); width: <?php echo ($rifa['meta_boletos'] > 0) ? ($rifa['boletos_vendidos'] / $rifa['meta_boletos']) * 100 : 0; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container" style="display:grid; grid-template-columns: 2fr 1fr; gap:3rem;">

            <div>
                <div class="controls-wrapper">
                    <div class="view-tabs">
                        <button class="view-tab active" id="tab-tickets" onclick="switchView('tickets')">
                            <i class="fas fa-ticket-alt"></i> Boletos
                        </button>
                        <button class="view-tab" id="tab-participants" onclick="switchView('participants')">
                            <i class="fas fa-users"></i> Participantes
                        </button>
                    </div>
                    <div class="ticket-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar número..." onkeyup="filterTickets()">
                    </div>
                </div>

                <div class="quick-select" id="quickSelectContainer">
                    <span style="font-weight:600; font-size:0.9rem; color:#9a3412;">Suerte Rápida:</span>
                    <button class="btn-quick" onclick="randomSelect(1)">1 Boleto</button>
                    <button class="btn-quick" onclick="randomSelect(3)">3 Boletos</button>
                    <button class="btn-quick" onclick="randomSelect(5)">5 Boletos</button>
                </div>

                <div class="tickets-legend" id="ticketsLegend">
                    <div class="legend-item">
                        <div class="dot available"></div> Disponible
                    </div>
                    <div class="legend-item">
                        <div class="dot selected"></div> Tu Selección
                    </div>
                    <div class="legend-item">
                        <div class="dot sold"></div> Vendido / Ocupado
                    </div>
                </div>

                <div id="gridContainer" class="tickets-grid">
                    <?php
                    $inicio = ($rifa['usa_cero']) ? 0 : 1;
                    $fin = ($rifa['usa_cero']) ? $rifa['meta_boletos'] - 1 : $rifa['meta_boletos'];

                    for ($i = $inicio; $i <= $fin; $i++):
                        $estado_clase = '';
                        $disabled = '';
                        $onclick = 'onclick="toggleTicket(this)"';

                        if (isset($mapa_ocupados[$i])) {
                            $estado_bd = $mapa_ocupados[$i];
                            $estado_clase = ($estado_bd === 'pagado') ? 'sold' : 'pending';
                            $disabled = 'disabled'; 
                            $onclick = ''; 
                        }

                        $numero_visual = str_pad($i, $rifa['cifras'], '0', STR_PAD_LEFT);
                    ?>
                        <button class="ticket-btn <?php echo $estado_clase; ?>"
                            data-number="<?php echo $numero_visual; ?>"
                            <?php echo $onclick; ?>>
                            <?php echo $numero_visual; ?>
                        </button>
                    <?php endfor; ?>
                </div>

                <div id="participantsContainer" class="participants-list">
                    <?php if (count($lista_participantes) > 0): ?>
                        <?php foreach ($lista_participantes as $p): ?>
                            <div class="participant-row">
                                <div>
                                    <span class="p-number">#<?php echo $p['numero']; ?></span>
                                    <span style="font-weight:600; margin-left:10px;"><?php echo htmlspecialchars($p['nombre']); ?></span>
                                </div>
                                <div style="color:#666; font-size:0.8rem;">
                                    <?php echo htmlspecialchars($p['estado']); ?>
                                    (<?php echo ucfirst($p['status']); ?>)
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; padding:2rem; color:#888;">Aún no hay participantes registrados.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="selection-sidebar">
                <h3 style="font-family:'Lora',serif; margin-bottom:1rem;">Tu Selección</h3>

                <div id="selectedList" class="selected-tickets-list">
                    <p class="empty-selection-msg">Ningún boleto seleccionado</p>
                </div>

                <div style="border-top:1px solid #eee; padding-top:1rem; margin-top:1rem;">
                    <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1.2rem; color:var(--black-blue);">
                        <span>Total:</span>
                        <span id="totalLabel">$0.00</span>
                    </div>
                </div>

                <button id="btnApartar" class="btn-primary" disabled onclick="openCheckoutModal()">
                    Apartar Boletos
                </button>
                <button onclick="clearSelection()" style="margin-top:10px; width:100%; border:none; background:transparent; color:#666; cursor:pointer; text-decoration:underline;">Limpiar selección</button>
            </div>
        </div>
    </div>

    <div id="checkoutModal" class="modal">
        <div class="modal-content-form">
            <span onclick="closeCheckoutModal()" style="position:absolute; right:1.5rem; top:1.5rem; cursor:pointer; font-size:1.5rem;">&times;</span>
            <h2 style="font-family:'Lora', serif; margin-bottom:1.5rem;">Datos de Reserva</h2>

            <form id="reserveForm">
                <input type="hidden" id="rifaId" value="<?php echo $rifa['id']; ?>">

                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" id="nombreCliente" class="form-control" required placeholder="Tu nombre">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono (WhatsApp)</label>
                    <input type="tel" id="telCliente" class="form-control" required placeholder="55 1234 5678">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado / Ciudad</label>
                    <input type="text" id="estadoCliente" class="form-control" required placeholder="Ej. Jalisco">
                </div>

                <div style="background:#f9f9f9; padding:1rem; border-radius:0.5rem; margin:1.5rem 0;">
                    <p><strong>Boletos:</strong> <span id="modalTicketNumbers"></span></p>
                    <p><strong>Total a Pagar:</strong> <span id="modalTotal" style="color:var(--brown); font-weight:bold;"></span></p>
                </div>

                <button type="submit" class="btn-primary" style="width:100%;">Confirmar Reserva</button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        const ticketPrice = <?php echo $rifa['precio_boleto']; ?>;
        const images = <?php echo json_encode($rifa['imagenes']); ?>;
        // URL API Rifas
        const API_URL = "https://rifas.rancholastrojes.com.mx/api/reservar.php";

        function selectImage(index) {
            const mainImg = document.getElementById('mainImage');
            mainImg.style.opacity = '0';
            setTimeout(() => {
                mainImg.src = images[index];
                mainImg.style.opacity = '1';
            }, 200);
            document.querySelectorAll('.thumbnail').forEach((t, i) => {
                t.classList.toggle('active', i === index);
            });
        }

        function initTimer() {
            const timerBox = document.querySelector('.raffle-timer-box');
            if (!timerBox) return;
            const endDate = new Date(timerBox.dataset.date).getTime();
            const timerInterval = setInterval(function() {
                const now = new Date().getTime();
                const distance = endDate - now;
                if (distance < 0) {
                    clearInterval(timerInterval);
                    timerBox.innerHTML = "<div style='text-align:center; width:100%; font-weight:bold;'>¡Sorteo Iniciado!</div>";
                    return;
                }
                document.getElementById("days").innerText = String(Math.floor(distance / (1000 * 60 * 60 * 24))).padStart(2, '0');
                document.getElementById("hours").innerText = String(Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                document.getElementById("minutes").innerText = String(Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                document.getElementById("seconds").innerText = String(Math.floor((distance % (1000 * 60)) / 1000)).padStart(2, '0');
            }, 1000);
        }
        initTimer();

        function switchView(viewName) {
            document.querySelectorAll('.view-tab').forEach(t => t.classList.remove('active'));
            document.getElementById('tab-' + viewName).classList.add('active');
            const grid = document.getElementById('gridContainer');
            const list = document.getElementById('participantsContainer');
            const quick = document.getElementById('quickSelectContainer');
            const legend = document.getElementById('ticketsLegend');
            if (viewName === 'tickets') {
                grid.style.display = 'grid';
                quick.style.display = 'flex';
                legend.style.display = 'flex';
                list.classList.remove('active');
            } else {
                grid.style.display = 'none';
                quick.style.display = 'none';
                legend.style.display = 'none';
                list.classList.add('active');
            }
        }

        function filterTickets() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const btns = document.querySelectorAll('.ticket-btn');
            btns.forEach(btn => {
                const num = btn.getAttribute('data-number');
                if (num.includes(query)) {
                    btn.style.display = 'block';
                } else {
                    btn.style.display = 'none';
                }
            });
        }

        let selectedTickets = [];

        function toggleTicket(btn) {
            if (btn.classList.contains('sold') || btn.classList.contains('pending')) return;
            const number = btn.getAttribute('data-number');
            if (selectedTickets.includes(number)) {
                selectedTickets = selectedTickets.filter(n => n !== number);
                btn.classList.remove('selected');
            } else {
                if (selectedTickets.length >= 20) {
                    alert("Máximo 20 boletos por reserva.");
                    return;
                }
                selectedTickets.push(number);
                btn.classList.add('selected');
            }
            updateSidebar();
        }

        function randomSelect(count) {
            clearSelection();
            const availableBtns = Array.from(document.querySelectorAll('.ticket-btn:not(.sold):not(.pending)'));
            if (availableBtns.length < count) {
                alert("No hay suficientes boletos disponibles.");
                return;
            }
            availableBtns.sort(() => Math.random() - 0.5);
            const toSelect = availableBtns.slice(0, count);
            toSelect.forEach(btn => {
                const number = btn.getAttribute('data-number');
                selectedTickets.push(number);
                btn.classList.add('selected');
            });
            updateSidebar();
        }

        function clearSelection() {
            selectedTickets = [];
            document.querySelectorAll('.ticket-btn.selected').forEach(b => b.classList.remove('selected'));
            updateSidebar();
        }

        function updateSidebar() {
            const list = document.getElementById('selectedList');
            const btn = document.getElementById('btnApartar');
            if (selectedTickets.length === 0) {
                list.innerHTML = '<p class="empty-selection-msg">Ningún boleto seleccionado</p>';
                btn.disabled = true;
                document.getElementById('totalLabel').innerText = "$0.00";
                return;
            }
            selectedTickets.sort();
            list.innerHTML = selectedTickets.map(num => `<span class="selected-ticket-tag">${num}</span>`).join('');
            const total = selectedTickets.length * ticketPrice;
            document.getElementById('totalLabel').innerText = "$" + total.toLocaleString('en-US', { minimumFractionDigits: 2 });
            btn.disabled = false;
        }

        function openCheckoutModal() {
            document.getElementById('modalTicketNumbers').innerText = selectedTickets.join(', ');
            document.getElementById('modalTotal').innerText = document.getElementById('totalLabel').innerText;
            document.getElementById('checkoutModal').style.display = 'block';
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').style.display = 'none';
        }
        
        document.getElementById('reserveForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerText = "Procesando...";

            const nombre = document.getElementById('nombreCliente').value;
            const telefono = document.getElementById('telCliente').value;
            const estado = document.getElementById('estadoCliente').value;
            const rifaId = document.getElementById('rifaId').value;

            // --- CAMBIO CLAVE: UNA SOLA PETICIÓN AL SERVIDOR ---
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        rifa_id: rifaId,
                        boletos: selectedTickets, // Enviamos el ARRAY completo
                        nombre: nombre,
                        telefono: telefono,
                        estado: estado
                    })
                });

                const textResponse = await response.text();
                let result;
                
                try {
                    result = JSON.parse(textResponse);
                } catch (e) {
                    console.error("Respuesta no es JSON:", textResponse);
                    throw new Error("El servidor devolvió un formato inválido.");
                }

                if (result.success) {
                    // Éxito total
                    alert(`¡Reserva exitosa! Se han apartado ${result.reservados.length} boletos. Te contactaremos por WhatsApp.`);
                    window.location.reload();
                } else {
                    // Manejo de errores parciales o totales
                    if(result.reservados && result.reservados.length > 0) {
                        alert(`Se apartaron ${result.reservados.length} boletos, pero hubo errores con otros. Revisa los mensajes.`);
                        window.location.reload();
                    } else {
                        console.error(result);
                        alert("No se pudo completar la reserva: " + (result.message || "Error desconocido"));
                        btn.disabled = false;
                        btn.innerText = "Confirmar Reserva";
                    }
                }

            } catch (error) {
                console.error(error);
                alert("Error de conexión al procesar la solicitud.");
                btn.disabled = false;
                btn.innerText = "Confirmar Reserva";
            }
        });
    </script>
</body>
</html>