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

// Lógica CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Añadir Conductor (solo desde este módulo, no desde el registro)
    if (isset($_POST['add_conductor'])) {
        // Para añadir un conductor desde el panel de admin, primero deberíamos crear un usuario
        // y luego asignarle el rol de 'conductor' y llenar sus detalles.
        // Esto es más complejo ya que implica crear un usuario en la tabla 'usuarios'
        // y luego el registro en la tabla 'conductores'.
        // POR SIMPLICIDAD, ESTE ADD_CONDUCTOR AQUI NO CREA EL USUARIO PRINCIPAL, SOLO ASUME QUE EL USUARIO EXISTE Y SE LE ASIGNARÁ UN ID.
        // EN UN SISTEMA REAL, EL ADMINISTRADOR PODRÍA TENER UN FORMULARIO PARA REGISTRAR UN NUEVO USUARIO Y ASIGNARLE ROL.
        // O SE PUEDE USAR EL ID DE UN USUARIO EXISTENTE.
        // POR AHORA, LO DEJAREMOS COMO UN EJEMPLO DE CÓMO SE MANIPULARÍAN LOS DATOS SI EL USUARIO BASE YA EXISTIERA.
        // SE RECOMIENDA QUE EL REGISTRO DE USUARIOS PASE SIEMPRE POR index.php Y LUEGO EL ADMIN LOS MODIFIQUE.
        // Si el admin añade un conductor, se debe crear un usuario en la tabla `usuarios` primero.
        // Aquí solo se ejemplifican los campos específicos de `conductores`.
        $id_usuario_existente = (int)sanitize_input($_POST['id_usuario_existente'] ?? 0); // Asume que se selecciona un usuario existente

        $cedula = sanitize_input($_POST['cedula']);
        $nombre = sanitize_input($_POST['nombre']);
        $telefono = sanitize_input($_POST['telefono']);
        $licencia = sanitize_input($_POST['licencia']);
        $matricula_autobus = sanitize_input($_POST['matricula_autobus']);
        $institucion_educativa = sanitize_input($_POST['institucion_educativa']);

        if (empty($id_usuario_existente) || empty($cedula) || empty($nombre) || empty($telefono) || empty($licencia) || empty($matricula_autobus) || empty($institucion_educativa)) {
            $message = "Todos los campos obligatorios para añadir conductor deben ser llenados.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                // Aquí deberías también actualizar el rol del usuario en la tabla 'usuarios' a 'conductor'
                // if ($stmt_update_user_role = $conn->prepare("UPDATE usuarios SET rol = 'conductor' WHERE id = ?")) {
                //     $stmt_update_user_role->bind_param("i", $id_usuario_existente);
                //     $stmt_update_user_role->execute();
                //     $stmt_update_user_role->close();
                // }

                $stmt = $conn->prepare("INSERT INTO conductores (id, cedula, nombre, telefono, licencia, matricula_autobus, institucion_educativa) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $id_usuario_existente, $cedula, $nombre, $telefono, $licencia, $matricula_autobus, $institucion_educativa);
                if ($stmt->execute()) {
                    $message = "Conductor añadido exitosamente.";
                    $message_type = 'success';
                } else {
                    $message = "Error al añadir conductor: " . $stmt->error;
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

    // Editar Conductor
    if (isset($_POST['edit_conductor'])) {
        $id = (int)sanitize_input($_POST['conductor_id']);
        $cedula = sanitize_input($_POST['cedula']);
        $nombre = sanitize_input($_POST['nombre']);
        $telefono = sanitize_input($_POST['telefono']);
        $licencia = sanitize_input($_POST['licencia']);
        $matricula_autobus = sanitize_input($_POST['matricula_autobus']);
        $institucion_educativa = sanitize_input($_POST['institucion_educativa']);

        // Subida de fotos (perfil y autobús)
        $foto_perfil_path = null;
        $foto_autobus_path = null;
        $upload_dir = '../../uploads/'; // Ajusta la ruta para guardar en sistema_rutas_escolares/uploads/

        // Obtener rutas de fotos existentes para no borrarlas si no se sube una nueva
        $conn_temp = connectDB();
        $stmt_get_paths = $conn_temp->prepare("SELECT foto_perfil, foto_autobus FROM conductores WHERE id = ?");
        $stmt_get_paths->bind_param("i", $id);
        $stmt_get_paths->execute();
        $result_paths = $stmt_get_paths->get_result();
        $current_paths = $result_paths->fetch_assoc();
        $stmt_get_paths->close();
        $conn_temp->close();

        $foto_perfil_path = $current_paths['foto_perfil'];
        $foto_autobus_path = $current_paths['foto_autobus'];


        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['foto_perfil']['tmp_name'];
            $file_extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $new_file_name = uniqid('profile_') . '.' . $file_extension;
            if (move_uploaded_file($file_tmp_name, $upload_dir . $new_file_name)) {
                $foto_perfil_path = 'uploads/' . $new_file_name;
            } else {
                $message .= " Error al subir la foto de perfil.";
                $message_type = 'error';
            }
        }

        if (isset($_FILES['foto_autobus']) && $_FILES['foto_autobus']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['foto_autobus']['tmp_name'];
            $file_extension = pathinfo($_FILES['foto_autobus']['name'], PATHINFO_EXTENSION);
            $new_file_name = uniqid('bus_') . '.' . $file_extension;
            if (move_uploaded_file($file_tmp_name, $upload_dir . $new_file_name)) {
                $foto_autobus_path = 'uploads/' . $new_file_name;
            } else {
                $message .= " Error al subir la foto del autobús.";
                $message_type = 'error';
            }
        }


        if (empty($cedula) || empty($nombre) || empty($telefono) || empty($licencia) || empty($matricula_autobus) || empty($institucion_educativa)) {
            $message = "Todos los campos obligatorios para editar conductor deben ser llenados.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                $stmt = $conn->prepare("UPDATE conductores SET cedula=?, nombre=?, telefono=?, licencia=?, matricula_autobus=?, institucion_educativa=?, foto_perfil=?, foto_autobus=? WHERE id=?");
                $stmt->bind_param("ssssssssi", $cedula, $nombre, $telefono, $licencia, $matricula_autobus, $institucion_educativa, $foto_perfil_path, $foto_autobus_path, $id);
                if ($stmt->execute()) {
                    $message = "Conductor actualizado exitosamente.";
                    $message_type = 'success';
                } else {
                    $message = "Error al actualizar conductor: " . $stmt->error;
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

    // Eliminar Conductor
    if (isset($_POST['delete_conductor'])) {
        $id = (int)sanitize_input($_POST['conductor_id']);

        $conn = connectDB();
        if ($conn) {
            // Eliminar entradas de fotos asociadas si es necesario (opcional)
            $stmt_get_paths = $conn->prepare("SELECT foto_perfil, foto_autobus FROM conductores WHERE id = ?");
            $stmt_get_paths->bind_param("i", $id);
            $stmt_get_paths->execute();
            $result_paths = $stmt_get_paths->get_result();
            if ($row_paths = $result_paths->fetch_assoc()) {
                if ($row_paths['foto_perfil'] && file_exists('../../' . $row_paths['foto_perfil'])) {
                    unlink('../../' . $row_paths['foto_perfil']);
                }
                if ($row_paths['foto_autobus'] && file_exists('../../' . $row_paths['foto_autobus'])) {
                    unlink('../../' . $row_paths['foto_autobus']);
                }
            }
            $stmt_get_paths->close();

            // Eliminar de la tabla conductores
            $stmt = $conn->prepare("DELETE FROM conductores WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                // También eliminar el usuario de la tabla 'usuarios' si el ID es el mismo
                $stmt_user = $conn->prepare("DELETE FROM usuarios WHERE id=?");
                $stmt_user->bind_param("i", $id);
                $stmt_user->execute();
                $stmt_user->close();

                $message = "Conductor eliminado exitosamente.";
                $message_type = 'success';
            } else {
                $message = "Error al eliminar conductor: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
            $conn->close();
        } else {
            $message = "Error de conexión a la base de datos.";
            $message_type = 'error';
        }
    }
    header("Location: conductores_module.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Obtener mensaje de la URL si existe
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Obtener lista de conductores y usuarios disponibles para asignar
$conductores = [];
$usuarios_sin_rol_asignado = []; // Usuarios que aún no son conductores ni estudiantes

$conn = connectDB();
if ($conn) {
    $result = $conn->query("SELECT c.*, u.username, u.email FROM conductores c JOIN usuarios u ON c.id = u.id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $conductores[] = $row;
        }
        $result->free();
    } else {
        $message .= " Error al cargar conductores: " . $conn->error;
        $message_type = 'error';
    }

    // Obtener usuarios que no son ni conductores ni estudiantes para asignarlos
    // (Asumiendo que 'admin' es el único otro rol principal, o que 'usuarios' tiene un campo 'rol_asignado')
    $result_users = $conn->query("SELECT id, username, email FROM usuarios WHERE rol NOT IN ('conductor', 'estudiante', 'admin')");
    if ($result_users) {
        while ($row = $result_users->fetch_assoc()) {
            $usuarios_sin_rol_asignado[] = $row;
        }
        $result_users->free();
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
    <title>Gestión de Conductores - Admin</title>
    <link rel="stylesheet" href="../panelA.css">
    <link rel="stylesheet" href="conductores_module.css">
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-panel-container">
        <h1>Gestión de Conductores</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo isset($_GET['edit']) ? 'Editar Conductor' : 'Añadir Nuevo Conductor (a usuario existente)'; ?></h2>
            <form action="conductores_module.php" method="POST" enctype="multipart/form-data">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="conductor_id" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                    <?php if (isset($_GET['edit_data']['foto_perfil']) && !empty($_GET['edit_data']['foto_perfil'])): ?>
                        <div class="input-group">
                            <label>Foto de Perfil Actual:</label>
                            <img src="../../<?php echo htmlspecialchars($_GET['edit_data']['foto_perfil']); ?>" alt="Foto Perfil" style="max-width: 100px; height: auto; border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['edit_data']['foto_autobus']) && !empty($_GET['edit_data']['foto_autobus'])): ?>
                        <div class="input-group">
                            <label>Foto de Autobús Actual:</label>
                            <img src="../../<?php echo htmlspecialchars($_GET['edit_data']['foto_autobus']); ?>" alt="Foto Autobús" style="max-width: 100px; height: auto; border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="input-group">
                        <label for="id_usuario_existente">Asignar a Usuario Existente:</label>
                        <select id="id_usuario_existente" name="id_usuario_existente" required>
                            <option value="">-- Seleccione un usuario --</option>
                            <?php foreach ($usuarios_sin_rol_asignado as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['id']); ?>">
                                    <?php echo htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Solo se muestran usuarios que no tienen rol de 'estudiante', 'conductor' o 'admin' asignado.</small>
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <label for="cedula">Cédula:</label>
                    <input type="text" id="cedula" name="cedula" value="<?php echo isset($_GET['edit_data']['cedula']) ? htmlspecialchars($_GET['edit_data']['cedula']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="nombre">Nombre Completo:</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo isset($_GET['edit_data']['nombre']) ? htmlspecialchars($_GET['edit_data']['nombre']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono" value="<?php echo isset($_GET['edit_data']['telefono']) ? htmlspecialchars($_GET['edit_data']['telefono']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="licencia">Licencia:</label>
                    <input type="text" id="licencia" name="licencia" value="<?php echo isset($_GET['edit_data']['licencia']) ? htmlspecialchars($_GET['edit_data']['licencia']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="matricula_autobus">Matrícula del Autobús:</label>
                    <input type="text" id="matricula_autobus" name="matricula_autobus" value="<?php echo isset($_GET['edit_data']['matricula_autobus']) ? htmlspecialchars($_GET['edit_data']['matricula_autobus']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="institucion_educativa">Institución Educativa:</label>
                    <select id="institucion_educativa" name="institucion_educativa" required>
                        <option value="">Seleccione su institución</option>
                        <option value="Unidad Educativa Bolívar" <?php echo (isset($_GET['edit_data']['institucion_educativa']) && $_GET['edit_data']['institucion_educativa'] == 'Unidad Educativa Bolívar') ? 'selected' : ''; ?>>Unidad Educativa Bolívar</option>
                        <option value="Colegio Vicente Fierro" <?php echo (isset($_GET['edit_data']['institucion_educativa']) && $_GET['edit_data']['institucion_educativa'] == 'Colegio Vicente Fierro') ? 'selected' : ''; ?>>Colegio Vicente Fierro</option>
                        <option value="Unidad Educativa Tulcán" <?php echo (isset($_GET['edit_data']['institucion_educativa']) && $_GET['edit_data']['institucion_educativa'] == 'Unidad Educativa Tulcán') ? 'selected' : ''; ?>>Unidad Educativa Tulcán</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="foto_perfil">Foto de Perfil:</label>
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                </div>
                <div class="input-group">
                    <label for="foto_autobus">Foto del Autobús:</label>
                    <input type="file" id="foto_autobus" name="foto_autobus" accept="image/*">
                </div>

                <button type="submit" name="<?php echo isset($_GET['edit']) ? 'edit_conductor' : 'add_conductor'; ?>" class="btn primary-btn">
                    <?php echo isset($_GET['edit']) ? 'Actualizar Conductor' : 'Añadir Conductor'; ?>
                </button>
                <?php if (isset($_GET['edit'])): ?>
                    <a href="conductores_module.php" class="btn secondary-btn">Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <h2>Conductores Registrados</h2>
        <?php if (empty($conductores)): ?>
            <p>No hay conductores registrados aún.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Licencia</th>
                            <th>Matrícula Autobús</th>
                            <th>Institución</th>
                            <th>Foto Perfil</th>
                            <th>Foto Autobús</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conductores as $conductor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($conductor['id']); ?></td>
                                <td><?php echo htmlspecialchars($conductor['username']); ?></td>
                                <td><?php echo htmlspecialchars($conductor['cedula']); ?></td>
                                <td><?php echo htmlspecialchars($conductor['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($conductor['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($conductor['licencia']); ?></td>
                                <td><?php echo htmlspecialchars($conductor['matricula_autobus']); ?></td>
                                <td><?php echo htmlspecialchars($conductor['institucion_educativa']); ?></td>
                                <td>
                                    <?php if (!empty($conductor['foto_perfil'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($conductor['foto_perfil']); ?>" alt="Perfil" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($conductor['foto_autobus'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($conductor['foto_autobus']); ?>" alt="Autobús" style="width: 70px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <form action="conductores_module.php" method="GET" style="display:inline-block;">
                                        <input type="hidden" name="edit" value="<?php echo htmlspecialchars($conductor['id']); ?>">
                                        <?php
                                            foreach ($conductor as $key => $value) {
                                                echo '<input type="hidden" name="edit_data[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars($value) . '">';
                                            }
                                        ?>
                                        <button type="submit" class="btn edit-btn"><i class="fas fa-edit"></i> Editar</button>
                                    </form>
                                    <form action="conductores_module.php" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este conductor? Esto también eliminará su cuenta de usuario.');">
                                        <input type="hidden" name="conductor_id" value="<?php echo htmlspecialchars($conductor['id']); ?>">
                                        <button type="submit" name="delete_conductor" class="btn delete-btn"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="conductores_module.js"></script>
</body>
</html>