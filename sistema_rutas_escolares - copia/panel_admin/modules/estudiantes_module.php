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
    // Añadir Estudiante (solo desde este módulo, no desde el registro)
    if (isset($_POST['add_estudiante'])) {
        $id_usuario_existente = (int)sanitize_input($_POST['id_usuario_existente'] ?? 0);

        $cedula = sanitize_input($_POST['cedula']);
        $nombre = sanitize_input($_POST['nombre']);
        $institucion = sanitize_input($_POST['institucion']);
        $correo = sanitize_input($_POST['correo']); // Asumo que se puede editar aquí

        if (empty($id_usuario_existente) || empty($cedula) || empty($nombre) || empty($institucion) || empty($correo)) {
            $message = "Todos los campos obligatorios para añadir estudiante deben ser llenados.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                // Aquí deberías también actualizar el rol del usuario en la tabla 'usuarios' a 'estudiante'
                // if ($stmt_update_user_role = $conn->prepare("UPDATE usuarios SET rol = 'estudiante' WHERE id = ?")) {
                //     $stmt_update_user_role->bind_param("i", $id_usuario_existente);
                //     $stmt_update_user_role->execute();
                //     $stmt_update_user_role->close();
                // }

                $stmt = $conn->prepare("INSERT INTO estudiantes (id, cedula, nombre, institucion, correo) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $id_usuario_existente, $cedula, $nombre, $institucion, $correo);
                if ($stmt->execute()) {
                    $message = "Estudiante añadido exitosamente.";
                    $message_type = 'success';
                } else {
                    $message = "Error al añadir estudiante: " . $stmt->error;
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

    // Editar Estudiante
    if (isset($_POST['edit_estudiante'])) {
        $id = (int)sanitize_input($_POST['estudiante_id']);
        $cedula = sanitize_input($_POST['cedula']);
        $nombre = sanitize_input($_POST['nombre']);
        $institucion = sanitize_input($_POST['institucion']);
        $correo = sanitize_input($_POST['correo']);

        // Subida de foto de perfil
        $foto_perfil_path = null;
        $upload_dir = '../../uploads/';

        $conn_temp = connectDB();
        $stmt_get_paths = $conn_temp->prepare("SELECT foto_perfil FROM estudiantes WHERE id = ?");
        $stmt_get_paths->bind_param("i", $id);
        $stmt_get_paths->execute();
        $result_paths = $stmt_get_paths->get_result();
        $current_paths = $result_paths->fetch_assoc();
        $stmt_get_paths->close();
        $conn_temp->close();

        $foto_perfil_path = $current_paths['foto_perfil'];

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

        if (empty($cedula) || empty($nombre) || empty($institucion) || empty($correo)) {
            $message = "Todos los campos obligatorios para editar estudiante deben ser llenados.";
            $message_type = 'error';
        } else {
            $conn = connectDB();
            if ($conn) {
                $stmt = $conn->prepare("UPDATE estudiantes SET cedula=?, nombre=?, institucion=?, correo=?, foto_perfil=? WHERE id=?");
                $stmt->bind_param("sssssi", $cedula, $nombre, $institucion, $correo, $foto_perfil_path, $id);
                if ($stmt->execute()) {
                    $message = "Estudiante actualizado exitosamente.";
                    $message_type = 'success';
                } else {
                    $message = "Error al actualizar estudiante: " . $stmt->error;
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

    // Eliminar Estudiante
    if (isset($_POST['delete_estudiante'])) {
        $id = (int)sanitize_input($_POST['estudiante_id']);

        $conn = connectDB();
        if ($conn) {
            // Obtener la ruta de la foto de perfil para eliminarla del servidor
            $stmt_get_path = $conn->prepare("SELECT foto_perfil FROM estudiantes WHERE id = ?");
            $stmt_get_path->bind_param("i", $id);
            $stmt_get_path->execute();
            $result_path = $stmt_get_path->get_result();
            if ($row_path = $result_path->fetch_assoc()) {
                if ($row_path['foto_perfil'] && file_exists('../../' . $row_path['foto_perfil'])) {
                    unlink('../../' . $row_path['foto_perfil']); // Eliminar el archivo físico
                }
            }
            $stmt_get_path->close();

            // Eliminar de la tabla estudiantes
            $stmt = $conn->prepare("DELETE FROM estudiantes WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                // También eliminar el usuario de la tabla 'usuarios' si el ID es el mismo
                $stmt_user = $conn->prepare("DELETE FROM usuarios WHERE id=?");
                $stmt_user->bind_param("i", $id);
                $stmt_user->execute();
                $stmt_user->close();

                $message = "Estudiante eliminado exitosamente.";
                $message_type = 'success';
            } else {
                $message = "Error al eliminar estudiante: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
            $conn->close();
        } else {
            $message = "Error de conexión a la base de datos.";
            $message_type = 'error';
        }
    }
    header("Location: estudiantes_module.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
    exit();
}

// Obtener mensaje de la URL si existe
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}

// Obtener lista de estudiantes y usuarios disponibles para asignar
$estudiantes = [];
$usuarios_sin_rol_asignado = []; // Usuarios que aún no son conductores ni estudiantes

$conn = connectDB();
if ($conn) {
    $result = $conn->query("SELECT e.*, u.username, u.email FROM estudiantes e JOIN usuarios u ON e.id = u.id");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row;
        }
        $result->free();
    } else {
        $message .= " Error al cargar estudiantes: " . $conn->error;
        $message_type = 'error';
    }

    // Obtener usuarios que no son ni conductores ni estudiantes para asignarlos
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
    <title>Gestión de Estudiantes - Admin</title>
    <link rel="stylesheet" href="../panelA.css">
    <link rel="stylesheet" href="estudiantes_module.css">
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="admin-panel-container">
        <h1>Gestión de Estudiantes</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo isset($_GET['edit']) ? 'Editar Estudiante' : 'Añadir Nuevo Estudiante (a usuario existente)'; ?></h2>
            <form action="estudiantes_module.php" method="POST" enctype="multipart/form-data">
                <?php if (isset($_GET['edit'])): ?>
                    <input type="hidden" name="estudiante_id" value="<?php echo htmlspecialchars($_GET['edit']); ?>">
                    <?php if (isset($_GET['edit_data']['foto_perfil']) && !empty($_GET['edit_data']['foto_perfil'])): ?>
                        <div class="input-group">
                            <label>Foto de Perfil Actual:</label>
                            <img src="../../<?php echo htmlspecialchars($_GET['edit_data']['foto_perfil']); ?>" alt="Foto Perfil" style="max-width: 100px; height: auto; border-radius: 5px;">
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
                    <label for="institucion">Institución Educativa:</label>
                    <select id="institucion" name="institucion" required>
                        <option value="">Seleccione su institución</option>
                        <option value="Unidad Educativa Bolívar" <?php echo (isset($_GET['edit_data']['institucion']) && $_GET['edit_data']['institucion'] == 'Unidad Educativa Bolívar') ? 'selected' : ''; ?>>Unidad Educativa Bolívar</option>
                        <option value="Colegio Vicente Fierro" <?php echo (isset($_GET['edit_data']['institucion']) && $_GET['edit_data']['institucion'] == 'Colegio Vicente Fierro') ? 'selected' : ''; ?>>Colegio Vicente Fierro</option>
                        <option value="Unidad Educativa Tulcán" <?php echo (isset($_GET['edit_data']['institucion']) && $_GET['edit_data']['institucion'] == 'Unidad Educativa Tulcán') ? 'selected' : ''; ?>>Unidad Educativa Tulcán</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" value="<?php echo isset($_GET['edit_data']['correo']) ? htmlspecialchars($_GET['edit_data']['correo']) : ''; ?>" required>
                </div>
                <div class="input-group">
                    <label for="foto_perfil">Foto de Perfil:</label>
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                </div>

                <button type="submit" name="<?php echo isset($_GET['edit']) ? 'edit_estudiante' : 'add_estudiante'; ?>" class="btn primary-btn">
                    <?php echo isset($_GET['edit']) ? 'Actualizar Estudiante' : 'Añadir Estudiante'; ?>
                </button>
                <?php if (isset($_GET['edit'])): ?>
                    <a href="estudiantes_module.php" class="btn secondary-btn">Cancelar Edición</a>
                <?php endif; ?>
            </form>
        </div>

        <hr>

        <h2>Estudiantes Registrados</h2>
        <?php if (empty($estudiantes)): ?>
            <p>No hay estudiantes registrados aún.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Institución</th>
                            <th>Correo</th>
                            <th>Foto Perfil</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $estudiante): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($estudiante['id']); ?></td>
                                <td><?php echo htmlspecialchars($estudiante['username']); ?></td>
                                <td><?php echo htmlspecialchars($estudiante['cedula']); ?></td>
                                <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($estudiante['institucion']); ?></td>
                                <td><?php echo htmlspecialchars($estudiante['correo']); ?></td>
                                <td>
                                    <?php if (!empty($estudiante['foto_perfil'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($estudiante['foto_perfil']); ?>" alt="Perfil" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <form action="estudiantes_module.php" method="GET" style="display:inline-block;">
                                        <input type="hidden" name="edit" value="<?php echo htmlspecialchars($estudiante['id']); ?>">
                                        <?php
                                            foreach ($estudiante as $key => $value) {
                                                echo '<input type="hidden" name="edit_data[' . htmlspecialchars($key) . ']"" value="' . htmlspecialchars($value) . '">';
                                            }
                                        ?>
                                        <button type="submit" class="btn edit-btn"><i class="fas fa-edit"></i> Editar</button>
                                    </form>
                                    <form action="estudiantes_module.php" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este estudiante? Esto también eliminará su cuenta de usuario.');">
                                        <input type="hidden" name="estudiante_id" value="<?php echo htmlspecialchars($estudiante['id']); ?>">
                                        <button type="submit" name="delete_estudiante" class="btn delete-btn"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script src="estudiantes_module.js"></script>
</body>
</html>