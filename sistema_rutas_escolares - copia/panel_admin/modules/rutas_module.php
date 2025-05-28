<?php
// panel_admin/modules/rutas_module.php

// Asegurarse de que $conn esté disponible (viene de panelA.php)

// --- Lógica CRUD para Rutas ---

// 1. Añadir Ruta
if (isset($_POST['add_ruta'])) {
    $id = htmlspecialchars(trim($_POST['id_ruta']));
    $nombre = htmlspecialchars(trim($_POST['nombre_ruta']));
    $descripcion = htmlspecialchars(trim($_POST['descripcion_ruta']));
    $coordenadas = trim($_POST['coordenadas_ruta']); // No sanear con htmlspecialchars para JSON
    $institucion = htmlspecialchars(trim($_POST['institucion_ruta']));

    // Validar si las coordenadas son un JSON válido
    if (!json_decode($coordenadas) && json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error_message'] = "Las coordenadas no tienen un formato JSON válido.";
        header('Location: panelA.php?module=rutas');
        exit();
    }

    if (!empty($id) && !empty($nombre) && !empty($coordenadas) && !empty($institucion)) {
        $stmt = $conn->prepare("INSERT INTO rutas (id, nombre, descripcion, coordenadas, institucion_asignada) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $id, $nombre, $descripcion, $coordenadas, $institucion);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Ruta '" . $nombre . "' agregada exitosamente.";
        } else {
            $_SESSION['error_message'] = "Error al agregar ruta: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Los campos ID, Nombre, Coordenadas e Institución son obligatorios para la ruta.";
    }
    header('Location: panelA.php?module=rutas');
    exit();
}

// 2. Editar Ruta
if (isset($_POST['edit_ruta'])) {
    $id_original = htmlspecialchars(trim($_POST['id_ruta_original'])); // Usar el ID original para WHERE
    $id_new = htmlspecialchars(trim($_POST['id_ruta'])); // Nuevo ID (si se permite cambiar, cuidado con FKs)
    $nombre = htmlspecialchars(trim($_POST['nombre_ruta']));
    $descripcion = htmlspecialchars(trim($_POST['descripcion_ruta']));
    $coordenadas = trim($_POST['coordenadas_ruta']);
    $institucion = htmlspecialchars(trim($_POST['institucion_ruta']));

    // Validar si las coordenadas son un JSON válido
    if (!json_decode($coordenadas) && json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error_message'] = "Las coordenadas no tienen un formato JSON válido.";
        header('Location: panelA.php?module=rutas');
        exit();
    }

    if (!empty($id_new) && !empty($nombre) && !empty($coordenadas) && !empty($institucion)) {
        $stmt = $conn->prepare("UPDATE rutas SET id = ?, nombre = ?, descripcion = ?, coordenadas = ?, institucion_asignada = ? WHERE id = ?");
        $stmt->bind_param("ssssss", $id_new, $nombre, $descripcion, $coordenadas, $institucion, $id_original);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Ruta '" . $nombre . "' actualizada exitosamente.";
        } else {
            $_SESSION['error_message'] = "Error al actualizar ruta: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Todos los campos de ruta son obligatorios.";
    }
    header('Location: panelA.php?module=rutas');
    exit();
}

// 3. Eliminar Ruta
if (isset($_GET['action']) && $_GET['action'] == 'delete_ruta' && isset($_GET['id'])) {
    $id = htmlspecialchars(trim($_GET['id']));
    // Considerar eliminar de tablas dependientes (estudiantes, conductores, horarios) o establecer en NULL
    // Si usas ON DELETE CASCADE en la DB, esto se manejará automáticamente.
    try {
        $conn->begin_transaction();
        // Setear a NULL en estudiantes que referencian esta ruta (si no quieres eliminar)
        $stmt_est = $conn->prepare("UPDATE estudiantes SET id_ruta_asignada = NULL WHERE id_ruta_asignada = ?");
        $stmt_est->bind_param("s", $id);
        $stmt_est->execute();
        $stmt_est->close();

        // Setear a NULL en conductores que referencian esta ruta
        $stmt_cond = $conn->prepare("UPDATE conductores SET id_ruta_asignada = NULL WHERE id_ruta_asignada = ?");
        $stmt_cond->bind_param("s", $id);
        $stmt_cond->execute();
        $stmt_cond->close();

        // Eliminar horarios asociados a esta ruta
        $stmt_hor = $conn->prepare("DELETE FROM horarios WHERE id_ruta = ?");
        $stmt_hor->bind_param("s", $id);
        $stmt_hor->execute();
        $stmt_hor->close();

        $stmt = $conn->prepare("DELETE FROM rutas WHERE id = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Ruta ID " . $id . " eliminada exitosamente.";
        } else {
            throw new Exception("Error al eliminar ruta: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error al eliminar ruta: " . $e->getMessage();
    }
    header('Location: panelA.php?module=rutas');
    exit();
}

// 4. Obtener datos de ruta para edición
$ruta_to_edit = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit_ruta_form' && isset($_GET['id'])) {
    $id = htmlspecialchars(trim($_GET['id']));
    $stmt = $conn->prepare("SELECT id, nombre, descripcion, coordenadas, institucion_asignada FROM rutas WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $ruta_to_edit = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- HTML para la Gestión de Rutas ---
?>

<h2>Gestión de Rutas</h2>

<div class="form-container">
    <h3><?php echo $ruta_to_edit ? 'Editar Ruta' : 'Agregar Nueva Ruta'; ?></h3>
    <form action="panelA.php?module=rutas" method="POST">
        <?php if ($ruta_to_edit): ?>
            <input type="hidden" name="id_ruta_original" value="<?php echo htmlspecialchars($ruta_to_edit['id']); ?>">
        <?php endif; ?>

        <div class="input-group">
            <label for="id_ruta">ID de Ruta:</label>
            <input type="text" id="id_ruta" name="id_ruta" value="<?php echo htmlspecialchars($ruta_to_edit['id'] ?? ''); ?>" <?php echo $ruta_to_edit ? 'readonly' : ''; ?> required>
            <?php if ($ruta_to_edit): ?><small>El ID de ruta no puede ser modificado directamente (considere si su sistema permite cambiarlo o es una clave fija).</small><?php endif; ?>
        </div>
        <div class="input-group">
            <label for="nombre_ruta">Nombre de la Ruta:</label>
            <input type="text" id="nombre_ruta" name="nombre_ruta" value="<?php echo htmlspecialchars($ruta_to_edit['nombre'] ?? ''); ?>" required>
        </div>
        <div class="input-group">
            <label for="descripcion_ruta">Descripción:</label>
            <textarea id="descripcion_ruta" name="descripcion_ruta" rows="3"><?php echo htmlspecialchars($ruta_to_edit['descripcion'] ?? ''); ?></textarea>
        </div>
        <div class="input-group">
            <label for="coordenadas_ruta">Coordenadas (Formato JSON Array):</label>
            <textarea id="coordenadas_ruta" name="coordenadas_ruta" rows="5" required><?php echo htmlspecialchars($ruta_to_edit['coordenadas'] ?? ''); ?></textarea>
            <small>Ej: [[0.799631, -77.730106],[0.797206, -77.735336]]</small>
            <a href="panelA.php?module=mapa_coordenadas" target="_blank" class="btn-secondary small-btn"><i class="fas fa-map"></i> Generar Coordenadas con Mapa</a>
        </div>
        <div class="input-group">
            <label for="institucion_ruta">Institución Asignada:</label>
            <select id="institucion_ruta" name="institucion_ruta" required>
                <option value="">Seleccione una institución</option>
                <?php
                // Definir las instituciones (pueden venir de la DB en un futuro)
                $instituciones = [
                    "Unidad Educativa Bolívar",
                    "Colegio Vicente Fierro",
                    "Unidad Educativa Tulcán"
                ];
                foreach ($instituciones as $inst) {
                    $selected = (isset($ruta_to_edit['institucion_asignada']) && $ruta_to_edit['institucion_asignada'] == $inst) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($inst) . "' " . $selected . ">" . htmlspecialchars($inst) . "</option>";
                }
                ?>
            </select>
        </div>
        <button type="submit" name="<?php echo $ruta_to_edit ? 'edit_ruta' : 'add_ruta'; ?>" class="btn-primary">
            <?php echo $ruta_to_edit ? '<i class="fas fa-save"></i> Guardar Cambios' : '<i class="fas fa-plus-circle"></i> Añadir Ruta'; ?>
        </button>
        <?php if ($ruta_to_edit): ?>
            <a href="panelA.php?module=rutas" class="btn-secondary"><i class="fas fa-times"></i> Cancelar Edición</a>
        <?php endif; ?>
    </form>
</div>

<h3>Lista de Rutas</h3>
<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Coordenadas</th>
                <th>Institución</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $conn->prepare("SELECT id, nombre, descripcion, coordenadas, institucion_asignada FROM rutas ORDER BY id ASC");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                    echo "<td class='coordinates-cell'>" . htmlspecialchars($row['coordenadas']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['institucion_asignada']) . "</td>";
                    echo "<td class='actions'>";
                    echo "<a href='panelA.php?module=rutas&action=edit_ruta_form&id=" . htmlspecialchars($row['id']) . "' class='btn-action edit'><i class='fas fa-edit'></i> Editar</a>";
                    echo "<a href='panelA.php?module=rutas&action=delete_ruta&id=" . htmlspecialchars($row['id']) . "' class='btn-action delete' onclick=\"return confirm('¿Está seguro de que desea eliminar esta ruta? Esto afectará a estudiantes, conductores y horarios asociados.');\"><i class='fas fa-trash-alt'></i> Eliminar</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No hay rutas registradas.</td></tr>";
            }
            $stmt->close();
            ?>
        </tbody>
    </table>
</div>