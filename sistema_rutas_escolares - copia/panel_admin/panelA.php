<?php
// panelA.php
session_start(); // Iniciar sesión para gestionar el logout
require_once '../includes/db_connection.php';

// Redireccionar si no es admin o no está logueado
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$conn = connectDB();
if (!$conn) {
    die("Error de conexión a la base de datos.");
}

// Variables para mensajes
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Lógica de módulos (usuarios, rutas, vehiculos, etc.)
$module = $_GET['module'] ?? 'dashboard'; // Módulo por defecto

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIRUTA - Panel de Administrador</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="./panelA.css" />
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="header-content">
                <div class="logo-brand">
                    <img src="../img/logo.png" alt="MIRUTA" class="brand-logo">
                    <h1 class="brand-title">MIRUTA <span class="brand-subtitle">Administrador</span></h1>
                </div>
                <nav class="user-nav">
                    <div class="user-profile">
                        <img src="<?php echo $_SESSION['user_avatar'] ?? '../assets/admin-avatar.png'; ?>" alt="Usuario" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
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
                    <h3 class="sidebar-title"><i class="fas fa-cogs"></i> Administración</h3>
                    <ul class="sidebar-menu">
                        <li><a href="?module=dashboard" class="<?php echo ($module == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="?module=usuarios" class="<?php echo ($module == 'usuarios') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Gestión de Usuarios</a></li>
                        <li><a href="?module=rutas" class="<?php echo ($module == 'rutas') ? 'active' : ''; ?>"><i class="fas fa-route"></i> Gestión de Rutas</a></li>
                        <li><a href="?module=vehiculos" class="<?php echo ($module == 'vehiculos') ? 'active' : ''; ?>"><i class="fas fa-bus"></i> Gestión de Vehículos</a></li>
                        <li><a href="?module=conductores" class="<?php echo ($module == 'conductores') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Gestión de Conductores</a></li>
                        <li><a href="?module=estudiantes" class="<?php echo ($module == 'estudiantes') ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> Gestión de Estudiantes</a></li>
                        <li><a href="?module=horarios" class="<?php echo ($module == 'horarios') ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Gestión de Horarios</a></li>
                        <li><a href="?module=mapa_coordenadas" class="<?php echo ($module == 'mapa_coordenadas') ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i> Mapa Coordenadas</a></li>
                    </ul>
                </div>
                </aside>

            <section class="content-area">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php
                switch ($module) {
                    case 'dashboard':
                        echo '<h2>Dashboard Administrativo</h2>';
                        echo '<p>Bienvenido al panel de administración. Seleccione una opción del menú lateral para gestionar el sistema.</p>';
                        // Puedes añadir estadísticas aquí
                        break;
                    case 'usuarios':
                        include 'modules/usuarios_module.php'; // Incluir el módulo de gestión de usuarios
                        break;
                    case 'rutas':
                        include 'modules/rutas_module.php'; // Incluir el módulo de gestión de rutas
                        break;
                    case 'vehiculos':
                        include 'modules/vehiculos_module.php'; // Incluir el módulo de gestión de vehículos
                        break;
                    case 'conductores':
                        include 'modules/conductores_module.php'; // Incluir el módulo de gestión de conductores
                        break;
                    case 'estudiantes':
                        include 'modules/estudiantes_module.php'; // Incluir el módulo de gestión de estudiantes
                        break;
                    case 'horarios':
                        include 'modules/horarios_module.php'; // Incluir el módulo de gestión de horarios
                        break;
                    case 'mapa_coordenadas':
                        include 'mapa_coordenadas.php'; // Incluir el nuevo módulo de mapa
                        break;
                    default:
                        echo '<p>Módulo no encontrado.</p>';
                        break;
                }
                ?>
            </section>
        </main>
    </div>

    <script src="../app.js"></script> <script>
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
    </script>
    <?php if ($module == 'mapa_coordenadas'): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="js/mapa_coordenadas.js"></script>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
header('Content-Type: text/html; charset=utf-8'); // Establecer el tipo de contenido como HTML

// Incluir el archivo de conexión a la base de datos
require_once('../includes/db_connection.php'); // Ajusta la ruta si es diferente

// --- Lógica del Backend (PHP) ---
// Si hay una solicitud AJAX, procesarla y salir
if (isset($_GET['action'])) {
    header('Content-Type: application/json'); // Para las respuestas AJAX
    $action = $_GET['action'];
    $method = $_SERVER['REQUEST_METHOD'];

    // Determinar si los datos vienen de JSON o FormData (para archivos)
    $data = [];
    if ($method === 'POST') {
        if (strpos($_SERVER['Content-Type'], 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            $data = $_POST; // Datos de formulario POST o FormData
        }
    }

    $mysqli = connectDB();

    if (!$mysqli && $action !== 'logout') {
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos.']);
        exit;
    }

    // Directorio para subir archivos (relativo a la raíz del proyecto)
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Crea la carpeta si no existe
    }

    switch ($action) {
        case 'estudiantes':
            if ($method === 'GET') {
                $result = $mysqli->query('SELECT id, cedula, nombre, institucion, correo, id_ruta_asignada, foto_perfil FROM estudiantes');
                $estudiantes = [];
                while ($row = $result->fetch_assoc()) {
                    $estudiantes[] = $row;
                }
                echo json_encode($estudiantes);
            } elseif ($method === 'POST') {
                if (!$data) {
                    echo json_encode(['success' => false, 'error' => 'No data']);
                    exit;
                }
                // Manejar subida de foto de perfil
                $foto_perfil_path = null;
                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('profile_') . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                        $foto_perfil_path = "uploads/" . $file_name; // Ruta relativa para la base de datos
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Error al subir la foto de perfil.']);
                        exit;
                    }
                }

                $hashed_password = password_hash($data['contrasena'], PASSWORD_DEFAULT); // Hash de la contraseña

                // Iniciar transacción para insertar en usuarios y estudiantes
                $mysqli->begin_transaction();
                try {
                    // Insertar en la tabla `usuarios`
                    $stmt_user = $mysqli->prepare("INSERT INTO usuarios (username, password_hash, email, rol) VALUES (?, ?, ?, 'estudiante')");
                    $stmt_user->bind_param("sss", $data['nombre'], $hashed_password, $data['correo']);
                    if (!$stmt_user->execute()) {
                        throw new Exception("Error al crear usuario: " . $stmt_user->error);
                    }
                    $user_id = $mysqli->insert_id;
                    $stmt_user->close();

                    // Insertar en la tabla `estudiantes`
                    $stmt_estudiante = $mysqli->prepare('INSERT INTO estudiantes (id, cedula, nombre, institucion, correo, id_ruta_asignada, foto_perfil) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt_estudiante->bind_param('issssss', $user_id, $data['cedula'], $data['nombre'], $data['institucion'], $data['correo'], $data['id_ruta_asignada'], $foto_perfil_path);
                    if (!$stmt_estudiante->execute()) {
                         throw new Exception("Error al insertar estudiante: " . $stmt_estudiante->error);
                    }
                    $stmt_estudiante->close();

                    $mysqli->commit();
                    echo json_encode(['success' => true]);

                } catch (Exception $e) {
                    $mysqli->rollback();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            }
            break;

        case 'conductores':
            if ($method === 'GET') {
                $result = $mysqli->query('SELECT id, cedula, nombre, licencia, telefono, matricula_autobus, institucion_educativa, foto_perfil, foto_autobus, id_vehiculo_asignado, id_ruta_asignada FROM conductores');
                $conductores = [];
                while ($row = $result->fetch_assoc()) {
                    $conductores[] = $row;
                }
                echo json_encode($conductores);
            } elseif ($method === 'POST') {
                if (!$data) {
                    echo json_encode(['success' => false, 'error' => 'No data']);
                    exit;
                }

                // Manejar subida de foto de perfil
                $foto_perfil_path = null;
                if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('profile_') . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                        $foto_perfil_path = "uploads/" . $file_name;
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Error al subir la foto de perfil del conductor.']);
                        exit;
                    }
                }

                // Manejar subida de foto de autobús
                $foto_autobus_path = null;
                if (isset($_FILES['foto_autobus']) && $_FILES['foto_autobus']['error'] === UPLOAD_ERR_OK) {
                    $file_extension = pathinfo($_FILES['foto_autobus']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid('bus_') . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['foto_autobus']['tmp_name'], $target_file)) {
                        $foto_autobus_path = "uploads/" . $file_name;
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Error al subir la foto del autobús.']);
                        exit;
                    }
                }

                // Aquí, el ID de conductor que viene del formulario de `panelA` es el ID de usuario.
                // El formulario de `panelA` debería requerir el ID de un usuario existente.
                $stmt = $mysqli->prepare('INSERT INTO conductores (id, cedula, nombre, licencia, telefono, matricula_autobus, institucion_educativa, foto_perfil, foto_autobus, id_vehiculo_asignado, id_ruta_asignada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('issssssssis',
                    $data['id'],
                    $data['cedula'],
                    $data['nombre'],
                    $data['licencia'],
                    $data['telefono'],
                    $data['matricula_autobus'],
                    $data['institucion_educativa'],
                    $foto_perfil_path,
                    $foto_autobus_path,
                    $data['id_vehiculo_asignado'],
                    $data['id_ruta_asignada']
                );
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'error' => $mysqli->error]); // Añadir error de MySQL
            }
            break;

        case 'horarios':
            if ($method === 'GET') {
                // CORRECCIÓN: Usar 'escuela' y 'id_vehiculo' y 'id_conductor' de tu DB
                $result = $mysqli->query('SELECT id, id_ruta, escuela, id_vehiculo, id_conductor, hora_salida, hora_llegada FROM horarios');
                $horarios = [];
                while ($row = $result->fetch_assoc()) {
                    $horarios[] = $row;
                }
                echo json_encode($horarios);
            } elseif ($method === 'POST') {
                if (!$data) {
                    echo json_encode(['success' => false, 'error' => 'No data']);
                    exit;
                }
                // 'id' es AUTO_INCREMENT, no se debe incluir en el INSERT
                $stmt = $mysqli->prepare('INSERT INTO horarios (id_ruta, escuela, id_vehiculo, id_conductor, hora_salida, hora_llegada) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssiss', $data['id_ruta'], $data['escuela'], $data['id_vehiculo'], $data['id_conductor'], $data['hora_salida'], $data['hora_llegada']);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'error' => $mysqli->error]);
            }
            break;

        case 'rutas':
            if ($method === 'GET') {
                $result = $mysqli->query('SELECT id, nombre, descripcion, coordenadas FROM rutas');
                $rutas = [];
                while ($row = $result->fetch_assoc()) {
                    $rutas[] = $row;
                }
                echo json_encode($rutas);
            } elseif ($method === 'POST') {
                if (!$data) {
                    echo json_encode(['success' => false, 'error' => 'No data']);
                    exit;
                }
                // Validar que las coordenadas sean JSON válido antes de insertar
                if (!json_decode($data['coordenadas'])) {
                    echo json_encode(['success' => false, 'error' => 'Coordenadas no son un JSON válido.']);
                    exit;
                }

                $stmt = $mysqli->prepare('INSERT INTO rutas (id, nombre, descripcion, coordenadas) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $data['id'], $data['nombre'], $data['descripcion'], $data['coordenadas']);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'error' => $mysqli->error]);
            }
            break;

        case 'usuarios':
            if ($method === 'GET') {
                $result = $mysqli->query('SELECT id, username, rol, email FROM usuarios'); // Coincide con tu DB
                $usuarios = [];
                while ($row = $result->fetch_assoc()) {
                    $usuarios[] = $row;
                }
                echo json_encode($usuarios);
            } elseif ($method === 'POST') {
                if (!$data) {
                    echo json_encode(['success' => false, 'error' => 'No data']);
                    exit;
                }
                $hashed_password = password_hash($data['contrasena'], PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare('INSERT INTO usuarios (id, username, password_hash, email, rol) VALUES (?, ?, ?, ?, ?)'); // Coincide con tu DB
                $stmt->bind_param('issss', $data['id'], $data['username'], $hashed_password, $data['email'], $data['rol']);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'error' => $mysqli->error]);
            }
            break;

        case 'vehiculos':
            if ($method === 'GET') {
                $result = $mysqli->query('SELECT id, placa, modelo, capacidad, estado FROM vehiculos');
                $vehiculos = [];
                while ($row = $result->fetch_assoc()) {
                    $vehiculos[] = $row;
                }
                echo json_encode($vehiculos);
            } elseif ($method === 'POST') {
                if (!$data) {
                    echo json_encode(['success' => false, 'error' => 'No data']);
                    exit;
                }
                $stmt = $mysqli->prepare('INSERT INTO vehiculos (id, placa, modelo, capacidad, estado) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssis', $data['id'], $data['placa'], $data['modelo'], $data['capacidad'], $data['estado']);
                $success = $stmt->execute();
                $stmt->close();
                echo json_encode(['success' => $success, 'error' => $mysqli->error]);
            }
            break;

        case 'logout':
            session_destroy();
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Sesión cerrada.']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida.']);
            break;
    }

    if ($mysqli) {
        $mysqli->close();
    }
    exit; // Importante: Salir después de procesar la solicitud AJAX
}

// --- Contenido del Frontend (HTML) ---
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - Mi Ruta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.3/leaflet.css" />
    <link rel="stylesheet" href="panelA.css" />
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="../img/MIRUTAazul.png" alt="MIRUTAazul"> </div>
            <div class="sidebar-menu">
                <ul>
                    <li class="menu-item" data-section="horarios" onclick="showSection('horarios'); fetchHorarios();">Horarios</li>
                    <li class="menu-item" data-section="estudiantes" onclick="showSection('estudiantes'); fetchEstudiantes();">Estudiantes</li>
                    <li class="menu-item" data-section="conductores" onclick="showSection('conductores'); fetchConductores();">Conductores</li>
                    <li class="menu-item" data-section="configuracion" onclick="showSection('configuracion'); fetchRutas();">Configuración Rutas</li>
                    <li class="menu-item" data-section="vehiculos" onclick="showSection('vehiculos'); fetchVehiculos();">Vehículos</li>
                    <li class="menu-item" data-section="usuarios" onclick="showSection('usuarios'); fetchUsuarios();">Usuarios</li>
                </ul>
            </div>
        </div>

        <div class="content">
            <div class="top-bar">
                <h2>MI RUTA</h2>
                <div class="user-info">
                    <img src="https://via.placeholder.com/40" alt="User ">
                    <span>Administrador</span>
                    <button class="ver-ruta-btn" style="margin-left:15px;" onclick="logout()">Cerrar Sesión</button>
                </div>
            </div>

            <div class="section-content active" id="configuracion-section">
                <div class="section-container">
                    <h3>Configuración de Rutas</h3>
                    <button class="ver-ruta-btn" onclick="showAddRutaForm()">Agregar Ruta</button>
                    <div class="table-container">
                        <table class="data-table" id="rutas-table">
                            <thead>
                                <tr>
                                    <th>IdRuta</th>
                                    <th>Nombre Ruta</th>
                                    <th>Descripción</th>
                                    <th>Coordenadas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                    <div id="add-ruta-form" style="display:none; margin-top:20px;">
                        <h4>Agregar Ruta</h4>
                        <form onsubmit="return addRuta(event)">
                            <div class="input-group">
                                <label for="ruta-id">ID Ruta</label>
                                <input type="text" id="ruta-id" required>
                            </div>
                            <div class="input-group">
                                <label for="ruta-nombre">Nombre Ruta</label>
                                <input type="text" id="ruta-nombre" required>
                            </div>
                            <div class="input-group">
                                <label for="ruta-descripcion">Descripción</label>
                                <input type="text" id="ruta-descripcion" required>
                            </div>
                            <div class="input-group">
                                <label for="ruta-coordenadas">Coordenadas (JSON Array de [lat, lon])</label>
                                <textarea id="ruta-coordenadas" required placeholder='Ej: [[0.123, -78.456], [0.789, -77.123]]'></textarea>
                            </div>
                            <button type="submit" class="ver-ruta-btn">Guardar</button>
                            <button type="button" class="ver-ruta-btn" onclick="document.getElementById('add-ruta-form').style.display='none'">Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="section-content" id="horarios-section">
                <div class="section-container">
                    <h3>Horarios de Rutas</h3>
                    <button class="ver-ruta-btn" onclick="showAddHorarioForm()">Agregar Horario</button>
                    <div class="table-container">
                        <table class="data-table" id="horarios-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ID Ruta</th>
                                    <th>Institución</th>
                                    <th>ID Vehículo</th>
                                    <th>ID Conductor</th>
                                    <th>Hora Salida</th>
                                    <th>Hora Llegada</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                    <div id="add-horario-form" style="display:none; margin-top:20px;">
                        <h4>Agregar Horario</h4>
                        <form onsubmit="return addHorario(event)">
                            <div class="input-group">
                                <label for="horario-id-ruta">ID Ruta</label>
                                <input type="text" id="horario-id-ruta" required>
                            </div>
                            <div class="input-group">
                                <label for="horario-escuela">Institución</label>
                                <select id="horario-escuela" required>
                                    <option value="">Seleccione una institución</option>
                                    <option value="Unidad Educativa Bolívar">Unidad Educativa Bolívar</option>
                                    <option value="Colegio Vicente Fierro">Colegio Vicente Fierro</option>
                                    <option value="Unidad Educativa Tulcán">Unidad Educativa Tulcán</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label for="horario-id-vehiculo">ID Autobús</label>
                                <input type="text" id="horario-id-vehiculo" required>
                            </div>
                            <div class="input-group">
                                <label for="horario-id-conductor">ID Conductor</label>
                                <input type="number" id="horario-id-conductor">
                            </div>
                            <div class="input-group">
                                <label for="horario-salida">Hora Salida</label>
                                <input type="time" id="horario-salida" required>
                            </div>
                            <div class="input-group">
                                <label for="horario-llegada">Hora Llegada</label>
                                <input type="time" id="horario-llegada" required>
                            </div>
                            <button type="submit" class="ver-ruta-btn">Guardar</button>
                            <button type="button" class="ver-ruta-btn" onclick="document.getElementById('add-horario-form').style.display='none'">Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="section-content" id="estudiantes-section">
                <div class="section-container">
                    <h3>Gestión de Estudiantes</h3>
                    <button class="ver-ruta-btn" onclick="showAddEstudianteForm()">Agregar Estudiante</button>
                    <div class="table-container">
                        <table class="data-table" id="estudiantes-table">
                            <thead>
                                <tr>
                                    <th>ID (Usuario)</th>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Institución</th>
                                    <th>Correo</th>
                                    <th>Ruta Asignada</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                    <div id="add-estudiante-form" style="display:none; margin-top:20px;">
                        <h4>Agregar Estudiante</h4>
                        <form onsubmit="return addEstudiante(event)" enctype="multipart/form-data">
                            <div class="input-group">
                                <label for="estudiante-cedula">Cédula</label>
                                <input type="text" id="estudiante-cedula" name="cedula" required>
                            </div>
                            <div class="input-group">
                                <label for="estudiante-nombre">Nombre</label>
                                <input type="text" id="estudiante-nombre" name="nombre" required>
                            </div>
                            <div class="input-group">
                                <label for="estudiante-institucion">Institución</label>
                                <select id="estudiante-institucion" name="institucion" required>
                                    <option value="">Seleccione una institución</option>
                                    <option value="Unidad Educativa Bolívar">Unidad Educativa Bolívar</option>
                                    <option value="Colegio Vicente Fierro">Colegio Vicente Fierro</option>
                                    <option value="Unidad Educativa Tulcán">Unidad Educativa Tulcán</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label for="estudiante-correo">Correo</label>
                                <input type="email" id="estudiante-correo" name="correo" required>
                            </div>
                             <div class="input-group">
                                <label for="estudiante-password">Contraseña</label>
                                <input type="password" id="estudiante-password" name="contrasena" required>
                            </div>
                            <div class="input-group">
                                <label for="estudiante-id-ruta-asignada">ID Ruta Asignada</label>
                                <input type="text" id="estudiante-id-ruta-asignada" name="id_ruta_asignada">
                            </div>
                             <div class="input-group">
                                <label for="estudiante-foto-perfil">Foto de Perfil</label>
                                <input type="file" id="estudiante-foto-perfil" name="foto_perfil" accept="image/*">
                            </div>
                            <button type="submit" class="ver-ruta-btn">Guardar</button>
                            <button type="button" class="ver-ruta-btn" onclick="document.getElementById('add-estudiante-form').style.display='none'">Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="section-content" id="conductores-section">
                <div class="section-container">
                    <h3>Gestión de Conductores</h3>
                    <button class="ver-ruta-btn" onclick="showAddConductorForm()">Agregar Conductor</button>
                    <div class="table-container">
                        <table class="data-table" id="conductores-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Licencia</th>
                                    <th>Teléfono</th>
                                    <th>Matrícula Autobús</th>
                                    <th>Institución</th>
                                    <th>ID Vehículo</th>
                                    <th>ID Ruta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                    <div id="add-conductor-form" style="display:none; margin-top:20px;">
                        <h4>Agregar Conductor</h4>
                        <form onsubmit="return addConductor(event)" enctype="multipart/form-data">
                            <div class="input-group">
                                <label for="conductor-id">ID Conductor (debe coincidir con un ID de Usuario existente)</label>
                                <input type="number" id="conductor-id" name="id" required>
                            </div>
                            <div class="input-group">
                                <label for="conductor-cedula">Cédula</label>
                                <input type="text" id="conductor-cedula" name="cedula" required>
                            </div>
                            <div class="input-group">
                                <label for="conductor-nombre">Nombre</label>
                                <input type="text" id="conductor-nombre" name="nombre" required>
                            </div>
                            <div class="input-group">
                                <label for="conductor-licencia">Licencia</label>
                                <input type="text" id="conductor-licencia" name="licencia" required>
                            </div>
                            <div class="input-group">
                                <label for="conductor-telefono">Teléfono</label>
                                <input type="text" id="conductor-telefono" name="telefono" required>
                            </div>
                             <div class="input-group">
                                <label for="conductor-matricula-autobus">Matrícula Autobús</label>
                                <input type="text" id="conductor-matricula-autobus" name="matricula_autobus">
                            </div>
                            <div class="input-group">
                                <label for="conductor-institucion-educativa">Institución Educativa</label>
                                <select id="conductor-institucion-educativa" name="institucion_educativa" required>
                                    <option value="">Seleccione una institución</option>
                                    <option value="Unidad Educativa Bolívar">Unidad Educativa Bolívar</option>
                                    <option value="Colegio Vicente Fierro">Colegio Vicente Fierro</option>
                                    <option value="Unidad Educativa Tulcán">Unidad Educativa Tulcán</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label for="conductor-id-vehiculo-asignado">ID Vehículo Asignado</label>
                                <input type="text" id="conductor-id-vehiculo-asignado" name="id_vehiculo_asignado">
                            </div>
                            <div class="input-group">
                                <label for="conductor-id-ruta-asignada">ID Ruta Asignada</label>
                                <input type="text" id="conductor-id-ruta-asignada" name="id_ruta_asignada">
                            </div>
                            <div class="input-group">
                                <label for="conductor-foto-perfil">Foto de Perfil</label>
                                <input type="file" id="conductor-foto-perfil" name="foto_perfil" accept="image/*">
                            </div>
                             <div class="input-group">
                                <label for="conductor-foto-autobus">Foto del Autobús</label>
                                <input type="file" id="conductor-foto-autobus" name="foto_autobus" accept="image/*">
                            </div>
                            <button type="submit" class="ver-ruta-btn">Guardar</button>
                            <button type="button" class="ver-ruta-btn" onclick="document.getElementById('add-conductor-form').style.display='none'">Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="section-content" id="vehiculos-section">
                <div class="section-container">
                    <h3>Gestión de Vehículos</h3>
                    <button class="ver-ruta-btn" onclick="showAddVehiculoForm()">Agregar Vehículo</button>
                    <div class="table-container">
                        <table class="data-table" id="vehiculos-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Placa</th>
                                    <th>Modelo</th>
                                    <th>Capacidad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                    <div id="add-vehiculo-form" style="display:none; margin-top:20px;">
                        <h4>Agregar Vehículo</h4>
                        <form onsubmit="return addVehiculo(event)">
                            <div class="input-group">
                                <label for="vehiculo-id">ID Vehículo</label>
                                <input type="text" id="vehiculo-id" required>
                            </div>
                            <div class="input-group">
                                <label for="vehiculo-placa">Placa</label>
                                <input type="text" id="vehiculo-placa" required>
                            </div>
                            <div class="input-group">
                                <label for="vehiculo-modelo">Modelo</label>
                                <input type="text" id="vehiculo-modelo" required>
                            </div>
                            <div class="input-group">
                                <label for="vehiculo-capacidad">Capacidad</label>
                                <input type="number" id="vehiculo-capacidad" required>
                            </div>
                            <div class="input-group">
                                <label for="vehiculo-estado">Estado</label>
                                <input type="text" id="vehiculo-estado" required>
                            </div>
                            <button type="submit" class="ver-ruta-btn">Guardar</button>
                            <button type="button" class="ver-ruta-btn" onclick="document.getElementById('add-vehiculo-form').style.display='none'">Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="section-content" id="usuarios-section">
                <div class="section-container">
                    <h3>Gestión de Usuarios</h3>
                    <button class="ver-ruta-btn" onclick="showAddUsuarioForm()">Agregar Usuario</button>
                    <div class="table-container">
                        <table class="data-table" id="usuarios-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre de Usuario</th>
                                    <th>Rol</th>
                                    <th>Correo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>
                    <div id="add-usuario-form" style="display:none; margin-top:20px;">
                        <h4>Agregar Usuario</h4>
                        <form onsubmit="return addUsuario(event)">
                            <div class="input-group">
                                <label for="usuario-id">ID Usuario</label>
                                <input type="number" id="usuario-id" required>
                            </div>
                            <div class="input-group">
                                <label for="usuario-username">Nombre de Usuario</label>
                                <input type="text" id="usuario-username" required>
                            </div>
                            <div class="input-group">
                                <label for="usuario-rol">Rol</label>
                                <select id="usuario-rol" required>
                                    <option value="">Seleccione un rol</option>
                                    <option value="admin">Administrador</option>
                                    <option value="estudiante">Estudiante</option>
                                    <option value="conductor">Conductor</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label for="usuario-email">Correo</label>
                                <input type="email" id="usuario-email" required>
                            </div>
                            <div class="input-group">
                                <label for="usuario-password">Contraseña</label>
                                <input type="password" id="usuario-password" required>
                            </div>
                            <button type="submit" class="ver-ruta-btn">Guardar</button>
                            <button type="button" class="ver-ruta-btn" onclick="document.getElementById('add-usuario-form').style.display='none'">Cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.3/leaflet.js"></script>
    <script src="panelA.js"></script>
</body>
</html>