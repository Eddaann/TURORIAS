<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

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
                    <?php if (is_logged_in()): ?>
                        <?php if (isset($_SESSION['user_role'])): ?>
                            <?php if ($_SESSION['user_role'] == 'estudiante'): ?>
                                <li><a href="dashboard_estudiante.php">Mi Dashboard</a></li>
                            <?php elseif ($_SESSION['user_role'] == 'tutor'): ?>
                                <li><a href="dashboard_tutor.php">Mi Dashboard</a></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <li><a href="perfil.php">Mi Perfil</a></li> 
                        <li>
                            <a href="logout.php">Cerrar Sesión</a>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                        <li><a href="register.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="content">
        <div class="container">