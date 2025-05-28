<?php
// panel_estudiante/panelE.php
session_start();
require_once '../includes/db_connection.php';

// Redireccionar si no es estudiante o no está logueado
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header('Location: ../index.php');
    exit();
}

$conn = connectDB();
if (!$conn) {
    die("Error de conexión a la base de datos.");
}

$user_id = $_SESSION['user_id'];
$estudiante_data = null;
$ruta_data = null;

// Obtener datos del estudiante y su ruta asignada
$stmt = $conn->prepare("SELECT e.*, r.nombre AS ruta_nombre, r.descripcion AS ruta_descripcion, r.coordenadas AS ruta_coordenadas
                        FROM estudiantes e
                        LEFT JOIN rutas r ON e.id_ruta_asignada = r.id
                        WHERE e.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $estudiante_data = $result->fetch_assoc();
    if ($estudiante_data['id_ruta_asignada']) {
        $ruta_data = [
            'id' => $estudiante_data['id_ruta_asignada'],
            'nombre' => $estudiante_data['ruta_nombre'],
            'descripcion' => $estudiante_data['ruta_descripcion'],
            'coordenadas' => $estudiante_data['ruta_coordenadas'] // JSON string
        ];
    }
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIRUTA - Panel de Estudiante</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="./panelE.css" />
    <link rel="icon" href="../img/logo.png" type="image/png">
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="header-content">
                <div class="logo-brand">
                    <img src="../img/logo.png" alt="MIRUTA" class="brand-logo">
                    <h1 class="brand-title">MIRUTA <span class="brand-subtitle">Estudiante</span></h1>
                </div>
                <nav class="user-nav">
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($estudiante_data['foto_perfil'] ?? '../assets/user-avatar.jpg'); ?>" alt="Usuario" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Estudiante'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                        <div class="dropdown-menu">
                            <a href="#">Mi Perfil</a>
                            <a href="../logout.php">Cerrar Sesión</a>
                        </div>
                    </div>
                </nav>
            </div>
        </header>

        <main class="app-main">
            <aside class="app-sidebar">
                <div class="sidebar-section">
                    <h3 class="sidebar-title"><i class="fas fa-info-circle"></i> Información Personal</h3>
                    <?php if ($estudiante_data): ?>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($estudiante_data['nombre']); ?></p>
                        <p><strong>Cédula:</strong> <?php echo htmlspecialchars($estudiante_data['cedula']); ?></p>
                        <p><strong>Correo:</strong> <?php echo htmlspecialchars($estudiante_data['correo']); ?></p> <p><strong>Institución:</strong> <?php echo htmlspecialchars($estudiante_data['institucion']); ?></p>
                        <?php if ($estudiante_data['id_ruta_asignada']): ?>
                            <p><strong>Ruta Asignada:</strong> <?php echo htmlspecialchars($ruta_data['nombre']); ?></p>
                        <?php else: ?>
                            <p>No tienes una ruta asignada aún.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No se encontraron datos de estudiante.</p>
                    <?php endif; ?>
                </div>

                <div class="sidebar-section">
                    <h3 class="sidebar-title"><i class="fas fa-user-tie"></i> Información del Conductor</h3>
                    <?php
                    // Obtener información del conductor asignado a la ruta (si hay ruta)
                    $conductor_data = null;
                    if ($ruta_data) {
                        $stmt_conductor = $conn->prepare("SELECT c.nombre, c.telefono, c.licencia, c.matricula_autobus, c.foto_perfil, c.foto_autobus, v.modelo, v.placa
                                                        FROM conductores c
                                                        LEFT JOIN vehiculos v ON c.id_vehiculo_asignado = v.id
                                                        WHERE c.id_ruta_asignada = ? LIMIT 1");
                        $stmt_conductor->bind_param("s", $ruta_data['id']);
                        $stmt_conductor->execute();
                        $result_conductor = $stmt_conductor->get_result();
                        if ($result_conductor->num_rows === 1) {
                            $conductor_data = $result_conductor->fetch_assoc();
                        }
                        $stmt_conductor->close();
                    }
                    ?>
                    <?php if ($conductor_data): ?>
                        <div class="driver-profile">
                            <img src="<?php echo htmlspecialchars($conductor_data['foto_perfil'] ?? '../assets/user-avatar.jpg'); ?>" alt="Conductor" class="driver-avatar">
                            <div class="driver-details">
                                <h4 class="driver-name"><?php echo htmlspecialchars($conductor_data['nombre']); ?></h4>
                                <p class="driver-id"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($conductor_data['telefono']); ?></p>
                                <p class="driver-id"><i class="fas fa-id-card"></i> Licencia: <?php echo htmlspecialchars($conductor_data['licencia']); ?></p>
                            </div>
                        </div>
                        <div class="vehicle-info">
                            <h4><i class="fas fa-car"></i> Vehículo Asignado</h4>
                            <img src="<?php echo htmlspecialchars($conductor_data['foto_autobus'] ?? '../assets/bus_default.jpg'); ?>" alt="Vehículo" class="vehicle-image">
                            <div class="vehicle-details">
                                <p><strong>Modelo:</strong> <?php echo htmlspecialchars($conductor_data['modelo'] ?? 'N/A'); ?></p>
                                <p><strong>Placa:</strong> <?php echo htmlspecialchars($conductor_data['placa'] ?? 'N/A'); ?></p>
                                <p><strong>Matrícula Autobús:</strong> <?php echo htmlspecialchars($conductor_data['matricula_autobus']); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>No hay información de conductor disponible para tu ruta.</p>
                    <?php endif; ?>
                </div>

                </aside>

            <section class="map-area">
                <h2>Ruta Asignada: <?php echo htmlspecialchars($ruta_data['nombre'] ?? 'Ninguna'); ?></h2>
                <p><?php echo htmlspecialchars($ruta_data['descripcion'] ?? 'No hay descripción para la ruta.'); ?></p>
                <div id="map-student" style="height: 600px; width: 100%;"></div>

                <div class="instructions-panel" id="instructionsPanel" style="display: none;">
                    <div class="panel-header">
                        <h3><i class="fas fa-list-ol"></i> Instrucciones de Ruta</h3>
                        <button class="panel-close" onclick="toggleInstructions()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="panel-content" id="instructions-container">
                        <div class="no-route-selected">
                            <i class="fas fa-route"></i>
                            <p>Cargando instrucciones...</p>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <div class="route-summary">
                            <div class="summary-item">
                                <i class="fas fa-road"></i>
                                <span id="distance">-- km</span>
                            </div>
                            <div class="summary-item">
                                <i class="fas fa-clock"></i>
                                <span id="duration">-- min</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-routing-machine/3.2.12/leaflet-routing-machine.min.js"></script>
    <script>
        // Script para el dropdown del usuario
        document.querySelector('.user-profile').addEventListener('click', function() {
            document.querySelector('.dropdown-menu').classList.toggle('show');
        });
        window.onclick = function(event) {
            if (!event.target.matches('.user-profile, .user-profile *')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        // Datos de la ruta para el JS del mapa
        const rutaData = <?php echo json_encode($ruta_data); ?>;
        // La institución del estudiante (para filtrar futuras rutas si fuera necesario)
        const estudianteInstitucion = "<?php echo htmlspecialchars($estudiante_data['institucion'] ?? ''); ?>";
    </script>
    <script src="./js/mapa_rutas.js"></script>
</body>
</html>
<?php $conn->close(); ?>