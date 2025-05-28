<?php
// sistema_rutas_escolares/index.php
session_start(); // ¡IMPORTANTE! Inicia la sesión al principio de cada archivo PHP que la use
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Rutas Escolares - Iniciar Sesión y Registrarse</title>
    <link rel="stylesheet" type="text/css" href="./style.css" />
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
    <link rel="icon" href="./img/logo.png" type="image/png">
</head>
<body>
    <?php
    // Mostrar mensajes de éxito o error al inicio del cuerpo
    if (isset($_SESSION['success_message'])) {
        echo '<div style="color: green; padding: 10px; border: 1px solid green; margin-bottom: 10px; text-align: center; background-color: #e0ffe0; border-radius: 5px; max-width: 400px; margin: 20px auto;">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['error_message'])) {
        echo '<div style="color: red; padding: 10px; border: 1px solid red; margin-bottom: 10px; text-align: center; background-color: #ffe0e0; border-radius: 5px; max-width: 400px; margin: 20px auto;">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="container">
        <div class="forms-container">
            <div class="signin-signup">
                <form action="procesar_auth.php" method="POST" class="sign-in-form">
                    <img src="./img/logo.png" alt="Logo del Sistema" class="logo-form">
                    <h2 class="title">Iniciar Sesión</h2>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" placeholder="Usuario o Correo" name="username_login" required />
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" placeholder="Contraseña" name="password_login" required />
                    </div>
                    <input type="submit" value="Ingresar" class="btn solid" name="login-submit" />
                </form>

                <form action="procesar_auth.php" method="POST" class="sign-up-form" enctype="multipart/form-data">
                    <img src="./img/logo.png" alt="Logo del Sistema" class="logo-form">
                    <h2 class="title">Registrarse</h2>

                    <p class="registration-question">¿Desea registrarse como?</p>
                    <div class="registration-type-selector">
                        <button type="button" class="btn transparent" id="btn-estudiante">Estudiante</button>
                        <button type="button" class="btn transparent" id="btn-conductor">Conductor</button>
                    </div>

                    <input type="hidden" name="selected_rol" id="selected_rol" value="">

                    <div class="input-field">
                        <i class="fas fa-user-tag"></i>
                        <input type="text" placeholder="Nombre de Usuario (para Login)" name="username" required />
                    </div>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" placeholder="Nombre Completo" name="nombre_completo" required />
                    </div>
                    <div class="input-field">
                        <i class="fas fa-id-card"></i>
                        <input type="text" placeholder="Número de Cédula" name="cedula" required />
                    </div>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" placeholder="Correo Electrónico" name="email" required />
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" placeholder="Contraseña" name="password" required />
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" placeholder="Confirme su Contraseña" name="confirm_password" required />
                    </div>
                    <div class="input-field input-file-field">
                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="input-file" />
                        <label for="foto_perfil" class="btn-file">
                            <i class="fas fa-camera"></i> Subir Foto de Perfil
                        </label>
                    </div>

                    <div class="form-student-fields active" id="form-estudiante">
                        <div class="input-field">
                            <i class="fas fa-school"></i>
                            <input type="text" placeholder="Institución Educativa (Estudiante)" name="institucion_estudiante" />
                        </div>
                        <input type="submit" value="Registrarse Estudiante" class="btn solid" name="register-submit" />
                    </div>

                    <div class="form-conductor-fields" id="form-conductor">
                        <div class="input-field">
                            <i class="fas fa-phone"></i>
                            <input type="text" placeholder="Número de Teléfono" name="telefono_conductor" />
                        </div>
                        <div class="input-field">
                            <i class="fas fa-id-badge"></i>
                            <input type="text" placeholder="Número de Licencia" name="licencia_conductor" />
                        </div>
                         <div class="input-field">
                            <i class="fas fa-school"></i>
                            <input type="text" placeholder="Institución Educativa (Conductor)" name="institucion_conductor" />
                        </div>
                        <div class="input-field">
                            <i class="fas fa-bus-alt"></i>
                            <input type="text" placeholder="Matrícula del Autobús" name="matricula_autobus" />
                        </div>
                        <div class="input-field input-file-field">
                            <input type="file" id="foto_autobus_conductor" name="foto_autobus" accept="image/*" class="input-file" />
                            <label for="foto_autobus_conductor" class="btn-file">
                                <i class="fas fa-bus"></i> Subir Foto del Autobús
                            </label>
                        </div>
                        <input type="submit" value="Registrarse Conductor" class="btn solid" name="register-submit" />
                    </div>
                </form>
            </div>
        </div>

        <div class="panels-container">
            <div class="panel left-panel">
                <div class="content">
                    <h3>¿Eres nuevo aquí?</h3>
                    <p>Regístrate para disfrutar las rutas de los autobuses escolares de forma sencilla y segura.</p>
                    <button class="btn transparent" id="sign-up-btn">Registrarse</button>
                </div>
                <img src="./img/log.svg" class="image" alt="Ilustración de inicio de sesión">
            </div>

            <div class="panel right-panel">
                <div class="content">
                    <h3>¿Ya tienes cuenta?</h3>
                    <p>Inicia sesión para acceder a tu panel de rutas escolares, fácil y confiable.</p>
                    <button class="btn transparent" id="sign-in-btn">Iniciar Sesión</button>
                </div>
                <img src="./img/register.svg" class="image" alt="Ilustración de registro">
            </div>
        </div>
    </div>

    <script src="./app.js"></script>
</body>
</html>
