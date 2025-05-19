<?php
// includes/header.php

// Asegúrate de iniciar la sesión. Si ya está iniciada, no hará nada.
// Esto es crucial para acceder a $_SESSION variables.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de funciones.
// __DIR__ se refiere al directorio actual del archivo (es decir, 'includes').
require_once __DIR__ . '/functions.php';

// Define $page_title ANTES de incluir este header en tus páginas,
// o se usará un valor por defecto.
if (!isset($page_title)) {
    $page_title = "Sistema de Tutorías Universitarias";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Tutorías</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="index.php">Sistema Tutorías</a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <?php if (is_logged_in()): // Verifica si el usuario ha iniciado sesión ?>
                        <?php // Enlaces para usuarios logueados ?>
                        <?php if (isset($_SESSION['user_role'])): ?>
                            <?php if ($_SESSION['user_role'] == 'estudiante'): ?>
                                <li><a href="dashboard_estudiante.php">Mi Dashboard</a></li>
                            <?php elseif ($_SESSION['user_role'] == 'tutor'): ?>
                                <li><a href="dashboard_tutor.php">Mi Dashboard</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li><a href="perfil.php">Mi Perfil</a></li> <?php // Enlace a una futura página de perfil ?>
                        <li>
                            <a href="logout.php">Cerrar Sesión</a>
                        </li>
                    <?php else: ?>
                        <?php // Enlaces para usuarios no logueados ?>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                        <li><a href="register.php">Registrarse</a></li>
                    <?php endif; ?>
                    </ul>
            </nav>
        </div>
    </header>

    <main class="content">
        <div class="container">
            