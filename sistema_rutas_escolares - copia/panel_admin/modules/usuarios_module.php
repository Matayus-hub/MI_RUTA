<?php
// panel_admin/modules/usuarios_module.php

// Asegurarse de que $conn esté disponible (viene de panelA.php)

// --- Lógica CRUD para Usuarios ---

// 1. Añadir Usuario
if (isset($_POST['add_user'])) {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['password']));
    $rol = htmlspecialchars(trim($_POST['rol']));

    if (!empty($username) && !empty($email) && !empty($password) && !empty($rol)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (username, email, password_hash, rol) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password_hash, $rol);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Usuario '" . $username . "' agregado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Error al agregar usuario: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Todos los campos de usuario son obligatorios.";
    }
    header('Location: panelA.php?module=usuarios');
    exit();
}

// 2. Editar Usuario
if (isset($_POST['edit_user'])) {
    $id = (int)$_POST['user_id'];
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $rol = htmlspecialchars(trim($_POST['rol']));
    $new_password = htmlspecialchars(trim($_POST['new_password'] ?? '')); // Campo opcional

    if (!empty($username) && !empty($email) && !empty($rol)) {
        $sql = "UPDATE usuarios SET username = ?, email = ?, rol = ? WHERE id = ?";
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET username = ?, email = ?, password_hash = ?, rol = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $username, $email, $password_hash, $rol, $id);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $email, $rol, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Usuario ID " . $id . " actualizado exitosamente.";
        } else {
            $_SESSION['error_message'] = "Error al actualizar usuario: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Todos los campos de usuario son obligatorios (excepto nueva contraseña).";
    }
    header('Location: panelA.php?module=usuarios');
    exit();
}

// 3. Eliminar Usuario
if (isset($_GET['action']) && $_GET['action'] == 'delete_user' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // IMPORTANTE: Considerar eliminar de tablas dependientes (estudiantes, conductores) o establecer en NULL
    // Para simplificar, aquí se eliminará solo el usuario. Si hay FKs, fallará a menos que uses ON DELETE CASCADE o manejes la eliminación en cascada manualmente.
    
    // Primero, intenta eliminar de tablas dependientes (si no usas ON DELETE CASCADE)
    // Asumiendo que 'id' de estudiantes y conductores es FK a 'usuarios.id'
    try {
        $conn->begin_transaction();
        $stmt_del_est = $conn->prepare("DELETE FROM estudiantes WHERE id = ?");
        $stmt_del_est->bind_param("i", $id);
        $stmt_del_est->execute();
        $stmt_del_est->close();

        $stmt_del_cond = $conn->prepare("DELETE FROM conductores WHERE id = ?");
        $stmt_del_cond->bind_param("i", $id);
        $stmt_del_cond->execute();
        $stmt_del_cond->close();

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Usuario ID " . $id . " eliminado exitosamente.";
        } else {
            throw new Exception("Error al eliminar usuario principal: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error al eliminar usuario: " . $e->getMessage();
    }
    header('Location: panelA.php?module=usuarios');
    exit();
}

// 4. Obtener datos de usuario para edición (si se solicita)
$user_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit_user_form' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id, username, email, rol FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- HTML para la Gestión de Usuarios ---
?>

<h2>Gestión de Usuarios</h2>

<div class="form-container">
    <h3><?php echo $user_to_edit ? 'Editar Usuario' : 'Agregar Nuevo Usuario'; ?></h3>
    <form action="panelA.php?module=usuarios" method="POST">
        <?php if ($user_to_edit): ?>
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_to_edit['id']); ?>">
        <?php endif; ?>

        <div class="input-group">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_to_edit['username'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_to_edit['email'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="password"><?php echo $user_to_edit ? 'Nueva Contraseña (dejar en blanco para no cambiar)' : 'Contraseña:'; ?></label>
            <input type="password" id="password" name="<?php echo $user_to_edit ? 'new_password' : 'password'; ?>" <?php echo $user_to_edit ? '' : 'required'; ?>>
        </div>
        <div class="input-group">
            <label for="rol">Rol:</label>
            <select id="rol" name="rol" required>
                <option value="admin" <?php echo (isset($user_to_edit['rol']) && $user_to_edit['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                <option value="estudiante" <?php echo (isset($user_to_edit['rol']) && $user_to_edit['rol'] == 'estudiante') ? 'selected' : ''; ?>>Estudiante</option>
                <option value="conductor" <?php echo (isset($user_to_edit['rol']) && $user_to_edit['rol'] == 'conductor') ? 'selected' : ''; ?>>Conductor</option>
            </select>
        </div>
        <button type="submit" name="<?php echo $user_to_edit ? 'edit_user' : 'add_user'; ?>" class="btn-primary">
            <?php echo $user_to_edit ? '<i class="fas fa-save"></i> Guardar Cambios' : '<i class="fas fa-plus-circle"></i> Añadir Usuario'; ?>
        </button>
        <?php if ($user_to_edit): ?>
            <a href="panelA.php?module=usuarios" class="btn-secondary"><i class="fas fa-times"></i> Cancelar Edición</a>
        <?php endif; ?>
    </form>
</div>

<h3>Lista de Usuarios</h3>
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Correo</th>
                <th>Contraseña (Hash)</th> <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $conn->prepare("SELECT id, username, email, password_hash, rol FROM usuarios ORDER BY id ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td class='password-hash-cell'>" . htmlspecialchars($row['password_hash']) . "</td>"; // Mostrar el hash
                    echo "<td>" . htmlspecialchars($row['rol']) . "</td>";
                    echo "<td class='actions'>";
                    echo "<a href='panelA.php?module=usuarios&action=edit_user_form&id=" . htmlspecialchars($row['id']) . "' class='btn-action edit'><i class='fas fa-edit'></i> Editar</a>";
                    echo "<a href='panelA.php?module=usuarios&action=delete_user&id=" . htmlspecialchars($row['id']) . "' class='btn-action delete' onclick=\"return confirm('¿Está seguro de que desea eliminar este usuario y sus datos asociados?');\"><i class='fas fa-trash-alt'></i> Eliminar</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No hay usuarios registrados.</td></tr>";
            }
            $stmt->close();
            ?>
        </tbody>
    </table>
</div>