<?php
session_start(); // Inicia la sesión para manejar variables de sesión

// Incluye el archivo de conexión a la base de datos
require_once 'includes/db_connection.php';

// Función para limpiar y validar datos de entrada
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Lógica para el registro de usuarios
if (isset($_POST['register'])) {
    $nombre = sanitize_input($_POST['nombre_completo']);
    $email = sanitize_input($_POST['email_registro']);
    $password = sanitize_input($_POST['password_registro']);
    $tipo_usuario = sanitize_input($_POST['tipo_usuario']);
    $institucion = sanitize_input($_POST['institucion']);

    // Validaciones básicas (puedes añadir más si es necesario)
    if (empty($nombre) || empty($email) || empty($password) || empty($tipo_usuario) || empty($institucion)) {
        $_SESSION['error_message'] = "Todos los campos son obligatorios para el registro.";
        header("Location: index.php");
        exit();
    }

    // Hash de la contraseña antes de almacenarla
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Prepara la consulta SQL para insertar el nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, password, tipo_usuario, institucion) VALUES (:nombre, :email, :password, :tipo_usuario, :institucion)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
        $stmt->bindParam(':institucion', $institucion);

        // Ejecuta la consulta
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Registro exitoso. Ahora puedes iniciar sesión.";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error al registrar el usuario. Inténtalo de nuevo.";
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        // Manejo de errores de la base de datos (por ejemplo, email duplicado)
        if ($e->getCode() == '23000') { // Código para violación de clave única (email duplicado)
            $_SESSION['error_message'] = "El email ingresado ya está registrado. Por favor, utiliza otro.";
        } else {
            $_SESSION['error_message'] = "Error de base de datos al registrar: " . $e->getMessage();
        }
        header("Location: index.php");
        exit();
    }
}

// Lógica para el inicio de sesión de usuarios
if (isset($_POST['login'])) {
    $email = sanitize_input($_POST['email_login']);
    $password = sanitize_input($_POST['password_login']);

    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Por favor, ingresa tu email y contraseña para iniciar sesión.";
        header("Location: index.php");
        exit();
    }

    try {
        // Prepara la consulta para buscar el usuario por email
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verifica la contraseña
            if (password_verify($password, $user['password'])) {
                // Contraseña correcta, iniciar sesión y redirigir según el tipo de usuario
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre_completo'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['tipo_usuario'];
                $_SESSION['user_institucion'] = $user['institucion']; // Guarda la institución del usuario

                switch ($user['tipo_usuario']) {
                    case 'administrador':
                        header("Location: panel_admin/panelA.php");
                        break;
                    case 'estudiante':
                        header("Location: panel_estudiante/panelE.php");
                        break;
                    case 'conductor':
                        header("Location: panel_conductor/panelC.php");
                        break;
                    default:
                        // Si el tipo de usuario no es reconocido
                        $_SESSION['error_message'] = "Tipo de usuario no válido. Por favor, contacta al administrador.";
                        header("Location: index.php");
                        break;
                }
                exit();
            } else {
                // Contraseña incorrecta
                $_SESSION['error_message'] = "Contraseña incorrecta. Inténtalo de nuevo.";
                header("Location: index.php");
                exit();
            }
        } else {
            // Usuario no encontrado
            $_SESSION['error_message'] = "El email no está registrado.";
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error de base de datos al iniciar sesión: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Si se accede a procesar_auth.php sin enviar datos de login o registro
header("Location: index.php");
exit();
?>