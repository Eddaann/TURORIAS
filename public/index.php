<?php
session_start();
require_once '../includes/functions.php';

if (is_logged_in()) {
    if ($_SESSION['user_role'] == 'estudiante') {
        redirect('dashboard_estudiante.php');
    } elseif ($_SESSION['user_role'] == 'tutor') {
        redirect('dashboard_tutor.php');
    }
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Tutorías - Bienvenido</title>
    <link rel="stylesheet" href="css/style.css">
    </head>
<body>
    <div class="container welcome-container">
        <h1>Bienvenido al Sistema de Tutorías</h1>
        <p>¿Qué deseas hacer?</p>
        <div class="button-group">
            <a href="login.php" class="welcome-button">Iniciar Sesión</a>
            <a href="register.php" class="welcome-button">Registrarse</a>
        </div>
    </div>
</body>
</html>