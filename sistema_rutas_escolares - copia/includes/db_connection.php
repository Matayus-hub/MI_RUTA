<?php
// includes/db_connection.php

// Configuración de la base de datos
// Estas constantes facilitan el cambio de configuración en un solo lugar
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Usuario por defecto de XAMPP para MySQL
define('DB_PASS', '');     // Contraseña por defecto de XAMPP para MySQL (vacía)
define('DB_NAME', 'sistema_rutas_escolares'); // Nombre de la base de datos que creaste
define('DB_PORT', 3306);   // Puerto por defecto de MySQL (no lo cambiaste, así que 3306)

/**
 * Función para conectar a la base de datos.
 * Utiliza la extensión MySQLi para una conexión segura y eficiente.
 * @return mysqli|null Retorna el objeto mysqli si la conexión es exitosa, o null en caso de error.
 */
function connectDB() {
    // Crear una nueva instancia de mysqli
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    // Verificar si hay errores en la conexión
    if ($mysqli->connect_errno) {
        // En un entorno de desarrollo, puedes mostrar el error.
        // En producción, es mejor registrarlo en un log y mostrar un mensaje genérico.
        error_log('Error de conexión a la base de datos: ' . $mysqli->connect_error);
        // Opcional: para depuración intensa (no en producción)
        // die('Error de conexión a la base de datos: ' . $mysqli->connect_error);
        return null; // Retorna null si la conexión falla
    }

    // Establecer el conjunto de caracteres a UTF-8 para evitar problemas con caracteres especiales
    $mysqli->set_charset("utf8mb4");

    return $mysqli; // Retorna el objeto mysqli de la conexión exitosa
}


?>