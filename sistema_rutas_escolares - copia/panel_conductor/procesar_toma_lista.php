<?php
// panel_conductor/procesar_toma_lista.php
session_start();
require_once '../includes/db_connection.php';

// Redireccionar si no es conductor o no está logueado
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'conductor') {
    header('Location: ../index.php');
    exit();
}

$conn = connectDB();
if (!$conn) {
    $_SESSION['error_message'] = "Error de conexión a la base de datos.";
    header('Location: panelC.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['asistencia'])) {
    $id_conductor = (int)$_POST['id_conductor'];
    $id_ruta = htmlspecialchars(trim($_POST['id_ruta']));
    $asistencia_data = $_POST['asistencia']; // Array: [id_estudiante] => 'presente'

    if ($id_conductor !== $_SESSION['user_id']) {
        $_SESSION['error_message'] = "Intento de toma de asistencia no autorizado.";
        $conn->close();
        header('Location: panelC.php');
        exit();
    }

    // Preparar para registrar la asistencia
    // Necesitarías una tabla `asistencia` en tu DB:
    // CREATE TABLE asistencia (
    //     id INT AUTO_INCREMENT PRIMARY KEY,
    //     id_estudiante INT NOT NULL,
    //     id_conductor INT NOT NULL,
    //     id_ruta VARCHAR(50) NOT NULL,
    //     fecha DATE NOT NULL,
    //     estado ENUM('presente', 'ausente') NOT NULL,
    //     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    //     FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id),
    //     FOREIGN KEY (id_conductor) REFERENCES conductores(id),
    //     FOREIGN KEY (id_ruta) REFERENCES rutas(id)
    // );
    
    // Eliminamos registros de asistencia del día para esta ruta y conductor si existen
    // Esto evita duplicados si el conductor envía la lista varias veces
    $current_date = date('Y-m-d');
    $stmt_delete = $conn->prepare("DELETE FROM asistencia WHERE id_conductor = ? AND id_ruta = ? AND fecha = ?");
    $stmt_delete->bind_param("iss", $id_conductor, $id_ruta, $current_date);
    $stmt_delete->execute();
    $stmt_delete->close();

    $conn->begin_transaction();
    try {
        $stmt_insert_asistencia = $conn->prepare("INSERT INTO asistencia (id_estudiante, id_conductor, id_ruta, fecha, estado) VALUES (?, ?, ?, ?, ?)");

        // Primero, marcar a todos los estudiantes de la ruta como 'ausente' por defecto (o los que estaban en la lista mostrada)
        // Luego actualizar a 'presente' los que fueron marcados
        $estudiantes_en_lista_raw = $_POST['estudiantes_en_lista'] ?? ''; // Asumiendo que enviarías una lista de IDs de estudiantes visibles en el formulario
        $estudiantes_en_lista = [];
        if (!empty($estudiantes_en_lista_raw)) {
            $estudiantes_en_lista = explode(',', $estudiantes_en_lista_raw); // Si lo envías como cadena separada por comas
        } else {
            // Si no se envió una lista explícita, se puede asumir que todos los estudiantes de la INSTITUCION
            // del conductor son los que se procesaron. Sin embargo, lo más seguro es obtener los IDs que se mostraron
            // en el formulario original. Para este ejemplo, lo haremos con el ID del conductor y la ruta.
            // Para ser preciso, necesitarías obtener los IDs de estudiantes de la consulta de panelC.php que se mostraron
            // y pasarlos como un input hidden en el formulario.
            // Por simplicidad, asumiremos que todos los estudiantes para la INSTITUCION DEL CONDUCTOR para esta RUTA son relevantes.
            // Esto es un punto a refinar para asegurar que la "toma de lista" sea precisa.
            // ALTERNATIVA: Pasa todos los IDs de estudiantes mostrados en el formulario como un array o string CSV en un input hidden.
        }

        // Marcar inicialmente a todos los estudiantes de la institución del conductor como AUSENTE para la ruta y fecha de hoy
        // Esto es una simplificación. Un sistema robusto manejaría esto mejor.
        $stmt_all_students_for_institution = $conn->prepare("SELECT id FROM estudiantes WHERE institucion = (SELECT institucion_educativa FROM conductores WHERE id = ?)");
        $stmt_all_students_for_institution->bind_param("i", $id_conductor);
        $stmt_all_students_for_institution->execute();
        $res_all_students = $stmt_all_students_for_institution->get_result();
        while($row = $res_all_students->fetch_assoc()) {
            $estudiante_id = $row['id'];
            // Insertar o actualizar como 'ausente' si no fue marcado como 'presente'
            if (!isset($asistencia_data[$estudiante_id])) {
                 $stmt_insert_asistencia->bind_param("iisss", $estudiante_id, $id_conductor, $id_ruta, $current_date, $estado_ausente);
                 $estado_ausente = 'ausente';
                 $stmt_insert_asistencia->execute();
            }
        }
        $stmt_all_students_for_institution->close();


        // Insertar o actualizar los que SÍ fueron marcados como 'presente'
        foreach ($asistencia_data as $estudiante_id => $estado) {
            if ($estado === 'presente') {
                // Eliminar registro existente para este estudiante/fecha/ruta/conductor para evitar duplicados
                // (Ya lo hicimos con el DELETE FROM al inicio, pero si no se hace el DELETE, aquí iría un UPDATE o UPSERT)
                $stmt_insert_asistencia->bind_param("iisss", $estudiante_id, $id_conductor, $id_ruta, $current_date, $estado_presente);
                $estado_presente = 'presente';
                $stmt_insert_asistencia->execute();
            }
        }
        $stmt_insert_asistencia->close();
        
        $conn->commit();
        $_SESSION['success_message'] = "Asistencia guardada exitosamente.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error al guardar asistencia: " . $e->getMessage();
        error_log("Error de asistencia: " . $e->getMessage());
    } finally {
        $conn->close();
    }
} else {
    $_SESSION['error_message'] = "Datos de asistencia no válidos.";
}

header('Location: panelC.php');
exit();
?>