<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php');
    exit();
}

require_once '../../includes/db_connection.php';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$message = '';
$message_type = '';

// Lógica CRUD para Rutas y Paradas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Añadir Ruta
    if (isset($_POST['add_ruta'])) {
        $nombre = sanitize_input($_POST['nombre_ruta']);
        $institucion = sanitize_input($_POST['institucion_ruta']);
        $conductor_id = !empty($_POST['conductor_id']) ? (int)sanitize_input($_POST['conductor_id']) : NULL;
        $vehiculo_id = !empty($_POST['vehiculo_id']) ? (int)sanitize_input($_POST['vehiculo_id']) : NULL;
        $descripcion = sanitize_input($_POST['descripcion_ruta']);

        // Obtener paradas (ej. un JSON string o múltiples campos de formulario)
        // Por simplicidad, asumiremos que las paradas se ingresan en un textarea como "lat,long|lat,long"
        $paradas_raw = sanitize_input($_POST['paradas_ruta_raw']); // Formato: lat1,long1;lat2,long2;...

        if (empty($nombre) || empty($institucion)) {
            $message = "Los campos Nombre de Ruta e Institución son obligatorios.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                $conn->begin_transaction();
                try {
                    // Insertar la ruta
                    $stmt = $conn->prepare("INSERT INTO rutas (nombre, institucion, conductor_id, vehiculo_id, descripcion) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssiis", $nombre, $institucion, $conductor_id, $vehiculo_id, $descripcion);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al añadir ruta: " . $stmt->error);
                    }
                    $ruta_id = $conn->insert_id;
                    $stmt->close();

                    // Insertar paradas
                    $paradas_array = explode(';', $paradas_raw);
                    $orden = 1;
                    foreach ($paradas_array as $parada_str) {
                        $coords = explode(',', $parada_str);
                        if (count($coords) == 2) {
                            $lat = (float)trim($coords[0]);
                            $lon = (float)trim($coords[1]);

                            $stmt_parada = $conn->prepare("INSERT INTO paradas_ruta (ruta_id, nombre_parada, latitud, longitud, orden) VALUES (?, ?, ?, ?, ?)");
                            // Puedes generar un nombre de parada genérico o pedirlo en el formulario
                            $nombre_parada = "Parada " . $orden;
                            $stmt_parada->bind_param("isddi", $ruta_id, $nombre_parada, $lat, $lon, $orden);
                            if (!$stmt_parada->execute()) {
                                throw new Exception("Error al añadir parada " . $orden . ": " . $stmt_parada->error);
                            }
                            $stmt_parada->close();
                            $orden++;
                        }
                    }

                    $conn->commit();
                    $message = "Ruta y sus paradas añadidas exitosamente.";
                    $message_type = 'success';

                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Fallo al añadir ruta: " . $e->getMessage();
                    $message_type = 'error';
                } finally {
                    $conn->close();
                }
            } else {
                $message = "Error de conexión a la base de datos.";
                $message_type = 'error';
            }
        }
    }

    // Editar Ruta
    if (isset($_POST['edit_ruta'])) {
        $ruta_id = (int)sanitize_input($_POST['ruta_id']);
        $nombre = sanitize_input($_POST['nombre_ruta']);
        $institucion = sanitize_input($_POST['institucion_ruta']);
        $conductor_id = !empty($_POST['conductor_id']) ? (int)sanitize_input($_POST['conductor_id']) : NULL;
        $vehiculo_id = !empty($_POST['vehiculo_id']) ? (int)sanitize_input($_POST['vehiculo_id']) : NULL;
        $descripcion = sanitize_input($_POST['descripcion_ruta']);
        $estado = sanitize_input($_POST['estado_ruta']);

        $paradas_raw = sanitize_input($_POST['paradas_ruta_raw']); // Formato: lat1,long1;lat2,long2;...

        if (empty($nombre) || empty($institucion)) {
            $message = "Los campos Nombre de Ruta e Institución son obligatorios para editar.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                $conn->begin_transaction();
                try {
                    // Actualizar la ruta
                    $stmt = $conn->prepare("UPDATE rutas SET nombre=?, institucion=?, conductor_id=?, vehiculo_id=?, descripcion=?, estado=? WHERE id=?");
                    $stmt->bind_param("ssiisss", $nombre, $institucion, $conductor_id, $vehiculo_id, $descripcion, $estado, $ruta_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error al actualizar ruta: " . $stmt->error);
                    }
                    $stmt->close();

                    // Eliminar paradas existentes y reinsertar nuevas
                    $stmt_delete_paradas = $conn->prepare("DELETE FROM paradas_ruta WHERE ruta_id = ?");
                    $stmt_delete_paradas->bind_param("i", $ruta_id);
                    $stmt_delete_paradas->execute();
                    $stmt_delete_paradas->close();

                    $paradas_array = explode(';', $paradas_raw);
                    $orden = 1;
                    foreach ($paradas_array as $parada_str) {
                        $coords = explode(',', $parada_str);
                        if (count($coords) == 2) {
                            $lat = (float)trim($coords[0]);
                            $lon = (float)trim($coords[1]);

                            $stmt_parada = $conn->prepare("INSERT INTO paradas_ruta (ruta_id, nombre_parada, latitud, longitud, orden) VALUES (?, ?, ?, ?, ?)");
                            $nombre_parada = "Parada " . $orden;
                            $stmt_parada->bind_param("isddi", $ruta_id, $nombre_parada, $lat, $lon, $orden);
                            if (!$stmt_parada->execute()) {
                                throw new Exception("Error al añadir parada " . $orden . " durante la edición: " . $stmt_parada->error);
                            }
                            $stmt_parada->close();
                            $orden++;
                        }
                    }

                    $conn->commit();
                    $message = "Ruta y sus paradas actualizadas exitosamente.";
                    $message_type = 'success';

                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Fallo al actualizar ruta: " . $e->getMessage();
                    $message_type = 'error';
                } finally {
                    $conn->close();
                }
            } else {
                $message = "Error de conexión a la base de datos.";
                $message_type = 'error';
            }
        }
    }

    // Eliminar Ruta
    if (isset($_POST['delete_ruta'])) {
        $ruta_id = (int)sanitize_input($_POST['ruta_id']);

        $conn = connectDB();
        if ($conn) {
            $conn->begin_transaction();
            try {
                // Las paradas se eliminan automáticamente por ON DELETE CASCADE si está configurado en la FK
                $stmt = $conn->prepare("DELETE FROM rutas WHERE id=?");
                $stmt->bind_param("i", $ruta_id);
                if (!$stmt->execute()) {
                    throw new Exception("Error al eliminar ruta: " . $stmt->error);
                }
                $stmt->close();
                $conn->commit();
                $message = "Ruta eliminada exitosamente.";
                $message_type = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Fallo al eliminar ruta: " . $e->getMessage();
                $message_type = 'error';
            } finally {
                $conn->close();
            }
        } else {
            $message = "Error de conexión a la base de datos.";
            $message_type = 'error';
        }
    }
    header("Location: horarios_module.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Obtener mensaje de la URL si existe
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Obtener lista de rutas, conductores y vehículos
$rutas = [];
$conductores = [];
$vehiculos = [];

$conn = connectDB();
if ($conn) {
    // Obtener rutas con información de conductor y vehículo
    $result_rutas = $conn->query("
        SELECT r.*, c.nombre AS conductor_nombre, v.matricula AS vehiculo_matricula
        FROM rutas r
        LEFT JOIN conductores c ON r.conductor_id = c.id
        LEFT JOIN vehiculos v ON r.vehiculo_id = v.id
    ");
    if ($result_rutas) {
        while ($row = $result_rutas->fetch_assoc()) {
            $rutas[] = $row;
        }
        $result_rutas->free();
    } else {
        $message .= " Error al cargar rutas: " . $conn->error;
        $message_type = 'error';
    }

    // Obtener paradas para cada ruta
    foreach ($rutas as $key => $ruta) {
        $stmt_paradas = $conn->prepare("SELECT latitud, longitud FROM paradas_ruta WHERE ruta_id = ? ORDER BY orden");
        $stmt_paradas->bind_param("i", $ruta['id']);
        $stmt_paradas->execute();
        $result_paradas = $stmt_paradas->get_result();
        $rutas[$key]['paradas'] = [];
        while ($parada = $result_paradas->fetch_assoc()) {
            $rutas[$key]['paradas'][] = $parada;
        }
        $stmt_paradas->close();
    }


    // Obtener conductores
    $result_conductores = $conn->query("SELECT id, nombre FROM conductores ORDER BY nombre");
    if ($result_conductores) {
        while ($row = $result_conductores->fetch_assoc()) {
            $conductores[] = $row;
        }
        $result_conductores->free();
    }

    // Obtener vehículos
    $result_vehiculos = $conn->query("SELECT id, matricula FROM vehiculos ORDER BY matricula");
    if ($result_vehiculos) {
        while ($row = $result_vehiculos->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        $result_vehiculos->free();
    }

    $conn->close();
} else {
    $message .= " Error de conexión a la base de datos.";
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios y Rutas - Admin</title>
    <link rel="stylesheet" href="../panelA.css">
    <link rel="stylesheet" href="horarios_module.css">
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-panel-container">
        <h1>Gestión de Horarios y Rutas</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo isset($_GET['edit']) ? 'Editar Ruta' : 'Añadir Nueva Ruta'; ?></h2>
            <form action="horarios_module.php" method="POST">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="ruta_id" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                <?php endif; ?>

                <div class="input-group">
                    <label for="nombre_ruta">Nombre de la Ruta:</label>
                    <input type="text" id="nombre_ruta" name="nombre_ruta" value="<?php echo isset($_GET['edit_data']['nombre']) ? htmlspecialchars($_GET['edit_data']['nombre']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="institucion_ruta">Institución Educativa:</label>
                    <select id="institucion_ruta" name="institucion_ruta" required>
                        <option value="">Seleccione la institución</option>
                        <option value="Unidad Educativa Bolívar" <?php echo (isset($_GET['edit_data']['institucion']) && $_GET['edit_data']['institucion'] == 'Unidad Educativa Bolívar') ? 'selected' : ''; ?>>Unidad Educativa Bolívar</option>
                        <option value="Colegio Vicente Fierro" <?php echo (isset($_GET['edit_data']['institucion']) && $_GET['edit_data']['institucion'] == 'Colegio Vicente Fierro') ? 'selected' : ''; ?>>Colegio Vicente Fierro</option>
                        <option value="Unidad Educativa Tulcán" <?php echo (isset($_GET['edit_data']['institucion']) && $_GET['edit_data']['institucion'] == 'Unidad Educativa Tulcán') ? 'selected' : ''; ?>>Unidad Educativa Tulcán</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="conductor_id">Conductor Asignado:</label>
                    <select id="conductor_id" name="conductor_id">
                        <option value="">-- Sin Asignar --</option>
                        <?php foreach ($conductores as $conductor): ?>
                            <option value="<?php echo htmlspecialchars($conductor['id']); ?>"
                                <?php echo (isset($_GET['edit_data']['conductor_id']) && $_GET['edit_data']['conductor_id'] == $conductor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($conductor['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="vehiculo_id">Vehículo Asignado:</label>
                    <select id="vehiculo_id" name="vehiculo_id">
                        <option value="">-- Sin Asignar --</option>
                        <?php foreach ($vehiculos as $vehiculo): ?>
                            <option value="<?php echo htmlspecialchars($vehiculo['id']); ?>"
                                <?php echo (isset($_GET['edit_data']['vehiculo_id']) && $_GET['edit_data']['vehiculo_id'] == $vehiculo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($vehiculo['matricula']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="descripcion_ruta">Descripción de la Ruta:</label>
                    <textarea id="descripcion_ruta" name="descripcion_ruta" rows="3"><?php echo isset($_GET['edit_data']['descripcion']) ? htmlspecialchars($_GET['edit_data']['descripcion']) : ''; ?></textarea>
                </div>
                <?php if (isset($_GET['edit'])): ?>
                <div class="input-group">
                    <label for="estado_ruta">Estado de la Ruta:</label>
                    <select id="estado_ruta" name="estado_ruta" required>
                        <option value="activa" <?php echo (isset($_GET['edit_data']['estado']) && $_GET['edit_data']['estado'] == 'activa') ? 'selected' : ''; ?>>Activa</option>
                        <option value="inactiva" <?php echo (isset($_GET['edit_data']['estado']) && $_GET['edit_data']['estado'] == 'inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="input-group">
                    <label for="paradas_ruta_raw">Coordenadas de las Paradas (Latitud,Longitud;Latitud,Longitud;...):</label>
                    <textarea id="paradas_ruta_raw" name="paradas_ruta_raw" rows="5" placeholder="-77.730106,0.799631;-77.735336,0.797206;..." required><?php
                        if (isset($_GET['edit_data']['paradas']) && is_array($_GET['edit_data']['paradas'])) {
                            $paradas_str = [];
                            foreach ($_GET['edit_data']['paradas'] as $parada) {
                                $paradas_str[] = htmlspecialchars($parada['latitud']) . ',' . htmlspecialchars($parada['longitud']);
                            }
                            echo implode(';', $paradas_str);
                        }
                    ?></textarea>
                    <small>Ingrese las coordenadas separadas por coma y cada parada separada por punto y coma.</small>
                </div>

                <button type="submit" name="<?php echo isset($_GET['edit']) ? 'edit_ruta' : 'add_ruta'; ?>" class="btn primary-btn">
                    <?php echo isset($_GET['edit']) ? 'Actualizar Ruta' : 'Añadir Ruta'; ?>
                </button>
                <?php if (isset($_GET['edit'])): ?>
                    <a href="horarios_module.php" class="btn secondary-btn">Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <h2>Rutas Existentes</h2>
        <?php if (empty($rutas)): ?>
            <p>No hay rutas registradas aún.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Institución</th>
                            <th>Conductor</th>
                            <th>Vehículo</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Paradas (Lat,Lon)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rutas as $ruta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ruta['id']); ?></td>
                                <td><?php echo htmlspecialchars($ruta['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($ruta['institucion']); ?></td>
                                <td><?php echo htmlspecialchars($ruta['conductor_nombre'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($ruta['vehiculo_matricula'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($ruta['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($ruta['estado']); ?></td>
                                <td>
                                    <?php
                                    $paradas_display = [];
                                    foreach ($ruta['paradas'] as $parada) {
                                        $paradas_display[] = htmlspecialchars($parada['latitud']) . ',' . htmlspecialchars($parada['longitud']);
                                    }
                                    echo implode('<br>', $paradas_display);
                                    ?>
                                </td>
                                <td class="actions">
                                    <form action="horarios_module.php" method="GET" style="display:inline-block;">
                                        <input type="hidden" name="edit" value="<?php echo htmlspecialchars($ruta['id']); ?>">
                                        <?php
                                            // Pasar todos los datos para pre-llenar el formulario de edición
                                            foreach ($ruta as $key => $value) {
                                                if ($key === 'paradas' && is_array($value)) {
                                                    $paradas_str_for_input = [];
                                                    foreach ($value as $p) {
                                                        $paradas_str_for_input[] = $p['latitud'] . ',' . $p['longitud'];
                                                    }
                                                    echo '<input type="hidden" name="edit_data[paradas_ruta_raw]" value="' . htmlspecialchars(implode(';', $paradas_str_for_input)) . '">';
                                                } else {
                                                    echo '<input type="hidden" name="edit_data[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars($value) . '">';
                                                }
                                            }
                                        ?>
                                        <button type="submit" class="btn edit-btn"><i class="fas fa-edit"></i> Editar</button>
                                    </form>
                                    <form action="horarios_module.php" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta ruta y todas sus paradas?');">
                                        <input type="hidden" name="ruta_id" value="<?php echo htmlspecialchars($ruta['id']); ?>">
                                        <button type="submit" name="delete_ruta" class="btn delete-btn"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="horarios_module.js"></script>
</body>
</html>