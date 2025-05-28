<?php
// panel_conductor/panelC.php
session_start();
require_once '../includes/db_connection.php';

// Redireccionar si no es conductor o no está logueado
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'conductor') {
    header('Location: ../index.php');
    exit();
}

$conn = connectDB();
if (!$conn) {
    die("Error de conexión a la base de datos.");
}

$user_id = $_SESSION['user_id'];
$conductor_data = null;
$ruta_data = null;
$estudiantes_ruta = [];

// Obtener datos del conductor y su ruta asignada
$stmt = $conn->prepare("SELECT c.*, r.nombre AS ruta_nombre, r.descripcion AS ruta_descripcion, r.coordenadas AS ruta_coordenadas,
                        v.modelo, v.placa, v.capacidad
                        FROM conductores c
                        LEFT JOIN rutas r ON c.id_ruta_asignada = r.id
                        LEFT JOIN vehiculos v ON c.id_vehiculo_asignado = v.id
                        WHERE c.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $conductor_data = $result->fetch_assoc();
    if ($conductor_data['id_ruta_asignada']) {
        $ruta_data = [
            'id' => $conductor_data['id_ruta_asignada'],
            'nombre' => $conductor_data['ruta_nombre'],
            'descripcion' => $conductor_data['ruta_descripcion'],
            'coordenadas' => $conductor_data['ruta_coordenadas'] // JSON string
        ];

        // Obtener estudiantes asignados a la misma INSTITUCIÓN que el conductor (y/o ruta)
        $stmt_estudiantes = $conn->prepare("SELECT e.id, u.username, e.nombre, e.cedula, e.institucion, e.correo, e.foto_perfil
                                            FROM estudiantes e
                                            JOIN usuarios u ON e.id = u.id
                                            WHERE e.institucion = ?"); // Filtrar por institución del conductor
        $stmt_estudiantes->bind_param("s", $conductor_data['institucion_educativa']);
        $stmt_estudiantes->execute();
        $result_estudiantes = $stmt_estudiantes->get_result();
        while ($row = $result_estudiantes->fetch_assoc()) {
            $estudiantes_ruta[] = $row;
        }
        $stmt_estudiantes->close();

    }
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIRUTA - Panel de Conductor</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="./panelC.css" />
    <link rel="icon" href="../img/logo.png" type="image/png">
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="header-content">
                <div class="logo-brand">
                    <img src="../img/logo.png" alt="MIRUTA" class="brand-logo">
                    <h1 class="brand-title">MIRUTA <span class="brand-subtitle">Conductor</span></h1>
                </div>
                <nav class="user-nav">
                    <div class="user-profile">
                        <img src="<?php echo htmlspecialchars($conductor_data['foto_perfil'] ?? '../assets/user-avatar.jpg'); ?>" alt="Usuario" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Conductor'); ?></span>
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
                    <h3 class="sidebar-title"><i class="fas fa-info-circle"></i> Mi Información</h3>
                    <?php if ($conductor_data): ?>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($conductor_data['nombre']); ?></p>
                        <p><strong>Cédula:</strong> <?php echo htmlspecialchars($conductor_data['cedula']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($conductor_data['telefono']); ?></p>
                        <p><strong>Licencia:</strong> <?php echo htmlspecialchars($conductor_data['licencia']); ?></p>
                        <p><strong>Matrícula Autobús:</strong> <?php echo htmlspecialchars($conductor_data['matricula_autobus']); ?></p>
                        <p><strong>Institución:</strong> <?php echo htmlspecialchars($conductor_data['institucion_educativa']); ?></p>
                        <p><strong>Vehículo:</strong> <?php echo htmlspecialchars($conductor_data['modelo'] ?? 'N/A') . ' (' . htmlspecialchars($conductor_data['placa'] ?? 'N/A') . ')'; ?></p>
                        <p><strong>Ruta Asignada:</strong> <?php echo htmlspecialchars($ruta_data['nombre'] ?? 'Ninguna'); ?></p>
                    <?php else: ?>
                        <p>No se encontraron datos de conductor.</p>
                    <?php endif; ?>
                </div>

                </aside>

            <section class="content-area">
                <h2>Ruta Asignada: <?php echo htmlspecialchars($ruta_data['nombre'] ?? 'Ninguna'); ?></h2>
                <p><?php echo htmlspecialchars($ruta_data['descripcion'] ?? 'No hay descripción para la ruta.'); ?></p>
                <div id="map-conductor" style="height: 400px; width: 100%;"></div>

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

                <h2 style="margin-top: 30px;">Lista de Estudiantes para Toma de Asistencia</h2>
                <?php if (!empty($estudiantes_ruta)): ?>
                    <form id="attendance-form" action="./procesar_toma_lista.php" method="POST">
                        <input type="hidden" name="id_conductor" value="<?php echo htmlspecialchars($user_id); ?>">
                        <input type="hidden" name="id_ruta" value="<?php echo htmlspecialchars($ruta_data['id'] ?? ''); ?>">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID Estudiante</th>
                                        <th>Nombre</th>
                                        <th>Institución</th>
                                        <th>Asistencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estudiantes_ruta as $estudiante): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($estudiante['id']); ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['institucion']); ?></td>
                                            <td>
                                                <input type="checkbox" name="asistencia[<?php echo htmlspecialchars($estudiante['id']); ?>]" value="presente">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top: 20px;"><i class="fas fa-check-circle"></i> Guardar Asistencia</button>
                    </form>
                <?php else: ?>
                    <p>No hay estudiantes asignados a rutas de tu institución para tomar asistencia.</p>
                <?php endif; ?>
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
        const rutaDataConductor = <?php echo json_encode($ruta_data); ?>;
    </script>
    <script src="./js/mapa_rutas_conductor.js"></script>
</body>
</html>
<?php $conn->close(); ?>