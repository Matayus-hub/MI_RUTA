<?php
session_start();
// Asegúrate de que solo los administradores puedan acceder
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../index.php'); // Redirigir si no es admin
    exit();
}

require_once '../../includes/db_connection.php'; // Ajusta la ruta si es necesario

// Función para limpiar y validar datos de entrada
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$message = '';
$message_type = ''; // 'success' or 'error'

// Lógica CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Añadir Vehículo
    if (isset($_POST['add_vehiculo'])) {
        $matricula = sanitize_input($_POST['matricula']);
        $marca = sanitize_input($_POST['marca']);
        $modelo = sanitize_input($_POST['modelo']);
        $capacidad = (int)sanitize_input($_POST['capacidad']);
        $año = (int)sanitize_input($_POST['año']);
        $estado = sanitize_input($_POST['estado']);
        $conductor_id = !empty($_POST['conductor_id']) ? (int)sanitize_input($_POST['conductor_id']) : NULL;

        if (empty($matricula) || empty($marca) || empty($capacidad) || empty($estado)) {
            $message = "Todos los campos obligatorios deben ser llenados.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                $stmt = $conn->prepare("INSERT INTO vehiculos (matricula, marca, modelo, capacidad, año, estado, conductor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiisi", $matricula, $marca, $modelo, $capacidad, $año, $estado, $conductor_id);
                if ($stmt->execute()) {
                    $message = "Vehículo añadido exitosamente.";
                    $message_type = 'success';
                } else {
                    $message = "Error al añadir vehículo: " . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                $conn->close();
            } else {
                $message = "Error de conexión a la base de datos.";
                $message_type = 'error';
            }
        }
    }

    // Editar Vehículo
    if (isset($_POST['edit_vehiculo'])) {
        $id = (int)sanitize_input($_POST['vehiculo_id']);
        $matricula = sanitize_input($_POST['matricula']);
        $marca = sanitize_input($_POST['marca']);
        $modelo = sanitize_input($_POST['modelo']);
        $capacidad = (int)sanitize_input($_POST['capacidad']);
        $año = (int)sanitize_input($_POST['año']);
        $estado = sanitize_input($_POST['estado']);
        $conductor_id = !empty($_POST['conductor_id']) ? (int)sanitize_input($_POST['conductor_id']) : NULL;

        if (empty($matricula) || empty($marca) || empty($capacidad) || empty($estado)) {
            $message = "Todos los campos obligatorios deben ser llenados para editar.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                $stmt = $conn->prepare("UPDATE vehiculos SET matricula=?, marca=?, modelo=?, capacidad=?, año=?, estado=?, conductor_id=? WHERE id=?");
                $stmt->bind_param("sssiisii", $matricula, $marca, $modelo, $capacidad, $año, $estado, $conductor_id, $id);
                if ($stmt->execute()) {
                    $message = "Vehículo actualizado exitosamente.";
                    $message_type = 'success';
                } else {
                    $message = "Error al actualizar vehículo: " . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
                $conn->close();
            } else {
                $message = "Error de conexión a la base de datos.";
                $message_type = 'error';
            }
        }
    }

    // Eliminar Vehículo
    if (isset($_POST['delete_vehiculo'])) {
        $id = (int)sanitize_input($_POST['vehiculo_id']);

        $conn = connectDB();
        if ($conn) {
            $stmt = $conn->prepare("DELETE FROM vehiculos WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Vehículo eliminado exitosamente.";
                $message_type = 'success';
            } else {
                $message = "Error al eliminar vehículo: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
            $conn->close();
        } else {
            $message = "Error de conexión a la base de datos.";
            $message_type = 'error';
        }
    }
    // Redirigir para evitar re-envío de formularios
    header("Location: vehiculos_module.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Obtener mensaje de la URL si existe
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Obtener lista de vehículos y conductores
$vehiculos = [];
$conductores = [];
$conn = connectDB();
if ($conn) {
    // Obtener vehículos
    $result = $conn->query("SELECT v.*, c.nombre AS conductor_nombre FROM vehiculos v LEFT JOIN conductores c ON v.conductor_id = c.id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        $result->free();
    } else {
        $message .= " Error al cargar vehículos: " . $conn->error;
        $message_type = 'error';
    }

    // Obtener conductores para el dropdown
    $result_conductores = $conn->query("SELECT id, nombre FROM conductores ORDER BY nombre");
    if ($result_conductores) {
        while ($row = $result_conductores->fetch_assoc()) {
            $conductores[] = $row;
        }
        $result_conductores->free();
    } else {
        $message .= " Error al cargar conductores: " . $conn->error;
        $message_type = 'error';
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
    <title>Gestión de Vehículos - Admin</title>
    <link rel="stylesheet" href="../panelA.css"> <link rel="stylesheet" href="vehiculos_module.css"> <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-panel-container">
        <h1>Gestión de Vehículos</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo isset($_GET['edit']) ? 'Editar Vehículo' : 'Añadir Nuevo Vehículo'; ?></h2>
            <form action="vehiculos_module.php" method="POST">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="vehiculo_id" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                <?php endif; ?>

                <div class="input-group">
                    <label for="matricula">Matrícula:</label>
                    <input type="text" id="matricula" name="matricula" value="<?php echo isset($_GET['edit_data']['matricula']) ? htmlspecialchars($_GET['edit_data']['matricula']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="marca">Marca:</label>
                    <input type="text" id="marca" name="marca" value="<?php echo isset($_GET['edit_data']['marca']) ? htmlspecialchars($_GET['edit_data']['marca']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="modelo">Modelo:</label>
                    <input type="text" id="modelo" name="modelo" value="<?php echo isset($_GET['edit_data']['modelo']) ? htmlspecialchars($_GET['edit_data']['modelo']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label for="capacidad">Capacidad:</label>
                    <input type="number" id="capacidad" name="capacidad" value="<?php echo isset($_GET['edit_data']['capacidad']) ? htmlspecialchars($_GET['edit_data']['capacidad']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="año">Año:</label>
                    <input type="number" id="año" name="año" value="<?php echo isset($_GET['edit_data']['año']) ? htmlspecialchars($_GET['edit_data']['año']) : ''; ?>">
                </div>
                <div class="input-group">
                    <label for="estado">Estado:</label>
                    <select id="estado" name="estado" required>
                        <option value="activo" <?php echo (isset($_GET['edit_data']['estado']) && $_GET['edit_data']['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="en mantenimiento" <?php echo (isset($_GET['edit_data']['estado']) && $_GET['edit_data']['estado'] == 'en mantenimiento') ? 'selected' : ''; ?>>En Mantenimiento</option>
                        <option value="inactivo" <?php echo (isset($_GET['edit_data']['estado']) && $_GET['edit_data']['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="conductor_id">Conductor Asignado (Opcional):</label>
                    <select id="conductor_id" name="conductor_id">
                        <option value="">-- Sin Conductor --</option>
                        <?php foreach ($conductores as $conductor): ?>
                            <option value="<?php echo htmlspecialchars($conductor['id']); ?>"
                                <?php echo (isset($_GET['edit_data']['conductor_id']) && $_GET['edit_data']['conductor_id'] == $conductor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($conductor['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="<?php echo isset($_GET['edit']) ? 'edit_vehiculo' : 'add_vehiculo'; ?>" class="btn primary-btn">
                    <?php echo isset($_GET['edit']) ? 'Actualizar Vehículo' : 'Añadir Vehículo'; ?>
                </button>
                <?php if (isset($_GET['edit'])): ?>
                    <a href="vehiculos_module.php" class="btn secondary-btn">Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <h2>Vehículos Existentes</h2>
        <?php if (empty($vehiculos)): ?>
            <p>No hay vehículos registrados aún.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Matrícula</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Capacidad</th>
                            <th>Año</th>
                            <th>Estado</th>
                            <th>Conductor Asignado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehiculos as $vehiculo): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vehiculo['id']); ?></td>
                                <td><?php echo htmlspecialchars($vehiculo['matricula']); ?></td>
                                <td><?php echo htmlspecialchars($vehiculo['marca']); ?></td>
                                <td><?php echo htmlspecialchars($vehiculo['modelo']); ?></td>
                                <td><?php echo htmlspecialchars($vehiculo['capacidad']); ?></td>
                                <td><?php echo htmlspecialchars($vehiculo['año']); ?></td>
                                <td><?php echo htmlspecialchars($vehiculo['estado']); ?></td>
                                <td><?php echo htmlspecialchars($vehiculo['conductor_nombre'] ?? 'N/A'); ?></td>
                                <td class="actions">
                                    <form action="vehiculos_module.php" method="GET" style="display:inline-block;">
                                        <input type="hidden" name="edit" value="<?php echo htmlspecialchars($vehiculo['id']); ?>">
                                        <?php
                                            // Pasar todos los datos para pre-llenar el formulario de edición
                                            foreach ($vehiculo as $key => $value) {
                                                echo '<input type="hidden" name="edit_data[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars($value) . '">';
                                            }
                                        ?>
                                        <button type="submit" class="btn edit-btn"><i class="fas fa-edit"></i> Editar</button>
                                    </form>
                                    <form action="vehiculos_module.php" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este vehículo?');">
                                        <input type="hidden" name="vehiculo_id" value="<?php echo htmlspecialchars($vehiculo['id']); ?>">
                                        <button type="submit" name="delete_vehiculo" class="btn delete-btn"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="vehiculos_module.js"></script> </body>
</html>